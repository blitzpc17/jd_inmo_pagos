<?php

namespace App\Http\Controllers;

use App\Exports\DevelopmentSummaryReportExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class DevelopmentSummaryController extends Controller
{
    public function index(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->endOfMonth()->format('Y-m-d'));

        return view('lotificaciones.summary', compact('startDate', 'endDate'));
    }

    public function data(Request $request)
    {
        $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->endOfMonth()->format('Y-m-d'));

        return response()->json([
            'data' => $this->buildRows($startDate, $endDate),
        ]);
    }

    public function export(Request $request)
    {
        $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->endOfMonth()->format('Y-m-d'));

        return Excel::download(
            new DevelopmentSummaryReportExport(
                $this->buildRows($startDate, $endDate),
                $startDate,
                $endDate
            ),
            'resumen_lotificaciones_' . $startDate . '_al_' . $endDate . '.xlsx'
        );
    }

    protected function buildRows(?string $startDate = null, ?string $endDate = null): array
    {
        $startDate = $startDate ?: now()->startOfMonth()->format('Y-m-d');
        $endDate = $endDate ?: now()->endOfMonth()->format('Y-m-d');

        $rows = DB::table('developments as d')
            ->leftJoin('lots as l', function ($join) use ($startDate, $endDate) {
                $join->on('l.development_id', '=', 'd.id')
                    ->whereNull('l.fecha_baja')
                    ->whereDate('l.updated_at', '>=', $startDate)
                    ->whereDate('l.updated_at', '<=', $endDate);
            })
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
                            WHEN UPPER(COALESCE(s.nombre, '')) IN ('VENDIDO', 'OCUPADO', 'CONTRATADO') THEN 1
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