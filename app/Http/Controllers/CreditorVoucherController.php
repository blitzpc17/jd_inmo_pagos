<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CreditorVoucherController extends Controller
{
    public function index()
    {
        return view('pagos_acreedores.index');
    }

    public function datatable()
    {
        $rows = DB::table('creditor_vouchers as cv')
            ->join('creditors as c', 'c.id', '=', 'cv.creditor_id')
            ->join('statuses as s', 's.id', '=', 'cv.status_id')
            ->whereNull('cv.fecha_baja')
            ->select([
                'cv.id',
                'cv.numero_referencia',
                'cv.total',
                'cv.meses',
                'cv.mensualidad',
                'cv.total_pagado',
                'cv.saldo_pendiente',
                'cv.observacion',
                'cv.fecha_registro',
                'c.nombres',
                'c.apellidos',
                's.nombre as estado',
            ])
            ->orderByDesc('cv.id')
            ->get()
            ->map(function ($r) {
                $r->acreedor = trim(($r->nombres ?? '') . ' ' . ($r->apellidos ?? ''));

                $progress = $this->getVoucherProgressStatus((object) [
                    'fecha_registro' => $r->fecha_registro,
                    'meses' => $r->meses,
                    'mensualidad' => $r->mensualidad,
                    'total_pagado' => $r->total_pagado,
                ]);

                $r->estado_pago_texto = $progress['estado_pago'];
                $r->deberia_llevar = $progress['deberia_llevar'];
                $r->diferencia = $progress['diferencia'];
                $r->estado_pago_badge = $progress['estado_pago'] === 'ATRASADO'
                    ? '<span class="badge bg-danger">ATRASADO</span>'
                    : '<span class="badge bg-success">AL CORRIENTE</span>';

                $r->acciones = '
                    <button class="btn btn-sm btn-outline-info btn-view" data-id="'.$r->id.'">
                        <i class="fa-solid fa-eye"></i>
                    </button>
                ';

                return $r;
            });

        return response()->json(['data' => $rows]);
    }

    public function options()
    {
        $creditors = DB::table('creditors as c')
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

        return response()->json([
            'creditors' => $creditors,
        ]);
    }

    public function store(Request $request)
    {
        $data = Validator::make($request->all(), [
            'creditor_id' => ['required', 'integer', 'exists:creditors,id'],
            'total' => ['required', 'numeric', 'min:0.01'],
            'enganche' => ['required', 'numeric', 'min:0'],
            'num_socios' => ['required', 'integer', 'min:1'],
            'fecha_inicio' => ['required', 'date'],
            'meses' => ['required', 'integer', 'min:1'],
            'observacion' => ['nullable', 'string'],
        ])->validate();

        $statusId = $this->getActiveStatusId();

        $total = round((float) $data['total'], 2);
        $enganche = round((float) $data['enganche'], 2);
        $numSocios = (int) $data['num_socios'];
        $meses = (int) $data['meses'];
        $mensualidad = round(($total - $enganche) / max(1, $meses), 2);
        
        $fechaInicio = Carbon::parse($data['fecha_inicio']);
        $fechaFin = $fechaInicio->copy()->addMonths($meses);

        DB::beginTransaction();

        try {
            $voucherId = DB::table('creditor_vouchers')->insertGetId([
                'numero_referencia' => '',
                'creditor_id' => $data['creditor_id'],
                'total' => $total,
                'enganche' => $enganche,
                'num_socios' => $numSocios,
                'fecha_inicio' => $fechaInicio->toDateString(),
                'fecha_fin' => $fechaFin->toDateString(),
                'meses' => $meses,
                'mensualidad' => $mensualidad,
                'total_pagado' => 0,
                'saldo_pendiente' => max(0, $total - $enganche),
                'status_id' => $statusId,
                'observacion' => $data['observacion'] ?? null,
                'fecha_registro' => now(),
                'usuario_genero_id' => session('auth_user.id'),
                'updated_at' => now(),
                'created_at' => now(),
            ]);

            DB::table('creditor_vouchers')
                ->where('id', $voucherId)
                ->update([
                    'numero_referencia' => 'BOL-ACR-' . str_pad((string) $voucherId, 6, '0', STR_PAD_LEFT),
                    'updated_at' => now(),
                ]);

            DB::commit();

            return response()->json([
                'ok' => true,
                'message' => 'Boleta de acreedor registrada correctamente.'
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function show(int $id)
    {
        $this->recalculateVoucherTotals($id);

        $row = DB::table('creditor_vouchers as cv')
            ->join('creditors as c', 'c.id', '=', 'cv.creditor_id')
            ->join('statuses as s', 's.id', '=', 'cv.status_id')
            ->where('cv.id', $id)
            ->select([
                'cv.*',
                'c.nombres',
                'c.apellidos',
                's.nombre as estado',
            ])
            ->first();

        abort_if(!$row, 404, 'Boleta no encontrada');

        $items = DB::table('creditor_voucher_items as cvi')
            ->join('payment_methods as pm', 'pm.id', '=', 'cvi.payment_method_id')
            ->leftJoin('users as u', 'u.id', '=', 'cvi.usuario_genero_id')
            ->where('cvi.creditor_voucher_id', $id)
            ->whereNull('cvi.fecha_baja')
            ->orderBy('cvi.id')
            ->get([
                'cvi.fecha_recibido',
                'pm.nombre as forma_pago',
                'cvi.cantidad',
                'u.alias as usuario_registro',
            ]);

        $progress = $this->getVoucherProgressStatus($row);

        return response()->json([
            'ok' => true,
            'data' => [
                'id' => $row->id,
                'numero_referencia' => $row->numero_referencia,
                'acreedor' => trim(($row->nombres ?? '') . ' ' . ($row->apellidos ?? '')),
                'total' => $row->total,
                'enganche' => $row->enganche,
                'num_socios' => $row->num_socios,
                'fecha_inicio' => $row->fecha_inicio,
                'fecha_fin' => $row->fecha_fin,
                'meses' => $row->meses,
                'mensualidad' => $row->mensualidad,
                'total_pagado' => $row->total_pagado,
                'saldo_pendiente' => $row->saldo_pendiente,
                'estado' => $row->estado,
                'observacion' => $row->observacion,
                'deberia_llevar' => $progress['deberia_llevar'],
                'meses_exigibles' => $progress['meses_exigibles'],
                'diferencia' => $progress['diferencia'],
                'estado_pago' => $progress['estado_pago'],
                'items' => $items,
            ]
        ]);
    }

    protected function recalculateVoucherTotals(int $voucherId): void
    {
        $voucher = DB::table('creditor_vouchers')->where('id', $voucherId)->first();
        if (!$voucher) {
            return;
        }

        $totalPagado = (float) DB::table('creditor_voucher_items')
            ->where('creditor_voucher_id', $voucherId)
            ->whereNull('fecha_baja')
            ->sum('cantidad');

        $saldoPendiente = max(0, (float) $voucher->total - (float) $voucher->enganche - $totalPagado);

        DB::table('creditor_vouchers')
            ->where('id', $voucherId)
            ->update([
                'total_pagado' => $totalPagado,
                'saldo_pendiente' => $saldoPendiente,
                'updated_at' => now(),
            ]);
    }

    protected function getVoucherProgressStatus(object $voucher): array
    {
        $fechaInicio = Carbon::parse($voucher->fecha_registro)->startOfDay();
        $hoy = now()->startOfDay();

        $mesesTranscurridos = max(1, $fechaInicio->diffInMonths($hoy) + 1);
        $mesesExigibles = min((int) $voucher->meses, $mesesTranscurridos);

        $deberiaLlevar = round($mesesExigibles * (float) $voucher->mensualidad, 2);
        $haPagado = (float) $voucher->total_pagado;
        $diferencia = round($deberiaLlevar - $haPagado, 2);

        $estadoPago = $diferencia > 0.009 ? 'ATRASADO' : 'AL CORRIENTE';

        return [
            'meses_exigibles' => $mesesExigibles,
            'deberia_llevar' => $deberiaLlevar,
            'ha_pagado' => $haPagado,
            'diferencia' => max(0, $diferencia),
            'estado_pago' => $estadoPago,
        ];
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