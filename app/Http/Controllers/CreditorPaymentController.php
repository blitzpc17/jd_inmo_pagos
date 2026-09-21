<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Services\PdfReceiptService;
use Carbon\Carbon;

class CreditorPaymentController extends Controller
{
    public function index()
    {
        return view('pagos_acreedores.index');
    }

    public function datatable()
    {
        $rows = DB::table('creditor_payments as sp')
            ->join('creditors as c', 'c.id', '=', 'sp.creditor_id')
            ->join('statuses as st', 'st.id', '=', 'sp.status_id')
            ->whereNull('sp.fecha_baja')
            ->select([
                'sp.id',
                'sp.numero_referencia',
                'sp.fecha_inicio',
                'sp.fecha_fin',
                'sp.importe', // Total
                'sp.enganche',
                'sp.total_pagado',
                'sp.saldo_pendiente',
                'sp.observacion',
                'sp.concepto',
                'c.nombre as acreedor',
                'st.nombre as estado',
            ])
            ->orderByDesc('sp.id')
            ->get()
            ->map(function ($r) {
                $progress = $this->getCreditorProgressStatus((array)$r);
                
                $r->estado_pago = $progress['estado_pago'];
                
                $badgeClass = match ($r->estado_pago) {
                    'VIGENTE' => 'bg-success',
                    'PAGADO' => 'bg-primary',
                    default => 'bg-secondary'
                };
                
                if ($r->saldo_pendiente <= 0.01) {
                    $r->estado_pago = 'PAGADO';
                    $badgeClass = 'bg-primary';
                }

                $r->estado_pago_badge = '<span class="badge ' . $badgeClass . '">' . $r->estado_pago . '</span>';
                
                $r->acciones = '
                    <div class="d-flex gap-1">
                        <button class="btn btn-sm btn-outline-info btn-view" data-id="'.$r->id.'" title="Ver / Abonar">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>
                ';
                return $r;
            });

        return response()->json(['data' => $rows]);
    }

    public function options()
    {
        $suppliers = DB::table('creditors as c')
            ->join('statuses as st', 'st.id', '=', 'c.status_id')
            ->join('processes as p', 'p.id', '=', 'st.process_id')
            ->where('p.clave', 'GENERAL')
            ->where('st.clave', 'ACTIVE')
            ->whereNull('c.fecha_baja')
            ->orderBy('c.nombre')
            ->get([
                'c.id as value',
                'c.nombre as text'
            ]);

        $paymentMethods = DB::table('payment_methods')
            ->orderBy('nombre')
            ->get([
                'id as value',
                'nombre as text',
            ]);

        return response()->json([
            'creditors' => $suppliers,
            'payment_methods' => $paymentMethods,
        ]);
    }

