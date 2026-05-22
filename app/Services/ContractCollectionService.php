<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ContractCollectionService
{
    protected float $lateFeePercent = 10.0;
    protected int $maxContinuousLateMonths = 3;

    public function __construct()
    {
        $this->loadRules();
    }

    public function preview(int $contractId): array
    {
        $this->enforceDelinquencyRule($contractId);

        $contract = $this->contract($contractId);

        if (!$contract) {
            return [
                'can_charge' => false,
                'blocking_reason' => 'Contrato no encontrado.',
            ];
        }

        if (!in_array($contract->estado_clave, ['VIGENTE'], true)) {
            return [
                'can_charge' => false,
                'blocking_reason' => 'No se pueden recibir cobros porque el contrato está ' . $contract->estado_nombre . '.',
                'contract' => $this->contractSummary($contract),
                'required_payment' => [
                    'total_minimo' => 0,
                    'total_liquidacion' => 0,
                    'conceptos' => [],
                ],
                'calendar' => [],
            ];
        }

        $calendar = $this->calendar($contractId);
        $currentDate = now()->startOfDay();

        $concepts = [];
        $overdueTotal = 0.0;

        foreach ($calendar as $row) {
            $analysis = $this->scheduleAnalysis($row, $currentDate);

            if ($analysis['is_overdue'] && !$analysis['is_paid']) {
                if ($analysis['principal_remaining'] > 0) {
                    $concepts[] = [
                        'schedule_id' => $row->id,
                        'installment_number' => $row->installment_number,
                        'periodo' => $analysis['period_label'],
                        'tipo' => 'MENSUALIDAD ATRASADA',
                        'monto' => round($analysis['principal_remaining'], 2),
                        'razon' => 'Mensualidad vencida.',
                    ];

                    $overdueTotal += $analysis['principal_remaining'];
                }

                if ($analysis['late_fee'] > 0) {
                    $concepts[] = [
                        'schedule_id' => $row->id,
                        'installment_number' => $row->installment_number,
                        'periodo' => $analysis['period_label'],
                        'tipo' => 'RECARGO',
                        'monto' => round($analysis['late_fee'], 2),
                        'razon' => $analysis['late_months'] . ' recargo(s) acumulado(s).',
                    ];

                    $overdueTotal += $analysis['late_fee'];
                }
            }
        }

        $currentSchedule = $this->currentSchedule($calendar, $currentDate);
        $currentRemaining = 0.0;

        if ($currentSchedule) {
            $currentAnalysis = $this->scheduleAnalysis($currentSchedule, $currentDate);

            if (!$currentAnalysis['is_paid']) {
                $currentRemaining = $currentAnalysis['principal_remaining'];

                if ($currentRemaining > 0) {
                    $concepts[] = [
                        'schedule_id' => $currentSchedule->id,
                        'installment_number' => $currentSchedule->installment_number,
                        'periodo' => $currentAnalysis['period_label'],
                        'tipo' => 'MENSUALIDAD',
                        'monto' => round($currentRemaining, 2),
                        'razon' => 'Mensualidad vigente del periodo actual.',
                    ];
                }
            }
        }

        $remainingPrincipal = $this->remainingPrincipal($calendar);
        $totalLateFees = $this->totalLateFees($calendar, $currentDate);
        $totalLiquidation = round($remainingPrincipal + $totalLateFees, 2);

        $minimumToPay = round($overdueTotal > 0 ? $overdueTotal : $currentRemaining, 2);

        $calendarRows = $this->calendarRowsForUi($calendar, $currentDate);

        return [
            'can_charge' => true,
            'blocking_reason' => null,
            'contract' => $this->contractSummary($contract),
            'required_payment' => [
                'total_minimo' => $minimumToPay,
                'total_atrasado' => round($overdueTotal, 2),
                'total_liquidacion' => $totalLiquidation,
                'mensualidad_actual_pendiente' => round($currentRemaining, 2),
                'conceptos' => $concepts,
            ],
            'calendar' => $calendarRows,
        ];
    }

    public function applyPayment(int $contractId, array $data): array
    {
        return DB::transaction(function () use ($contractId, $data) {
            $this->enforceDelinquencyRule($contractId);

            $contract = $this->contract($contractId);

            if (!$contract) {
                throw new \RuntimeException('Contrato no encontrado.');
            }

            if ($contract->estado_clave !== 'VIGENTE') {
                throw new \RuntimeException('No se pueden recibir cobros porque el contrato está ' . $contract->estado_nombre . '.');
            }

            $amount = round((float) $data['monto'], 2);

            if ($amount <= 0) {
                throw new \RuntimeException('El monto recibido debe ser mayor a cero.');
            }

            $paymentMethodId = (int) $data['payment_method_id'];
            $officeId = (int) $data['office_receives_charge_id'];
            $observation = $data['observacion'] ?? null;

            $preview = $this->preview($contractId);

            if (!$preview['can_charge']) {
                throw new \RuntimeException($preview['blocking_reason'] ?? 'El contrato no permite cobros.');
            }

            $overdueTotal = round((float) $preview['required_payment']['total_atrasado'], 2);
            $totalLiquidation = round((float) $preview['required_payment']['total_liquidacion'], 2);

            if ($overdueTotal > 0 && $amount + 0.009 < $overdueTotal) {
                throw new \RuntimeException(
                    'El contrato tiene mensualidades atrasadas. Debe cubrir al menos $' .
                    number_format($overdueTotal, 2) .
                    ' correspondiente a atrasos y recargos.'
                );
            }

            if ($amount > $totalLiquidation + 0.009) {
                throw new \RuntimeException(
                    'El monto recibido excede el saldo total de liquidación: $' .
                    number_format($totalLiquidation, 2) . '.'
                );
            }

            $groupUuid = (string) Str::uuid();
            $chargeStatusId = $this->statusId('CHARGE_STATUS', 'REGISTRADO');

            $createdCharges = [];
            $remaining = $amount;

            $calendar = $this->calendar($contractId);
            $currentDate = now()->startOfDay();

            $isLiquidation = abs($amount - $totalLiquidation) <= 0.009;

            if ($isLiquidation) {
                $createdCharges = array_merge(
                    $createdCharges,
                    $this->applyOverduePayments(
                        $contract,
                        $calendar,
                        $currentDate,
                        $remaining,
                        $groupUuid,
                        $chargeStatusId,
                        $paymentMethodId,
                        $officeId,
                        $observation
                    )
                );

                $remaining = round($amount - collect($createdCharges)->sum('total_amount'), 2);

                if ($remaining > 0.009) {
                    $charge = $this->createCharge([
                        'type' => 'LIQUIDACION',
                        'contract' => $contract,
                        'schedule_id' => null,
                        'group_uuid' => $groupUuid,
                        'charge_status_id' => $chargeStatusId,
                        'payment_method_id' => $paymentMethodId,
                        'office_id' => $officeId,
                        'monto' => $remaining,
                        'monto_recargo' => 0,
                        'observacion' => $observation ?: 'Liquidación total del contrato.',
                    ]);

                    $createdCharges[] = [
                        'id' => $charge->id,
                        'numero_referencia' => $charge->numero_referencia,
                        'tipo' => 'LIQUIDACION',
                        'monto' => $remaining,
                        'monto_recargo' => 0,
                        'total_amount' => $remaining,
                    ];
                }

                $this->markAllSchedulesPaid($contractId);
                $this->liquidateContract($contractId);

                $createdCharges = $this->appendReceiptUrls($createdCharges);

                return [
                    'ok' => true,
                    'message' => 'Liquidación registrada correctamente.',
                    'payment_group_uuid' => $groupUuid,
                    'charges' => $createdCharges,
                    'receipt_url' => $createdCharges[0]['receipt_url'] ?? null,
                ];
            }

            $createdCharges = array_merge(
                $createdCharges,
                $this->applyOverduePayments(
                    $contract,
                    $calendar,
                    $currentDate,
                    $remaining,
                    $groupUuid,
                    $chargeStatusId,
                    $paymentMethodId,
                    $officeId,
                    $observation
                )
            );

            $remaining = round($amount - collect($createdCharges)->sum('total_amount'), 2);

            if ($remaining > 0.009) {
                $createdCharges = array_merge(
                    $createdCharges,
                    $this->applyCurrentAndFuturePayments(
                        $contract,
                        $currentDate,
                        $remaining,
                        $groupUuid,
                        $chargeStatusId,
                        $paymentMethodId,
                        $officeId,
                        $observation
                    )
                );
            }

            $this->refreshScheduleStatuses($contractId);

            $createdCharges = $this->appendReceiptUrls($createdCharges);

            return [
                'ok' => true,
                'message' => 'Cobro registrado correctamente.',
                'payment_group_uuid' => $groupUuid,
                'charges' => $createdCharges,
                'receipt_url' => $createdCharges[0]['receipt_url'] ?? null,
            ];
        });
    }

    protected function applyOverduePayments(
        object $contract,
        $calendar,
        Carbon $currentDate,
        float $availableAmount,
        string $groupUuid,
        int $chargeStatusId,
        int $paymentMethodId,
        int $officeId,
        ?string $observation
    ): array {
        $created = [];
        $remaining = $availableAmount;

        foreach ($calendar as $schedule) {
            $analysis = $this->scheduleAnalysis($schedule, $currentDate);

            if (!$analysis['is_overdue'] || $analysis['is_paid']) {
                continue;
            }

            $principal = round($analysis['principal_remaining'], 2);
            $lateFee = round($analysis['late_fee'], 2);
            $required = round($principal + $lateFee, 2);

            if ($required <= 0) {
                continue;
            }

            if ($remaining + 0.009 < $required) {
                break;
            }

            if ($principal > 0) {
                $charge = $this->createCharge([
                    'type' => 'MENSUALIDAD ATRASADA',
                    'contract' => $contract,
                    'schedule_id' => $schedule->id,
                    'group_uuid' => $groupUuid,
                    'charge_status_id' => $chargeStatusId,
                    'payment_method_id' => $paymentMethodId,
                    'office_id' => $officeId,
                    'monto' => $principal,
                    'monto_recargo' => 0,
                    'observacion' => $observation ?: 'Pago de mensualidad atrasada ' . $analysis['period_label'] . '.',
                ]);

                $created[] = [
                    'id' => $charge->id,
                    'numero_referencia' => $charge->numero_referencia,
                    'tipo' => 'MENSUALIDAD ATRASADA',
                    'monto' => $principal,
                    'monto_recargo' => 0,
                    'total_amount' => $principal,
                ];

                $this->addSchedulePayment($schedule->id, $principal);
            }

            if ($lateFee > 0) {
                $charge = $this->createCharge([
                    'type' => 'RECARGO',
                    'contract' => $contract,
                    'schedule_id' => $schedule->id,
                    'group_uuid' => $groupUuid,
                    'charge_status_id' => $chargeStatusId,
                    'payment_method_id' => $paymentMethodId,
                    'office_id' => $officeId,
                    'monto' => 0,
                    'monto_recargo' => $lateFee,
                    'observacion' => $observation ?: 'Recargo de ' . $analysis['late_months'] . ' mes(es) sobre ' . $analysis['period_label'] . '.',
                ]);

                $created[] = [
                    'id' => $charge->id,
                    'numero_referencia' => $charge->numero_referencia,
                    'tipo' => 'RECARGO',
                    'monto' => 0,
                    'monto_recargo' => $lateFee,
                    'total_amount' => $lateFee,
                ];

                DB::table('payment_schedules')
                    ->where('id', $schedule->id)
                    ->update([
                        'late_fee_amount' => $lateFee,
                        'late_fee_applied' => true,
                        'updated_at' => now(),
                    ]);
            }

            $remaining = round($remaining - $required, 2);
        }

        return $created;
    }

    protected function applyCurrentAndFuturePayments(
        object $contract,
        Carbon $currentDate,
        float $availableAmount,
        string $groupUuid,
        int $chargeStatusId,
        int $paymentMethodId,
        int $officeId,
        ?string $observation
    ): array {
        $created = [];
        $remaining = $availableAmount;

        $schedules = $this->calendar($contract->id)
            ->filter(fn ($schedule) => $this->scheduleAnalysis($schedule, $currentDate)['principal_remaining'] > 0)
            ->values();

        foreach ($schedules as $schedule) {
            if ($remaining <= 0.009) {
                break;
            }

            $analysis = $this->scheduleAnalysis($schedule, $currentDate);

            if ($analysis['is_overdue']) {
                continue;
            }

            $principalRemaining = round($analysis['principal_remaining'], 2);

            if ($principalRemaining <= 0) {
                continue;
            }

            $isCurrent = $analysis['is_current'];
            $isFuture = $analysis['is_future'];

            $payAmount = min($remaining, $principalRemaining);
            $isFull = abs($payAmount - $principalRemaining) <= 0.009;

            if ($isCurrent) {
                $type = $isFull ? 'MENSUALIDAD' : 'PAGO PARCIAL';
            } elseif ($isFuture) {
                $type = $isFull ? 'MENSUALIDAD ADELANTADA' : 'PAGO PARCIAL ADELANTADO';
            } else {
                $type = $isFull ? 'MENSUALIDAD' : 'PAGO PARCIAL';
            }

            $charge = $this->createCharge([
                'type' => $type,
                'contract' => $contract,
                'schedule_id' => $schedule->id,
                'group_uuid' => $groupUuid,
                'charge_status_id' => $chargeStatusId,
                'payment_method_id' => $paymentMethodId,
                'office_id' => $officeId,
                'monto' => $payAmount,
                'monto_recargo' => 0,
                'observacion' => $observation ?: $type . ' ' . $analysis['period_label'] . '.',
            ]);

            $created[] = [
                'id' => $charge->id,
                'numero_referencia' => $charge->numero_referencia,
                'tipo' => $type,
                'monto' => $payAmount,
                'monto_recargo' => 0,
                'total_amount' => $payAmount,
            ];

            $this->addSchedulePayment($schedule->id, $payAmount, $isFuture && $isFull ? 'ADELANTADO' : null);

            $remaining = round($remaining - $payAmount, 2);
        }

        return $created;
    }

    protected function createCharge(array $payload): object
    {
        $typeId = $this->chargeTypeId($payload['type']);
        $contract = $payload['contract'];

        $chargeId = DB::table('charges')->insertGetId([
            'numero_referencia' => '',
            'fecha_emision' => now()->toDateString(),
            'charge_type_id' => $typeId,
            'payment_method_id' => $payload['payment_method_id'],
            'client_id' => $contract->client_id,
            'contract_id' => $contract->id,
            'reservation_id' => null,
            'status_id' => $payload['charge_status_id'],
            'monto' => round((float) $payload['monto'], 2),
            'aplica_recargo' => ((float) $payload['monto_recargo']) > 0,
            'monto_recargo' => round((float) $payload['monto_recargo'], 2),
            'observacion' => $payload['observacion'],
            'office_receives_charge_id' => $payload['office_id'],
            'payment_group_uuid' => $payload['group_uuid'],
            'payment_schedule_id' => $payload['schedule_id'] ?? null,
            'usuario_genero_id' => session('auth_user.id'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $reference = 'COB-' . str_pad((string) $chargeId, 6, '0', STR_PAD_LEFT);

        DB::table('charges')
            ->where('id', $chargeId)
            ->update([
                'numero_referencia' => $reference,
                'updated_at' => now(),
            ]);

        if (!empty($payload['schedule_id'])) {
            DB::table('payment_schedules')
                ->where('id', $payload['schedule_id'])
                ->update([
                    'charge_id' => $chargeId,
                    'updated_at' => now(),
                ]);
        }

        return (object) [
            'id' => $chargeId,
            'numero_referencia' => $reference,
        ];
    }

    protected function addSchedulePayment(int $scheduleId, float $amount, ?string $forcedStatus = null): void
    {
        $schedule = DB::table('payment_schedules')->where('id', $scheduleId)->first();

        if (!$schedule) {
            return;
        }

        $newPaid = round((float) $schedule->amount_paid + $amount, 2);
        $targetAmount = round((float) $schedule->amount, 2);

        if ($newPaid >= $targetAmount - 0.009) {
            $status = $forcedStatus ?: 'PAGADO';
            $newPaid = $targetAmount;
        } elseif ($newPaid > 0) {
            $status = 'PARCIAL';
        } else {
            $status = 'PENDIENTE';
        }

        DB::table('payment_schedules')
            ->where('id', $scheduleId)
            ->update([
                'amount_paid' => $newPaid,
                'status' => $status,
                'updated_at' => now(),
            ]);
    }

    public function enforceDelinquencyRule(int $contractId): void
    {
        $contract = $this->contract($contractId);

        if (!$contract || $contract->estado_clave !== 'VIGENTE') {
            return;
        }

        $calendar = $this->calendar($contractId);
        $currentDate = now()->startOfDay();

        foreach ($calendar as $schedule) {
            $analysis = $this->scheduleAnalysis($schedule, $currentDate);

            if (
                $analysis['is_overdue']
                && !$analysis['is_paid']
                && $analysis['late_months'] >= $this->maxContinuousLateMonths
            ) {
                $this->finalizeContractByDelinquency($contractId);
                return;
            }
        }
    }

    protected function finalizeContractByDelinquency(int $contractId): void
    {
        $finalizedStatusId = $this->statusId('CONTRACT_STATUS', 'FINALIZADO');
        $freeLotStatusId = $this->statusId('LOT_STATUS', 'LIBRE');

        DB::table('contracts')
            ->where('id', $contractId)
            ->update([
                'status_id' => $finalizedStatusId,
                'updated_at' => now(),
            ]);

        $lotIds = DB::table('contract_lots')
            ->where('contract_id', $contractId)
            ->pluck('lot_id')
            ->all();

        if (!empty($lotIds)) {
            DB::table('lots')
                ->whereIn('id', $lotIds)
                ->update([
                    'status_id' => $freeLotStatusId,
                    'updated_at' => now(),
                ]);
        }

        DB::table('payment_schedules')
            ->where('contract_id', $contractId)
            ->whereNotIn(DB::raw('UPPER(status)'), ['PAGADO', 'ADELANTADO'])
            ->update([
                'status' => 'CANCELADO',
                'updated_at' => now(),
            ]);
    }

    protected function liquidateContract(int $contractId): void
    {
        $liquidatedStatusId = $this->statusId('CONTRACT_STATUS', 'LIQUIDADO');

        DB::table('contracts')
            ->where('id', $contractId)
            ->update([
                'status_id' => $liquidatedStatusId,
                'updated_at' => now(),
            ]);

        $lotLiquidatedStatus = DB::table('statuses as s')
            ->join('processes as p', 'p.id', '=', 's.process_id')
            ->where('p.clave', 'LOT_STATUS')
            ->where('s.clave', 'LIQUIDADO')
            ->value('s.id');

        if ($lotLiquidatedStatus) {
            $lotIds = DB::table('contract_lots')
                ->where('contract_id', $contractId)
                ->pluck('lot_id')
                ->all();

            if (!empty($lotIds)) {
                DB::table('lots')
                    ->whereIn('id', $lotIds)
                    ->update([
                        'status_id' => $lotLiquidatedStatus,
                        'updated_at' => now(),
                    ]);
            }
        }
    }

    protected function markAllSchedulesPaid(int $contractId): void
    {
        DB::table('payment_schedules')
            ->where('contract_id', $contractId)
            ->update([
                'amount_paid' => DB::raw('amount'),
                'status' => 'PAGADO',
                'updated_at' => now(),
            ]);
    }

    protected function refreshScheduleStatuses(int $contractId): void
    {
        $calendar = $this->calendar($contractId);
        $currentDate = now()->startOfDay();

        foreach ($calendar as $schedule) {
            $analysis = $this->scheduleAnalysis($schedule, $currentDate);

            if ($analysis['is_paid']) {
                $status = $analysis['is_future'] ? 'ADELANTADO' : 'PAGADO';
            } elseif ($analysis['amount_paid'] > 0 && $analysis['is_overdue']) {
                $status = 'ATRASADO_PARCIAL';
            } elseif ($analysis['amount_paid'] > 0) {
                $status = 'PARCIAL';
            } elseif ($analysis['is_overdue']) {
                $status = 'ATRASADO';
            } else {
                $status = 'PENDIENTE';
            }

            DB::table('payment_schedules')
                ->where('id', $schedule->id)
                ->update([
                    'late_fee_amount' => $analysis['late_fee'],
                    'late_fee_applied' => $analysis['late_fee'] > 0,
                    'status' => $status,
                    'updated_at' => now(),
                ]);
        }
    }

    protected function calendarRowsForUi($calendar, Carbon $currentDate): array
    {
        return $calendar->map(function ($schedule) use ($currentDate) {
            $analysis = $this->scheduleAnalysis($schedule, $currentDate);

            $chargeCount = DB::table('charges')
                ->where('payment_schedule_id', $schedule->id)
                ->whereNull('fecha_baja')
                ->count();

            return [
                'id' => $schedule->id,
                'installment_number' => $schedule->installment_number,
                'periodo' => $analysis['period_label'],
                'due_date' => optional(Carbon::parse($schedule->due_date))->format('Y-m-d'),
                'amount' => round((float) $schedule->amount, 2),
                'amount_paid' => round((float) $schedule->amount_paid, 2),
                'principal_remaining' => round($analysis['principal_remaining'], 2),
                'late_fee_amount' => round($analysis['late_fee'], 2),
                'late_months' => $analysis['late_months'],
                'status' => $analysis['status'],
                'ui_class' => $analysis['ui_class'],
                'charge_count' => $chargeCount,
            ];
        })->values()->all();
    }

    protected function scheduleAnalysis(object $schedule, Carbon $currentDate): array
    {
        $due = Carbon::parse($schedule->due_date)->startOfDay();
        $dueMonth = $due->copy()->startOfMonth();
        $currentMonth = $currentDate->copy()->startOfMonth();

        $amount = round((float) $schedule->amount, 2);
        $amountPaid = round((float) $schedule->amount_paid, 2);
        $principalRemaining = max(0, round($amount - $amountPaid, 2));

        $monthDiff = (int) $dueMonth->diffInMonths($currentMonth, false);
        $isOverdue = $monthDiff > 0 && $principalRemaining > 0;
        $lateMonths = $isOverdue ? $monthDiff : 0;

        $lateFee = $lateMonths > 0
            ? round($amount * ($this->lateFeePercent / 100) * $lateMonths, 2)
            : 0.0;

        $isCurrent = $dueMonth->equalTo($currentMonth);
        $isFuture = $dueMonth->greaterThan($currentMonth);
        $isPaid = $principalRemaining <= 0.009;

        if ($isPaid) {
            $status = $isFuture ? 'ADELANTADO' : 'PAGADO';
            $uiClass = 'success';
        } elseif ($isOverdue && $amountPaid > 0) {
            $status = 'ATRASADO_PARCIAL';
            $uiClass = 'danger';
        } elseif ($isOverdue) {
            $status = 'ATRASADO';
            $uiClass = 'danger';
        } elseif ($amountPaid > 0) {
            $status = 'PARCIAL';
            $uiClass = 'warning';
        } else {
            $status = 'PENDIENTE';
            $uiClass = 'secondary';
        }

        return [
            'amount' => $amount,
            'amount_paid' => $amountPaid,
            'principal_remaining' => $principalRemaining,
            'is_overdue' => $isOverdue,
            'late_months' => $lateMonths,
            'late_fee' => $lateFee,
            'is_current' => $isCurrent,
            'is_future' => $isFuture,
            'is_paid' => $isPaid,
            'period_label' => mb_strtoupper($due->locale('es')->translatedFormat('F Y')),
            'status' => $status,
            'ui_class' => $uiClass,
        ];
    }

    protected function currentSchedule($calendar, Carbon $currentDate): ?object
    {
        $currentMonth = $currentDate->copy()->startOfMonth();

        return $calendar
            ->first(function ($schedule) use ($currentMonth) {
                return Carbon::parse($schedule->due_date)->startOfMonth()->equalTo($currentMonth);
            });
    }

    protected function remainingPrincipal($calendar): float
    {
        return round($calendar->sum(function ($schedule) {
            return max(0, (float) $schedule->amount - (float) $schedule->amount_paid);
        }), 2);
    }

    protected function totalLateFees($calendar, Carbon $currentDate): float
    {
        return round($calendar->sum(function ($schedule) use ($currentDate) {
            return $this->scheduleAnalysis($schedule, $currentDate)['late_fee'];
        }), 2);
    }

    protected function contract(int $contractId): ?object
    {
        return DB::table('contracts as c')
            ->join('clients as cl', 'cl.id', '=', 'c.client_id')
            ->join('statuses as s', 's.id', '=', 'c.status_id')
            ->leftJoin('developments as d', 'd.id', '=', 'c.development_id')
            ->leftJoin('contract_payment_types as cpt', 'cpt.id', '=', 'c.contract_payment_type_id')
            ->where('c.id', $contractId)
            ->whereNull('c.fecha_baja')
            ->select([
                'c.*',
                'cl.nombres',
                'cl.apellidos',
                's.clave as estado_clave',
                's.nombre as estado_nombre',
                'd.nombre as lotificacion',
                'cpt.nombre as tipo_pago',
            ])
            ->first();
    }

    protected function calendar(int $contractId)
    {
        return DB::table('payment_schedules')
            ->where('contract_id', $contractId)
            ->orderBy('installment_number')
            ->orderBy('due_date')
            ->get();
    }

    protected function contractSummary(object $contract): array
    {
        $paid = (float) DB::table('charges')
            ->where('contract_id', $contract->id)
            ->whereNull('fecha_baja')
            ->sum('monto');

        $lateFees = (float) DB::table('charges')
            ->where('contract_id', $contract->id)
            ->whereNull('fecha_baja')
            ->sum('monto_recargo');

        return [
            'id' => $contract->id,
            'folio' => $contract->numero_referencia,
            'cliente' => trim(($contract->nombres ?? '') . ' ' . ($contract->apellidos ?? '')),
            'lotificacion' => $contract->lotificacion,
            'tipo_pago' => $contract->tipo_pago,
            'estado_clave' => $contract->estado_clave,
            'estado_nombre' => $contract->estado_nombre,
            'importe' => round((float) $contract->importe, 2),
            'mensualidad' => round((float) $contract->cuota_mensual, 2),
            'pagado_acumulado' => round($paid, 2),
            'recargos_acumulados' => round($lateFees, 2),
            'saldo_estimado' => round(max(0, (float) $contract->importe - $paid), 2),
        ];
    }

    protected function chargeTypeId(string $name): int
    {
        $id = DB::table('charge_types')
            ->whereRaw('UPPER(nombre) = ?', [mb_strtoupper($name)])
            ->value('id');

        if (!$id) {
            throw new \RuntimeException('No existe el tipo de cobro: ' . $name);
        }

        return (int) $id;
    }

    protected function statusId(string $processClave, string $statusClave): int
    {
        $id = DB::table('statuses as s')
            ->join('processes as p', 'p.id', '=', 's.process_id')
            ->where('p.clave', $processClave)
            ->where('s.clave', $statusClave)
            ->value('s.id');

        if (!$id) {
            throw new \RuntimeException("No existe el estado {$statusClave} para {$processClave}.");
        }

        return (int) $id;
    }

    protected function loadRules(): void
    {
        try {
            $value = DB::table('global_variables')
                ->where('nombre', 'COLLECTION_RULES')
                ->value('valor');

            if (!$value) {
                return;
            }

            $json = is_array($value) ? $value : json_decode($value, true);

            if (!is_array($json)) {
                return;
            }

            $this->lateFeePercent = (float) ($json['late_fee_percent'] ?? 10);
            $this->maxContinuousLateMonths = (int) ($json['max_continuous_late_months'] ?? 3);
        } catch (\Throwable $e) {
            $this->lateFeePercent = 10;
            $this->maxContinuousLateMonths = 3;
        }
    }

    protected function appendReceiptUrls(array $createdCharges): array
    {
        return collect($createdCharges)
            ->map(function ($charge) {
                $charge['receipt_url'] = route('cobros.receipt', $charge['id']);
                return $charge;
            })
            ->values()
            ->all();
    }
}