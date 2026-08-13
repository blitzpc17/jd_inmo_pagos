<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CreditorController extends Controller
{
    public function index()
    {
        return view('acreedores.index');
    }

    public function datatable()
    {
        $rows = DB::table('creditors as c')
            ->join('statuses as s', 's.id', '=', 'c.status_id')
            ->select([
                'c.id',
                'c.nombre',
                'c.telefonos',
                'c.direcciones',
                's.nombre as estado',
            ])
            ->orderByDesc('c.id')
            ->get()
            ->map(function ($r) {
                $r->acciones = '
                    <div class="d-flex gap-1">
                        <button class="btn btn-sm btn-outline-primary btn-edit" data-id="'.$r->id.'">
                            <i class="fa-solid fa-pen"></i>
                        </button>
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

    public function store(Request $request)
    {
        $data = Validator::make($request->all(), [
            'nombre' => ['required', 'string', 'max:150'],
            'telefonos' => ['nullable', 'string'],
            'direcciones' => ['nullable', 'string'],
            'status_id' => ['nullable', 'integer', 'exists:statuses,id'],
        ])->validate();

        $activeId = DB::table('statuses as s')
            ->join('processes as p', 'p.id', '=', 's.process_id')
            ->where('p.clave', 'GENERAL')
            ->where('s.clave', 'ACTIVE')
            ->value('s.id');

        DB::table('creditors')->insert([
            'nombre' => mb_strtoupper($data['nombre']),
            'telefonos' => $data['telefonos'] ?? null,
            'direcciones' => $data['direcciones'] ?? null,
            'status_id' => $data['status_id'] ?? $activeId,
            'usuario_genero_id' => session('auth_user.id'),
            'updated_at' => now(),
            'created_at' => now(),
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'Acreedor registrado correctamente.'
        ]);
    }

    public function show(int $id)
    {
        $row = DB::table('creditors')->where('id', $id)->first();
        abort_if(!$row, 404, 'Acreedor no encontrado');

        return response()->json([
            'ok' => true,
            'data' => $row
        ]);
    }

    public function update(Request $request, int $id)
    {
        $data = Validator::make($request->all(), [
            'nombre' => ['required', 'string', 'max:150'],
            'telefonos' => ['nullable', 'string'],
            'direcciones' => ['nullable', 'string'],
            'status_id' => ['required', 'integer', 'exists:statuses,id'],
        ])->validate();

        $updateData = [
            'nombre' => mb_strtoupper($data['nombre']),
            'telefonos' => $data['telefonos'] ?? null,
            'direcciones' => $data['direcciones'] ?? null,
            'status_id' => $data['status_id'],
            'updated_at' => now(),
        ];

        $inactiveId = DB::table('statuses as s')
            ->join('processes as p', 'p.id', '=', 's.process_id')
            ->where('p.clave', 'GENERAL')
            ->where('s.clave', 'INACTIVE')
            ->value('s.id');

        if ($data['status_id'] == $inactiveId) {
            $updateData['fecha_baja'] = now();
            $updateData['usuario_baja_id'] = session('auth_user.id');
        } else {
            $updateData['fecha_baja'] = null;
            $updateData['usuario_baja_id'] = null;
        }

        DB::table('creditors')
            ->where('id', $id)
            ->update($updateData);

        return response()->json([
            'ok' => true,
            'message' => 'Acreedor actualizado correctamente.'
        ]);
    }
}