    public function store(Request $request)
    {
        $data = Validator::make($request->all(), [
            'creditor_id' => ['required', 'integer', 'exists:creditors,id'],
            'fecha_inicio' => ['required', 'date'],
            'total' => ['required', 'numeric', 'min:0'],
            'enganche' => ['required', 'numeric', 'min:0'],
            'observacion' => ['nullable', 'string'],
            'concepto' => ['nullable', 'string', 'max:350'],
        ])->validate();

        $statusId = $this->getActiveStatusId();
        
        $total = round((float) $data['total'], 2);
        $enganche = round((float) $data['enganche'], 2);

        DB::beginTransaction();

        try {
            $paymentId = DB::table('creditor_payments')->insertGetId([
                'numero_referencia' => '',
                'creditor_id' => $data['creditor_id'],
                'concepto' => $data['concepto'] ?? null,
                'fecha_inicio' => $data['fecha_inicio'],
                'fecha_fin' => $data['fecha_inicio'], // without months, end date is basically start date or null
                'importe' => $total,
                'enganche' => $enganche,
                'meses' => 0,
                'mensualidad' => 0,
                'total_pagado' => 0,
                'saldo_pendiente' => $total,
                'capital' => 0, // Not used in new logic, but kept for DB compat
                'porcentaje_interes' => 0,
                'monto_interes' => 0,
                'plazo' => 0,
                'status_id' => $statusId,
                'observacion' => $data['observacion'] ?? null,
                'usuario_genero_id' => session('auth_user.id'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('creditor_payments')
                ->where('id', $paymentId)
                ->update([
                    'numero_referencia' => 'BOL-ACR-' . str_pad((string) $paymentId, 5, '0', STR_PAD_LEFT),
                    'updated_at' => now(),
                ]);
                
            $this->recalculateVoucherTotals($paymentId);

            DB::commit();

            return response()->json([
                'ok' => true,
                'message' => 'Boleta registrada correctamente.',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function show(int $id)
    {
        $row = DB::table('creditor_payments as sp')
            ->join('creditors as c', 'c.id', '=', 'sp.creditor_id')
            ->join('statuses as st', 'st.id', '=', 'sp.status_id')
            ->where('sp.id', $id)
            ->select([
                'sp.*',
                'c.nombre as acreedor',
                'st.nombre as estado',
            ])
            ->first();

        abort_if(!$row, 404, 'Boleta no encontrada');

        $items = DB::table('creditor_payment_concepts as i')
            ->leftJoin('users as u', 'u.id', '=', 'i.usuario_genero_id')
            ->where('i.creditor_payment_id', $id)
            ->whereNull('i.fecha_baja')
            ->orderBy('i.fecha')
            ->get([
                'i.id',
                'i.fecha',
                'i.concepto',
                'i.importe',
                'i.interes_pagado',
                'u.alias as usuario_registro'
            ]);
            
        $interests = DB::table('creditor_payment_interests as i')
            ->where('i.creditor_payment_id', $id)
            ->whereNull('i.fecha_baja')
            ->orderBy('i.created_at')
            ->get();

        $progress = $this->getCreditorProgressStatus((array)$row);

        return response()->json([
            'ok' => true,
            'data' => [
                'id' => $row->id,
                'numero_referencia' => $row->numero_referencia,
                'fecha_inicio' => $row->fecha_inicio,
                'fecha_fin' => $row->fecha_fin,
                'total' => $row->importe,
                'enganche' => $row->enganche,
                'total_pagado' => $row->total_pagado,
                'saldo_pendiente' => $row->saldo_pendiente,
                'observacion' => $row->observacion,
                'acreedor' => $row->acreedor,
                'concepto' => $row->concepto,
                'estado' => $row->estado,
                'items' => $items,
                'interests' => $interests,
                'estado_pago' => $progress['estado_pago'],
                'interes_acumulado' => $progress['interes_acumulado'],
                'interes_pagado' => $progress['interes_pagado'],
                'interes_pendiente' => $progress['interes_pendiente'],
            ]
        ]);
    }

    public function storeAbonoInteres(Request $request)
    {
        $data = Validator::make($request->all(), [
            'creditor_payment_id' => ['required', 'integer', 'exists:creditor_payments,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.tipo' => ['required', 'string', 'in:abono_capital,pago_interes,generar_interes'],
            'items.*.monto' => ['required', 'numeric', 'min:0'],
            'items.*.fecha_recibido' => ['required', 'date'],
            'items.*.payment_method_id' => ['nullable', 'integer', 'exists:payment_methods,id'],
            'items.*.observaciones' => ['nullable', 'string'],
        ])->validate();

        $boleta = DB::table('creditor_payments')->where('id', $data['creditor_payment_id'])->first();
        abort_if(!$boleta, 404, 'Boleta no encontrada');

        DB::beginTransaction();
        try {
            foreach ($data['items'] as $item) {
                if ($item['tipo'] === 'abono_capital') {
                    DB::table('creditor_payment_concepts')->insert([
                        'creditor_payment_id' => $boleta->id,
                        'fecha' => $item['fecha_recibido'],
                        'importe' => $item['monto'],
                        'interes_pagado' => 0,
                        'concepto' => $item['observaciones'] ?? 'Abono a capital',
                        'payment_method_id' => $item['payment_method_id'] ?? null,
                        'status_id' => $this->getActiveStatusId(),
                        'usuario_genero_id' => session('auth_user.id'),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } elseif ($item['tipo'] === 'pago_interes') {
                    DB::table('creditor_payment_concepts')->insert([
                        'creditor_payment_id' => $boleta->id,
                        'fecha' => $item['fecha_recibido'],
                        'importe' => 0,
                        'interes_pagado' => $item['monto'],
                        'concepto' => $item['observaciones'] ?? 'Pago de intereses',
                        'payment_method_id' => $item['payment_method_id'] ?? null,
                        'status_id' => $this->getActiveStatusId(),
                        'usuario_genero_id' => session('auth_user.id'),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } elseif ($item['tipo'] === 'generar_interes') {
                    DB::table('creditor_payment_interests')->insert([
                        'creditor_payment_id' => $boleta->id,
                        'cantidad' => $item['monto'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            $this->recalculateVoucherTotals($boleta->id);

            DB::commit();

            return response()->json([
                'ok' => true,
                'message' => 'Operación registrada correctamente.',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
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

    protected function getCreditorProgressStatus(array $row)
    {
        $enganche = (float) $row['enganche'];
        $totalPagado = (float) $row['total_pagado'];
        $saldoPendiente = (float) $row['saldo_pendiente'];

        $estadoPago = 'VIGENTE';
        if ($saldoPendiente <= 0.01) {
            $estadoPago = 'PAGADO';
        }

        $interesesList = DB::table('creditor_payment_interests')
            ->where('creditor_payment_id', $row['id'])
            ->whereNull('fecha_baja')
            ->get();
        
        $interesAcumulado = $interesesList->sum('cantidad');

        $pagosList = DB::table('creditor_payment_concepts')
            ->where('creditor_payment_id', $row['id'])
            ->whereNull('fecha_baja')
            ->get();
        
        $interesPagado = collect($pagosList)->sum('interes_pagado');
        $interesPendiente = max(0, $interesAcumulado - $interesPagado);

        return [
            'ha_pagado' => $totalPagado,
            'estado_pago' => $estadoPago,
            'interes_acumulado' => round($interesAcumulado, 2),
            'interes_pagado' => round($interesPagado, 2),
            'interes_pendiente' => round($interesPendiente, 2),
        ];
    }
    
    public function recalculateVoucherTotals($voucherId)
    {
        $voucher = DB::table('creditor_payments')->where('id', $voucherId)->first();
        if (!$voucher) return;

        $totalPagado = DB::table('creditor_payment_concepts')
            ->where('creditor_payment_id', $voucherId)
            ->whereNull('fecha_baja')
            ->sum('importe');

        $saldoPendiente = max(0, $voucher->importe - $totalPagado);

        DB::table('creditor_payments')
            ->where('id', $voucherId)
            ->update([
                'total_pagado' => $totalPagado,
                'saldo_pendiente' => $saldoPendiente,
                'updated_at' => now(),
            ]);
    }

    public function pdfBoleta(int $id, PdfReceiptService $pdf)
    {
        $voucher = DB::table('creditor_payments as cp')
            ->join('creditors as c', 'c.id', '=', 'cp.creditor_id')
            ->join('statuses as st', 'st.id', '=', 'cp.status_id')
            ->where('cp.id', $id)
            ->select([
                'cp.*',
                'c.nombre as acreedor',
                'st.nombre as estado_pago',
            ])
            ->first();

        abort_if(!$voucher, 404, 'Boleta no encontrada');

        $items = DB::table('creditor_payment_concepts')
            ->where('creditor_payment_id', $id)
            ->whereNull('fecha_baja')
            ->orderBy('id')
            ->get();
            
        $interests = DB::table('creditor_payment_interests')
            ->where('creditor_payment_id', $id)
            ->whereNull('fecha_baja')
            ->orderBy('created_at')
            ->get();

        $allItems = [];
        
        foreach ($items as $i) {
            if ($i->importe > 0) {
                $allItems[] = (object)[
                    'fecha' => $i->fecha,
                    'tipo' => 'Abono Capital',
                    'monto' => $i->importe,
                    'concepto' => $i->concepto
                ];
            }
            if ($i->interes_pagado > 0) {
                $allItems[] = (object)[
                    'fecha' => $i->fecha,
                    'tipo' => 'Pago Interés',
                    'monto' => $i->interes_pagado,
                    'concepto' => $i->concepto
                ];
            }
        }
        foreach ($interests as $i) {
            $allItems[] = (object)[
                'fecha' => substr($i->created_at, 0, 10),
                'tipo' => 'Cargo Interés',
                'monto' => $i->cantidad,
                'concepto' => 'Generación Automática / Manual'
            ];
        }

        usort($allItems, function($a, $b) {
            return strtotime($a->fecha) - strtotime($b->fecha);
        });

        $progress = $this->getCreditorProgressStatus((array)$voucher);
        $voucher->estado_pago = $progress['estado_pago'];

        return $pdf->stream(
            'pdf.receipts.creditor_boleta',
            [
                'document_type' => 'ESTADO DE CUENTA ACREEDOR',
                'folio' => $voucher->numero_referencia,
                'voucher' => $voucher,
                'items' => $allItems,
                'progress' => $progress,
            ],
            'estado-cuenta-acreedor-'.$voucher->numero_referencia.'.pdf'
        );
    }

    public function pdfRecibo(int $id, int $abonoId, PdfReceiptService $pdf)
    {
        $voucher = DB::table('creditor_payments as cp')
            ->join('creditors as c', 'c.id', '=', 'cp.creditor_id')
            ->where('cp.id', $id)
            ->select([
                'cp.*',
                'c.nombre as acreedor',
            ])
            ->first();

        abort_if(!$voucher, 404, 'Boleta no encontrada');

        $item = DB::table('creditor_payment_concepts')
            ->where('id', $abonoId)
            ->where('creditor_payment_id', $id)
            ->whereNull('fecha_baja')
            ->first();

        abort_if(!$item, 404, 'Abono no encontrado');

        $stats = [
            'total_payments' => DB::table('creditor_payment_concepts')->where('creditor_payment_id', $id)->count(),
            'paid_payments' => DB::table('creditor_payment_concepts')->where('creditor_payment_id', $id)->count(),
            'pending_payments' => 0,
        ];

        return $pdf->stream(
            'pdf.receipts.creditor_recibo',
            [
                'document_type' => 'RECIBO DE ABONO A ACREEDOR',
                'folio' => 'REC-ACR-' . str_pad((string) $item->id, 6, '0', STR_PAD_LEFT),
                'voucher' => $voucher,
                'item' => $item,
                'stats' => $stats,
            ],
            'recibo-acreedor-'.$item->id.'.pdf'
        );
    }
}