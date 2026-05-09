<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ChargeController extends Controller
{
    public function index()
    {
        return view('cobros.index');
    }

    public function datatable()
    {
        $this->refreshSchedulesStatus();

        $rows = DB::table('charges as c')
            ->join('charge_types as ct', 'ct.id', '=', 'c.charge_type_id')
            ->join('payment_methods as pm', 'pm.id', '=', 'c.payment_method_id')
            ->join('clients as cl', 'cl.id', '=', 'c.client_id')
            ->join('statuses as s', 's.id', '=', 'c.status_id')
            ->leftJoin('contracts as co', 'co.id', '=', 'c.contract_id')
            ->leftJoin('reservations as r', 'r.id', '=', 'c.reservation_id')
            ->whereNull('c.fecha_baja')
            ->select([
                'c.id',
                'c.numero_referencia',
                'c.fecha_emision',
                'c.monto',
                'c.aplica_recargo',
                'c.monto_recargo',
                'c.observacion',
                'ct.nombre as tipo_cobro',
                'pm.nombre as forma_pago',
                'cl.nombres',
                'cl.apellidos',
                's.nombre as estado',
                'co.numero_referencia as contrato_ref',
                'r.numero_referencia as apartado_ref',
            ])
            ->orderByDesc('c.id')
            ->get()
            ->map(function ($r) {
                $r->cliente = trim(($r->nombres ?? '') . ' ' . ($r->apellidos ?? ''));
                $r->referencia_origen = $r->contrato_ref ?: $r->apartado_ref ?: '';
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

        $contracts = DB::table('contracts as c')
            ->join('clients as cl', 'cl.id', '=', 'c.client_id')
            ->join('statuses as s', 's.id', '=', 'c.status_id')
            ->join('processes as p', 'p.id', '=', 's.process_id')
            ->where('p.clave', 'CONTRACT_STATUS')
            ->where('s.clave', 'VIGENTE')
            ->whereNull('c.fecha_baja')
            ->orderByDesc('c.id')
            ->get([
                'c.id as value',
                DB::raw("c.numero_referencia || ' - ' || cl.nombres || ' ' || cl.apellidos as text")
            ]);

        $paymentMethods = DB::table('payment_methods')
            ->orderBy('nombre')
            ->get(['id as value', 'nombre as text']);

        $chargeTypes = DB::table('charge_types')
            ->orderBy('nombre')
            ->get(['id as value', 'nombre as text']);

        return response()->json([
            'clients' => $clients,
            'contracts' => $contracts,
            'payment_methods' => $paymentMethods,
            'charge_types' => $chargeTypes,
        ]);
    }

    public function contractSummary(int $contractId)
    {
        $this->refreshSchedulesStatus();

        $contract = DB::table('contracts as c')
            ->join('clients as cl', 'cl.id', '=', 'c.client_id')
            ->join('contract_payment_types as cpt', 'cpt.id', '=', 'c.contract_payment_type_id')
            ->where('c.id', $contractId)
            ->select([
                'c.*',
                'cl.nombres',
                'cl.apellidos',
                'cpt.nombre as tipo_pago',
            ])
            ->first();

        abort_if(!$contract, 404, 'Contrato no encontrado');

        $schedules = DB::table('payment_schedules')
            ->where('contract_id', $contractId)
            ->orderBy('installment_number')
            ->get();

        $paid = DB::table('charges')
            ->where('contract_id', $contractId)
            ->whereNull('fecha_baja')
            ->sum(DB::raw('COALESCE(monto,0) + COALESCE(monto_recargo,0)'));

        return response()->json([
            'ok' => true,
            'data' => [
                'contract_id' => $contract->id,
                'numero_referencia' => $contract->numero_referencia,
                'cliente' => trim($contract->nombres . ' ' . $contract->apellidos),
                'tipo_pago' => $contract->tipo_pago,
                'importe' => (float) $contract->importe,
                'monto_pago_inicial' => (float) $contract->monto_pago_inicial,
                'saldo_financiado' => (float) $contract->saldo_financiado,
                'cuota_mensual' => (float) $contract->cuota_mensual,
                'pagado_total' => (float) $paid,
                'schedules' => $schedules,
            ]
        ]);
    }

    public function store(Request $request)
    {
        $this->refreshSchedulesStatus();

        $data = Validator::make($request->all(), [
            'client_id' => ['required', 'integer', 'exists:clients,id'],
            'contract_id' => ['nullable', 'integer', 'exists:contracts,id'],
            'reservation_id' => ['nullable', 'integer', 'exists:reservations,id'],
            'charge_type_id' => ['required', 'integer', 'exists:charge_types,id'],
            'payment_method_id' => ['required', 'integer', 'exists:payment_methods,id'],
            'monto' => ['required', 'numeric', 'min:0.01'],
            'observacion' => ['nullable', 'string'],
        ])->validate();

        if (empty($data['contract_id']) && empty($data['reservation_id'])) {
            return response()->json([
                'message' => 'El cobro debe relacionarse a un contrato o a un apartado.'
            ], 422);
        }

        $chargeType = DB::table('charge_types')->where('id', $data['charge_type_id'])->first();
        abort_if(!$chargeType, 404, 'Tipo de cobro no encontrado');

        $chargeStatusId = $this->getChargeStatusId('REGISTRADO');

        DB::beginTransaction();

        try {
            $lateFee = 0;
            $applyLateFee = false;

            if (!empty($data['contract_id'])) {
                $contract = DB::table('contracts')->where('id', $data['contract_id'])->first();
                abort_if(!$contract, 404, 'Contrato no encontrado');

                $typeName = mb_strtoupper(trim($chargeType->nombre));

                if ($typeName === 'MENSUALIDAD') {
                    $lateInfo = $this->detectLateFee($contract->id, (float) $contract->cuota_mensual);
                    $applyLateFee = $lateInfo['apply'];
                    $lateFee = $lateInfo['amount'];
                }

                $chargeId = DB::table('charges')->insertGetId([
                    'numero_referencia' => '',
                    'fecha_emision' => now()->toDateString(),
                    'charge_type_id' => $data['charge_type_id'],
                    'payment_method_id' => $data['payment_method_id'],
                    'client_id' => $data['client_id'],
                    'contract_id' => $data['contract_id'] ?? null,
                    'reservation_id' => $data['reservation_id'] ?? null,
                    'status_id' => $chargeStatusId,
                    'monto' => $data['monto'],
                    'aplica_recargo' => $applyLateFee,
                    'monto_recargo' => $lateFee,
                    'observacion' => $data['observacion'] ?? null,
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

                $this->applyChargeToSchedules($contract->id, $chargeId, (float) $data['monto']);
                $this->closeContractIfPaid($contract->id);
            } else {
                $chargeId = DB::table('charges')->insertGetId([
                    'numero_referencia' => '',
                    'fecha_emision' => now()->toDateString(),
                    'charge_type_id' => $data['charge_type_id'],
                    'payment_method_id' => $data['payment_method_id'],
                    'client_id' => $data['client_id'],
                    'contract_id' => null,
                    'reservation_id' => $data['reservation_id'] ?? null,
                    'status_id' => $chargeStatusId,
                    'monto' => $data['monto'],
                    'aplica_recargo' => false,
                    'monto_recargo' => 0,
                    'observacion' => $data['observacion'] ?? null,
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
            }

            DB::commit();

            return response()->json([
                'ok' => true,
                'message' => 'Cobro registrado correctamente.',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    protected function refreshSchedulesStatus(): void
    {
        $rows = DB::table('payment_schedules')->get();

        foreach ($rows as $row) {
            $newStatus = $row->status;

            if ((float) $row->amount_paid >= (float) $row->amount && (float) $row->amount > 0) {
                $newStatus = 'PAGADO';
            } elseif ((float) $row->amount_paid > 0 && (float) $row->amount_paid < (float) $row->amount) {
                $newStatus = 'PARCIAL';
            } elseif (Carbon::parse($row->due_date)->lt(now()->startOfDay())) {
                $newStatus = 'VENCIDO';
            } else {
                $newStatus = 'PENDIENTE';
            }

            if ($newStatus !== $row->status) {
                DB::table('payment_schedules')
                    ->where('id', $row->id)
                    ->update([
                        'status' => $newStatus,
                        'updated_at' => now(),
                    ]);
            }
        }
    }

    protected function detectLateFee(int $contractId, float $monthlyAmount): array
    {
        $schedules = DB::table('payment_schedules')
            ->where('contract_id', $contractId)
            ->orderBy('installment_number')
            ->get();

        $lateCount = $schedules->filter(function ($row) {
            return in_array($row->status, ['VENCIDO', 'PARCIAL'], true);
        })->count();

        return [
            'apply' => $lateCount >= 3,
            'amount' => $lateCount >= 3 ? round($monthlyAmount * 0.10, 2) : 0,
        ];
    }

    protected function applyChargeToSchedules(int $contractId, int $chargeId, float $amount): void
    {
        $remaining = $amount;

        $schedules = DB::table('payment_schedules')
            ->where('contract_id', $contractId)
            ->orderByRaw("
                CASE status
                    WHEN 'VENCIDO' THEN 1
                    WHEN 'PARCIAL' THEN 2
                    WHEN 'PENDIENTE' THEN 3
                    WHEN 'ADELANTADO' THEN 4
                    WHEN 'PAGADO' THEN 5
                    ELSE 99
                END
            ")
            ->orderBy('installment_number')
            ->get();

        foreach ($schedules as $schedule) {
            if ($remaining <= 0) break;

            $pending = max(0, (float) $schedule->amount - (float) $schedule->amount_paid);
            if ($pending <= 0) continue;

            $toApply = min($remaining, $pending);
            $newPaid = (float) $schedule->amount_paid + $toApply;

            $newStatus = 'PARCIAL';
            if ($newPaid >= (float) $schedule->amount) {
                $newStatus = 'PAGADO';
            }

            DB::table('payment_schedules')
                ->where('id', $schedule->id)
                ->update([
                    'amount_paid' => $newPaid,
                    'charge_id' => $chargeId,
                    'status' => $newStatus,
                    'updated_at' => now(),
                ]);

            $remaining -= $toApply;
        }

        if ($remaining > 0) {
            $lastInstallment = DB::table('payment_schedules')
                ->where('contract_id', $contractId)
                ->max('installment_number');

            DB::table('payment_schedules')->insert([
                'contract_id' => $contractId,
                'installment_number' => ((int) $lastInstallment) + 1,
                'due_date' => now()->toDateString(),
                'amount' => $remaining,
                'amount_paid' => $remaining,
                'late_fee_amount' => 0,
                'late_fee_applied' => false,
                'status' => 'ADELANTADO',
                'charge_id' => $chargeId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    protected function closeContractIfPaid(int $contractId): void
    {
        $contract = DB::table('contracts')->where('id', $contractId)->first();
        if (!$contract) return;

        $totalSchedules = DB::table('payment_schedules')
            ->where('contract_id', $contractId)
            ->count();

        $paidSchedules = DB::table('payment_schedules')
            ->where('contract_id', $contractId)
            ->where('status', 'PAGADO')
            ->count();

        $totalPaid = DB::table('charges')
            ->where('contract_id', $contractId)
            ->whereNull('fecha_baja')
            ->sum(DB::raw('COALESCE(monto,0) + COALESCE(monto_recargo,0)'));

        $contractStatusId = null;

        if ((float) $contract->saldo_financiado <= 0 || ($totalSchedules > 0 && $totalSchedules === $paidSchedules)) {
            $contractStatusId = $this->getContractStatusId('LIQUIDADO');
        }

        if ((float) $totalPaid >= (float) $contract->importe && !$contractStatusId) {
            $contractStatusId = $this->getContractStatusId('LIQUIDADO');
        }

        if ($contractStatusId) {
            DB::table('contracts')
                ->where('id', $contractId)
                ->update([
                    'status_id' => $contractStatusId,
                    'updated_at' => now(),
                ]);
        }
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
}