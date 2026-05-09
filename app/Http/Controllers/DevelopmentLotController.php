<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class DevelopmentLotController extends Controller
{
    public function index(int $developmentId)
    {
        $development = DB::table('developments')->where('id', $developmentId)->first();
        abort_if(!$development, 404, 'Lotificación no encontrada');

        return view('lotificaciones.detalle', compact('development'));
    }

    public function datatable(int $developmentId)
    {
        $rows = DB::table('lots as l')
            ->join('statuses as s', 's.id', '=', 'l.status_id')
            ->where('l.development_id', $developmentId)
            ->whereNull('l.fecha_baja')
            ->select([
                'l.id',
                'l.identificador',
                'l.manzana',
                'l.precio_contado',
                'l.precio_credito',
                's.clave as estado_clave',
                's.nombre as estado',
            ])
            ->orderByRaw("
                CASE
                    WHEN l.manzana IS NULL OR l.manzana = '' THEN 999999
                    ELSE CAST(regexp_replace(l.manzana, '[^0-9]', '', 'g') AS INTEGER)
                END ASC
            ")
            ->orderBy('l.identificador')
            ->get()
            ->map(function ($r) {
                $canEdit = $r->estado_clave === 'LIBRE';

                $badgeStyle = match ($r->estado_clave) {
                    'LIBRE' => 'background:#ffffff;color:#111827;border:1px solid #d1d5db;',
                    'APARTADO' => 'background:#f59e0b;color:#ffffff;border:1px solid #d97706;',
                    'VENDIDO' => 'background:#dc2626;color:#ffffff;border:1px solid #b91c1c;',
                    'LIQUIDADO' => 'background:#2563eb;color:#ffffff;border:1px solid #1d4ed8;',
                    default => 'background:#e5e7eb;color:#111827;border:1px solid #d1d5db;',
                };

                $r->estado_badge = '<span class="badge rounded-pill px-3 py-2" style="'.$badgeStyle.'">'.$r->estado.'</span>';

                $r->acciones = $canEdit
                    ? '
                        <div class="d-flex gap-1">
                            <button class="btn btn-sm btn-outline-primary btn-edit" data-id="'.$r->id.'">
                                <i class="fa-solid fa-pen"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger btn-delete" data-id="'.$r->id.'">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                    '
                    : '
                        <div class="d-flex gap-1">
                            <button class="btn btn-sm btn-outline-secondary" disabled title="Solo se puede modificar si está libre">
                                <i class="fa-solid fa-lock"></i>
                            </button>
                        </div>
                    ';

                return $r;
            });

        return response()->json(['data' => $rows]);
    }

    public function options(int $developmentId)
    {
        $lotStatuses = DB::table('statuses as s')
            ->join('processes as p', 'p.id', '=', 's.process_id')
            ->where('p.clave', 'LOT_STATUS')
            ->orderByRaw("
                CASE s.clave
                    WHEN 'LIBRE' THEN 1
                    WHEN 'APARTADO' THEN 2
                    WHEN 'VENDIDO' THEN 3
                    WHEN 'LIQUIDADO' THEN 4
                    ELSE 99
                END
            ")
            ->get(['s.id as value', 's.nombre as text', 's.clave']);

        $development = DB::table('developments')->where('id', $developmentId)->first();
        abort_if(!$development, 404, 'Lotificación no encontrada');

        $manzanas = [];
        $totalManzanas = (int) ($development->manzanas ?? 0);

        for ($i = 1; $i <= $totalManzanas; $i++) {
            $manzanas[] = [
                'value' => 'M'.$i,
                'text' => 'Manzana '.$i,
            ];
        }

        $offices = DB::table('development_offices as do')
            ->join('offices as o', 'o.id', '=', 'do.office_id')
            ->where('do.development_id', $developmentId)
            ->orderBy('o.nombre')
            ->get(['o.id as value', 'o.nombre as text']);

        $partners = DB::table('development_partners as dp')
            ->join('partners as p', 'p.id', '=', 'dp.partner_id')
            ->where('dp.development_id', $developmentId)
            ->orderBy('p.nombre')
            ->get(['p.id as value', 'p.nombre as text']);

        return response()->json([
            'lot_statuses' => $lotStatuses,
            'manzanas' => $manzanas,
            'offices' => $offices,
            'partners' => $partners,
            'development' => [
                'id' => $development->id,
                'nombre' => $development->nombre,
                'manzanas' => (int) ($development->manzanas ?? 0),
                'lotes' => (int) ($development->lotes ?? 0),
            ],
        ]);
    }

    public function show(int $developmentId, int $lotId)
    {
        $row = DB::table('lots as l')
            ->join('statuses as s', 's.id', '=', 'l.status_id')
            ->where('l.development_id', $developmentId)
            ->where('l.id', $lotId)
            ->select([
                'l.*',
                's.nombre as estado_nombre',
                's.clave as estado_clave',
            ])
            ->first();

        abort_if(!$row, 404, 'Lote no encontrado');
        abort_if($row->estado_clave !== 'LIBRE', 422, 'Solo se pueden editar lotes en estado libre');

        $officeIds = DB::table('lot_offices')
            ->where('lot_id', $lotId)
            ->pluck('office_id')
            ->map(fn ($v) => (int) $v)
            ->values();

        $partnerIds = DB::table('lot_partners')
            ->where('lot_id', $lotId)
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

    public function store(Request $request, int $developmentId)
    {
        $data = $this->validateLot($request, null, $developmentId);
        $libreStatusId = $this->getLibreStatusId();

        DB::beginTransaction();

        try {
            $lotId = DB::table('lots')->insertGetId([
                'development_id' => $developmentId,
                'identificador' => $data['identificador'],
                'manzana' => $data['manzana'] ?? null,
                'precio_contado' => $data['precio_contado'],
                'precio_credito' => $data['precio_credito'],
                'status_id' => $libreStatusId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->syncLotOffices($lotId, $data['office_ids'] ?? []);
            $this->syncLotPartners($lotId, $data['partner_ids'] ?? []);

            DB::commit();

            return response()->json([
                'ok' => true,
                'message' => 'Lote creado correctamente',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function update(Request $request, int $developmentId, int $lotId)
    {
        $lot = DB::table('lots as l')
            ->join('statuses as s', 's.id', '=', 'l.status_id')
            ->where('l.development_id', $developmentId)
            ->where('l.id', $lotId)
            ->select(['l.*', 's.clave as estado_clave'])
            ->first();

        abort_if(!$lot, 404, 'Lote no encontrado');
        abort_if($lot->estado_clave !== 'LIBRE', 422, 'Solo se pueden editar lotes en estado libre');

        $data = $this->validateLotUpdate($request);

        DB::beginTransaction();

        try {
            DB::table('lots')
                ->where('id', $lotId)
                ->update([
                    'precio_contado' => $data['precio_contado'],
                    'precio_credito' => $data['precio_credito'],
                    'updated_at' => now(),
                ]);

            $this->syncLotOffices($lotId, $data['office_ids'] ?? []);
            $this->syncLotPartners($lotId, $data['partner_ids'] ?? []);

            DB::commit();

            return response()->json([
                'ok' => true,
                'message' => 'Lote actualizado correctamente',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function destroy(int $developmentId, int $lotId)
    {
        $lot = DB::table('lots as l')
            ->join('statuses as s', 's.id', '=', 'l.status_id')
            ->where('l.development_id', $developmentId)
            ->where('l.id', $lotId)
            ->select(['l.id', 's.clave as estado_clave'])
            ->first();

        abort_if(!$lot, 404, 'Lote no encontrado');
        abort_if($lot->estado_clave !== 'LIBRE', 422, 'Solo se pueden eliminar lotes en estado libre');

        DB::table('lots')
            ->where('id', $lotId)
            ->update([
                'fecha_baja' => now(),
                'updated_at' => now(),
            ]);

        return response()->json([
            'ok' => true,
            'message' => 'Lote eliminado correctamente',
        ]);
    }

    public function generate(Request $request, int $developmentId)
    {
        $development = DB::table('developments')->where('id', $developmentId)->first();
        abort_if(!$development, 404, 'Lotificación no encontrada');

        $data = Validator::make($request->all(), [
            'crear_todos' => ['nullable'],
            'manzana' => ['nullable', 'string', 'max:20'],
            'precio_contado' => ['required', 'numeric', 'min:0'],
            'precio_credito' => ['required', 'numeric', 'min:0'],
            'office_ids' => ['nullable', 'array'],
            'office_ids.*' => ['integer', 'exists:offices,id'],
            'partner_ids' => ['nullable', 'array'],
            'partner_ids.*' => ['integer', 'exists:partners,id'],
        ])->validate();

        $crearTodos = filter_var($request->input('crear_todos', false), FILTER_VALIDATE_BOOLEAN);
        $manzanas = (int) ($development->manzanas ?? 0);
        $lotesPorManzana = (int) ($development->lotes ?? 0);

        if ($lotesPorManzana <= 0) {
            return response()->json([
                'message' => 'La lotificación no tiene definido el número de lotes.'
            ], 422);
        }

        if (!$crearTodos && $manzanas > 0 && empty($data['manzana'])) {
            return response()->json([
                'message' => 'Debes seleccionar una manzana.'
            ], 422);
        }

        $libreStatusId = $this->getLibreStatusId();

        DB::beginTransaction();

        try {
            if ($crearTodos) {
                if ($manzanas > 0) {
                    for ($m = 1; $m <= $manzanas; $m++) {
                        $manzanaCode = 'M' . $m;
                        for ($l = 1; $l <= $lotesPorManzana; $l++) {
                            $identifier = $manzanaCode . ' L' . $l;

                            $lotId = DB::table('lots')->insertGetId([
                                'development_id' => $developmentId,
                                'identificador' => $identifier,
                                'manzana' => $manzanaCode,
                                'precio_contado' => $data['precio_contado'],
                                'precio_credito' => $data['precio_credito'],
                                'status_id' => $libreStatusId,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);

                            $this->syncLotOffices($lotId, $data['office_ids'] ?? []);
                            $this->syncLotPartners($lotId, $data['partner_ids'] ?? []);
                        }
                    }
                } else {
                    for ($l = 1; $l <= $lotesPorManzana; $l++) {
                        $identifier = 'SM L' . $l;

                        $lotId = DB::table('lots')->insertGetId([
                            'development_id' => $developmentId,
                            'identificador' => $identifier,
                            'manzana' => null,
                            'precio_contado' => $data['precio_contado'],
                            'precio_credito' => $data['precio_credito'],
                            'status_id' => $libreStatusId,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        $this->syncLotOffices($lotId, $data['office_ids'] ?? []);
                        $this->syncLotPartners($lotId, $data['partner_ids'] ?? []);
                    }
                }
            } else {
                if ($manzanas > 0) {
                    $selectedManzana = trim($data['manzana']);
                    preg_match('/\d+/', $selectedManzana, $matches);
                    $manzanaNumber = $matches[0] ?? null;

                    if (!$manzanaNumber) {
                        DB::rollBack();
                        return response()->json([
                            'message' => 'La manzana seleccionada es inválida.'
                        ], 422);
                    }

                    $manzanaCode = 'M' . $manzanaNumber;

                    for ($l = 1; $l <= $lotesPorManzana; $l++) {
                        $identifier = $manzanaCode . ' L' . $l;

                        $lotId = DB::table('lots')->insertGetId([
                            'development_id' => $developmentId,
                            'identificador' => $identifier,
                            'manzana' => $manzanaCode,
                            'precio_contado' => $data['precio_contado'],
                            'precio_credito' => $data['precio_credito'],
                            'status_id' => $libreStatusId,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        $this->syncLotOffices($lotId, $data['office_ids'] ?? []);
                        $this->syncLotPartners($lotId, $data['partner_ids'] ?? []);
                    }
                } else {
                    for ($l = 1; $l <= $lotesPorManzana; $l++) {
                        $identifier = 'SM L' . $l;

                        $lotId = DB::table('lots')->insertGetId([
                            'development_id' => $developmentId,
                            'identificador' => $identifier,
                            'manzana' => null,
                            'precio_contado' => $data['precio_contado'],
                            'precio_credito' => $data['precio_credito'],
                            'status_id' => $libreStatusId,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        $this->syncLotOffices($lotId, $data['office_ids'] ?? []);
                        $this->syncLotPartners($lotId, $data['partner_ids'] ?? []);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'ok' => true,
                'message' => 'Lotes generados correctamente en estado libre.',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function bulkUpdate(Request $request, int $developmentId)
    {
        $development = DB::table('developments')->where('id', $developmentId)->first();
        abort_if(!$development, 404, 'Lotificación no encontrada');

        $data = Validator::make($request->all(), [
            'manzana' => ['nullable', 'string', 'max:20'],
            'desde' => ['required', 'integer', 'min:1'],
            'hasta' => ['required', 'integer', 'min:1'],
            'precio_contado' => ['required', 'numeric', 'min:0'],
            'precio_credito' => ['required', 'numeric', 'min:0'],
            'office_ids' => ['nullable', 'array'],
            'office_ids.*' => ['integer', 'exists:offices,id'],
            'partner_ids' => ['nullable', 'array'],
            'partner_ids.*' => ['integer', 'exists:partners,id'],
        ], [
            'desde.required' => 'El lote inicial es obligatorio.',
            'hasta.required' => 'El lote final es obligatorio.',
        ])->validate();

        if ((int)$data['hasta'] < (int)$data['desde']) {
            return response()->json([
                'message' => 'El rango es inválido.'
            ], 422);
        }

        $freeStatusId = $this->getLibreStatusId();
        $hasManzanas = ((int) ($development->manzanas ?? 0)) > 0;

        if ($hasManzanas && empty($data['manzana'])) {
            return response()->json([
                'message' => 'Debes seleccionar una manzana para la modificación masiva.'
            ], 422);
        }

        $identifiers = [];
        if ($hasManzanas) {
            preg_match('/\d+/', trim($data['manzana']), $matches);
            $manzanaNumber = $matches[0] ?? null;

            if (!$manzanaNumber) {
                return response()->json([
                    'message' => 'La manzana seleccionada es inválida.'
                ], 422);
            }

            $manzanaCode = 'M' . $manzanaNumber;

            for ($i = (int)$data['desde']; $i <= (int)$data['hasta']; $i++) {
                $identifiers[] = $manzanaCode . ' L' . $i;
            }
        } else {
            for ($i = (int)$data['desde']; $i <= (int)$data['hasta']; $i++) {
                $identifiers[] = 'SM L' . $i;
            }
        }

        $lotIds = DB::table('lots')
            ->where('development_id', $developmentId)
            ->whereNull('fecha_baja')
            ->where('status_id', $freeStatusId)
            ->whereIn('identificador', $identifiers)
            ->pluck('id')
            ->map(fn ($v) => (int) $v)
            ->all();

        if (empty($lotIds)) {
            return response()->json([
                'message' => 'No se encontraron lotes libres dentro del rango seleccionado.'
            ], 422);
        }

        DB::beginTransaction();

        try {
            DB::table('lots')
                ->whereIn('id', $lotIds)
                ->update([
                    'precio_contado' => $data['precio_contado'],
                    'precio_credito' => $data['precio_credito'],
                    'updated_at' => now(),
                ]);

            DB::table('lot_offices')->whereIn('lot_id', $lotIds)->delete();
            DB::table('lot_partners')->whereIn('lot_id', $lotIds)->delete();

            $officeRows = [];
            foreach ($lotIds as $lotId) {
                foreach (array_unique($data['office_ids'] ?? []) as $officeId) {
                    $officeRows[] = [
                        'lot_id' => $lotId,
                        'office_id' => (int) $officeId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            $partnerRows = [];
            foreach ($lotIds as $lotId) {
                foreach (array_unique($data['partner_ids'] ?? []) as $partnerId) {
                    $partnerRows[] = [
                        'lot_id' => $lotId,
                        'partner_id' => (int) $partnerId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            if (!empty($officeRows)) {
                DB::table('lot_offices')->insert($officeRows);
            }

            if (!empty($partnerRows)) {
                DB::table('lot_partners')->insert($partnerRows);
            }

            DB::commit();

            return response()->json([
                'ok' => true,
                'message' => 'Modificación masiva aplicada a los lotes libres del rango seleccionado.',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    protected function validateLot(Request $request, ?int $lotId = null, ?int $developmentId = null): array
    {
        return Validator::make($request->all(), [
            'identificador' => ['required', 'string', 'max:100'],
            'manzana' => ['nullable', 'string', 'max:50'],
            'precio_contado' => ['required', 'numeric', 'min:0'],
            'precio_credito' => ['required', 'numeric', 'min:0'],
            'office_ids' => ['nullable', 'array'],
            'office_ids.*' => ['integer', 'exists:offices,id'],
            'partner_ids' => ['nullable', 'array'],
            'partner_ids.*' => ['integer', 'exists:partners,id'],
        ])->after(function ($validator) use ($request, $lotId, $developmentId) {
            $exists = DB::table('lots')
                ->where('development_id', $developmentId)
                ->where('identificador', $request->identificador);

            if ($lotId) {
                $exists->where('id', '<>', $lotId);
            }

            if ($exists->exists()) {
                $validator->errors()->add('identificador', 'Ese identificador ya existe en la lotificación.');
            }
        })->validate();
    }

    protected function validateLotUpdate(Request $request): array
    {
        return Validator::make($request->all(), [
            'precio_contado' => ['required', 'numeric', 'min:0'],
            'precio_credito' => ['required', 'numeric', 'min:0'],
            'office_ids' => ['nullable', 'array'],
            'office_ids.*' => ['integer', 'exists:offices,id'],
            'partner_ids' => ['nullable', 'array'],
            'partner_ids.*' => ['integer', 'exists:partners,id'],
        ])->validate();
    }

    protected function getLibreStatusId(): int
    {
        $id = DB::table('statuses as s')
            ->join('processes as p', 'p.id', '=', 's.process_id')
            ->where('p.clave', 'LOT_STATUS')
            ->where('s.clave', 'LIBRE')
            ->value('s.id');

        if (!$id) {
            abort(500, 'No existe el estado LIBRE para LOT_STATUS. Ejecuta el seeder LotStatusSeeder.');
        }

        return (int) $id;
    }

    protected function syncLotOffices(int $lotId, array $officeIds): void
    {
        DB::table('lot_offices')->where('lot_id', $lotId)->delete();

        $rows = collect($officeIds)
            ->filter()
            ->unique()
            ->map(fn ($officeId) => [
                'lot_id' => $lotId,
                'office_id' => (int) $officeId,
                'created_at' => now(),
                'updated_at' => now(),
            ])
            ->values()
            ->all();

        if (!empty($rows)) {
            DB::table('lot_offices')->insert($rows);
        }
    }

    protected function syncLotPartners(int $lotId, array $partnerIds): void
    {
        DB::table('lot_partners')->where('lot_id', $lotId)->delete();

        $rows = collect($partnerIds)
            ->filter()
            ->unique()
            ->map(fn ($partnerId) => [
                'lot_id' => $lotId,
                'partner_id' => (int) $partnerId,
                'created_at' => now(),
                'updated_at' => now(),
            ])
            ->values()
            ->all();

        if (!empty($rows)) {
            DB::table('lot_partners')->insert($rows);
        }
    }
}