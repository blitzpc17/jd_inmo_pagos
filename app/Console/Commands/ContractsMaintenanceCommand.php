<?php

namespace App\Console\Commands;

use App\Services\ContractCollectionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ContractsMaintenanceCommand extends Command
{
    protected $signature = 'contracts:maintenance {--notify=1}';

    protected $description = 'Valida contratos vencidos, finaliza contratos con atraso permitido vencido y libera lotes.';

    public function handle(ContractCollectionService $collectionService): int
    {
        $notify = (bool) ((int) $this->option('notify'));

        $vigenteStatusId = DB::table('statuses as s')
            ->join('processes as p', 'p.id', '=', 's.process_id')
            ->where('p.clave', 'CONTRACT_STATUS')
            ->where('s.clave', 'VIGENTE')
            ->value('s.id');

        if (!$vigenteStatusId) {
            $this->error('No existe CONTRACT_STATUS / VIGENTE.');
            return self::FAILURE;
        }

        $contracts = DB::table('contracts')
            ->where('status_id', $vigenteStatusId)
            ->whereNull('fecha_baja')
            ->orderBy('id')
            ->get(['id', 'numero_referencia', 'status_id']);

        $finalized = [];

        foreach ($contracts as $contract) {
            $beforeStatus = DB::table('contracts')
                ->where('id', $contract->id)
                ->value('status_id');

            try {
                $collectionService->enforceDelinquencyRule((int) $contract->id);
            } catch (\Throwable $e) {
                Log::error('Error en mantenimiento de contrato', [
                    'contract_id' => $contract->id,
                    'folio' => $contract->numero_referencia,
                    'error' => $e->getMessage(),
                ]);

                continue;
            }

            $after = DB::table('contracts as c')
                ->leftJoin('statuses as s', 's.id', '=', 'c.status_id')
                ->leftJoin('clients as cl', 'cl.id', '=', 'c.client_id')
                ->leftJoin('developments as d', 'd.id', '=', 'c.development_id')
                ->where('c.id', $contract->id)
                ->select([
                    'c.id',
                    'c.numero_referencia',
                    'c.status_id',
                    's.clave as status_clave',
                    's.nombre as status_nombre',
                    DB::raw("TRIM(COALESCE(cl.nombres,'') || ' ' || COALESCE(cl.apellidos,'')) as cliente"),
                    'd.nombre as lotificacion',
                ])
                ->first();

            if (
                $after
                && (int) $beforeStatus !== (int) $after->status_id
                && $after->status_clave === 'FINALIZADO'
            ) {
                $finalized[] = $after;
            }
        }

        if ($notify && count($finalized) > 0) {
            $this->sendFinalizationNotifications($finalized);
        }

        $this->info('Contratos revisados: ' . $contracts->count());
        $this->info('Contratos finalizados: ' . count($finalized));

        return self::SUCCESS;
    }

    protected function sendFinalizationNotifications(array $contracts): void
    {
        $settings = $this->collectionEmailSettings();

        if (!($settings['enabled'] ?? false)) {
            return;
        }

        if (!($settings['notify_on_contract_finalized'] ?? false)) {
            return;
        }

        $emails = $this->recipientEmails($settings);

        if (empty($emails)) {
            Log::warning('No hay correos configurados para notificación de contratos finalizados.');
            return;
        }

        $subject = $settings['subject_finalized'] ?? 'Contrato finalizado por atraso';

        foreach ($emails as $email) {
            try {
                Mail::raw($this->finalizedMessage($contracts), function ($message) use ($email, $subject) {
                    $message->to($email)->subject($subject);
                });
            } catch (\Throwable $e) {
                Log::error('No se pudo enviar correo de contratos finalizados', [
                    'email' => $email,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    protected function collectionEmailSettings(): array
    {
        $default = [
            'enabled' => false,
            'notify_on_contract_finalized' => false,
            'notify_before_finalization' => false,
            'days_before_finalization' => 5,
            'recipient_user_ids' => [],
            'fallback_user_id' => 1,
            'subject_finalized' => 'Contrato finalizado por atraso',
        ];

        try {
            $value = DB::table('global_variables')
                ->where('nombre', 'COLLECTION_EMAIL_SETTINGS')
                ->value('valor');

            if (!$value) {
                return $default;
            }

            $json = is_array($value) ? $value : json_decode($value, true);

            if (!is_array($json)) {
                return $default;
            }

            return array_merge($default, $json);
        } catch (\Throwable $e) {
            return $default;
        }
    }

    protected function recipientEmails(array $settings): array
    {
        $userIds = $settings['recipient_user_ids'] ?? [];

        if (!is_array($userIds)) {
            $userIds = [];
        }

        $fallbackUserId = $settings['fallback_user_id'] ?? null;

        if (empty($userIds) && $fallbackUserId) {
            $userIds = [$fallbackUserId];
        }

        $emails = DB::table('users')
            ->whereIn('id', $userIds)
            ->whereNotNull('email')
            ->where('email', '<>', '')
            ->pluck('email')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($emails) && $fallbackUserId) {
            $fallbackEmail = DB::table('users')
                ->where('id', $fallbackUserId)
                ->whereNotNull('email')
                ->where('email', '<>', '')
                ->value('email');

            if ($fallbackEmail) {
                $emails[] = $fallbackEmail;
            }
        }

        return $emails;
    }

    protected function finalizedMessage(array $contracts): string
    {
        $lines = [];
        $lines[] = 'Se finalizaron contratos por atraso.';
        $lines[] = '';
        $lines[] = 'Total: ' . count($contracts);
        $lines[] = '';

        foreach ($contracts as $contract) {
            $lots = DB::table('contract_lots as cl')
                ->join('lots as l', 'l.id', '=', 'cl.lot_id')
                ->where('cl.contract_id', $contract->id)
                ->pluck('l.identificador')
                ->implode(', ');

            $lines[] = 'Contrato: ' . ($contract->numero_referencia ?? 'S/F');
            $lines[] = 'Cliente: ' . ($contract->cliente ?? 'N/A');
            $lines[] = 'Lotificación: ' . ($contract->lotificacion ?? 'N/A');
            $lines[] = 'Lotes liberados: ' . ($lots ?: 'N/A');
            $lines[] = 'Estado: FINALIZADO';
            $lines[] = 'Fecha: ' . now()->format('d/m/Y H:i:s');
            $lines[] = str_repeat('-', 40);
        }

        return implode(PHP_EOL, $lines);
    }
}