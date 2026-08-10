<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Services\PdfReceiptService;

class SupplierVoucherPaymentController extends Controller
{
    public function index()
    {
        return view('pagos_proveedores_abonos.index');
    }

    public function options()
    {
        $suppliers = DB::table('suppliers as s')
            ->join('statuses as st', 'st.id', '=', 's.status_id')
            ->join('processes as p', 'p.id', '=', 'st.process_id')
            ->where('p.clave', 'GENERAL')
            ->where('st.clave', 'ACTIVE')
            ->whereNull('s.fecha_baja')
            ->orderBy('s.nombres')
            ->get([
                's.id as value',
                's.nombres',
                's.apellidos'
            ])->map(function($r) {
                return [
                    'value' => $r->value,
                    'text' => trim(($r->nombres ?? '') . ' ' . ($r->apellidos ?? ''))
                ];
            });

        $paymentMethods = DB::table('payment_methods')
            ->orderBy('nombre')
            ->get([
                'id as value',
                'nombre as text'
            ]);

        return response()->json([
            'suppliers' => $suppliers,
            'payment_methods' => $paymentMethods,
        ]);
    }

    public function creditorVouchers(int $supplierId)
    {
        $rows = DB::table('supplier_vouchers as cv')
            ->where('cv.supplier_id', $supplierId)
            ->whereNull('cv.fecha_baja')
            ->orderByDesc('cv.id')
            ->get([
                'cv.id',
                'cv.numero_referencia',
                'cv.total',
                'cv.enganche',
                'cv.total_pagado',
                'cv.saldo_pendiente',
                'cv.fecha_inicio',
                'cv.fecha_registro',
                'cv.meses',
                'cv.mensualidad'
            ]);

        $result = [];
        foreach ($rows as $row) {
            $progress = $this->getVoucherProgressStatus($row);
            
            if ($progress['estado_pago'] === 'LIQUIDADO') {
                continue;
            }
            
            $interesPendiente = $progress['interes_pendiente'] ?? 0;
            
            $text = "{$row->numero_referencia} | TOTAL: {$row->total} | PAGADO: {$row->total_pagado} | DEBE: {$row->saldo_pendiente}";
            if ($interesPendiente > 0) {
                $text .= " | INT. PENDIENTE: " . number_format($interesPendiente, 2, '.', '');
            }
            
            $result[] = [
                'value' => $row->id,
                'text'  => $text
            ];
        }

        return response()->json($result);
    }

