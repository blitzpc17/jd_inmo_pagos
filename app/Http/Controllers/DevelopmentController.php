<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class DevelopmentController extends Controller
{
    public function index()
    {
        return view('lotificaciones.index');
    }

    public function datatable()
    {
        $rows = DB::table('developments as d')
            ->join('statuses as s', 's.id', '=', 'd.status_id')
            ->whereNull('d.fecha_baja')
            ->select([
                'd.id',
                'd.nombre',
                'd.manzanas',
                'd.lotes',
                's.nombre as estado',
            ])
            ->orderByDesc('d.id')
            ->get()
            ->map(function ($r) {
                $r->acciones = '
                    <div class="d-flex gap-1">
                        <a href="'.route('lotificaciones.detalle', $r->id).'" class="btn btn-sm btn-outline-info">
                            <i class="fa-solid fa-table-cells-large"></i>
                        </a>
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

    public function options()
    {
        $statuses = DB::table('statuses as s')
            ->join('processes as p', 'p.id', '=', 's.process_id')
            ->where('p.clave', 'GENERAL')
            ->orderBy('s.nombre')
            ->get(['s.id as value', 's.nombre as text']);

        return response()->json([
            'statuses' => $statuses,
        ]);
    }

    public function show(int $id)
    {
        $row = DB::table('developments')->where('id', $id)->first();
        abort_if(!$row, 404, 'Lotificación no encontrada');

        return response()->json([
            'ok' => true,
            'data' => $row,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);

        DB::table('developments')->insert([
            'nombre' => $data['nombre'],
            'manzanas' => $data['manzanas'] ?? 0,
            'lotes' => $data['lotes'] ?? 0,
            'status_id' => $data['status_id'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'Lotificación creada correctamente',
        ]);
    }

    public function update(Request $request, int $id)
    {
        abort_if(!DB::table('developments')->where('id', $id)->exists(), 404, 'Lotificación no encontrada');

        $data = $this->validateData($request);

        DB::table('developments')
            ->where('id', $id)
            ->update([
                'nombre' => $data['nombre'],
                'manzanas' => $data['manzanas'] ?? 0,
                'lotes' => $data['lotes'] ?? 0,
                'status_id' => $data['status_id'],
                'updated_at' => now(),
            ]);

        return response()->json([
            'ok' => true,
            'message' => 'Lotificación actualizada correctamente',
        ]);
    }

    public function destroy(int $id)
    {
        $inactiveId = DB::table('statuses as s')
            ->join('processes as p', 'p.id', '=', 's.process_id')
            ->where('p.clave', 'GENERAL')
            ->where('s.clave', 'INACTIVE')
            ->value('s.id');

        DB::table('developments')
            ->where('id', $id)
            ->update([
                'status_id' => $inactiveId,
                'fecha_baja' => now(),
                'updated_at' => now(),
            ]);

        return response()->json([
            'ok' => true,
            'message' => 'Lotificación dada de baja correctamente',
        ]);
    }

    protected function validateData(Request $request): array
    {
        return Validator::make($request->all(), [
            'nombre' => ['required', 'string', 'max:150'],
            'manzanas' => ['nullable', 'integer', 'min:0'],
            'lotes' => ['nullable', 'integer', 'min:0'],
            'status_id' => ['required', 'integer', 'exists:statuses,id'],
        ])->validate();
    }
}