<?php

namespace App\Http\Controllers;

use App\Exports\CreditorReportExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class CreditorReportController extends Controller
{
    public function index()
    {
        return view('acreedores.report');
    }

    public function data(Request $request)
    {
        return response()->json([
            'data' => $this->buildRows(),
        ]);
    }

    public function export(Request $request)
    {
        return Excel::download(
            new CreditorReportExport($this->buildRows(true)),
            'reporte_acreedores_' . now()->format('Ymd_His') . '.xlsx'
        );
    }

    protected function buildRows($forExport = false): array
    {
        $interestCharges = DB::table('creditor_payment_interests')
            ->select('creditor_payment_id', DB::raw('SUM(cantidad) as interes_acumulado'))
            ->whereNull('fecha_baja')
            ->groupBy('creditor_payment_id');

        $interestPayments = DB::table('creditor_payment_concepts')
            ->select('creditor_payment_id', DB::raw('SUM(interes_pagado) as interes_pagado'))
            ->whereNull('fecha_baja')
            ->groupBy('creditor_payment_id');

        $rows = DB::table('creditor_payments as cp')
            ->join('creditors as c', 'c.id', '=', 'cp.creditor_id')
            ->leftJoinSub($interestCharges, 'ic', function ($join) {
                $join->on('ic.creditor_payment_id', '=', 'cp.id');
            })
            ->leftJoinSub($interestPayments, 'ip', function ($join) {
                $join->on('ip.creditor_payment_id', '=', 'cp.id');
            })
            ->whereNull('cp.fecha_baja')
            ->select([
                'c.nombre as acreedor',
                'cp.concepto as descripcion',
                'cp.importe as capital',
                'cp.total_pagado as abono_capital',
                'cp.saldo_pendiente as resto_capital',
                'cp.mensualidad as mensualidad_capital',
                'cp.porcentaje_interes as porcentaje_interes',
                'cp.monto_interes as interes_pagar_mensual',
                DB::raw('COALESCE(ic.interes_acumulado, 0) as interes_acumulado'),
                DB::raw('COALESCE(ip.interes_pagado, 0) as interes_abonado'),
            ])
            ->get()
            ->map(function ($row) use ($forExport) {
                $interes_pendiente = max(0, $row->interes_acumulado - $row->interes_abonado);
                
                if ($forExport) {
                    return [
                        'acreedor' => $row->acreedor,
                        'descripcion' => $row->descripcion,
                        'capital' => (float)$row->capital,
                        'abono_capital' => (float)$row->abono_capital,
                        'resto_capital' => (float)$row->resto_capital,
                        'mensualidad_capital' => (float)$row->mensualidad_capital,
                        'porcentaje_interes' => (float)$row->porcentaje_interes,
                        'interes_pagar_mensual' => (float)$row->interes_pagar_mensual,
                        'interes_abonado' => (float)$row->interes_abonado,
                        'resta_interes_por_abonar' => (float)$interes_pendiente,
                    ];
                }

                return [
                    'acreedor' => $row->acreedor,
                    'descripcion' => $row->descripcion,
                    'capital' => (float)$row->capital,
                    'abono_capital' => (float)$row->abono_capital,
                    'resto_capital' => (float)$row->resto_capital,
                    'mensualidad_capital' => (float)$row->mensualidad_capital,
                    'porcentaje_interes' => (float)$row->porcentaje_interes,
                    'interes_pagar_mensual' => (float)$row->interes_pagar_mensual,
                    'interes_abonado' => (float)$row->interes_abonado,
                    'resta_interes_por_abonar' => (float)$interes_pendiente,
                ];
            })
            ->all();

        return $rows;
    }
}
