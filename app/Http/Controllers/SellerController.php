<?php

namespace App\Http\Controllers;

use App\Services\FolioService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class SellerController extends Controller
{
    public function index()
    {
        return view('vendedores.index');
    }

    public function datatable()
    {
        $rows = DB::table('sellers as v')
            ->join('personal as p', 'p.id', '=', 'v.personal_id')
            ->join('statuses as s', 's.id', '=', 'v.status_id')
            ->select([
                'v.id',
                'v.clave',
                'v.monto_comision',
                'p.nombres',
                'p.apellidos',
                'p.email',
                'p.telefono',
                's.nombre as estado',
            ])
            ->whereNull('v.fecha_baja')
            ->orderByDesc('v.id')
            ->get()
            ->map(function ($r) {
                $r->acciones = '
                    <div class="d-flex gap-1">
                        <button class="btn btn-sm btn-outline-primary btn-edit" data-id="'.$r->id.'"><i class="fa-solid fa-pen"></i></button>
                        <button class="btn btn-sm btn-outline-danger btn-delete" data-id="'.$r->id.'"><i class="fa-solid fa-trash"></i></button>
                    </div>
                ';
                return $r;
            });

        return response()->json(['data' => $rows]);
    }

    public function options()
    {
        $statuses = DB::table('statuses as s')
            ->join('processes as p', 'p.id', '=', 's.process_id')
            ->where('p.clave', 'GENERAL')
            ->orderBy('s.nombre')
            ->get(['s.id as value', 's.nombre as text']);

        $personals = DB::table('personal as p')
            ->leftJoin('sellers as v', 'v.personal_id', '=', 'p.id')
            ->whereNull('v.id')
            ->orderBy('p.nombres')
            ->get([
                'p.id as value',
                DB::raw("p.nombres || ' ' || p.apellidos as text")
            ]);

        return response()->json([
            'statuses' => $statuses,
            'personals' => $personals,
        ]);
    }

    public function show(int $id)
    {
        $row = DB::table('sellers')->where('id', $id)->first();
        abort_if(!$row, 404, 'Vendedor no encontrado');

        return response()->json(['ok' => true, 'data' => $row]);
    }

    public function store(Request $request, FolioService $folioService)
    {
        $data = $this->validateData($request);

        $clave = $folioService->generate('FOLIO_VENDEDOR');

        DB::table('sellers')->insert([
            'personal_id' => $data['personal_id'],
            'clave' => $clave,
            'monto_comision' => $data['monto_comision'],
            'status_id' => $data['status_id'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'Vendedor creado correctamente',
        ]);
    }

    public function update(Request $request, int $id)
    {
        $seller = DB::table('sellers')->where('id', $id)->first();
        abort_if(!$seller, 404, 'Vendedor no encontrado');

        $data = $this->validateData($request, $id);

        DB::table('sellers')
            ->where('id', $id)
            ->update([
                'personal_id' => $data['personal_id'],
                'monto_comision' => $data['monto_comision'],
                'status_id' => $data['status_id'],
                'updated_at' => now(),
            ]);

        return response()->json([
            'ok' => true,
            'message' => 'Vendedor actualizado correctamente',
        ]);
    }

    public function destroy(int $id)
    {
        $inactiveId = DB::table('statuses as s')
            ->join('processes as p', 'p.id', '=', 's.process_id')
            ->where('p.clave', 'GENERAL')
            ->where('s.clave', 'INACTIVE')
            ->value('s.id');

        DB::table('sellers')
            ->where('id', $id)
            ->update([
                'status_id' => $inactiveId,
                'fecha_baja' => now(),
                'updated_at' => now(),
            ]);

        return response()->json([
            'ok' => true,
            'message' => 'Vendedor dado de baja correctamente',
        ]);
    }

    protected function validateData(Request $request, ?int $id = null): array
    {
        return Validator::make($request->all(), [
            'personal_id' => [
                'required',
                'integer',
                'exists:personal,id',
                Rule::unique('sellers', 'personal_id')->ignore($id),
            ],
            'monto_comision' => ['required', 'numeric', 'min:0'],
            'status_id' => ['required', 'integer', 'exists:statuses,id'],
        ])->validate();
    }
}