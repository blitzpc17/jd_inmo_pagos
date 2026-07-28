<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Services\PdfReceiptService;

class CreditorVoucherPaymentController extends Controller
{
    public function index()
    {
        return view('pagos_acreedores_abonos.index');
    }

    public function options()
    {
        $creditors = DB::table('creditors as c')
            ->join('statuses as s', 's.id', '=', 'c.status_id')
            ->join('processes as p', 'p.id', '=', 's.process_id')
            ->where('p.clave', 'GENERAL')
            ->where('s.clave', 'ACTIVE')
            ->whereNull('c.fecha_baja')
            ->orderBy('c.nombres')
            ->get([
                'c.id as value',
                DB::raw("c.nombres || ' ' || c.apellidos as text")
            ]);

        $paymentMethods = DB::table('payment_methods')
            ->orderBy('nombre')
            ->get([
                'id as value',
                'nombre as text'
            ]);

        return response()->json([
            'creditors' => $creditors,
            'payment_methods' => $paymentMethods,
        ]);
    }

    public function creditorVouchers(int $creditorId)
    {
        $rows = DB::table('creditor_vouchers as cv')
            ->where('cv.creditor_id', $creditorId)
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

        $row = DB::table('creditor_vouchers as cv')
            ->join('creditors as c', 'c.id', '=', 'cv.creditor_id')
            ->join('statuses as s', 's.id', '=', 'cv.status_id')
            ->where('cv.id', $voucherId)
            ->select([
                'cv.*',
                'c.nombres',
                'c.apellidos',
                's.nombre as estado',
            ])
            ->first();

        abort_if(!$row, 404, 'Boleta no encontrada');

        $items = DB::table('creditor_voucher_items as cvi')
            ->join('payment_methods as pm', 'pm.id', '=', 'cvi.payment_method_id')
            ->leftJoin('users as u', 'u.id', '=', 'cvi.usuario_genero_id')
            ->where('cvi.creditor_voucher_id', $voucherId)
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

        $schedules = DB::table('creditor_payment_schedules')
            ->where('creditor_voucher_id', $voucherId)
            ->orderBy('installment_number')
            ->get();

        $progress = $this->getVoucherProgressStatus($row);

        return response()->json([
            'ok' => true,
            'data' => [
                'id' => $row->id,
                'numero_referencia' => $row->numero_referencia,
                'acreedor' => trim(($row->nombres ?? '') . ' ' . ($row->apellidos ?? '')),
                'total' => $row->total,
                'enganche' => $row->enganche,
                'num_socios' => $row->num_socios,
                'partner_percentages' => $row->partner_percentages,
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
                'items' => $items,
                'schedules' => $schedules,
            ]
        ]);
    }

