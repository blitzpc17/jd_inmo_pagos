<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ContractsMaintenanceCommand extends Command
{
    protected $signature = 'contracts:maintenance';
    protected $description = 'Recalcula estados de mensualidades, recargos y contratos liquidados';

    public function handle(): int
    {
        $this->info('Iniciando mantenimiento de contratos...');

        DB::beginTransaction();

        try {
            $this->refreshSchedulesStatus();
            $this->refreshLateFees();
            $this->refreshContractsStatus();

            DB::commit();
            $this->info('Mantenimiento completado correctamente.');
            return self::SUCCESS;
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Error en mantenimiento: ' . $e->getMessage());
            return self::FAILURE;
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

    protected function refreshLateFees(): void
    {
        $contracts = DB::table('contracts')->whereNull('fecha_baja')->get();

        foreach ($contracts as $contract) {
            $schedules = DB::table('payment_schedules')
                ->where('contract_id', $contract->id)
                ->orderBy('installment_number')
                ->get();

            $lateCount = $schedules->filter(function ($row) {
                return in_array($row->status, ['VENCIDO', 'PARCIAL'], true);
            })->count();

            $apply = $lateCount >= 3;
            $lateFee = $apply ? round((float) $contract->cuota_mensual * 0.10, 2) : 0;

            $firstPending = DB::table('payment_schedules')
                ->where('contract_id', $contract->id)
                ->whereIn('status', ['VENCIDO', 'PARCIAL', 'PENDIENTE'])
                ->orderBy('installment_number')
                ->first();

            if ($firstPending) {
                DB::table('payment_schedules')
                    ->where('id', $firstPending->id)
                    ->update([
                        'late_fee_amount' => $lateFee,
                        'late_fee_applied' => $apply,
                        'updated_at' => now(),
                    ]);
            }

            if (!$apply) {
                DB::table('payment_schedules')
                    ->where('contract_id', $contract->id)
                    ->where('late_fee_applied', true)
                    ->update([
                        'late_fee_amount' => 0,
                        'late_fee_applied' => false,
                        'updated_at' => now(),
                    ]);
            }
        }
    }

    protected function refreshContractsStatus(): void
    {
        $liquidadoStatusId = $this->getContractStatusId('LIQUIDADO');
        $vigenteStatusId = $this->getContractStatusId('VIGENTE');

        $contracts = DB::table('contracts')
            ->whereNull('fecha_baja')
            ->get();

        foreach ($contracts as $contract) {
            $totalSchedules = DB::table('payment_schedules')
                ->where('contract_id', $contract->id)
                ->count();

            $paidSchedules = DB::table('payment_schedules')
                ->where('contract_id', $contract->id)
                ->where('status', 'PAGADO')
                ->count();

            $principalPaid = $this->getContractPrincipalPaid($contract->id);
            $saldo = max(0, (float) $contract->importe - $principalPaid);

            $newStatus = $vigenteStatusId;

            if ((float) $principalPaid >= (float) $contract->importe) {
                $newStatus = $liquidadoStatusId;
            }

            if ($totalSchedules > 0 && $totalSchedules === $paidSchedules) {
                $newStatus = $liquidadoStatusId;
            }

            DB::table('contracts')
                ->where('id', $contract->id)
                ->update([
                    'saldo_financiado' => $saldo,
                    'status_id' => $newStatus,
                    'updated_at' => now(),
                ]);
        }
    }

    protected function getContractPrincipalPaid(int $contractId): float
    {
        $contract = DB::table('contracts')->where('id', $contractId)->first();
        if (!$contract) {
            return 0;
        }

        $chargesPrincipal = DB::table('charges as c')
            ->leftJoin('charge_types as ct', 'ct.id', '=', 'c.charge_type_id')
            ->where('c.contract_id', $contractId)
            ->whereNull('c.fecha_baja')
            ->where(function ($q) {
                $q->whereNull('ct.nombre')
                  ->orWhereNotIn(DB::raw('UPPER(ct.nombre)'), [
                      'RECARGO'
                  ]);
            })
            ->sum('c.monto');

        return (float) $contract->monto_pago_inicial + (float) $chargesPrincipal;
    }

    protected function getContractStatusId(string $clave): int
    {
        $id = DB::table('statuses as s')
            ->join('processes as p', 'p.id', '=', 's.process_id')
            ->where('p.clave', 'CONTRACT_STATUS')
            ->where('s.clave', $clave)
            ->value('s.id');

        if (!$id) {
            throw new \RuntimeException("No existe el estado {$clave} para CONTRACT_STATUS.");
        }

        return (int) $id;
    }
}