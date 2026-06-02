<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Exports\DevelopmentCollectionReportExport;
use Maatwebsite\Excel\Facades\Excel;

class DevelopmentCollectionReportController extends Controller
{
    public function index(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate   = $request->input('end_date', now()->endOfMonth()->format('Y-m-d'));

        return view('lotificaciones.collection_report', compact('startDate', 'endDate'));
    }

    public function data(Request $request)
    {
        $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date'   => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate   = $request->input('end_date', now()->endOfMonth()->format('Y-m-d'));

        $rows = $this->getReportRows($startDate, $endDate);

        return response()->json([
            'success' => true,
            'data' => $rows,
            'totals' => [
                'contratos' => $rows->sum('contratos'),
                'enganches' => $rows->sum('enganches'),
                'cobrado' => $rows->sum('cobrado'),
                'resto_por_cobrar' => $rows->sum('resto_por_cobrar'),
                'ingreso_mensual' => $rows->sum('ingreso_mensual'),
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
            new DevelopmentCollectionReportExport($startDate, $endDate),
            'reporte_cobranza_por_lotificacion_' . $startDate . '_al_' . $endDate . '.xlsx'
        );
    }

    public static function getReportRows(string $startDate, string $endDate)
    {
        return collect(DB::select("
            WITH contract_status AS (
                SELECT s.id
                FROM statuses s
                INNER JOIN processes p ON p.id = s.process_id
                WHERE p.clave = 'CONTRACT_STATUS'
                  AND s.clave = 'VIGENTE'
                LIMIT 1
            ),
            charge_status AS (
                SELECT s.id
                FROM statuses s
                INNER JOIN processes p ON p.id = s.process_id
                WHERE p.clave = 'CHARGE_STATUS'
                  AND s.clave = 'REGISTRADO'
                LIMIT 1
            ),
            contratos AS (
                SELECT
                    c.development_id,
                    SUM(COALESCE(c.importe, 0)) AS contratos,
                    SUM(COALESCE(c.monto_pago_inicial, 0)) AS enganches
                FROM contracts c
                WHERE c.fecha_baja IS NULL
                  AND c.status_id = (SELECT id FROM contract_status)
                  AND c.fecha_emision BETWEEN :start_date_1 AND :end_date_1
                GROUP BY c.development_id
            ),
            cobros AS (
                SELECT
                    c.development_id,
                    SUM(COALESCE(ch.monto, 0) + COALESCE(ch.monto_recargo, 0)) AS cobrado,
                    SUM(COALESCE(ch.monto, 0) + COALESCE(ch.monto_recargo, 0)) AS ingreso_mensual
                FROM charges ch
                INNER JOIN contracts c ON c.id = ch.contract_id
                WHERE ch.fecha_baja IS NULL
                  AND c.fecha_baja IS NULL
                  AND ch.status_id = (SELECT id FROM charge_status)
                  AND ch.fecha_emision BETWEEN :start_date_2 AND :end_date_2
                GROUP BY c.development_id
            ),
            pendientes AS (
                SELECT
                    c.development_id,
                    SUM(
                        GREATEST(
                            COALESCE(ps.amount, 0)
                            + COALESCE(ps.late_fee_amount, 0)
                            - COALESCE(ps.amount_paid, 0),
                            0
                        )
                    ) AS resto_por_cobrar
                FROM payment_schedules ps
                INNER JOIN contracts c ON c.id = ps.contract_id
                WHERE c.fecha_baja IS NULL
                  AND c.status_id = (SELECT id FROM contract_status)
                  AND ps.status IN ('PENDIENTE', 'PARCIAL', 'ATRASADO', 'ATRASADO_PARCIAL', 'ADELANTADO')
                  AND ps.due_date BETWEEN :start_date_3 AND :end_date_3
                GROUP BY c.development_id
            )
            SELECT
                d.id AS development_id,
                d.nombre AS lotificacion,
                COALESCE(ct.contratos, 0) AS contratos,
                COALESCE(ct.enganches, 0) AS enganches,
                COALESCE(cb.cobrado, 0) AS cobrado,
                COALESCE(pd.resto_por_cobrar, 0) AS resto_por_cobrar,
                COALESCE(cb.ingreso_mensual, 0) AS ingreso_mensual
            FROM developments d
            LEFT JOIN contratos ct ON ct.development_id = d.id
            LEFT JOIN cobros cb ON cb.development_id = d.id
            LEFT JOIN pendientes pd ON pd.development_id = d.id
            WHERE d.fecha_baja IS NULL
            ORDER BY d.nombre ASC
        ", [
            'start_date_1' => $startDate,
            'end_date_1'   => $endDate,
            'start_date_2' => $startDate,
            'end_date_2'   => $endDate,
            'start_date_3' => $startDate,
            'end_date_3'   => $endDate,
        ]));
    }
}