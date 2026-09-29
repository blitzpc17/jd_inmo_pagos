<?php

namespace App\Http\Controllers;

use App\Exports\SupplierReportExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class SupplierReportController extends Controller
{
    public function index()
    {
        return view('pagos_proveedores.report');
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
            new SupplierReportExport($this->buildRows(true)),
            'reporte_proveedores_' . now()->format('Ymd_His') . '.xlsx'
        );
    }

    protected function buildRows($forExport = false): array
    {
        $rows = DB::table('supplier_vouchers as sv')
            ->join('suppliers as s', 's.id', '=', 'sv.supplier_id')
            ->leftJoin('developments as d', 'd.id', '=', 'sv.development_id')
            ->whereNull('sv.fecha_baja')
            ->select([
                's.nombres',
                's.apellidos',
                'sv.motivo as descripcion',
                'd.nombre as lotificacion',
                'sv.total as capital',
                'sv.enganche',
                'sv.total_pagado as abono_capital',
                'sv.saldo_pendiente as resto_capital',
                'sv.meses',
                'sv.mensualidad',
            ])
            ->get()
            ->map(function ($row) use ($forExport) {
                $proveedor = trim($row->nombres . ' ' . $row->apellidos);
                
                if ($forExport) {
                    return [
                        'proveedor' => $proveedor,
                        'descripcion' => $row->descripcion,
                        'lotificacion' => $row->lotificacion,
                        'capital' => (float)$row->capital,
                        'enganche' => (float)$row->enganche,
                        'abono_capital' => (float)$row->abono_capital,
                        'resto_capital' => (float)$row->resto_capital,
                        'meses' => (int)$row->meses,
                        'mensualidad' => (float)$row->mensualidad,
                    ];
                }

                return [
                    'proveedor' => $proveedor,
                    'descripcion' => $row->descripcion,
                    'lotificacion' => $row->lotificacion,
                    'capital' => (float)$row->capital,
                    'enganche' => (float)$row->enganche,
                    'abono_capital' => (float)$row->abono_capital,
                    'resto_capital' => (float)$row->resto_capital,
                    'meses' => (int)$row->meses,
                    'mensualidad' => (float)$row->mensualidad,
                ];
            })
            ->all();

        return $rows;
    }
}
