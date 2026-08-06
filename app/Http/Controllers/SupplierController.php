<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SupplierController extends Controller
{
    public function index()
    {
        return view('proveedores.index');
    }

    public function datatable()
    {
        $rows = DB::table('suppliers as p')
            ->join('statuses as s', 's.id', '=', 'p.status_id')
            ->select([
                'p.id',
                'p.nombres',
                'p.apellidos',
                'p.telefono',
                'p.direccion',
                's.nombre as estado',
            ])
            ->orderByDesc('p.id')
            ->get()
            ->map(function ($r) {
                $r->nombre_completo = trim(($r->nombres ?? '') . ' ' . ($r->apellidos ?? ''));
                $r->acciones = '
                    <div class="d-flex gap-1">
                        <button class="btn btn-sm btn-outline-primary btn-edit" data-id="'.$r->id.'"><i class="fa-solid fa-pen"></i></button>
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
            ->get(['s.id as value', 's.nombre as text', 's.clave as clave']);

        return response()->json([
            'statuses' => $statuses
        ]);
    }

    public function show(int $id)
    {
        $row = DB::table('suppliers')->where('id', $id)->first();
        abort_if(!$row, 404, 'Proveedor no encontrado');

        return response()->json(['ok' => true, 'data' => $row]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);

        DB::table('suppliers')->insert([
            'nombres' => mb_strtoupper($data['nombres']),
            'apellidos' => mb_strtoupper($data['apellidos']),
            'telefono' => $data['telefono'] ?? null,
            'direccion' => $data['direccion'] ?? null,
            'status_id' => $data['status_id'],
            'usuario_genero_id' => session('auth_user.id'),
            'fecha_registro' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['ok' => true, 'message' => 'Proveedor creado correctamente']);
    }

    public function update(Request $request, int $id)
    {
        abort_if(!DB::table('suppliers')->where('id', $id)->exists(), 404, 'Proveedor no encontrado');

        $data = $this->validateData($request);

        $inactiveId = DB::table('statuses as s')
            ->join('processes as p', 'p.id', '=', 's.process_id')
            ->where('p.clave', 'GENERAL')
            ->where('s.clave', 'INACTIVE')
            ->value('s.id');

        $updateData = [
            'nombres' => mb_strtoupper($data['nombres']),
            'apellidos' => mb_strtoupper($data['apellidos']),
            'telefono' => $data['telefono'] ?? null,
            'direccion' => $data['direccion'] ?? null,
            'status_id' => $data['status_id'],
            'updated_at' => now(),
        ];

        if ($data['status_id'] == $inactiveId) {
            $updateData['fecha_baja'] = now();
            $updateData['usuario_baja_id'] = session('auth_user.id');
        } else {
            $updateData['fecha_baja'] = null;
            $updateData['usuario_baja_id'] = null;
        }

        DB::table('suppliers')
            ->where('id', $id)
            ->update($updateData);

        return response()->json(['ok' => true, 'message' => 'Proveedor actualizado correctamente']);
    }

    public function destroy(int $id)
    {
        $inactiveId = DB::table('statuses as s')
            ->join('processes as p', 'p.id', '=', 's.process_id')
            ->where('p.clave', 'GENERAL')
            ->where('s.clave', 'INACTIVE')
            ->value('s.id');

        DB::table('suppliers')
            ->where('id', $id)
            ->update([
                'status_id' => $inactiveId,
                'fecha_baja' => now(),
                'usuario_baja_id' => session('auth_user.id'),
                'updated_at' => now(),
            ]);

        return response()->json(['ok' => true, 'message' => 'Proveedor dado de baja correctamente']);
    }

    protected function validateData(Request $request): array
    {
        return Validator::make($request->all(), [
            'nombres' => ['required', 'string', 'max:150'],
            'apellidos' => ['required', 'string', 'max:150'],
            'telefono' => ['nullable', 'string', 'max:50'],
            'direccion' => ['nullable', 'string'],
            'status_id' => ['required', 'integer', 'exists:statuses,id'],
        ])->validate();
    }
}