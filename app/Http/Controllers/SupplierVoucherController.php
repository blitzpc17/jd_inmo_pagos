<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SupplierVoucherController extends Controller
{
    public function index()
    {
        return view('pagos_proveedores.index');
    }

    public function datatable()
    {
        $rows = DB::table('supplier_vouchers as cv')
            ->join('suppliers as s', 's.id', '=', 'cv.supplier_id')
            ->leftJoin('developments as d', 'd.id', '=', 'cv.development_id')
            ->join('statuses as st', 'st.id', '=', 'cv.status_id')
            ->whereNull('cv.fecha_baja')
            ->select([
                'cv.id',
                'cv.numero_referencia',
                'cv.total',
                'cv.enganche',
                'cv.meses',
                'cv.mensualidad',
                'cv.total_pagado',
                'cv.saldo_pendiente',
                'cv.observacion',
                'cv.fecha_registro',
                's.nombres',
                's.apellidos',
                'd.nombre as lotificacion',
                'st.nombre as estado',
            ])
            ->orderByDesc('cv.id')
            ->get()
            ->map(function ($r) {
                $r->proveedor = trim(($r->nombres ?? '') . ' ' . ($r->apellidos ?? ''));

                $progress = $this->getVoucherProgressStatus((object) [
                    'id' => $r->id,
                    'total' => $r->total,
                    'enganche' => $r->enganche ?? 0,
                    'fecha_registro' => $r->fecha_registro,
                    'meses' => $r->meses,
                    'mensualidad' => $r->mensualidad,
                    'total_pagado' => $r->total_pagado,
                ]);

                $r->estado_pago_texto = $progress['estado_pago'];
                $r->deberia_llevar = $progress['deberia_llevar'];
                $r->diferencia = $progress['diferencia'];
                
                $badge = '<span class="badge bg-success">AL CORRIENTE</span>';
                if ($progress['estado_pago'] === 'ATRASADO') {
                    $badge = '<span class="badge bg-danger">ATRASADO</span>';
                } elseif ($progress['estado_pago'] === 'LIQUIDADO') {
                    $badge = '<span class="badge bg-primary">LIQUIDADO</span>';
                }
                $r->estado_pago_badge = $badge;

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
        $suppliers = DB::table('suppliers as s')
            ->join('statuses as st', 'st.id', '=', 's.status_id')
            ->join('processes as p', 'p.id', '=', 'st.process_id')
            ->where('p.clave', 'GENERAL')
            ->where('st.clave', 'ACTIVE')
            ->whereNull('s.fecha_baja')
            ->orderBy('s.nombres')
            ->get([
                's.id as value',
                's.nombres',
                's.apellidos'
            ])->map(function($r) {
                return [
                    'value' => $r->value,
                    'text' => trim(($r->nombres ?? '') . ' ' . ($r->apellidos ?? ''))
                ];
            });

        $developments = DB::table('developments')
            ->whereNull('fecha_baja')
            ->orderBy('nombre')
            ->get([
                'id as value',
                'nombre as text',
            ]);

        return response()->json([
            'suppliers' => $suppliers,
            'developments' => $developments,
        ]);
    }

    public function store(Request $request)
    {
        $data = Validator::make($request->all(), [
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'development_id' => ['required', 'integer', 'exists:developments,id'],
            'total' => ['required', 'numeric', 'min:0.01'],
            'enganche' => ['required', 'numeric', 'min:0'],
            'num_socios' => ['required', 'integer', 'min:1'],
            'fecha_inicio' => ['required', 'date'],
            'meses' => ['required', 'integer', 'min:1'],
            'observacion' => ['nullable', 'string'],
            'partner_names' => ['nullable', 'array'],
            'partner_names.*' => ['string', 'max:255'],
            'partner_percentages' => ['nullable', 'array'],
            'partner_percentages.*' => ['numeric', 'min:0', 'max:100'],
            'partner_enganches' => ['nullable', 'array'],
            'partner_enganches.*' => ['numeric', 'min:0'],
            'titular_index' => ['nullable', 'integer', 'min:0'],
        ])->validate();

        $statusId = $this->getActiveStatusId();

        $total = round((float) $data['total'], 2);
        $enganche = round((float) $data['enganche'], 2);
        $numSocios = (int) $data['num_socios'];
        
        $partnerData = [];
        if (!empty($data['partner_percentages'])) {
            $sum = array_sum($data['partner_percentages']);
            if (abs($sum - 100) > 0.01 || count($data['partner_percentages']) !== $numSocios) {
                abort(422, 'Los porcentajes de los socios son inválidos o no suman 100.');
            }
            $titularIndex = $data['titular_index'] ?? 0;
            $enganchesTotal = 0;
            foreach ($data['partner_percentages'] as $idx => $pct) {
                $pEnganche = (float)($data['partner_enganches'][$idx] ?? 0);
                $enganchesTotal += $pEnganche;
                $partnerData[] = [
                    'nombre' => $data['partner_names'][$idx] ?? 'Socio ' . ($idx + 1),
                    'porcentaje' => (float)$pct,
                    'es_titular' => ((int)$titularIndex === $idx),
                    'enganche' => $pEnganche,
                ];
            }
            $enganche = round($enganchesTotal, 2);
        } else {
            $partnerData[] = [
                'nombre' => 'Socio Titular',
                'porcentaje' => 100.00,
                'es_titular' => true,
                'enganche' => $enganche,
            ];
        }

        $meses = (int) $data['meses'];
        $mensualidad = round(($total - $enganche) / max(1, $meses), 2);
        
        $fechaInicio = Carbon::parse($data['fecha_inicio']);
        $fechaFin = $fechaInicio->copy()->addMonths($meses);

        DB::beginTransaction();

        try {
            $voucherId = DB::table('supplier_vouchers')->insertGetId([
                'numero_referencia' => '',
                'supplier_id' => $data['supplier_id'],
                'development_id' => $data['development_id'],
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

            DB::table('supplier_vouchers')
                ->where('id', $voucherId)
                ->update([
                    'numero_referencia' => 'BOL-PROV-' . str_pad((string) $voucherId, 6, '0', STR_PAD_LEFT),
                    'updated_at' => now(),
                ]);

            foreach ($partnerData as $pData) {
                DB::table('supplier_voucher_partners')->insert([
                    'supplier_voucher_id' => $voucherId,
                    'nombre' => $pData['nombre'],
                    'porcentaje' => $pData['porcentaje'],
                    'enganche' => $pData['enganche'],
                    'es_titular' => $pData['es_titular'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $schedules = [];
            $totalCapital = (float) $total - (float) $enganche;
            $accumulated = 0;
            for ($i = 1; $i <= $meses; $i++) {
                $dueDate = $fechaInicio->copy()->addMonths($i);
                $isLast = ($i === (int)$meses);
                if ($isLast) {
                    $amount = round($totalCapital - $accumulated, 2);
                } else {
                    $amount = (float) $mensualidad;
                    $accumulated += $amount;
                }
                $schedules[] = [
                    'supplier_voucher_id' => $voucherId,
                    'installment_number' => $i,
                    'due_date' => $dueDate->toDateString(),
                    'amount' => $amount,
                    'amount_paid' => 0,
                    'status' => 'PENDING',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            if (!empty($schedules)) {
                DB::table('supplier_payment_schedules')->insert($schedules);
            }

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

        $row = DB::table('supplier_vouchers as cv')
            ->join('suppliers as s', 's.id', '=', 'cv.supplier_id')
            ->leftJoin('developments as d', 'd.id', '=', 'cv.development_id')
            ->join('statuses as st', 'st.id', '=', 'cv.status_id')
            ->where('cv.id', $id)
            ->select([
                'cv.*',
                's.nombres',
                's.apellidos',
                'd.nombre as lotificacion',
                'st.nombre as estado',
            ])
            ->first();

        abort_if(!$row, 404, 'Boleta no encontrada');

        $items = DB::table('supplier_voucher_items as cvi')
            ->join('payment_methods as pm', 'pm.id', '=', 'cvi.payment_method_id')
            ->leftJoin('users as u', 'u.id', '=', 'cvi.usuario_genero_id')
            ->where('cvi.supplier_voucher_id', $id)
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
                'proveedor' => trim(($row->nombres ?? '') . ' ' . ($row->apellidos ?? '')),
                'lotificacion' => $row->lotificacion,
                'total' => $row->total,
                'enganche' => $row->enganche,
                'num_socios' => $row->num_socios,
                'partners' => DB::table('supplier_voucher_partners')->where('supplier_voucher_id', $id)->orderBy('id')->get(),
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
        $voucher = DB::table('supplier_vouchers')->where('id', $voucherId)->first();
        if (!$voucher) {
            return;
        }

        $totalPagado = (float) DB::table('supplier_voucher_items')
            ->where('supplier_voucher_id', $voucherId)
            ->whereNull('fecha_baja')
            ->sum('cantidad');

        $saldoPendiente = max(0, (float) $voucher->total - (float) $voucher->enganche - $totalPagado);

        DB::table('supplier_vouchers')
            ->where('id', $voucherId)
            ->update([
                'total_pagado' => $totalPagado,
                'saldo_pendiente' => $saldoPendiente,
                'updated_at' => now(),
            ]);
    }

    protected function getVoucherProgressStatus(object $voucher): array
    {
        $fechaInicio = Carbon::parse($voucher->fecha_inicio ?? $voucher->fecha_registro)->startOfDay();
        $hoy = now()->startOfDay();

        $mesesTranscurridos = max(1, $fechaInicio->diffInMonths($hoy) + 1);
        $mesesExigibles = min((int) $voucher->meses, $mesesTranscurridos);

        $deberiaLlevar = round($mesesExigibles * (float) $voucher->mensualidad, 2);
        
        $totalAbonado = (float) DB::table('supplier_voucher_items')
            ->where('supplier_voucher_id', $voucher->id)
            ->whereNull('fecha_baja')
            ->sum('cantidad');
            
        $interesGenerado = (float) DB::table('supplier_voucher_interests')
            ->where('supplier_voucher_id', $voucher->id)
            ->whereNull('fecha_baja')
            ->sum('cantidad');
            
        $capitalTotal = max(0, (float) $voucher->total - (float) $voucher->enganche);
        $capitalPagado = min($totalAbonado, $capitalTotal);
        $excesoParaInteres = max(0, $totalAbonado - $capitalTotal);

        $interesPagado = min($excesoParaInteres, $interesGenerado);
        
        $saldoPendienteCapital = $capitalTotal - $capitalPagado;
        $saldoInteresPendiente = $interesGenerado - $interesPagado;
        
        $diferencia = round($deberiaLlevar - $capitalPagado, 2);

        $estadoPago = 'AL CORRIENTE';
        if ($saldoPendienteCapital <= 0.009 && $saldoInteresPendiente <= 0.009) {
            $estadoPago = 'LIQUIDADO';
        } elseif ($diferencia > 0.009) {
            $estadoPago = 'ATRASADO';
        }

        return [
            'meses_exigibles' => $mesesExigibles,
            'deberia_llevar' => $deberiaLlevar,
            'ha_pagado' => $capitalPagado,
            'diferencia' => max(0, $diferencia),
            'estado_pago' => $estadoPago,
            'interes_acumulado' => $interesGenerado,
            'interes_pagado' => $interesPagado,
            'interes_pendiente' => $saldoInteresPendiente,
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