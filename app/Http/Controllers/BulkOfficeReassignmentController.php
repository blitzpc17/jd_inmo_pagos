<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BulkOfficeReassignmentController extends Controller
{
    public function index()
    {
        return view('herramientas.reasignar_oficinas', [
            'title' => 'Reasignación de Oficinas'
        ]);
    }

    public function options()
    {
        $offices = DB::table('offices as o')
            ->join('statuses as st', 'st.id', '=', 'o.status_id')
            ->join('processes as p', 'p.id', '=', 'st.process_id')
            ->where('p.clave', 'GENERAL')
            ->where('st.clave', 'ACTIVE')
            ->whereNull('o.fecha_baja')
            ->orderBy('o.nombre')
            ->get([
                'o.id as value',
                'o.nombre as text',
            ]);

        return response()->json([
            'offices' => $offices
        ]);
    }

    public function datatable(Request $request)
    {
        $tipo = $request->query('tipo', 'proveedores'); // 'proveedores', 'acreedores' o 'cobranza'
        $office_id = $request->query('office_id');
        $search = $request->query('search_query');

        $rows = [];

        if ($tipo === 'proveedores') {
            $query = DB::table('supplier_vouchers as v')
                ->join('suppliers as p', 'p.id', '=', 'v.supplier_id')
                ->leftJoin('offices as o', 'o.id', '=', 'v.office_id')
                ->join('statuses as st', 'st.id', '=', 'v.status_id')
                ->whereNull('v.fecha_baja')
                ->select([
                    'v.id',
                    'v.numero_referencia as folio',
                    DB::raw("TRIM(CONCAT(COALESCE(p.nombres,''), ' ', COALESCE(p.apellidos,''))) as entidad"),
                    DB::raw("COALESCE(o.nombre, 'Sin oficina asignada') as oficina_actual"),
                    'v.total',
                    'st.nombre as estado'
                ])
                ->orderByDesc('v.id');

            if ($office_id === 'null') {
                $query->whereNull('v.office_id');
            } elseif ($office_id) {
                $query->where('v.office_id', $office_id);
            }

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('v.numero_referencia', 'like', "%{$search}%")
                      ->orWhere(DB::raw("CONCAT(COALESCE(p.nombres,''), ' ', COALESCE(p.apellidos,''))"), 'like', "%{$search}%");
                });
            }

            $rows = $query->get();
        } elseif ($tipo === 'acreedores') {
            $query = DB::table('creditor_payments as v')
                ->join('creditors as p', 'p.id', '=', 'v.creditor_id')
                ->leftJoin('offices as o', 'o.id', '=', 'v.office_id')
                ->join('statuses as st', 'st.id', '=', 'v.status_id')
                ->whereNull('v.fecha_baja')
                ->select([
                    'v.id',
                    'v.numero_referencia as folio',
                    'p.nombre as entidad',
                    DB::raw("COALESCE(o.nombre, 'Sin oficina asignada') as oficina_actual"),
                    'v.importe as total',
                    'st.nombre as estado'
                ])
                ->orderByDesc('v.id');

            if ($office_id === 'null') {
                $query->whereNull('v.office_id');
            } elseif ($office_id) {
                $query->where('v.office_id', $office_id);
            }

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('v.numero_referencia', 'like', "%{$search}%")
                      ->orWhere('p.nombre', 'like', "%{$search}%");
                });
            }

            $rows = $query->get();
        } elseif ($tipo === 'cobranza') {
            $query = DB::table('charges as v')
                ->join('clients as p', 'p.id', '=', 'v.client_id')
                ->leftJoin('offices as o', 'o.id', '=', 'v.office_receives_charge_id')
                ->join('statuses as st', 'st.id', '=', 'v.status_id')
                ->whereNull('v.fecha_baja')
                ->select([
                    'v.id',
                    'v.numero_referencia as folio',
                    DB::raw("TRIM(CONCAT(COALESCE(p.nombres,''), ' ', COALESCE(p.apellidos,''))) as entidad"),
                    DB::raw("COALESCE(o.nombre, 'Sin oficina asignada') as oficina_actual"),
                    'v.monto as total',
                    'st.nombre as estado'
                ])
                ->orderByDesc('v.id');

            if ($office_id === 'null') {
                $query->whereNull('v.office_receives_charge_id');
            } elseif ($office_id) {
                $query->where('v.office_receives_charge_id', $office_id);
            }

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('v.numero_referencia', 'like', "%{$search}%")
                      ->orWhere(DB::raw("CONCAT(COALESCE(p.nombres,''), ' ', COALESCE(p.apellidos,''))"), 'like', "%{$search}%");
                });
            }

            $rows = $query->get();
        }

        return response()->json(['data' => $rows]);
    }

    public function process(Request $request)
    {
        $data = Validator::make($request->all(), [
            'tipo' => ['required', 'string', 'in:proveedores,acreedores,cobranza'],
            'office_id' => ['required', 'integer', 'exists:offices,id'],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer']
        ])->validate();

        $table = 'supplier_vouchers';
        $officeCol = 'office_id';

        if ($data['tipo'] === 'acreedores') {
            $table = 'creditor_payments';
        } elseif ($data['tipo'] === 'cobranza') {
            $table = 'charges';
            $officeCol = 'office_receives_charge_id';
        }

        DB::beginTransaction();
        try {
            DB::table($table)
                ->whereIn('id', $data['ids'])
                ->update([
                    $officeCol => $data['office_id'],
                    'updated_at' => now()
                ]);

            DB::commit();

            return response()->json([
                'ok' => true,
                'message' => 'Se reasignaron ' . count($data['ids']) . ' boletas correctamente.'
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
