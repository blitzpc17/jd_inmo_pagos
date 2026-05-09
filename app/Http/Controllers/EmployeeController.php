<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class EmployeeController extends Controller
{
    public function index()
    {
        return view('empleados.index');
    }

    public function datatable()
    {
        $rows = DB::table('personal as p')
            ->join('positions as po', 'po.id', '=', 'p.position_id')
            ->join('statuses as s', 's.id', '=', 'p.status_id')
            ->select([
                'p.id',
                'p.nombres',
                'p.apellidos',
                'p.telefono',
                'p.email',
                'p.direccion',
                'po.nombre as puesto',
                's.nombre as estado',
            ])
            ->orderByDesc('p.id')
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
        $positions = DB::table('positions')->orderBy('nombre')->get(['id as value', 'nombre as text']);
        $statuses = DB::table('statuses as s')
            ->join('processes as p', 'p.id', '=', 's.process_id')
            ->where('p.clave', 'GENERAL')
            ->orderBy('s.nombre')
            ->get(['s.id as value', 's.nombre as text']);

        return response()->json([
            'positions' => $positions,
            'statuses' => $statuses,
        ]);
    }

    public function show(int $id)
    {
        $row = DB::table('personal')->where('id', $id)->first();
        abort_if(!$row, 404, 'Empleado no encontrado');

        return response()->json(['ok' => true, 'data' => $row]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);

        DB::table('personal')->insert([
            'nombres' => $data['nombres'],
            'apellidos' => $data['apellidos'],
            'telefono' => $data['telefono'] ?? null,
            'email' => $data['email'] ?? null,
            'direccion' => $data['direccion'] ?? null,
            'position_id' => $data['position_id'],
            'status_id' => $data['status_id'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['ok' => true, 'message' => 'Empleado creado correctamente']);
    }

    public function update(Request $request, int $id)
    {
        abort_if(!DB::table('personal')->where('id', $id)->exists(), 404, 'Empleado no encontrado');

        $data = $this->validateData($request, $id);

        DB::table('personal')
            ->where('id', $id)
            ->update([
                'nombres' => $data['nombres'],
                'apellidos' => $data['apellidos'],
                'telefono' => $data['telefono'] ?? null,
                'email' => $data['email'] ?? null,
                'direccion' => $data['direccion'] ?? null,
                'position_id' => $data['position_id'],
                'status_id' => $data['status_id'],
                'updated_at' => now(),
            ]);

        return response()->json(['ok' => true, 'message' => 'Empleado actualizado correctamente']);
    }

    public function destroy(int $id)
    {
        $inactiveId = DB::table('statuses as s')
            ->join('processes as p', 'p.id', '=', 's.process_id')
            ->where('p.clave', 'GENERAL')
            ->where('s.clave', 'INACTIVE')
            ->value('s.id');

        DB::table('personal')
            ->where('id', $id)
            ->update([
                'status_id' => $inactiveId,
                'fecha_baja' => now(),
                'updated_at' => now(),
            ]);

        return response()->json(['ok' => true, 'message' => 'Empleado dado de baja correctamente']);
    }

    protected function validateData(Request $request, ?int $id = null): array
    {
        return Validator::make($request->all(), [
            'nombres' => ['required', 'string', 'max:150'],
            'apellidos' => ['required', 'string', 'max:150'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150', Rule::unique('personal', 'email')->ignore($id)],
            'direccion' => ['nullable', 'string'],
            'position_id' => ['required', 'integer', 'exists:positions,id'],
            'status_id' => ['required', 'integer', 'exists:statuses,id'],
        ])->validate();
    }
}