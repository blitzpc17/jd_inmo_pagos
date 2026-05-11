<?php

namespace App\Http\Controllers;

use App\Exports\DevelopmentCollectionReportExport;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class DevelopmentCollectionReportController extends Controller
{
    public function index()
    {
        return view('lotificaciones.collection_report');
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
            new DevelopmentCollectionReportExport($this->buildRows()),
            'reporte_cobranza_lotificaciones.xlsx'
        );
    }

    protected function buildRows(): array
    {
        $vigenteContratoId = DB::table('statuses as s')
            ->join('processes as p', 'p.id', '=', 's.process_id')
            ->where('p.clave', 'CONTRACT_STATUS')
            ->whereRaw('UPPER(s.nombre) = ?', ['VIGENTE'])
            ->value('s.id');

        $vigenteApartadoId = DB::table('statuses as s')
            ->join('processes as p', 'p.id', '=', 's.process_id')
            ->where('p.clave', 'RESERVATION_STATUS')
            ->whereRaw('UPPER(s.nombre) = ?', ['VIGENTE'])
            ->value('s.id');

        $registradoCobroId = DB::table('statuses as s')
            ->join('processes as p', 'p.id', '=', 's.process_id')
            ->where('p.clave', 'CHARGE_STATUS')
            ->whereRaw('UPPER(s.nombre) = ?', ['REGISTRADO'])
            ->value('s.id');

        $monthStart = Carbon::now()->startOfMonth()->startOfDay();
        $monthEnd   = Carbon::now()->endOfMonth()->endOfDay();

        $developments = DB::table('developments as d')
            ->whereNull('d.fecha_baja')
            ->orderBy('d.nombre')
            ->get(['d.id', 'd.nombre']);

        $rows = [];

        foreach ($developments as $development) {
            // CONTRATOS vigentes
            $contractsTotal = (float) DB::table('contracts as c')
                ->where('c.development_id', $development->id)
                ->where('c.status_id', $vigenteContratoId)
                ->whereNull('c.fecha_baja')
                ->sum('c.importe');

            // ENGANCHES = apartados vigentes
            $reservationsTotal = (float) DB::table('reservations as r')
                ->where('r.development_id', $development->id)
                ->where('r.status_id', $vigenteApartadoId)
                ->whereNull('r.fecha_baja')
                ->sum('r.importe_apartado');

            // COBRADO = cobros registrados de contratos vigentes
            $chargedTotal = (float) DB::table('charges as ch')
                ->join('contracts as c', 'c.id', '=', 'ch.contract_id')
                ->where('c.development_id', $development->id)
                ->where('c.status_id', $vigenteContratoId)
                ->whereNull('c.fecha_baja')
                ->where('ch.status_id', $registradoCobroId)
                ->whereNull('ch.fecha_baja')
                ->sum(DB::raw('COALESCE(ch.monto,0) + COALESCE(ch.monto_recargo,0)'));

            // RESTO POR COBRAR = contratado - cobrado
            $remainingTotal = max(0, $contractsTotal - $chargedTotal);

            // INGRESO MENSUAL = cobros del mes actual
            $monthlyIncome = (float) DB::table('charges as ch')
                ->join('contracts as c', 'c.id', '=', 'ch.contract_id')
                ->where('c.development_id', $development->id)
                ->where('c.status_id', $vigenteContratoId)
                ->whereNull('c.fecha_baja')
                ->where('ch.status_id', $registradoCobroId)
                ->whereNull('ch.fecha_baja')
                ->whereBetween('ch.fecha_emision', [$monthStart, $monthEnd])
                ->sum(DB::raw('COALESCE(ch.monto,0) + COALESCE(ch.monto_recargo,0)'));

            $rows[] = [
                'lotificacion'      => $development->nombre,
                'contratos'         => round($contractsTotal, 2),
                'enganches'         => round($reservationsTotal, 2),
                'cobrado'           => round($chargedTotal, 2),
                'resto_por_cobrar'  => round($remainingTotal, 2),
                'ingreso_mensual'   => round($monthlyIncome, 2),
            ];
        }

        return $rows;
    }
}