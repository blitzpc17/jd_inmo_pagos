<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Services\PdfReceiptService;
use Carbon\Carbon;

class CreditorPaymentAbonoController extends Controller
{
    public function index()
    {
        return view('abonos_acreedores.index');
    }

    public function options()
    {
        $creditors = DB::table('creditors as c')
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
                'nombre as text'
            ]);

        return response()->json([
            'creditors' => $creditors,
            'payment_methods' => $paymentMethods,
        ]);
    }

    public function creditorVouchers(int $creditorId)
    {
        $rows = DB::table('creditor_payments as cp')
            ->where('cp.creditor_id', $creditorId)
            ->whereNull('cp.fecha_baja')
            ->orderByDesc('cp.id')
            ->get([
                'cp.id',
                'cp.numero_referencia',
                'cp.importe as total',
                'cp.enganche',
                'cp.total_pagado',
                'cp.saldo_pendiente',
                'cp.concepto'
            ]);

        $result = [];
        foreach ($rows as $row) {
            $progress = $this->getCreditorProgressStatus((array)$row);
            
            if ($progress['estado_pago'] === 'PAGADO') {
                continue;
            }
            
            $interesPendiente = $progress['interes_pendiente'] ?? 0;
            
            $text = "{$row->numero_referencia}";
            if (!empty($row->concepto)) {
                $motivoCorto = mb_strlen($row->concepto) > 50 ? mb_substr($row->concepto, 0, 47) . '...' : $row->concepto;
                $text .= " | CONCEPTO: {$motivoCorto}";
            }
            $text .= " | TOTAL: {$row->total} | PAGADO: {$row->total_pagado} | DEBE: {$row->saldo_pendiente}";
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

        $row = DB::table('creditor_payments as cp')
            ->join('creditors as c', 'c.id', '=', 'cp.creditor_id')
            ->join('statuses as st', 'st.id', '=', 'cp.status_id')
            ->where('cp.id', $voucherId)
            ->select([
                'cp.*',
                'c.nombre as acreedor',
                'st.nombre as estado',
            ])
            ->first();

        abort_if(!$row, 404, 'Boleta no encontrada');

        $items = DB::table('creditor_payment_concepts as i')
            ->leftJoin('payment_methods as pm', 'pm.id', '=', 'i.payment_method_id')
            ->leftJoin('users as u', 'u.id', '=', 'i.usuario_genero_id')
            ->where('i.creditor_payment_id', $voucherId)
            ->whereNull('i.fecha_baja')
            ->orderBy('i.id')
            ->get([
                'i.id',
                'i.fecha',
                'i.importe',
                'i.interes_pagado',
                'i.concepto',
                'pm.nombre as forma_pago',
                'u.alias as usuario_registro',
            ]);
            
        $interests = DB::table('creditor_payment_interests as i')
            ->where('i.creditor_payment_id', $voucherId)
            ->whereNull('i.fecha_baja')
            ->orderBy('i.id')
            ->get([
                'i.id',
                'i.cantidad',
                'i.created_at as fecha_registro'
            ]);

        $progress = $this->getCreditorProgressStatus((array)$row);

        return response()->json([
            'ok' => true,
            'data' => [
                'id' => $row->id,
                'numero_referencia' => $row->numero_referencia,
                'acreedor' => $row->acreedor,
                'total' => $row->importe,
                'enganche' => $row->enganche,
                'total_pagado' => $row->total_pagado,
                'saldo_pendiente' => $row->saldo_pendiente,
                'estado' => $row->estado,
                'concepto' => $row->concepto,
                'estado_pago' => $progress['estado_pago'],
                'interes_acumulado' => $progress['interes_acumulado'],
                'interes_pagado' => $progress['interes_pagado'],
                'interes_pendiente' => $progress['interes_pendiente'],
                'items' => $items,
                'interests' => $interests,
                'progress' => $progress
            ]
        ]);
    }

    public function store(Request $request)
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
        
        $progress = $this->getCreditorProgressStatus((array)$boleta);
        
        $totalCapitalPagadoNuevo = collect($data['items'])->where('tipo', 'abono_capital')->sum('monto');
        $saldoPendiente = (float) $progress['saldo_pendiente'];

        if (round($totalCapitalPagadoNuevo, 2) > round($saldoPendiente, 2)) {
            return response()->json([
                'message' => 'El total abonado a capital ($' . number_format($totalCapitalPagadoNuevo, 2) . ') excede el saldo pendiente de capital ($' . number_format($saldoPendiente, 2) . ').'
            ], 422);
        }

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

    public function recalculateVoucherTotals(int $voucherId): void
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
            'capital_total' => $row['importe'] ?? 0,
            'ha_pagado' => $totalPagado,
            'estado_pago' => $estadoPago,
            'saldo_pendiente' => round($saldoPendiente, 2),
            'interes_acumulado' => round($interesAcumulado, 2),
            'interes_pagado' => round($interesPagado, 2),
            'interes_pendiente' => round($interesPendiente, 2),
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

        $item = DB::table('creditor_payment_concepts as i')
            ->leftJoin('payment_methods as pm', 'pm.id', '=', 'i.payment_method_id')
            ->leftJoin('users as u', 'u.id', '=', 'i.usuario_genero_id')
            ->where('i.id', $abonoId)
            ->where('i.creditor_payment_id', $id)
            ->whereNull('i.fecha_baja')
            ->select([
                'i.*',
                'pm.nombre as forma_pago',
                'u.alias as usuario_registro'
            ])
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