    public function voucherSummary(int $voucherId)
    {
        $this->recalculateVoucherTotals($voucherId);

        $row = DB::table('supplier_vouchers as cv')
            ->join('suppliers as s', 's.id', '=', 'cv.supplier_id')
            ->join('statuses as st', 'st.id', '=', 'cv.status_id')
            ->where('cv.id', $voucherId)
            ->select([
                'cv.*',
                's.nombres',
                's.apellidos',
                'st.nombre as estado',
            ])
            ->first();

        if ($row) {
            $row->proveedor = trim(($row->nombres ?? '') . ' ' . ($row->apellidos ?? ''));
        }

        abort_if(!$row, 404, 'Boleta no encontrada');

        $schedules = DB::table('supplier_payment_schedules')
            ->where('supplier_voucher_id', $voucherId)
            ->orderBy('installment_number')
            ->get();

        $partners = DB::table('supplier_voucher_partners')->where('supplier_voucher_id', $voucherId)->orderBy('id')->get();
        foreach ($partners as $partner) {
            $partnerItems = DB::table('supplier_voucher_items as cvi')
                ->join('payment_methods as pm', 'pm.id', '=', 'cvi.payment_method_id')
                ->leftJoin('users as u', 'u.id', '=', 'cvi.usuario_genero_id')
                ->where('cvi.supplier_voucher_id', $voucherId)
                ->where('cvi.supplier_voucher_partner_id', $partner->id)
                ->whereNull('cvi.fecha_baja')
                ->orderBy('cvi.id')
                ->get([
                    'cvi.id',
                    'cvi.fecha_recibido',
                    'cvi.fecha_pago_programada',
                    'cvi.cantidad_a_pagar',
                    'cvi.interes_pagado',
                    'cvi.observaciones',
                    'pm.nombre as forma_pago',
                    'cvi.cantidad',
                    'u.alias as usuario_registro',
                ]);
            
            $partnerInterests = DB::table('supplier_voucher_interests as i')
                ->leftJoin('users as u', 'u.id', '=', 'i.usuario_genero_id')
                ->where('i.supplier_voucher_partner_id', $partner->id)
                ->whereNull('i.fecha_baja')
                ->orderBy('i.id')
                ->get([
                    'i.id',
                    'i.cantidad',
                    'i.fecha_registro',
                    'i.observacion',
                    'u.alias as usuario_registro'
                ]);

            $partnerProgress = $this->getPartnerProgressStatus($row, $partner);
            
            $partnerSchedules = [];
            $factor = (float) $partner->porcentaje / 100;
            $partnerPaid = $partnerProgress['ha_pagado'];
            
            foreach ($schedules as $globalSchedule) {
                $pAmount = round((float) $globalSchedule->amount * $factor, 2);
                $pPaid = 0;
                $pStatus = 'PENDING';
                
                if ($partnerPaid >= $pAmount) {
                    $pPaid = $pAmount;
                    $pStatus = 'PAID';
                    $partnerPaid -= $pAmount;
                } elseif ($partnerPaid > 0) {
                    $pPaid = $partnerPaid;
                    $pStatus = ($partnerProgress['saldo_pendiente'] <= 0.01) ? 'PAID' : 'PARTIAL';
                    $partnerPaid = 0;
                } else {
                    $pStatus = ($partnerProgress['saldo_pendiente'] <= 0.01) ? 'PAID' : 'PENDING';
                }
                
                $partnerSchedules[] = [
                    'installment_number' => $globalSchedule->installment_number,
                    'due_date'           => $globalSchedule->due_date,
                    'amount'             => $pAmount,
                    'amount_paid'        => $pPaid,
                    'status'             => $pStatus,
                ];
            }
            
            $partner->items = $partnerItems;
            $partner->interests = $partnerInterests;
            $partner->progress = $partnerProgress;
            $partner->schedules = $partnerSchedules;
        }

        $progress = $this->getVoucherProgressStatus($row);

        return response()->json([
            'ok' => true,
            'data' => [
                'id' => $row->id,
                'numero_referencia' => $row->numero_referencia,
                'proveedor' => $row->proveedor,
                'total' => $row->total,
                'enganche' => $row->enganche,
                'num_socios' => $row->num_socios,
                'partners' => $partners,
                'fecha_inicio' => $row->fecha_inicio,
                'fecha_fin' => $row->fecha_fin,
                'meses' => $row->meses,
                'mensualidad' => $row->mensualidad,
                'total_pagado' => $row->total_pagado,
                'saldo_pendiente' => $row->saldo_pendiente,
                'estado' => $row->estado,
                'observacion' => $row->observacion,
                'deberia_llevar' => $progress['deberia_llevar'],
                'meses_exigibles' => $progress['meses_exigibles'],
                'diferencia' => $progress['diferencia'],
                'estado_pago' => $progress['estado_pago'],
                'interes_acumulado' => $progress['interes_acumulado'],
                'interes_pagado' => $progress['interes_pagado'],
                'interes_pendiente' => $progress['interes_pendiente'],
                'schedules' => $schedules,
            ]
        ]);
    }

