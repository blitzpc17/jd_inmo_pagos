<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ContractController extends Controller
{
    public function index()
    {
        return view('contratos.index');
    }

    public function datatable()
    {
        $rows = DB::table('contracts as c')
            ->join('clients as cl', 'cl.id', '=', 'c.client_id')
            ->join('developments as d', 'd.id', '=', 'c.development_id')
            ->join('contract_payment_types as cpt', 'cpt.id', '=', 'c.contract_payment_type_id')
            ->join('statuses as s', 's.id', '=', 'c.status_id')
            ->leftJoin('offices as o', 'o.id', '=', 'c.office_id')
            ->leftJoin('sellers as sv', 'sv.id', '=', 'c.seller_id')
            ->leftJoin('personal as sp', 'sp.id', '=', 'sv.personal_id')
            ->select([
                'c.id',
                'c.numero_referencia',
                'c.fecha_emision',
                'c.meses',
                'c.importe',
                'c.monto_pago_inicial',
                'c.saldo_financiado',
                'c.cuota_mensual',
                'c.commission_amount',
                'cl.nombres',
                'cl.apellidos',
                'd.nombre as lotificacion',
                'cpt.nombre as tipo_pago',
                's.clave as estado_clave',
                's.nombre as estado',
                'o.nombre as oficina',
                'sv.clave as vendedor_clave',
                'sp.nombres as vendedor_nombres',
                'sp.apellidos as vendedor_apellidos',
            ])
            ->whereNull('c.fecha_baja')
            ->orderByDesc('c.id')
            ->get()
            ->map(function ($r) {
                $r->cliente = trim(($r->nombres ?? '') . ' ' . ($r->apellidos ?? ''));
                $r->vendedor = trim(($r->vendedor_clave ?? '') . ' - ' . ($r->vendedor_nombres ?? '') . ' ' . ($r->vendedor_apellidos ?? ''));

                $badgeStyle = match ($r->estado_clave) {
                    'VIGENTE' => 'background:#16a34a;color:#fff;',
                    'LIQUIDADO' => 'background:#2563eb;color:#fff;',
                    'CANCELADO' => 'background:#dc2626;color:#fff;',
                    'FINALIZADO' => 'background:#7c3aed;color:#fff;',
                    default => 'background:#6b7280;color:#fff;',
                };

                $r->estado_badge = '<span class="badge rounded-pill px-3 py-2" style="'.$badgeStyle.'">'.$r->estado.'</span>';
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

        $contractPaymentTypes = DB::table('contract_payment_types')
            ->orderBy('nombre')
            ->get(['id as value', 'nombre as text']);

        $offices = DB::table('offices')
            ->whereNull('fecha_baja')
            ->orderBy('nombre')
            ->get(['id as value', 'nombre as text']);

        $sellers = DB::table('sellers as s')
            ->join('personal as p', 'p.id', '=', 's.personal_id')
            ->whereNull('s.fecha_baja')
            ->orderBy('s.clave')
            ->get([
                's.id as value',
                DB::raw("s.clave || ' - ' || p.nombres || ' ' || p.apellidos as text")
            ]);

        $paymentMethods = DB::table('payment_methods')
            ->orderBy('nombre')
            ->get(['id as value', 'nombre as text']);

        return response()->json([
            'clients' => $clients,
            'contract_payment_types' => $contractPaymentTypes,
            'offices' => $offices,
            'sellers' => $sellers,
            'payment_methods' => $paymentMethods,
        ]);
    }

    public function clientReservations(int $clientId)
    {
        $vigenteId = $this->getReservationStatusId('VIGENTE');

        $rows = DB::table('reservations as r')
            ->join('developments as d', 'd.id', '=', 'r.development_id')
            ->where('r.client_id', $clientId)
            ->where('r.status_id', $vigenteId)
            ->whereNull('r.fecha_baja')
            ->orderByDesc('r.id')
            ->get([
                'r.id as value',
                DB::raw("r.numero_referencia || ' - ' || d.nombre as text")
            ]);

        return response()->json($rows);
    }

    public function clientDevelopments(int $clientId)
    {
        $rows = DB::table('developments as d')
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

        return response()->json($rows);
    }

    public function developmentLots(int $developmentId)
    {
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

    public function reservationData(int $reservationId)
    {
        $reservation = DB::table('reservations as r')
            ->join('clients as c', 'c.id', '=', 'r.client_id')
            ->join('developments as d', 'd.id', '=', 'r.development_id')
            ->join('statuses as s', 's.id', '=', 'r.status_id')
            ->where('r.id', $reservationId)
            ->select([
                'r.*',
                'c.nombres',
                'c.apellidos',
                'd.nombre as lotificacion',
                's.clave as estado_clave',
            ])
            ->first();

        abort_if(!$reservation, 404, 'Apartado no encontrado');

        if ($reservation->estado_clave !== 'VIGENTE') {
            return response()->json(['message' => 'Solo se puede formalizar un apartado vigente.'], 422);
        }

        $lots = DB::table('reservation_lots as rl')
            ->join('lots as l', 'l.id', '=', 'rl.lot_id')
            ->where('rl.reservation_id', $reservationId)
            ->orderBy('l.identificador')
            ->get([
                'l.id',
                'l.identificador',
                'l.manzana',
                'l.precio_contado',
                'l.precio_credito',
            ]);

        $sumComplements = DB::table('reservation_complements as rc')
            ->join('charges as c', 'c.id', '=', 'rc.charge_id')
            ->where('rc.reservation_id', $reservationId)
            ->sum('c.monto');

        return response()->json([
            'ok' => true,
            'data' => [
                'reservation_id' => $reservation->id,
                'numero_referencia' => $reservation->numero_referencia,
                'client_id' => $reservation->client_id,
                'client_name' => trim($reservation->nombres . ' ' . $reservation->apellidos),
                'development_id' => $reservation->development_id,
                'development_name' => $reservation->lotificacion,
                'importe_apartado' => (float) $reservation->importe_apartado,
                'complementos_total' => (float) $sumComplements,
                'pagos_previos_total' => (float) $reservation->importe_apartado + (float) $sumComplements,
                'lots' => $lots,
            ]
        ]);
    }

    public function sellerData(int $sellerId)
    {
        $seller = DB::table('sellers')->where('id', $sellerId)->first();
        abort_if(!$seller, 404, 'Vendedor no encontrado');

        return response()->json([
            'ok' => true,
            'data' => [
                'monto_comision' => (float) $seller->monto_comision,
            ]
        ]);
    }

    public function store(Request $request)
    {
        $data = Validator::make($request->all(), [
            'client_id' => ['required', 'integer', 'exists:clients,id'],
            'reservation_id' => ['nullable', 'integer', 'exists:reservations,id'],
            'development_id' => ['required', 'integer', 'exists:developments,id'],
            'lot_ids' => ['required', 'array', 'min:1'],
            'lot_ids.*' => ['integer', 'exists:lots,id'],
            'contract_payment_type_id' => ['required', 'integer', 'exists:contract_payment_types,id'],
            'meses' => ['nullable', 'integer', 'min:0'],
            'importe' => ['required', 'numeric', 'min:0.01'],
            'monto_pago_inicial' => ['required', 'numeric', 'min:0'],
            'saldo_financiado' => ['required', 'numeric', 'min:0'],
            'dia_pago' => ['nullable', 'integer', 'min:1', 'max:31'],
            'cuota_mensual' => ['nullable', 'numeric', 'min:0'],
            'observaciones' => ['nullable', 'string'],
            'office_id' => ['required', 'integer', 'exists:offices,id'],
            'seller_id' => ['required', 'integer', 'exists:sellers,id'],
            'commission_amount' => ['required', 'numeric', 'min:0'],
            'difference_payment_method_id' => ['nullable', 'integer', 'exists:payment_methods,id'],
        ])->after(function ($validator) use ($request) {
            $reservationId = $request->input('reservation_id');
            $pagoInicial = (float) $request->input('monto_pago_inicial', 0);

            if (!$reservationId) {
                return;
            }

            $reservation = DB::table('reservations')->where('id', $reservationId)->first();
            if (!$reservation) {
                return;
            }

            $complementos = DB::table('reservation_complements as rc')
                ->join('charges as c', 'c.id', '=', 'rc.charge_id')
                ->where('rc.reservation_id', $reservationId)
                ->sum('c.monto');

            $pagosPrevios = (float) $reservation->importe_apartado + (float) $complementos;

            if ($pagoInicial > $pagosPrevios && !$request->input('difference_payment_method_id')) {
                $validator->errors()->add('difference_payment_method_id', 'Debes seleccionar forma de pago para el complemento automático.');
            }
        })->validate();

        $paymentType = DB::table('contract_payment_types')->where('id', $data['contract_payment_type_id'])->first();
        abort_if(!$paymentType, 404, 'Tipo de contrato no encontrado');

        $typeName = mb_strtoupper(trim($paymentType->nombre));
        $isCredit = $typeName === 'CRÉDITO' || $typeName === 'CREDITO';
        $isContado = $typeName === 'CONTADO';

        if ($isContado && (int) ($data['meses'] ?? 0) > 1) {
            return response()->json(['message' => 'Si el contrato es de contado, meses debe ser 0 o 1.'], 422);
        }

        if ($isCredit) {
            if ((int) ($data['meses'] ?? 0) <= 0) {
                return response()->json(['message' => 'Si el contrato es a crédito, debe tener plazo.'], 422);
            }
            if ((float) $data['saldo_financiado'] <= 0) {
                return response()->json(['message' => 'Si el contrato es a crédito, debe tener saldo financiado.'], 422);
            }
            if ((float) ($data['cuota_mensual'] ?? 0) <= 0) {
                return response()->json(['message' => 'Si el contrato es a crédito, debe tener cuota mensual.'], 422);
            }
        }

        $lotTargetStatusId = $this->getLotStatusId($isContado ? 'LIQUIDADO' : 'VENDIDO');
        $contractStatusId = $this->getContractStatusId('VIGENTE');
        $chargeStatusId = $this->getChargeStatusId('REGISTRADO');

        $lotIds = collect($data['lot_ids'])->map(fn ($v) => (int) $v)->unique()->values()->all();

        if (!empty($data['reservation_id'])) {
            $lots = DB::table('reservation_lots as rl')
                ->join('lots as l', 'l.id', '=', 'rl.lot_id')
                ->where('rl.reservation_id', $data['reservation_id'])
                ->whereIn('l.id', $lotIds)
                ->get([
                    'l.id',
                    'l.identificador',
                    'l.precio_contado',
                    'l.precio_credito',
                    'l.status_id',
                    'l.development_id',
                ]);

            $reservation = DB::table('reservations')->where('id', $data['reservation_id'])->first();
            abort_if(!$reservation, 404, 'Apartado no encontrado');

            if ((int) $reservation->client_id !== (int) $data['client_id']) {
                return response()->json(['message' => 'El apartado no pertenece al cliente seleccionado.'], 422);
            }

            if ((int) $reservation->development_id !== (int) $data['development_id']) {
                return response()->json(['message' => 'El apartado no pertenece a la lotificación seleccionada.'], 422);
            }

            if ((int) $reservation->status_id !== (int) $this->getReservationStatusId('VIGENTE')) {
                return response()->json(['message' => 'Solo se puede usar un apartado vigente.'], 422);
            }

            if ($lots->count() !== count($lotIds)) {
                return response()->json(['message' => 'Los lotes seleccionados no coinciden con el apartado.'], 422);
            }
        } else {
            $freeLotStatusId = $this->getLotStatusId('LIBRE');

            $lots = DB::table('lots')
                ->whereIn('id', $lotIds)
                ->whereNull('fecha_baja')
                ->where('development_id', $data['development_id'])
                ->where('status_id', $freeLotStatusId)
                ->get([
                    'id',
                    'identificador',
                    'precio_contado',
                    'precio_credito',
                    'status_id',
                    'development_id',
                ]);

            if ($lots->count() !== count($lotIds)) {
                return response()->json(['message' => 'Solo puedes usar lotes libres para contratos directos.'], 422);
            }
        }

        $importeCalculado = $lots->sum(fn ($lot) => $isContado ? (float) $lot->precio_contado : (float) $lot->precio_credito);
        $pagosPrevios = 0.0;

        if (!empty($data['reservation_id'])) {
            $reservation = DB::table('reservations')->where('id', $data['reservation_id'])->first();

            $complementos = DB::table('reservation_complements as rc')
                ->join('charges as c', 'c.id', '=', 'rc.charge_id')
                ->where('rc.reservation_id', $data['reservation_id'])
                ->sum('c.monto');

            $pagosPrevios = (float) $reservation->importe_apartado + (float) $complementos;
        }

        if (abs($importeCalculado - (float) $data['importe']) > 0.01) {
            return response()->json([
                'message' => 'El importe no coincide con la suma de los lotes seleccionados.'
            ], 422);
        }

        $differenceToCreate = 0.0;
        if (!empty($data['reservation_id']) && (float) $data['monto_pago_inicial'] > $pagosPrevios) {
            $differenceToCreate = (float) $data['monto_pago_inicial'] - $pagosPrevios;
        }

        DB::beginTransaction();

        try {
            $contractId = DB::table('contracts')->insertGetId([
                'numero_referencia' => '',
                'fecha_emision' => now()->toDateString(),
                'status_id' => $contractStatusId,
                'client_id' => $data['client_id'],
                'development_id' => $data['development_id'],
                'contract_payment_type_id' => $data['contract_payment_type_id'],
                'meses' => $data['meses'] ?? 0,
                'importe' => $data['importe'],
                'monto_pago_inicial' => $data['monto_pago_inicial'],
                'saldo_financiado' => $data['saldo_financiado'],
                'dia_pago' => $data['dia_pago'] ?? null,
                'cuota_mensual' => $data['cuota_mensual'] ?? 0,
                'observaciones' => $data['observaciones'] ?? null,
                'office_id' => $data['office_id'],
                'seller_id' => $data['seller_id'],
                'commission_amount' => $data['commission_amount'],
                'usuario_genero_id' => session('auth_user.id'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('contracts')
                ->where('id', $contractId)
                ->update([
                    'numero_referencia' => 'CTR-' . str_pad((string) $contractId, 6, '0', STR_PAD_LEFT),
                    'updated_at' => now(),
                ]);

            $lotRows = [];
            foreach ($lots as $lot) {
                $basePrice = $isContado ? (float) $lot->precio_contado : (float) $lot->precio_credito;
                $lotRows[] = [
                    'contract_id' => $contractId,
                    'lot_id' => $lot->id,
                    'sale_price' => $basePrice,
                    'discount' => 0,
                    'subtotal' => $basePrice,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            DB::table('contract_lots')->insert($lotRows);

            if (!empty($data['reservation_id'])) {
                DB::table('contract_reservations')->insert([
                    'contract_id' => $contractId,
                    'reservation_id' => $data['reservation_id'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('reservations')
                    ->where('id', $data['reservation_id'])
                    ->update([
                        'status_id' => $this->getReservationStatusId('APLICADO'),
                        'updated_at' => now(),
                    ]);

                if ($differenceToCreate > 0.009) {
                    $chargeTypeId = DB::table('charge_types')
                        ->where('nombre', 'Complemento de apartado')
                        ->value('id');

                    $paymentMethodId = $data['difference_payment_method_id'] ?? null;

                    if (!$paymentMethodId) {
                        throw new \RuntimeException('No se recibió la forma de pago para el complemento automático.');
                    }

                    $chargeId = DB::table('charges')->insertGetId([
                        'numero_referencia' => '',
                        'fecha_emision' => now()->toDateString(),
                        'charge_type_id' => $chargeTypeId,
                        'payment_method_id' => $paymentMethodId,
                        'client_id' => $data['client_id'],
                        'contract_id' => $contractId,
                        'reservation_id' => $data['reservation_id'],
                        'status_id' => $chargeStatusId,
                        'monto' => $differenceToCreate,
                        'aplica_recargo' => false,
                        'monto_recargo' => 0,
                        'observacion' => 'Complemento automático por diferencia al formalizar contrato.',
                        'office_receives_charge_id' => $data['office_id'],
                        'usuario_genero_id' => session('auth_user.id'),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    DB::table('charges')
                        ->where('id', $chargeId)
                        ->update([
                            'numero_referencia' => 'COB-' . str_pad((string) $chargeId, 6, '0', STR_PAD_LEFT),
                            'updated_at' => now(),
                        ]);

                    DB::table('reservation_complements')->insert([
                        'reservation_id' => $data['reservation_id'],
                        'charge_id' => $chargeId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            DB::table('lots')
                ->whereIn('id', $lotIds)
                ->update([
                    'status_id' => $lotTargetStatusId,
                    'updated_at' => now(),
                ]);

            if ($isCredit && (int) ($data['meses'] ?? 0) > 0) {
                $this->generateSchedule(
                    $contractId,
                    (int) $data['meses'],
                    (float) $data['cuota_mensual'],
                    (int) ($data['dia_pago'] ?? now()->day)
                );
            }

            DB::commit();

            return response()->json([
                'ok' => true,
                'message' => 'Contrato generado correctamente.',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function show(int $id)
    {
        $contract = DB::table('contracts as c')
            ->join('clients as cl', 'cl.id', '=', 'c.client_id')
            ->join('developments as d', 'd.id', '=', 'c.development_id')
            ->join('contract_payment_types as cpt', 'cpt.id', '=', 'c.contract_payment_type_id')
            ->join('statuses as s', 's.id', '=', 'c.status_id')
            ->leftJoin('offices as o', 'o.id', '=', 'c.office_id')
            ->leftJoin('sellers as sv', 'sv.id', '=', 'c.seller_id')
            ->leftJoin('personal as sp', 'sp.id', '=', 'sv.personal_id')
            ->where('c.id', $id)
            ->select([
                'c.*',
                'cl.nombres',
                'cl.apellidos',
                'd.nombre as lotificacion',
                'cpt.nombre as tipo_pago',
                's.nombre as estado_nombre',
                'o.nombre as oficina',
                'sv.clave as vendedor_clave',
                'sp.nombres as vendedor_nombres',
                'sp.apellidos as vendedor_apellidos',
            ])
            ->first();

        abort_if(!$contract, 404, 'Contrato no encontrado');

        $lots = DB::table('contract_lots as cl')
            ->join('lots as l', 'l.id', '=', 'cl.lot_id')
            ->where('cl.contract_id', $id)
            ->orderBy('l.identificador')
            ->get([
                'l.identificador',
                'l.manzana',
                'cl.sale_price',
                'cl.discount',
                'cl.subtotal',
            ]);

        return response()->json([
            'ok' => true,
            'data' => [
                'numero_referencia' => $contract->numero_referencia,
                'fecha_emision' => $contract->fecha_emision,
                'cliente' => trim($contract->nombres . ' ' . $contract->apellidos),
                'lotificacion' => $contract->lotificacion,
                'tipo_pago' => $contract->tipo_pago,
                'estado' => $contract->estado_nombre,
                'oficina' => $contract->oficina,
                'meses' => $contract->meses,
                'importe' => $contract->importe,
                'monto_pago_inicial' => $contract->monto_pago_inicial,
                'saldo_financiado' => $contract->saldo_financiado,
                'dia_pago' => $contract->dia_pago,
                'cuota_mensual' => $contract->cuota_mensual,
                'observaciones' => $contract->observaciones,
                'vendedor' => trim(($contract->vendedor_clave ?? '') . ' - ' . ($contract->vendedor_nombres ?? '') . ' ' . ($contract->vendedor_apellidos ?? '')),
                'comision' => $contract->commission_amount,
                'lots' => $lots,
            ]
        ]);
    }

    protected function generateSchedule(int $contractId, int $months, float $monthlyAmount, int $dayOfMonth): void
    {
        $baseDate = now()->copy();

        for ($i = 1; $i <= $months; $i++) {
            $due = $baseDate->copy()->addMonthsNoOverflow($i);
            $lastDay = $due->copy()->endOfMonth()->day;
            $safeDay = min($dayOfMonth, $lastDay);
            $due->day($safeDay);

            DB::table('payment_schedules')->insert([
                'contract_id' => $contractId,
                'installment_number' => $i,
                'due_date' => $due->toDateString(),
                'amount' => $monthlyAmount,
                'amount_paid' => 0,
                'late_fee_amount' => 0,
                'late_fee_applied' => false,
                'status' => 'PENDIENTE',
                'charge_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
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

    protected function getContractStatusId(string $clave): int
    {
        $id = DB::table('statuses as s')
            ->join('processes as p', 'p.id', '=', 's.process_id')
            ->where('p.clave', 'CONTRACT_STATUS')
            ->where('s.clave', $clave)
            ->value('s.id');

        if (!$id) {
            abort(500, "No existe el estado {$clave} para CONTRACT_STATUS.");
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

    protected function getChargeStatusId(string $clave): int
    {
        $id = DB::table('statuses as s')
            ->join('processes as p', 'p.id', '=', 's.process_id')
            ->where('p.clave', 'CHARGE_STATUS')
            ->where('s.clave', $clave)
            ->value('s.id');

        if (!$id) {
            abort(500, "No existe el estado {$clave} para CHARGE_STATUS.");
        }

        return (int) $id;
    }
}