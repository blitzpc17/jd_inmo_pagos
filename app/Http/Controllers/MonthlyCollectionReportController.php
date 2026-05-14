<?php

namespace App\Http\Controllers;

use App\Exports\MonthlyCollectionReportExport;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class MonthlyCollectionReportController extends Controller
{
    public function index()
    {
        return view('lotificaciones.monthly_collection_report', [
            'currentMonth' => (int) now()->format('m'),
            'currentYear' => (int) now()->format('Y'),
            'months' => $this->months(),
        ]);
    }

    public function data(Request $request)
    {
        [$month, $year] = $this->validatedPeriod($request);

        return response()->json([
            'data' => $this->buildRows($month, $year),
            'meta' => [
                'month' => $month,
                'year' => $year,
                'month_name' => $this->monthName($month),
                'real_paid_heading' => 'REAL PAGADO ' . $this->monthName($month),
            ],
        ]);
    }

    public function export(Request $request)
    {
        [$month, $year] = $this->validatedPeriod($request);

        return Excel::download(
            new MonthlyCollectionReportExport(
                $this->buildRows($month, $year),
                $this->monthName($month),
                $year
            ),
            'reporte_cobros_mensuales_' . str_pad((string) $month, 2, '0', STR_PAD_LEFT) . '_' . $year . '.xlsx'
        );
    }

    protected function buildRows(int $month, int $year): array
    {
        $start = Carbon::create($year, $month, 1)->startOfDay();
        $end = (clone $start)->endOfMonth()->endOfDay();

        $registeredChargeStatusId = $this->statusId('CHARGE_STATUS', 'REGISTRADO');

        $downPaymentTypes = [
            'APARTADO INICIAL',
            'COMPLEMENTO DE APARTADO',
            'ENGANCHE',
        ];

        $contractLots = DB::table('contract_lots as cl')
            ->join('lots as l', 'l.id', '=', 'cl.lot_id')
            ->leftJoin('lot_offices as lo', 'lo.lot_id', '=', 'l.id')
            ->leftJoin('offices as o', 'o.id', '=', 'lo.office_id')
            ->groupBy('cl.contract_id')
            ->selectRaw("
                cl.contract_id,
                STRING_AGG(DISTINCT l.identificador, ', ' ORDER BY l.identificador) AS lotes,
                STRING_AGG(DISTINCT o.nombre, ', ' ORDER BY o.nombre) AS oficinas_lote
            ");

        $reservationLots = DB::table('reservation_lots as rl')
            ->join('lots as l', 'l.id', '=', 'rl.lot_id')
            ->leftJoin('lot_offices as lo', 'lo.lot_id', '=', 'l.id')
            ->leftJoin('offices as o', 'o.id', '=', 'lo.office_id')
            ->groupBy('rl.reservation_id')
            ->selectRaw("
                rl.reservation_id,
                STRING_AGG(DISTINCT l.identificador, ', ' ORDER BY l.identificador) AS lotes,
                STRING_AGG(DISTINCT o.nombre, ', ' ORDER BY o.nombre) AS oficinas_lote
            ");

        $monthlySchedules = DB::table('payment_schedules')
            ->whereBetween('due_date', [$start->toDateString(), $end->toDateString()])
            ->groupBy('contract_id')
            ->selectRaw('contract_id, SUM(COALESCE(amount,0)) AS mensualidad_mes');

        $contractCharges = DB::table('charges as ch')
            ->leftJoin('charge_types as ct', 'ct.id', '=', 'ch.charge_type_id')
            ->where('ch.status_id', $registeredChargeStatusId)
            ->whereNull('ch.fecha_baja')
            ->whereNotNull('ch.contract_id')
            ->whereBetween('ch.fecha_emision', [$start->toDateString(), $end->toDateString()])
            ->groupBy('ch.contract_id')
            ->selectRaw("
                ch.contract_id,
                SUM(
                    CASE
                        WHEN ch.reservation_id IS NULL
                         AND UPPER(COALESCE(ct.nombre,'')) NOT IN (?, ?, ?)
                        THEN COALESCE(ch.monto,0)
                        ELSE 0
                    END
                ) AS real_pagado,
                SUM(
                    CASE
                        WHEN ch.reservation_id IS NULL
                         AND UPPER(COALESCE(ct.nombre,'')) IN (?, ?, ?)
                        THEN COALESCE(ch.monto,0)
                        ELSE 0
                    END
                ) AS apartados_enganches,
                SUM(COALESCE(ch.monto_recargo,0)) AS cobro_recargo,
                STRING_AGG(DISTINCT ch.numero_referencia, ', ' ORDER BY ch.numero_referencia) AS folios,
                STRING_AGG(DISTINCT NULLIF(BTRIM(COALESCE(ch.observacion,'')), ''), ', ') AS observacion
            ", array_merge($downPaymentTypes, $downPaymentTypes));

        $contracts = DB::table('contracts as c')
            ->joinSub($contractCharges, 'cp', function ($join) {
                $join->on('cp.contract_id', '=', 'c.id');
            })
            ->join('clients as cli', 'cli.id', '=', 'c.client_id')
            ->join('developments as d', 'd.id', '=', 'c.development_id')
            ->leftJoin('offices as co', 'co.id', '=', 'c.office_id')
            ->leftJoinSub($contractLots, 'clots', function ($join) {
                $join->on('clots.contract_id', '=', 'c.id');
            })
            ->leftJoinSub($monthlySchedules, 'ps', function ($join) {
                $join->on('ps.contract_id', '=', 'c.id');
            })
            ->whereNull('c.fecha_baja')
            ->orderBy('d.nombre')
            ->orderBy('cli.nombres')
            ->get([
                DB::raw("COALESCE(clots.oficinas_lote, co.nombre, '') as oficina"),
                'd.nombre as lotificacion',
                DB::raw("COALESCE(clots.lotes, '') as lote"),
                DB::raw("BTRIM(COALESCE(cli.nombres,'') || ' ' || COALESCE(cli.apellidos,'')) as nombre_cliente"),
                DB::raw("COALESCE(cli.telefono, '') as num"),
                DB::raw("COALESCE(ps.mensualidad_mes, c.cuota_mensual, 0) as mensualidad"),
                DB::raw("COALESCE(cp.real_pagado, 0) as real_pagado"),
                DB::raw("COALESCE(cp.apartados_enganches, 0) as apartados_enganches"),
                DB::raw("COALESCE(cp.cobro_recargo, 0) as cobro_recargo"),
                DB::raw("COALESCE(cp.folios, '') as folio"),
                DB::raw("COALESCE(cp.observacion, '') as observacion"),
            ])
            ->map(fn ($row) => $this->normalizeRow($row))
            ->all();

        $reservationCharges = DB::table('charges as ch')
            ->where('ch.status_id', $registeredChargeStatusId)
            ->whereNull('ch.fecha_baja')
            ->whereNotNull('ch.reservation_id')
            ->whereBetween('ch.fecha_emision', [$start->toDateString(), $end->toDateString()])
            ->groupBy('ch.reservation_id')
            ->selectRaw("
                ch.reservation_id,
                SUM(COALESCE(ch.monto,0)) AS monto_cobrado,
                SUM(COALESCE(ch.monto_recargo,0)) AS cobro_recargo,
                STRING_AGG(DISTINCT ch.numero_referencia, ', ' ORDER BY ch.numero_referencia) AS folios,
                STRING_AGG(DISTINCT NULLIF(BTRIM(COALESCE(ch.observacion,'')), ''), ', ') AS observacion
            ");

        $reservations = DB::table('reservations as r')
            ->join('clients as cli', 'cli.id', '=', 'r.client_id')
            ->join('developments as d', 'd.id', '=', 'r.development_id')
            ->leftJoinSub($reservationLots, 'rlots', function ($join) {
                $join->on('rlots.reservation_id', '=', 'r.id');
            })
            ->leftJoinSub($reservationCharges, 'rp', function ($join) {
                $join->on('rp.reservation_id', '=', 'r.id');
            })
            ->whereNull('r.fecha_baja')
            ->whereBetween('r.fecha_emision', [$start->toDateString(), $end->toDateString()])
            ->orderBy('d.nombre')
            ->orderBy('cli.nombres')
            ->get([
                DB::raw("COALESCE(rlots.oficinas_lote, '') as oficina"),
                'd.nombre as lotificacion',
                DB::raw("COALESCE(rlots.lotes, '') as lote"),
                DB::raw("BTRIM(COALESCE(cli.nombres,'') || ' ' || COALESCE(cli.apellidos,'')) as nombre_cliente"),
                DB::raw("COALESCE(cli.telefono, '') as num"),
                DB::raw('0 as mensualidad'),
                DB::raw('0 as real_pagado'),
                DB::raw('COALESCE(r.importe_apartado,0) + COALESCE(rp.monto_cobrado,0) as apartados_enganches'),
                DB::raw('COALESCE(rp.cobro_recargo,0) as cobro_recargo'),
                DB::raw("BTRIM(CONCAT_WS(', ', r.numero_referencia, rp.folios)) as folio"),
                DB::raw("BTRIM(CONCAT_WS(', ', NULLIF(r.observaciones,''), rp.observacion)) as observacion"),
            ])
            ->map(fn ($row) => $this->normalizeRow($row))
            ->all();

        return collect(array_merge($contracts, $reservations))
            ->sortBy([
                ['lotificacion', 'asc'],
                ['nombre_cliente', 'asc'],
                ['lote', 'asc'],
            ])
            ->values()
            ->all();
    }

    protected function normalizeRow(object $row): array
    {
        return [
            'oficina' => (string) ($row->oficina ?? ''),
            'lotificacion' => (string) ($row->lotificacion ?? ''),
            'lote' => (string) ($row->lote ?? ''),
            'nombre_cliente' => (string) ($row->nombre_cliente ?? ''),
            'num' => (string) ($row->num ?? ''),
            'mensualidad' => round((float) ($row->mensualidad ?? 0), 2),
            'real_pagado' => round((float) ($row->real_pagado ?? 0), 2),
            'apartados_enganches' => round((float) ($row->apartados_enganches ?? 0), 2),
            'cobro_recargo' => round((float) ($row->cobro_recargo ?? 0), 2),
            'folio' => (string) ($row->folio ?? ''),
            'observacion' => (string) ($row->observacion ?? ''),
        ];
    }

    protected function validatedPeriod(Request $request): array
    {
        $data = Validator::make($request->all(), [
            'month' => ['nullable', 'integer', 'between:1,12'],
            'year' => ['nullable', 'integer', 'between:2000,2100'],
        ])->validate();

        return [
            (int) ($data['month'] ?? now()->format('m')),
            (int) ($data['year'] ?? now()->format('Y')),
        ];
    }

    protected function statusId(string $processKey, string $statusKey): int
    {
        $id = DB::table('statuses as s')
            ->join('processes as p', 'p.id', '=', 's.process_id')
            ->where('p.clave', $processKey)
            ->where('s.clave', $statusKey)
            ->value('s.id');

        if (!$id) {
            abort(500, "No existe el estado {$statusKey} para {$processKey}.");
        }

        return (int) $id;
    }

    protected function monthName(int $month): string
    {
        return $this->months()[$month] ?? '';
    }

    protected function months(): array
    {
        return [
            1 => 'ENERO',
            2 => 'FEBRERO',
            3 => 'MARZO',
            4 => 'ABRIL',
            5 => 'MAYO',
            6 => 'JUNIO',
            7 => 'JULIO',
            8 => 'AGOSTO',
            9 => 'SEPTIEMBRE',
            10 => 'OCTUBRE',
            11 => 'NOVIEMBRE',
            12 => 'DICIEMBRE',
        ];
    }
}