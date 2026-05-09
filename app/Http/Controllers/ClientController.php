<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ClientController extends Controller
{
    public function index()
    {
        return view('clientes.index');
    }

    public function datatable()
    {
        $rows = DB::table('clients as c')
            ->join('statuses as s', 's.id', '=', 'c.status_id')
            ->leftJoin('users as ug', 'ug.id', '=', 'c.usuario_genero_id')
            ->whereNull('c.fecha_baja')
            ->select([
                'c.id',
                'c.nombres',
                'c.apellidos',
                'c.telefono',
                'c.direccion',
                's.nombre as estado',
                'ug.alias as usuario_genero',
            ])
            ->orderByDesc('c.id')
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

        return response()->json([
            'statuses' => $statuses
        ]);
    }

    public function show(int $id)
    {
        $row = DB::table('clients')->where('id', $id)->first();
        abort_if(!$row, 404, 'Cliente no encontrado');

        return response()->json(['ok' => true, 'data' => $row]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);

        DB::table('clients')->insert([
            'nombres' => $data['nombres'],
            'apellidos' => $data['apellidos'],
            'telefono' => $data['telefono'] ?? null,
            'direccion' => $data['direccion'] ?? null,
            'status_id' => $data['status_id'],
            'usuario_genero_id' => session('auth_user.id'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['ok' => true, 'message' => 'Cliente creado correctamente']);
    }

    public function update(Request $request, int $id)
    {
        abort_if(!DB::table('clients')->where('id', $id)->exists(), 404, 'Cliente no encontrado');

        $data = $this->validateData($request);

        DB::table('clients')
            ->where('id', $id)
            ->update([
                'nombres' => $data['nombres'],
                'apellidos' => $data['apellidos'],
                'telefono' => $data['telefono'] ?? null,
                'direccion' => $data['direccion'] ?? null,
                'status_id' => $data['status_id'],
                'updated_at' => now(),
            ]);

        return response()->json(['ok' => true, 'message' => 'Cliente actualizado correctamente']);
    }

    public function destroy(int $id)
    {
        $inactiveId = DB::table('statuses as s')
            ->join('processes as p', 'p.id', '=', 's.process_id')
            ->where('p.clave', 'GENERAL')
            ->where('s.clave', 'INACTIVE')
            ->value('s.id');

        DB::table('clients')
            ->where('id', $id)
            ->update([
                'status_id' => $inactiveId,
                'fecha_baja' => now(),
                'usuario_baja_id' => session('auth_user.id'),
                'updated_at' => now(),
            ]);

        return response()->json(['ok' => true, 'message' => 'Cliente dado de baja correctamente']);
    }

    protected function validateData(Request $request): array
    {
        return Validator::make($request->all(), [
            'nombres' => ['required', 'string', 'max:150'],
            'apellidos' => ['required', 'string', 'max:150'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'direccion' => ['nullable', 'string'],
            'status_id' => ['required', 'integer', 'exists:statuses,id'],
        ])->validate();
    }
}