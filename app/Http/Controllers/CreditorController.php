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
            ->whereNull('c.fecha_baja')
            ->select([
                'c.id',
                'c.nombres',
                'c.apellidos',
                'c.telefono',
                'c.direccion',
                's.nombre as estado',
            ])
            ->orderByDesc('c.id')
            ->get()
            ->map(function ($r) {
                $r->nombre_completo = trim(($r->nombres ?? '') . ' ' . ($r->apellidos ?? ''));
                $r->acciones = '
                    <div class="d-flex gap-1">
                        <button class="btn btn-sm btn-outline-primary btn-edit" data-id="'.$r->id.'">
                            <i class="fa-solid fa-pen"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger btn-delete" data-id="'.$r->id.'">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                ';
                return $r;
            });

        return response()->json(['data' => $rows]);
    }

    public function store(Request $request)
    {
        $data = Validator::make($request->all(), [
            'nombres' => ['required', 'string', 'max:150'],
            'apellidos' => ['required', 'string', 'max:150'],
            'telefono' => ['nullable', 'string', 'max:50'],
            'direccion' => ['nullable', 'string'],
        ])->validate();

        $activeId = $this->getActiveStatusId();

        DB::table('creditors')->insert([
            'nombres' => mb_strtoupper($data['nombres']),
            'apellidos' => mb_strtoupper($data['apellidos']),
            'telefono' => $data['telefono'] ?? null,
            'direccion' => $data['direccion'] ?? null,
            'status_id' => $activeId,
            'fecha_registro' => now(),
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
            'nombres' => ['required', 'string', 'max:150'],
            'apellidos' => ['required', 'string', 'max:150'],
            'telefono' => ['nullable', 'string', 'max:50'],
            'direccion' => ['nullable', 'string'],
        ])->validate();

        DB::table('creditors')
            ->where('id', $id)
            ->update([
                'nombres' => mb_strtoupper($data['nombres']),
                'apellidos' => mb_strtoupper($data['apellidos']),
                'telefono' => $data['telefono'] ?? null,
                'direccion' => $data['direccion'] ?? null,
                'updated_at' => now(),
            ]);

        return response()->json([
            'ok' => true,
            'message' => 'Acreedor actualizado correctamente.'
        ]);
    }

    public function destroy(Request $request, int $id)
    {
        DB::table('creditors')
            ->where('id', $id)
            ->update([
                'fecha_baja' => now(),
                'usuario_baja_id' => session('auth_user.id'),
                'updated_at' => now(),
            ]);

        return response()->json([
            'ok' => true,
            'message' => 'Acreedor dado de baja correctamente.'
        ]);
    }

    protected function getActiveStatusId(): int
    {
        $id = DB::table('statuses as s')
            ->join('processes as p', 'p.id', '=', 's.process_id')
            ->where('p.clave', 'GENERAL')
            ->where('s.clave', 'ACTIVE')
            ->value('s.id');

        if (!$id) {
            abort(500, 'No existe estado ACTIVE para GENERAL.');
        }

        return (int) $id;
    }
}