<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ReservationController extends Controller
{
    public function index()
    {
        return view('apartados.index');
    }

    public function datatable()
    {
        $this->expireOverdueReservations();

        $rows = DB::table('reservations as r')
            ->join('clients as c', 'c.id', '=', 'r.client_id')
            ->join('developments as d', 'd.id', '=', 'r.development_id')
            ->join('statuses as s', 's.id', '=', 'r.status_id')
            ->whereNull('r.fecha_baja')
            ->select([
                'r.id',
                'r.numero_referencia',
                'r.fecha_emision',
                'r.fecha_vencimiento',
                'r.importe_apartado',
                'r.observaciones',
                'c.nombres',
                'c.apellidos',
                'd.nombre as lotificacion',
                's.clave as estado_clave',
                's.nombre as estado',
            ])
            ->orderByDesc('r.id')
            ->get()
            ->map(function ($r) {
                $badgeStyle = match ($r->estado_clave) {
                    'VIGENTE' => 'background:#16a34a;color:#fff;',
                    'VENCIDO' => 'background:#dc2626;color:#fff;',
                    'APLICADO' => 'background:#2563eb;color:#fff;',
                    'SALDO_FAVOR' => 'background:#7c3aed;color:#fff;',
                    'DEVOLUCION' => 'background:#f59e0b;color:#fff;',
                    default => 'background:#6b7280;color:#fff;',
                };

                $r->cliente = trim(($r->nombres ?? '') . ' ' . ($r->apellidos ?? ''));
                $r->estado_badge = '<span class="badge rounded-pill px-3 py-2" style="'.$badgeStyle.'">'.$r->estado.'</span>';

                $buttons = '
                    <button class="btn btn-sm btn-outline-info btn-view" data-id="'.$r->id.'">
                        <i class="fa-solid fa-eye"></i>
                    </button>
                ';

                if ($r->estado_clave === 'VIGENTE') {
                    $buttons .= '
                        <button class="btn btn-sm btn-outline-danger btn-delete" data-id="'.$r->id.'">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    ';
                }

                if ($r->estado_clave === 'VENCIDO') {
                    $buttons .= '
                        <button class="btn btn-sm btn-outline-warning btn-close-status" data-id="'.$r->id.'">
                            <i class="fa-solid fa-arrow-right-arrow-left"></i>
                        </button>
                    ';
                }

                $r->acciones = '<div class="d-flex gap-1">'.$buttons.'</div>';
                return $r;
            });

        return response()->json(['data' => $rows]);
    }

    public function options()
    {
        $this->expireOverdueReservations();

        $clients = DB::table('clients as c')
            ->join('statuses as s', 's.id', '=', 'c.status_id')
            ->join('processes as p', 'p.id', '=', 's.process_id')
            ->where('p.clave', 'GENERAL')
            ->where('s.clave', 'ACTIVE')
            ->whereNull('c.fecha_baja')
            ->orderBy('c.nombres')
            ->get([
                'c.id as value',
                DB::raw("c.nombres || ' ' || c.apellidos as text")
            ]);

        $developments = DB::table('developments as d')
            ->join('statuses as s', 's.id', '=', 'd.status_id')
            ->join('processes as p', 'p.id', '=', 's.process_id')
            ->where('p.clave', 'GENERAL')
            ->where('s.clave', 'ACTIVE')
            ->whereNull('d.fecha_baja')
            ->orderBy('d.nombre')
            ->get([
                'd.id as value',
                'd.nombre as text'
            ]);

        return response()->json([
            'clients' => $clients,
            'developments' => $developments,
        ]);
    }

    public function developmentLots(int $developmentId)
    {
        $this->expireOverdueReservations();

        $freeStatusId = $this->getLotStatusId('LIBRE');

        $lots = DB::table('lots')
            ->where('development_id', $developmentId)
            ->whereNull('fecha_baja')
            ->where('status_id', $freeStatusId)
            ->orderByRaw("
                CASE
                    WHEN manzana IS NULL OR manzana = '' THEN 999999
                    ELSE CAST(regexp_replace(manzana, '[^0-9]', '', 'g') AS INTEGER)
                END ASC
            ")
            ->orderBy('identificador')
            ->get([
                'id as value',
                'identificador as text',
                'precio_contado',
                'precio_credito',
                'manzana',
            ]);

        return response()->json($lots);
    }

    public function show(int $id)
    {
        $this->expireOverdueReservations();

        $row = DB::table('reservations as r')
            ->join('clients as c', 'c.id', '=', 'r.client_id')
            ->join('developments as d', 'd.id', '=', 'r.development_id')
            ->join('statuses as s', 's.id', '=', 'r.status_id')
            ->where('r.id', $id)
            ->select([
                'r.*',
                'c.nombres',
                'c.apellidos',
                'd.nombre as lotificacion',
                's.nombre as estado_nombre',
                's.clave as estado_clave',
            ])
            ->first();

        abort_if(!$row, 404, 'Apartado no encontrado');

        $lots = DB::table('reservation_lots as rl')
            ->join('lots as l', 'l.id', '=', 'rl.lot_id')
            ->where('rl.reservation_id', $id)
            ->orderBy('l.identificador')
            ->get([
                'l.id',
                'l.identificador',
                'l.manzana',
                'l.precio_contado',
                'l.precio_credito',
            ]);

        return response()->json([
            'ok' => true,
            'data' => [
                'id' => $row->id,
                'numero_referencia' => $row->numero_referencia,
                'fecha_emision' => $row->fecha_emision,
                'fecha_vencimiento' => $row->fecha_vencimiento,
                'importe_apartado' => $row->importe_apartado,
                'observaciones' => $row->observaciones,
                'cliente' => trim($row->nombres . ' ' . $row->apellidos),
                'lotificacion' => $row->lotificacion,
                'estado_nombre' => $row->estado_nombre,
                'estado_clave' => $row->estado_clave,
                'lots' => $lots,
            ]
        ]);
    }

    public function store(Request $request)
    {
        $this->expireOverdueReservations();

        $data = Validator::make($request->all(), [
            'client_id' => ['required', 'integer', 'exists:clients,id'],
            'development_id' => ['required', 'integer', 'exists:developments,id'],
            'lot_ids' => ['required', 'array', 'min:1'],
            'lot_ids.*' => ['integer', 'exists:lots,id'],
            'importe_apartado' => ['required', 'numeric', 'min:0.01'],
            'observaciones' => ['nullable', 'string'],
        ], [
            'lot_ids.required' => 'Debes seleccionar al menos un lote.',
        ])->validate();

        $vigenteStatusId = $this->getReservationStatusId('VIGENTE');
        $apartadoLotStatusId = $this->getLotStatusId('APARTADO');
        $freeLotStatusId = $this->getLotStatusId('LIBRE');

        $lotIds = collect($data['lot_ids'])->map(fn ($v) => (int)$v)->unique()->values()->all();

        $lots = DB::table('lots')
            ->whereIn('id', $lotIds)
            ->whereNull('fecha_baja')
            ->get();

        if ($lots->count() !== count($lotIds)) {
            return response()->json(['message' => 'Uno o más lotes no existen o están dados de baja.'], 422);
        }

        $invalidDevelopment = $lots->first(fn ($l) => (int)$l->development_id !== (int)$data['development_id']);
        if ($invalidDevelopment) {
            return response()->json(['message' => 'Todos los lotes deben pertenecer a la lotificación seleccionada.'], 422);
        }

        $notFree = $lots->first(fn ($l) => (int)$l->status_id !== (int)$freeLotStatusId);
        if ($notFree) {
            return response()->json(['message' => 'Solo puedes apartar lotes en estado libre.'], 422);
        }

        DB::beginTransaction();

        try {
            $reservationId = DB::table('reservations')->insertGetId([
                'numero_referencia' => '',
                'client_id' => $data['client_id'],
                'development_id' => $data['development_id'],
                'fecha_emision' => now()->toDateString(),
                'fecha_vencimiento' => now()->addDays(15)->toDateString(),
                'importe_apartado' => $data['importe_apartado'],
                'status_id' => $vigenteStatusId,
                'observaciones' => $data['observaciones'] ?? null,
                'usuario_genero_id' => session('auth_user.id'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('reservations')
                ->where('id', $reservationId)
                ->update([
                    'numero_referencia' => 'AP-' . str_pad((string) $reservationId, 6, '0', STR_PAD_LEFT),
                    'updated_at' => now(),
                ]);

            $pivotRows = [];
            foreach ($lotIds as $lotId) {
                $pivotRows[] = [
                    'reservation_id' => $reservationId,
                    'lot_id' => $lotId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            DB::table('reservation_lots')->insert($pivotRows);

            DB::table('lots')
                ->whereIn('id', $lotIds)
                ->update([
                    'status_id' => $apartadoLotStatusId,
                    'updated_at' => now(),
                ]);

            DB::commit();

            return response()->json([
                'ok' => true,
                'message' => 'Apartado creado correctamente.',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function destroy(int $id)
    {
        $this->expireOverdueReservations();

        $reservation = DB::table('reservations as r')
            ->join('statuses as s', 's.id', '=', 'r.status_id')
            ->where('r.id', $id)
            ->whereNull('r.fecha_baja')
            ->select(['r.*', 's.clave as estado_clave'])
            ->first();

        abort_if(!$reservation, 404, 'Apartado no encontrado');

        if ($reservation->estado_clave !== 'VIGENTE') {
            return response()->json(['message' => 'Solo se puede cancelar un apartado vigente.'], 422);
        }

        $freeLotStatusId = $this->getLotStatusId('LIBRE');
        $vencidoStatusId = $this->getReservationStatusId('VENCIDO');

        $lotIds = DB::table('reservation_lots')
            ->where('reservation_id', $id)
            ->pluck('lot_id')
            ->map(fn ($v) => (int)$v)
            ->all();

        DB::beginTransaction();

        try {
            DB::table('reservations')
                ->where('id', $id)
                ->update([
                    'status_id' => $vencidoStatusId,
                    'fecha_baja' => now(),
                    'usuario_baja_id' => session('auth_user.id'),
                    'updated_at' => now(),
                ]);

            if (!empty($lotIds)) {
                DB::table('lots')
                    ->whereIn('id', $lotIds)
                    ->update([
                        'status_id' => $freeLotStatusId,
                        'updated_at' => now(),
                    ]);
            }

            DB::commit();

            return response()->json([
                'ok' => true,
                'message' => 'Apartado cancelado y lotes liberados correctamente.',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function closeStatus(Request $request, int $id)
    {
        $this->expireOverdueReservations();

        $data = Validator::make($request->all(), [
            'target_status' => ['required', 'string'],
        ])->validate();

        $reservation = DB::table('reservations as r')
            ->join('statuses as s', 's.id', '=', 'r.status_id')
            ->where('r.id', $id)
            ->whereNull('r.fecha_baja')
            ->select(['r.id', 'r.status_id', 's.clave as estado_clave'])
            ->first();

        abort_if(!$reservation, 404, 'Apartado no encontrado');

        if ($reservation->estado_clave !== 'VENCIDO') {
            return response()->json([
                'message' => 'Solo los apartados vencidos pueden pasar a saldo a favor o devolución.'
            ], 422);
        }

        $target = strtoupper(trim($data['target_status']));
        if (!in_array($target, ['SALDO_FAVOR', 'DEVOLUCION'], true)) {
            return response()->json([
                'message' => 'Estado destino inválido.'
            ], 422);
        }

        $targetStatusId = $this->getReservationStatusId($target);

        DB::table('reservations')
            ->where('id', $id)
            ->update([
                'status_id' => $targetStatusId,
                'updated_at' => now(),
            ]);

        return response()->json([
            'ok' => true,
            'message' => 'Estado del apartado actualizado correctamente.',
        ]);
    }

    protected function expireOverdueReservations(): void
    {
        $vigenteStatusId = $this->getReservationStatusId('VIGENTE');
        $vencidoStatusId = $this->getReservationStatusId('VENCIDO');
        $freeLotStatusId = $this->getLotStatusId('LIBRE');

        $expiredReservations = DB::table('reservations')
            ->where('status_id', $vigenteStatusId)
            ->whereDate('fecha_vencimiento', '<', now()->toDateString())
            ->whereNull('fecha_baja')
            ->get(['id']);

        if ($expiredReservations->isEmpty()) {
            return;
        }

        DB::beginTransaction();

        try {
            foreach ($expiredReservations as $reservation) {
                DB::table('reservations')
                    ->where('id', $reservation->id)
                    ->update([
                        'status_id' => $vencidoStatusId,
                        'updated_at' => now(),
                    ]);

                $lotIds = DB::table('reservation_lots')
                    ->where('reservation_id', $reservation->id)
                    ->pluck('lot_id')
                    ->map(fn ($v) => (int)$v)
                    ->all();

                if (!empty($lotIds)) {
                    DB::table('lots')
                        ->whereIn('id', $lotIds)
                        ->update([
                            'status_id' => $freeLotStatusId,
                            'updated_at' => now(),
                        ]);
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    protected function getReservationStatusId(string $clave): int
    {
        $id = DB::table('statuses as s')
            ->join('processes as p', 'p.id', '=', 's.process_id')
            ->where('p.clave', 'RESERVATION_STATUS')
            ->where('s.clave', $clave)
            ->value('s.id');

        if (!$id) {
            abort(500, "No existe el estado {$clave} para RESERVATION_STATUS.");
        }

        return (int) $id;
    }

    protected function getLotStatusId(string $clave): int
    {
        $id = DB::table('statuses as s')
            ->join('processes as p', 'p.id', '=', 's.process_id')
            ->where('p.clave', 'LOT_STATUS')
            ->where('s.clave', $clave)
            ->value('s.id');

        if (!$id) {
            abort(500, "No existe el estado {$clave} para LOT_STATUS.");
        }

        return (int) $id;
    }
}