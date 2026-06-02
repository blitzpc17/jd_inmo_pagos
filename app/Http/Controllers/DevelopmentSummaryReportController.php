<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Exports\DevelopmentSummaryReportExport;
use Maatwebsite\Excel\Facades\Excel;

class DevelopmentSummaryReportController extends Controller
{
    public function index(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate   = $request->input('end_date', now()->endOfMonth()->format('Y-m-d'));

        return view('lotificaciones.summary_report', compact('startDate', 'endDate'));
    }

    public function data(Request $request)
    {
        $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date'   => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate   = $request->input('end_date', now()->endOfMonth()->format('Y-m-d'));

        $rows = self::getReportRows($startDate, $endDate);

        return response()->json([
            'success' => true,
            'data' => $rows,
            'totals' => [
                'total_lotes' => $rows->sum('total_lotes'),
                'libres' => $rows->sum('libres'),
                'apartados' => $rows->sum('apartados'),
                'ocupados' => $rows->sum('ocupados'),
                'liberados' => $rows->sum('liberados'),
                'otros' => $rows->sum('otros'),
            ],
        ]);
    }

    public function export(Request $request)
    {
        $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date'   => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate   = $request->input('end_date', now()->endOfMonth()->format('Y-m-d'));

        return Excel::download(
            new DevelopmentSummaryReportExport($startDate, $endDate),
            'resumen_general_lotificaciones_' . $startDate . '_al_' . $endDate . '.xlsx'
        );
    }

    public static function getReportRows(string $startDate, string $endDate)
    {
        return collect(DB::select("
            SELECT
                d.id AS development_id,
                d.nombre AS lotificacion,
                COUNT(l.id) AS total_lotes,

                COUNT(l.id) FILTER (
                    WHERE UPPER(s.clave) = 'LIBRE'
                ) AS libres,

                COUNT(l.id) FILTER (
                    WHERE UPPER(s.clave) = 'APARTADO'
                ) AS apartados,

                COUNT(l.id) FILTER (
                    WHERE UPPER(s.clave) IN ('OCUPADO', 'VENDIDO', 'CONTRATADO')
                ) AS ocupados,

                COUNT(l.id) FILTER (
                    WHERE UPPER(s.clave) = 'LIBERADO'
                ) AS liberados,

                COUNT(l.id) FILTER (
                    WHERE UPPER(s.clave) NOT IN ('LIBRE', 'APARTADO', 'OCUPADO', 'VENDIDO', 'CONTRATADO', 'LIBERADO')
                ) AS otros

            FROM developments d
            LEFT JOIN lots l
                ON l.development_id = d.id
               AND l.fecha_baja IS NULL
               AND DATE(l.updated_at) BETWEEN :start_date AND :end_date

            LEFT JOIN statuses s
                ON s.id = l.status_id

            LEFT JOIN processes p
                ON p.id = s.process_id
               AND p.clave = 'LOT_STATUS'

            WHERE d.fecha_baja IS NULL

            GROUP BY d.id, d.nombre
            ORDER BY d.nombre ASC
        ", [
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]));
    }
}