<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $from = $request->get('from')
            ? Carbon::parse($request->get('from'))
            : now()->startOfMonth()->startOfDay();

        $to = $request->get('to')
            ? Carbon::parse($request->get('to'))
            : now()->endOfDay();

        return view('dashboard.index', [
            'initialStats' => $this->buildStats($from, $to),
            'from' => $from->format('Y-m-d\TH:i'),
            'to' => $to->format('Y-m-d\TH:i'),
        ]);
    }

    public function stats(Request $request)
    {
        $from = $request->get('from')
            ? Carbon::parse($request->get('from'))
            : now()->startOfMonth()->startOfDay();

        $to = $request->get('to')
            ? Carbon::parse($request->get('to'))
            : now()->endOfDay();

        return response()->json($this->buildStats($from, $to));
    }

    protected function buildStats(Carbon $from, Carbon $to): array
    {
        $contractsCount = DB::table('contracts')
            ->whereNull('fecha_baja')
            ->whereBetween('created_at', [$from, $to])
            ->count();

        $supplierPaymentsCount = DB::table('supplier_payments')
            ->whereNull('fecha_baja')
            ->whereBetween('created_at', [$from, $to])
            ->count();

        $clientsCount = DB::table('clients')
            ->whereNull('fecha_baja')
            ->whereBetween('created_at', [$from, $to])
            ->count();

        $creditorsCount = DB::table('creditors')
            ->whereNull('fecha_baja')
            ->whereBetween('created_at', [$from, $to])
            ->count();

        /**
         * OJO:
         * Estos ya no son “hoy real”, sino “dentro del rango seleccionado”.
         * Así el usuario entiende que todo el dashboard responde al filtro.
         */
        $chargesRangeCount = DB::table('charges')
            ->whereNull('fecha_baja')
            ->whereBetween('fecha_emision', [$from, $to])
            ->count();

        $chargesRangeAmount = (float) DB::table('charges')
            ->whereNull('fecha_baja')
            ->whereBetween('fecha_emision', [$from, $to])
            ->sum('monto');

        $chargesByType = DB::table('charges as c')
            ->leftJoin('charge_types as ct', 'ct.id', '=', 'c.charge_type_id')
            ->whereNull('c.fecha_baja')
            ->whereBetween('c.fecha_emision', [$from, $to])
            ->groupBy('ct.nombre')
            ->orderBy('ct.nombre')
            ->get([
                DB::raw("COALESCE(ct.nombre, 'SIN TIPO') as label"),
                DB::raw('COUNT(*) as total')
            ]);

        $paymentsByMethod = DB::table('charges as c')
            ->leftJoin('payment_methods as pm', 'pm.id', '=', 'c.payment_method_id')
            ->whereNull('c.fecha_baja')
            ->whereBetween('c.fecha_emision', [$from, $to])
            ->groupBy('pm.nombre')
            ->orderBy('pm.nombre')
            ->get([
                DB::raw("COALESCE(pm.nombre, 'SIN FORMA') as label"),
                DB::raw('COUNT(*) as total')
            ]);

        /**
         * Serie diaria basada en el rango seleccionado
         */
        $startDate = $from->copy()->startOfDay();
        $endDate = $to->copy()->startOfDay();

        $dates = collect();
        $cursor = $startDate->copy();
        while ($cursor->lte($endDate)) {
            $dates->push($cursor->format('Y-m-d'));
            $cursor->addDay();
        }

        $chargesPerDayRaw = DB::table('charges')
            ->whereNull('fecha_baja')
            ->whereBetween('fecha_emision', [$from, $to])
            ->selectRaw('DATE(fecha_emision) as charge_date, COUNT(*) as total')
            ->groupByRaw('DATE(fecha_emision)')
            ->orderByRaw('DATE(fecha_emision)')
            ->get()
            ->pluck('total', 'charge_date');

        $chargesPerDay = $dates->map(function ($date) use ($chargesPerDayRaw) {
            return [
                'label' => Carbon::parse($date)->format('d/m'),
                'total' => (int) ($chargesPerDayRaw[$date] ?? 0),
            ];
        })->values();

        return [
            'cards' => [
                'contracts' => $contractsCount,
                'supplier_payments' => $supplierPaymentsCount,
                'clients' => $clientsCount,
                'creditors' => $creditorsCount,
                'charges_today_count' => $chargesRangeCount,
                'charges_today_amount' => $chargesRangeAmount,
            ],
            'charts' => [
                'charges_by_type' => $chargesByType,
                'payments_by_method' => $paymentsByMethod,
                'charges_per_day' => $chargesPerDay,
            ],
            'range' => [
                'from' => $from->format('Y-m-d H:i:s'),
                'to' => $to->format('Y-m-d H:i:s'),
            ],
        ];
    }
}