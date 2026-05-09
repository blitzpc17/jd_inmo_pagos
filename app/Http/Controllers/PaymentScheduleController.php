<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentScheduleController extends Controller
{
    public function index()
    {
        return view('calendario_pagos.index');
    }

    public function options()
    {
        $contracts = DB::table('contracts as c')
            ->join('clients as cl', 'cl.id', '=', 'c.client_id')
            ->whereNull('c.fecha_baja')
            ->orderByDesc('c.id')
            ->get([
                'c.id as value',
                DB::raw("c.numero_referencia || ' - ' || cl.nombres || ' ' || cl.apellidos as text")
            ]);

        return response()->json([
            'contracts' => $contracts
        ]);
    }

    public function byContract(int $contractId)
    {
        $contract = DB::table('contracts as c')
            ->join('clients as cl', 'cl.id', '=', 'c.client_id')
            ->where('c.id', $contractId)
            ->select([
                'c.numero_referencia',
                'c.importe',
                'c.monto_pago_inicial',
                'c.saldo_financiado',
                'c.cuota_mensual',
                'cl.nombres',
                'cl.apellidos',
            ])
            ->first();

        abort_if(!$contract, 404, 'Contrato no encontrado');

        $rows = DB::table('payment_schedules')
            ->where('contract_id', $contractId)
            ->orderBy('installment_number')
            ->get();

        return response()->json([
            'ok' => true,
            'contract' => [
                'numero_referencia' => $contract->numero_referencia,
                'cliente' => trim($contract->nombres . ' ' . $contract->apellidos),
                'importe' => $contract->importe,
                'inicial' => $contract->monto_pago_inicial,
                'saldo' => $contract->saldo_financiado,
                'cuota' => $contract->cuota_mensual,
            ],
            'rows' => $rows,
        ]);
    }
}