    public function store(Request $request)
    {
        $data = Validator::make($request->all(), [
            'creditor_voucher_id' => ['required', 'integer', 'exists:creditor_vouchers,id'],
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

        $voucher = DB::table('creditor_vouchers')->where('id', $data['creditor_voucher_id'])->first();
        if (!$voucher) {
            return response()->json(['message' => 'Boleta no encontrada.'], 404);
        }

        $totalCapitalPagadoNuevo = collect($data['items'])->sum('cantidad');
        $saldoPendiente = (float) $voucher->saldo_pendiente;

        if (round($totalCapitalPagadoNuevo, 2) > round($saldoPendiente, 2)) {
            return response()->json([
                'message' => 'El total abonado a capital ($' . number_format($totalCapitalPagadoNuevo, 2) . ') excede el saldo pendiente de capital ($' . number_format($saldoPendiente, 2) . ').'
            ], 422);
        }

        DB::beginTransaction();

        try {
            $rows = [];
            foreach ($data['items'] as $item) {
                $rows[] = [
                    'creditor_voucher_id' => $data['creditor_voucher_id'],
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

            DB::table('creditor_voucher_items')->insert($rows);

            $this->recalculateVoucherTotals($data['creditor_voucher_id']);

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

    public function recalculateVoucherTotals(int $voucherId): void
    {
        $voucher = DB::table('creditor_vouchers')->where('id', $voucherId)->first();
        if (!$voucher) {
            return;
        }

        $totalPagado = (float) DB::table('creditor_voucher_items')
            ->where('creditor_voucher_id', $voucherId)
            ->whereNull('fecha_baja')
            ->sum('cantidad');
            
        $interesGenerado = (float) DB::table('creditor_voucher_items')
            ->where('creditor_voucher_id', $voucherId)
            ->whereNull('fecha_baja')
            ->sum('interes_pagado'); // interes_pagado in DB represents interes_generado from UI

        $capitalTotal = max(0, (float) $voucher->total - (float) $voucher->enganche);
        $capitalPagado = min($totalPagado, $capitalTotal);
        $excesoParaInteres = max(0, $totalPagado - $capitalTotal);

        $interesPagado = min($excesoParaInteres, $interesGenerado);
        
        $saldoPendienteCapital = $capitalTotal - $capitalPagado;

        DB::table('creditor_vouchers')
            ->where('id', $voucherId)
            ->update([
                'total_pagado' => $totalPagado,
                'saldo_pendiente' => $saldoPendienteCapital,
                'updated_at' => now(),
            ]);

        $schedules = DB::table('creditor_payment_schedules')
            ->where('creditor_voucher_id', $voucherId)
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

            DB::table('creditor_payment_schedules')
                ->where('id', $schedule->id)
                ->update([
                    'amount_paid' => $paid,
                    'status' => $status,
                    'updated_at' => now(),
                ]);
        }
    }

    protected function getVoucherProgressStatus(object $voucher): array
    {
        $fechaInicio = Carbon::parse($voucher->fecha_inicio ?? $voucher->fecha_registro)->startOfDay();
        $hoy = now()->startOfDay();

        $mesesTranscurridos = max(1, $fechaInicio->diffInMonths($hoy) + 1);
        $mesesExigibles = min((int) $voucher->meses, $mesesTranscurridos);

        $deberiaLlevar = round($mesesExigibles * (float) $voucher->mensualidad, 2);
        
        $totalAbonado = (float) DB::table('creditor_voucher_items')
            ->where('creditor_voucher_id', $voucher->id)
            ->whereNull('fecha_baja')
            ->sum('cantidad');
            
        $interesGenerado = (float) DB::table('creditor_voucher_items')
            ->where('creditor_voucher_id', $voucher->id)
            ->whereNull('fecha_baja')
            ->sum('interes_pagado');
            
        $capitalTotal = max(0, (float) $voucher->total - (float) $voucher->enganche);
        $capitalPagado = min($totalAbonado, $capitalTotal);
        $excesoParaInteres = max(0, $totalAbonado - $capitalTotal);

        $interesPagado = min($excesoParaInteres, $interesGenerado);
        
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

    public function receipt(int $itemId, PdfReceiptService $pdf)
    {
        $item = DB::table('creditor_voucher_items as i')
            ->join('payment_methods as pm', 'pm.id', '=', 'i.payment_method_id')
            ->leftJoin('users as u', 'u.id', '=', 'i.usuario_genero_id')
            ->where('i.id', $itemId)
            ->select([
                'i.*',
                'pm.nombre as forma_pago',
                'u.alias as usuario_registro',
            ])
            ->first();

        abort_if(!$item, 404, 'Abono no encontrado');

        $this->recalculateVoucherTotals((int) $item->creditor_voucher_id);

        $voucher = DB::table('creditor_vouchers as cv')
            ->join('creditors as c', 'c.id', '=', 'cv.creditor_id')
            ->where('cv.id', $item->creditor_voucher_id)
            ->select([
                'cv.*',
                DB::raw("c.nombres || ' ' || c.apellidos as acreedor"),
            ])
            ->first();

        abort_if(!$voucher, 404, 'Boleta no encontrada');

        $progress = $this->getVoucherProgressStatus($voucher);
        $voucher->estado_pago = $progress['estado_pago'];

        $stats = [
            'total_payments' => $voucher->meses,
            'paid_payments' => $progress['meses_pagados'] ?? 0,
            'pending_payments' => max(0, $voucher->meses - ($progress['meses_pagados'] ?? 0)),
        ];

        $items = DB::table('creditor_voucher_items')
            ->where('creditor_voucher_id', $voucher->id)
            ->whereNull('fecha_baja')
            ->orderBy('id')
            ->get();
            
        $scheduleGrid = [];
        foreach ($items as $idx => $row) {
            $scheduleGrid[] = (object) [
                'installment_number' => $idx + 1,
                'due_date' => $row->fecha_pago_programada ?: $row->fecha_recibido,
                'amount' => $row->cantidad_a_pagar > 0 ? $row->cantidad_a_pagar : $voucher->mensualidad,
                'amount_paid' => $row->cantidad,
                'status' => 'PAGADO',
            ];
        }

        return $pdf->stream(
            'pdf.receipts.creditor_payment',
            [
                'document_type' => 'RECIBO DE ABONO A ACREEDOR',
                'folio' => 'REC-ACR-' . str_pad((string) $item->id, 6, '0', STR_PAD_LEFT),
                'item' => $item,
                'voucher' => $voucher,
                'stats' => $stats,
                'scheduleGrid' => $scheduleGrid,
            ],
            'recibo-abono-acreedor-'.$item->id.'.pdf'
        );
    }
}