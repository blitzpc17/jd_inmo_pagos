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

        $offices = DB::table('offices')
            ->whereNull('fecha_baja')
            ->orderBy('nombre')
            ->get(['id as value', 'nombre as text']);

        $partners = DB::table('partners')
            ->whereNull('fecha_baja')
            ->orderBy('nombre')
            ->get(['id as value', 'nombre as text']);

        return response()->json([
            'statuses' => $statuses,
            'offices' => $offices,
            'partners' => $partners,
        ]);
    }

    public function show(int $id)
    {
        $row = DB::table('developments')->where('id', $id)->first();
        abort_if(!$row, 404, 'Lotificación no encontrada');

        $officeIds = DB::table('development_offices')
            ->where('development_id', $id)
            ->pluck('office_id')
            ->map(fn ($v) => (int) $v)
            ->values();

        $partnerIds = DB::table('development_partners')
            ->where('development_id', $id)
            ->pluck('partner_id')
            ->map(fn ($v) => (int) $v)
            ->values();

        return response()->json([
            'ok' => true,
            'data' => array_merge((array) $row, [
                'office_ids' => $officeIds,
                'partner_ids' => $partnerIds,
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);

        DB::beginTransaction();

        try {
            $developmentId = DB::table('developments')->insertGetId([
                'nombre' => $data['nombre'],
                'manzanas' => $data['manzanas'] ?? 0,
                'lotes' => $data['lotes'] ?? 0,
                'status_id' => $data['status_id'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->syncOffices($developmentId, $data['office_ids'] ?? []);
            $this->syncPartners($developmentId, $data['partner_ids'] ?? []);

            DB::commit();

            return response()->json([
                'ok' => true,
                'message' => 'Lotificación creada correctamente',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function update(Request $request, int $id)
    {
        abort_if(!DB::table('developments')->where('id', $id)->exists(), 404, 'Lotificación no encontrada');

        $data = $this->validateData($request);

        DB::beginTransaction();

        try {
            DB::table('developments')
                ->where('id', $id)
                ->update([
                    'nombre' => $data['nombre'],
                    'manzanas' => $data['manzanas'] ?? 0,
                    'lotes' => $data['lotes'] ?? 0,
                    'status_id' => $data['status_id'],
                    'updated_at' => now(),
                ]);

            $this->syncOffices($id, $data['office_ids'] ?? []);
            $this->syncPartners($id, $data['partner_ids'] ?? []);

            DB::commit();

            return response()->json([
                'ok' => true,
                'message' => 'Lotificación actualizada correctamente',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
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
            'office_ids' => ['nullable', 'array'],
            'office_ids.*' => ['integer', 'exists:offices,id'],
            'partner_ids' => ['nullable', 'array'],
            'partner_ids.*' => ['integer', 'exists:partners,id'],
        ])->validate();
    }

    protected function syncOffices(int $developmentId, array $officeIds): void
    {
        DB::table('development_offices')
            ->where('development_id', $developmentId)
            ->delete();

        $rows = collect($officeIds)
            ->filter()
            ->unique()
            ->map(fn ($officeId) => [
                'development_id' => $developmentId,
                'office_id' => (int) $officeId,
                'created_at' => now(),
                'updated_at' => now(),
            ])
            ->values()
            ->all();

        if (!empty($rows)) {
            DB::table('development_offices')->insert($rows);
        }
    }

    protected function syncPartners(int $developmentId, array $partnerIds): void
    {
        DB::table('development_partners')
            ->where('development_id', $developmentId)
            ->delete();

        $rows = collect($partnerIds)
            ->filter()
            ->unique()
            ->map(fn ($partnerId) => [
                'development_id' => $developmentId,
                'partner_id' => (int) $partnerId,
                'created_at' => now(),
                'updated_at' => now(),
            ])
            ->values()
            ->all();

        if (!empty($rows)) {
            DB::table('development_partners')->insert($rows);
        }
    }
}