    public function store(Request $request)
    {
        $data = Validator::make($request->all(), [
            'supplier_voucher_id' => ['required', 'integer', 'exists:supplier_vouchers,id'],
            'supplier_voucher_partner_id' => ['required', 'integer', 'exists:supplier_voucher_partners,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.fecha_pago_programada' => ['nullable', 'date'],
            'items.*.cantidad_a_pagar' => ['nullable', 'numeric', 'min:0'],
            'items.*.fecha_recibido' => ['required', 'date'],
            'items.*.payment_method_id' => ['required', 'integer', 'exists:payment_methods,id'],
            'items.*.cantidad' => ['required', 'numeric', 'min:0'],
            'items.*.interes_pagado' => ['nullable', 'numeric', 'min:0'],
            'items.*.observaciones' => ['nullable', 'string'],
        ])->validate();

        $statusId = $this->getActiveStatusId();

        $voucher = DB::table('supplier_vouchers')->where('id', $data['supplier_voucher_id'])->first();
        if (!$voucher) {
            return response()->json(['message' => 'Boleta no encontrada.'], 404);
        }

        $partner = DB::table('supplier_voucher_partners')->where('id', $data['supplier_voucher_partner_id'])->first();
        $progress = $this->getPartnerProgressStatus($voucher, $partner);

        $totalCapitalPagadoNuevo = collect($data['items'])->sum('cantidad');
        $saldoPendiente = (float) $progress['saldo_pendiente'];

        if (round($totalCapitalPagadoNuevo, 2) > round($saldoPendiente, 2)) {
            return response()->json([
                'message' => 'El total abonado a capital ($' . number_format($totalCapitalPagadoNuevo, 2) . ') excede el saldo pendiente de capital del socio ($' . number_format($saldoPendiente, 2) . ').'
            ], 422);
        }

        DB::beginTransaction();

        try {
            $rows = [];
            foreach ($data['items'] as $item) {
                $rows[] = [
                    'supplier_voucher_id' => $data['supplier_voucher_id'],
                    'supplier_voucher_partner_id' => $data['supplier_voucher_partner_id'],
                    'fecha_pago_programada' => $item['fecha_pago_programada'] ?? null,
                    'cantidad_a_pagar' => $item['cantidad_a_pagar'] ?? 0,
                    'fecha_recibido' => $item['fecha_recibido'],
                    'payment_method_id' => $item['payment_method_id'],
                    'cantidad' => $item['cantidad'],
                    'interes_pagado' => $item['interes_pagado'] ?? 0,
                    'observaciones' => $item['observaciones'] ?? null,
                    'status_id' => $statusId,
                    'usuario_genero_id' => session('auth_user.id'),
                    'updated_at' => now(),
                    'created_at' => now(),
                ];
            }

            DB::table('supplier_voucher_items')->insert($rows);

            $this->recalculateVoucherTotals($data['supplier_voucher_id']);

            DB::commit();

            return response()->json([
                'ok' => true,
                'message' => 'Pago(s) registrado(s) correctamente.'
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function storeInterest(Request $request)
    {
        $data = Validator::make($request->all(), [
            'supplier_voucher_id' => ['required', 'integer', 'exists:supplier_vouchers,id'],
            'supplier_voucher_partner_id' => ['required', 'integer', 'exists:supplier_voucher_partners,id'],
            'cantidad' => ['required', 'numeric', 'min:0.01'],
            'fecha_registro' => ['required', 'date'],
            'observacion' => ['nullable', 'string'],
        ])->validate();

        DB::beginTransaction();

        try {
            DB::table('supplier_voucher_interests')->insert([
                'supplier_voucher_id' => $data['supplier_voucher_id'],
                'supplier_voucher_partner_id' => $data['supplier_voucher_partner_id'],
                'cantidad' => $data['cantidad'],
                'fecha_registro' => $data['fecha_registro'],
                'observacion' => $data['observacion'] ?? null,
                'usuario_genero_id' => session('auth_user.id'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->recalculateVoucherTotals($data['supplier_voucher_id']);

            DB::commit();

            return response()->json([
                'ok' => true,
                'message' => 'Cargo por interés registrado correctamente.'
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function recalculateVoucherTotals(int $voucherId): void
    {
        $voucher = DB::table('supplier_vouchers')->where('id', $voucherId)->first();
        if (!$voucher) {
            return;
        }

        $totalPagado = (float) DB::table('supplier_voucher_items')
            ->where('supplier_voucher_id', $voucherId)
            ->whereNull('fecha_baja')
            ->sum('cantidad');
            
        $interesGenerado = (float) DB::table('supplier_voucher_interests')
            ->where('supplier_voucher_id', $voucherId)
            ->whereNull('fecha_baja')
            ->sum('cantidad');

        $capitalTotal = max(0, (float) $voucher->total - (float) $voucher->enganche);
        $capitalPagado = min($totalPagado, $capitalTotal);
        
        $saldoPendienteCapital = $capitalTotal - $capitalPagado;

        DB::table('supplier_vouchers')
            ->where('id', $voucherId)
            ->update([
                'total_pagado' => $totalPagado,
                'saldo_pendiente' => $saldoPendienteCapital,
                'updated_at' => now(),
            ]);

        $schedules = DB::table('supplier_payment_schedules')
            ->where('supplier_voucher_id', $voucherId)
            ->orderBy('installment_number')
            ->get();

        $remainingPaid = $totalPagado;

        foreach ($schedules as $schedule) {
            $amount = (float) $schedule->amount;
            if ($remainingPaid >= $amount) {
                $paid = $amount;
                $status = 'PAID';
                $remainingPaid -= $amount;
            } elseif ($remainingPaid > 0) {
                $paid = $remainingPaid;
                $status = ($saldoPendienteCapital <= 0.01) ? 'PAID' : 'PARTIAL';
                $remainingPaid = 0;
            } else {
                $paid = 0;
                $status = ($saldoPendienteCapital <= 0.01) ? 'PAID' : 'PENDING';
            }

            DB::table('supplier_payment_schedules')
                ->where('id', $schedule->id)
                ->update([
                    'amount_paid' => $paid,
                    'status' => $status,
                    'updated_at' => now(),
                ]);
        }
    }

    protected function getPartnerProgressStatus(object $voucher, object $partner): array
    {
        $factor = (float) $partner->porcentaje / 100;
        $fechaInicio = Carbon::parse($voucher->fecha_inicio ?? $voucher->fecha_registro)->startOfDay();
        $hoy = now()->startOfDay();

        $mesesTranscurridos = max(1, $fechaInicio->diffInMonths($hoy) + 1);
        $mesesExigibles = min((int) $voucher->meses, $mesesTranscurridos);

        $deberiaLlevar = round($mesesExigibles * (float) $voucher->mensualidad * $factor, 2);
        
        $totalAbonado = (float) DB::table('supplier_voucher_items')
            ->where('supplier_voucher_partner_id', $partner->id)
            ->whereNull('fecha_baja')
            ->sum('cantidad');
            
        $interesGenerado = (float) DB::table('supplier_voucher_interests')
            ->where('supplier_voucher_partner_id', $partner->id)
            ->whereNull('fecha_baja')
            ->sum('cantidad');

        $interesPagado = (float) DB::table('supplier_voucher_items')
            ->where('supplier_voucher_partner_id', $partner->id)
            ->whereNull('fecha_baja')
            ->sum('interes_pagado');
            
        $capitalTotal = max(0, ((float) $voucher->total * $factor) - (float) $partner->enganche);
        $capitalPagado = min($totalAbonado, $capitalTotal);
        
        $saldoPendienteCapital = $capitalTotal - $capitalPagado;
        $saldoInteresPendiente = $interesGenerado - $interesPagado;
        
        $diferencia = round($deberiaLlevar - $capitalPagado, 2);

        $estadoPago = 'AL CORRIENTE';
        if ($saldoPendienteCapital <= 0.009 && $saldoInteresPendiente <= 0.009) {
            $estadoPago = 'LIQUIDADO';
        } elseif ($diferencia > 0.009) {
            $estadoPago = 'ATRASADO';
        }

        return [
            'enganche' => (float) $partner->enganche,
            'capital_total' => $capitalTotal,
            'meses_exigibles' => $mesesExigibles,
            'deberia_llevar' => $deberiaLlevar,
            'ha_pagado' => $capitalPagado,
            'saldo_pendiente' => $saldoPendienteCapital,
            'diferencia' => max(0, $diferencia),
            'estado_pago' => $estadoPago,
            'interes_acumulado' => $interesGenerado,
            'interes_pagado' => $interesPagado,
            'interes_pendiente' => $saldoInteresPendiente,
        ];
    }

    protected function getVoucherProgressStatus(object $voucher): array
    {
        $fechaInicio = Carbon::parse($voucher->fecha_inicio ?? $voucher->fecha_registro)->startOfDay();
        $hoy = now()->startOfDay();

        $mesesTranscurridos = max(1, $fechaInicio->diffInMonths($hoy) + 1);
        $mesesExigibles = min((int) $voucher->meses, $mesesTranscurridos);

        $deberiaLlevar = round($mesesExigibles * (float) $voucher->mensualidad, 2);
        
        $totalAbonado = (float) DB::table('supplier_voucher_items')
            ->where('supplier_voucher_id', $voucher->id)
            ->whereNull('fecha_baja')
            ->sum('cantidad');
            
        $interesGenerado = (float) DB::table('supplier_voucher_interests')
            ->where('supplier_voucher_id', $voucher->id)
            ->whereNull('fecha_baja')
            ->sum('cantidad');
            
        $interesPagado = (float) DB::table('supplier_voucher_items')
            ->where('supplier_voucher_id', $voucher->id)
            ->whereNull('fecha_baja')
            ->sum('interes_pagado');
            
        $capitalTotal = max(0, (float) $voucher->total - (float) $voucher->enganche);
        $capitalPagado = min($totalAbonado, $capitalTotal);
        
        $saldoPendienteCapital = $capitalTotal - $capitalPagado;
        $saldoInteresPendiente = $interesGenerado - $interesPagado;
        
        $diferencia = round($deberiaLlevar - $capitalPagado, 2);

        $estadoPago = 'AL CORRIENTE';
        if ($saldoPendienteCapital <= 0.009 && $saldoInteresPendiente <= 0.009) {
            $estadoPago = 'LIQUIDADO';
        } elseif ($diferencia > 0.009) {
            $estadoPago = 'ATRASADO';
        }

        return [
            'meses_exigibles' => $mesesExigibles,
            'deberia_llevar' => $deberiaLlevar,
            'ha_pagado' => $capitalPagado,
            'diferencia' => max(0, $diferencia),
            'estado_pago' => $estadoPago,
            'interes_acumulado' => $interesGenerado,
            'interes_pagado' => $interesPagado,
            'interes_pendiente' => $saldoInteresPendiente,
        ];
    }

    protected function getActiveStatusId(): int
    {
        $id = DB::table('statuses as s')
            ->join('processes as p', 'p.id', '=', 's.process_id')
            ->where('p.clave', 'GENERAL')
            ->where('s.clave', 'ACTIVE')
            ->value('s.id');

        if (!$id) {
            abort(500, 'No existe estado ACTIVE para GENERAL.');
        }

        return (int) $id;
    }

    public function pdfBoleta(int $id, PdfReceiptService $pdf)
    {
        $this->recalculateVoucherTotals((int) $id);

        $voucher = DB::table('supplier_vouchers as cv')
            ->join('suppliers as s', 's.id', '=', 'cv.supplier_id')
            ->join('statuses as st', 'st.id', '=', 'cv.status_id')
            ->where('cv.id', $id)
            ->select([
                'cv.*',
                's.nombres',
                's.apellidos',
                'st.nombre as estado_pago',
            ])
            ->first();

        abort_if(!$voucher, 404, 'Boleta no encontrada');
        
        $voucher->proveedor = trim(($voucher->nombres ?? '') . ' ' . ($voucher->apellidos ?? ''));

        $items = DB::table('supplier_voucher_items as i')
            ->leftJoin('payment_methods as pm', 'pm.id', '=', 'i.payment_method_id')
            ->leftJoin('supplier_voucher_partners as svp', 'svp.id', '=', 'i.supplier_voucher_partner_id')
            ->where('i.supplier_voucher_id', $voucher->id)
            ->whereNull('i.fecha_baja')
            ->orderBy('i.id')
            ->select([
                'i.*',
                'pm.nombre as forma_pago',
                'svp.nombre as socio_nombre'
            ])
            ->get();
            
        $totalAbonos = 0;
        $totalInteres = 0;
        foreach ($items as $idx => $row) {
            $totalAbonos += $row->cantidad;
            $totalInteres += ($row->interes_pagado ?? 0);
        }
        
        $partners = DB::table('supplier_voucher_partners')
            ->where('supplier_voucher_id', $voucher->id)
            ->get();
            
        $schedules = DB::table('supplier_payment_schedules')
            ->where('supplier_voucher_id', $voucher->id)
            ->orderBy('installment_number')
            ->get();
            
        foreach ($partners as $p) {
            $prog = $this->getPartnerProgressStatus($voucher, $p);
            $p->progress = $prog;
            
            $partnerSchedules = [];
            $factor = (float) $p->porcentaje / 100;
            $partnerPaid = $prog['ha_pagado'];
            
            foreach ($schedules as $globalSchedule) {
                $pAmount = round((float) $globalSchedule->amount * $factor, 2);
                $pPaid = 0;
                $pStatus = 'PENDING';
                
                if ($partnerPaid >= $pAmount) {
                    $pPaid = $pAmount;
                    $pStatus = 'PAID';
                    $partnerPaid -= $pAmount;
                } elseif ($partnerPaid > 0) {
                    $pPaid = $partnerPaid;
                    $pStatus = ($prog['saldo_pendiente'] <= 0.01) ? 'PAID' : 'PARTIAL';
                    $partnerPaid = 0;
                } else {
                    $pStatus = ($prog['saldo_pendiente'] <= 0.01) ? 'PAID' : 'PENDING';
                }
                
                $partnerSchedules[] = [
                    'installment_number' => $globalSchedule->installment_number,
                    'due_date'           => $globalSchedule->due_date,
                    'amount'             => $pAmount,
                    'amount_paid'        => $pPaid,
                    'status'             => $pStatus,
                ];
            }
            $p->schedules = $partnerSchedules;
        }

        $stats = [
            'total_payments' => count($items),
            'paid_payments' => count($items),
            'pending_payments' => 0,
        ];

        return $pdf->stream(
            'pdf.receipts.supplier_boleta',
            [
                'document_type' => 'BOLETA DE PAGO A PROVEEDOR',
                'folio' => $voucher->numero_referencia,
                'voucher' => $voucher,
                'items' => $items,
                'partners' => $partners,
                'totalAbonos' => $totalAbonos,
                'totalInteres' => $totalInteres,
                'stats' => $stats,
            ],
            'boleta-proveedor-'.$voucher->numero_referencia.'.pdf'
        );
    }

    public function pdfRecibo(int $id, int $abonoId, PdfReceiptService $pdf)
    {
        $item = DB::table('supplier_voucher_items as i')
            ->join('payment_methods as pm', 'pm.id', '=', 'i.payment_method_id')
            ->leftJoin('users as u', 'u.id', '=', 'i.usuario_genero_id')
            ->leftJoin('supplier_voucher_partners as svp', 'svp.id', '=', 'i.supplier_voucher_partner_id')
            ->where('i.id', $abonoId)
            ->where('i.supplier_voucher_id', $id)
            ->select([
                'i.*',
                'pm.nombre as forma_pago',
                'u.alias as usuario_registro',
                'svp.nombre as socio_nombre'
            ])
            ->first();

        abort_if(!$item, 404, 'Abono no encontrado');

        $this->recalculateVoucherTotals((int) $id);

        $voucher = DB::table('supplier_vouchers as cv')
            ->join('suppliers as s', 's.id', '=', 'cv.supplier_id')
            ->where('cv.id', $id)
            ->select([
                'cv.*',
                's.nombres',
                's.apellidos',
            ])
            ->first();

        abort_if(!$voucher, 404, 'Boleta no encontrada');

        $voucher->proveedor = trim(($voucher->nombres ?? '') . ' ' . ($voucher->apellidos ?? ''));

        $stats = [
            'total_payments' => DB::table('supplier_voucher_items')->where('supplier_voucher_id', $id)->whereNull('fecha_baja')->count(),
            'paid_payments' => DB::table('supplier_voucher_items')->where('supplier_voucher_id', $id)->whereNull('fecha_baja')->count(),
            'pending_payments' => 0,
        ];

        return $pdf->stream(
            'pdf.receipts.supplier_recibo',
            [
                'document_type' => 'RECIBO DE ABONO A PROVEEDOR',
                'folio' => 'REC-PRO-' . str_pad((string) $item->id, 6, '0', STR_PAD_LEFT),
                'item' => $item,
                'voucher' => $voucher,
                'stats' => $stats,
            ],
            'recibo-abono-proveedor-'.$item->id.'.pdf'
        );
    }
}