<?php

namespace App\Http\Controllers;

use App\Exports\DevelopmentSummaryExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class DevelopmentSummaryController extends Controller
{
    public function index()
    {
        return view('lotificaciones.summary');
    }

    public function data()
    {
        return response()->json([
            'data' => $this->buildRows(),
        ]);
    }

    public function export()
    {
        return Excel::download(
            new DevelopmentSummaryExport($this->buildRows()),
            'resumen_lotificaciones.xlsx'
        );
    }

    protected function buildRows(): array
    {
        $rows = DB::table('developments as d')
            ->leftJoin('lots as l', 'l.development_id', '=', 'd.id')
            ->leftJoin('statuses as s', 's.id', '=', 'l.status_id')
            ->whereNull('d.fecha_baja')
            ->groupBy('d.id', 'd.nombre')
            ->orderBy('d.nombre')
            ->select([
                'd.id',
                'd.nombre',
                DB::raw("
                    SUM(
                        CASE
                            WHEN UPPER(COALESCE(s.nombre, '')) = 'VENDIDO' THEN 1
                            ELSE 0
                        END
                    ) as vendidos
                "),
                DB::raw("
                    SUM(
                        CASE
                            WHEN UPPER(COALESCE(s.nombre, '')) = 'APARTADO' THEN 1
                            ELSE 0
                        END
                    ) as apartados
                "),
                DB::raw("
                    SUM(
                        CASE
                            WHEN UPPER(COALESCE(s.nombre, '')) IN ('LIBRE', 'DISPONIBLE') THEN 1
                            ELSE 0
                        END
                    ) as disponibles
                "),
                DB::raw("COUNT(l.id) as total")
            ])
            ->get();

        return $rows->map(function ($row) {
            return [
                'lotificacion' => $row->nombre,
                'vendidos' => (int) $row->vendidos,
                'apartados' => (int) $row->apartados,
                'disponibles' => (int) $row->disponibles,
                'total' => (int) $row->total,
            ];
        })->values()->all();
    }
}