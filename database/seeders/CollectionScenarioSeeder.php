<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CollectionScenarioSeeder extends Seeder
{
    protected int $activeStatusId;
    protected int $contractVigenteStatusId;
    protected int $lotVendidoStatusId;
    protected int $userId;
    protected int $sellerId;

    public function run(): void
    {
        DB::transaction(function () {
            $this->userId = $this->defaultAdminUserId();
            $this->sellerId = $this->defaultSellerId();

            $this->activeStatusId = $this->statusId('GENERAL', 'ACTIVE');
            $this->contractVigenteStatusId = $this->statusId('CONTRACT_STATUS', 'VIGENTE');
            $this->lotVendidoStatusId = $this->statusId('LOT_STATUS', 'VENDIDO');

            $officeId = $this->office('OFICINA PRUEBAS COBRANZA');
            $paymentCashId = $this->paymentMethod('EFECTIVO PRUEBA', $officeId);
            $this->paymentMethod('TRANSFERENCIA PRUEBA', $officeId);

            $developmentId = $this->development();
            $clientId = $this->client();
            $paymentTypeCreditId = $this->contractPaymentType('CREDITO');

            $scenarios = [
                [
                    'name' => 'ESCENARIO MENSUALIDAD EXACTA',
                    'ref' => 'TEST-MENSUALIDAD-EXACTA',
                    'monthly' => 6500,
                    'months' => 6,
                    'payment_type_id' => $paymentTypeCreditId,
                    'start_offset_months' => 0,
                    'preset' => 'current_pending',
                ],
                [
                    'name' => 'ESCENARIO PAGO PARCIAL',
                    'ref' => 'TEST-PAGO-PARCIAL',
                    'monthly' => 6500,
                    'months' => 6,
                    'payment_type_id' => $paymentTypeCreditId,
                    'start_offset_months' => 0,
                    'preset' => 'current_pending',
                ],
                [
                    'name' => 'ESCENARIO PAGO PARCIAL ADELANTADO',
                    'ref' => 'TEST-PARCIAL-ADELANTADO',
                    'monthly' => 6500,
                    'months' => 6,
                    'payment_type_id' => $paymentTypeCreditId,
                    'start_offset_months' => 0,
                    'preset' => 'current_paid_next_pending',
                ],
                [
                    'name' => 'ESCENARIO MENSUALIDAD ADELANTADA',
                    'ref' => 'TEST-MENSUALIDAD-ADELANTADA',
                    'monthly' => 6500,
                    'months' => 6,
                    'payment_type_id' => $paymentTypeCreditId,
                    'start_offset_months' => 0,
                    'preset' => 'current_paid_next_pending',
                ],
                [
                    'name' => 'ESCENARIO RECARGO',
                    'ref' => 'TEST-RECARGO',
                    'monthly' => 6500,
                    'months' => 6,
                    'payment_type_id' => $paymentTypeCreditId,
                    'start_offset_months' => -1,
                    'preset' => 'one_month_late',
                ],
                [
                    'name' => 'ESCENARIO MENSUALIDAD ATRASADA',
                    'ref' => 'TEST-MENSUALIDAD-ATRASADA',
                    'monthly' => 6500,
                    'months' => 6,
                    'payment_type_id' => $paymentTypeCreditId,
                    'start_offset_months' => -2,
                    'preset' => 'two_months_late',
                ],
                [
                    'name' => 'ESCENARIO LIQUIDACION NORMAL',
                    'ref' => 'TEST-LIQUIDACION-NORMAL',
                    'monthly' => 6500,
                    'months' => 4,
                    'payment_type_id' => $paymentTypeCreditId,
                    'start_offset_months' => 0,
                    'preset' => 'ready_to_liquidate',
                ],
                [
                    'name' => 'ESCENARIO LIQUIDACION CON ATRASOS',
                    'ref' => 'TEST-LIQUIDACION-ATRASOS',
                    'monthly' => 6500,
                    'months' => 5,
                    'payment_type_id' => $paymentTypeCreditId,
                    'start_offset_months' => -2,
                    'preset' => 'two_months_late',
                ],
                [
                    'name' => 'ESCENARIO FINALIZACION CUARTO MES',
                    'ref' => 'TEST-FINALIZACION-CUARTO-MES',
                    'monthly' => 6500,
                    'months' => 6,
                    'payment_type_id' => $paymentTypeCreditId,
                    'start_offset_months' => -3,
                    'preset' => 'three_months_late',
                ],
            ];

            foreach ($scenarios as $index => $scenario) {
                $lotId = $this->lot($developmentId, $officeId, $index + 1);
                $contractId = $this->contract($scenario, $clientId, $developmentId, $officeId, $paymentCashId, $lotId);
                $this->schedule($contractId, $scenario);
            }
        });
    }

    protected function defaultAdminUserId(): int
    {
        $user = DB::table('users')
            ->whereRaw('LOWER(alias) = ?', ['admin'])
            ->first();

        if (!$user) {
            $user = DB::table('users')
                ->whereRaw('LOWER(alias) LIKE ?', ['%admin%'])
                ->orderBy('id')
                ->first();
        }

        if (!$user) {
            $user = DB::table('users')
                ->orderBy('id')
                ->first();
        }

        if (!$user) {
            throw new \RuntimeException(
                'No existe ningún usuario en la tabla users. Crea el usuario admin antes de correr CollectionScenarioSeeder.'
            );
        }

        return (int) $user->id;
    }


    protected function defaultSellerId(): int
    {
        if (!Schema::hasTable('sellers')) {
            throw new \RuntimeException(
                'No existe la tabla sellers. Debes tener al menos un vendedor para crear contratos de prueba.'
            );
        }

        $seller = DB::table('sellers')
            ->orderBy('id')
            ->first();

        if (!$seller) {
            throw new \RuntimeException(
                'No existe ningún vendedor en la tabla sellers. Crea al menos un vendedor antes de correr CollectionScenarioSeeder.'
            );
        }

        return (int) $seller->id;
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

    protected function safeInsertGetId(string $table, array $payload): int
    {
        $payload = $this->filterPayloadByTableColumns($table, $payload);

        return DB::table($table)->insertGetId($payload);
    }

    protected function safeUpdate(string $table, int $id, array $payload): void
    {
        $payload = $this->filterPayloadByTableColumns($table, $payload);

        DB::table($table)
            ->where('id', $id)
            ->update($payload);
    }

    protected function filterPayloadByTableColumns(string $table, array $payload): array
    {
        $columns = Schema::getColumnListing($table);

        return collect($payload)
            ->filter(fn ($value, $key) => in_array($key, $columns, true))
            ->all();
    }

    protected function addAuditColumns(string $table, array $payload): array
    {
        /*
         * Aunque Schema a veces no detecta bien por caché/config,
         * dejamos el filtro final en safeInsertGetId().
         */
        $payload['usuario_genero_id'] = $this->userId;
        $payload['usuario_registro_id'] = $this->userId;
        $payload['created_by'] = $this->userId;
        $payload['updated_by'] = $this->userId;

        return $payload;
    }

    protected function office(string $name): int
    {
        $exists = DB::table('offices')
            ->where('nombre', $name)
            ->first();

        if ($exists) {
            return (int) $exists->id;
        }

        $payload = [
            'nombre' => $name,
            'status_id' => $this->activeStatusId,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $payload = $this->addAuditColumns('offices', $payload);

        return $this->safeInsertGetId('offices', $payload);
    }

    protected function paymentMethod(string $name, int $officeId): int
    {
        $exists = DB::table('payment_methods')
            ->where('nombre', $name)
            ->where('office_id', $officeId)
            ->first();

        if ($exists) {
            return (int) $exists->id;
        }

        $payload = [
            'nombre' => $name,
            'office_id' => $officeId,
            'status_id' => $this->activeStatusId,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $payload = $this->addAuditColumns('payment_methods', $payload);

        return $this->safeInsertGetId('payment_methods', $payload);
    }

    protected function development(): int
    {
        $name = 'LOTIFICACION PRUEBAS COBRANZA';

        $exists = DB::table('developments')
            ->where('nombre', $name)
            ->first();

        if ($exists) {
            return (int) $exists->id;
        }

        $payload = [
            'nombre' => $name,
            'numero_lotes' => 9,
            'oficina' => 'OFICINA PRUEBAS COBRANZA',
            'status_id' => $this->activeStatusId,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $payload = $this->addAuditColumns('developments', $payload);

        return $this->safeInsertGetId('developments', $payload);
    }

    protected function client(): int
    {
        $exists = DB::table('clients')
            ->where('nombres', 'CLIENTE')
            ->where('apellidos', 'PRUEBAS COBRANZA')
            ->first();

        if ($exists) {
            /*
             * Por si ya se creó antes con datos incompletos, lo actualizamos.
             */
            $payload = [
                'telefono' => '2380000000',
                'status_id' => $this->activeStatusId,
                'usuario_genero_id' => $this->userId,
                'updated_at' => now(),
            ];

            $this->safeUpdate('clients', (int) $exists->id, $payload);

            return (int) $exists->id;
        }

        /*
         * AQUÍ ESTÁ LA CORRECCIÓN DIRECTA:
         * usuario_genero_id va fijo con el usuario admin detectado.
         */
        $payload = [
            'nombres' => 'CLIENTE',
            'apellidos' => 'PRUEBAS COBRANZA',
            'telefono' => '2380000000',
            'status_id' => $this->activeStatusId,
            'usuario_genero_id' => $this->userId,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $payload = $this->addAuditColumns('clients', $payload);

        return $this->safeInsertGetId('clients', $payload);
    }

    protected function contractPaymentType(string $name): int
    {
        $row = DB::table('contract_payment_types')
            ->whereRaw('UPPER(nombre) = ?', [mb_strtoupper($name)])
            ->first();

        if ($row) {
            return (int) $row->id;
        }

        $payload = [
            'nombre' => $name,
            'status_id' => $this->activeStatusId,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $payload = $this->addAuditColumns('contract_payment_types', $payload);

        return $this->safeInsertGetId('contract_payment_types', $payload);
    }

    protected function lot(int $developmentId, int $officeId, int $number): int
    {
        $identifier = 'TEST-L' . str_pad((string) $number, 2, '0', STR_PAD_LEFT);

        $exists = DB::table('lots')
            ->where('identificador', $identifier)
            ->first();

        if ($exists) {
            DB::table('lot_offices')->updateOrInsert(
                [
                    'lot_id' => $exists->id,
                    'office_id' => $officeId,
                ],
                [
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            $this->safeUpdate('lots', (int) $exists->id, [
                'status_id' => $this->lotVendidoStatusId,
                'usuario_genero_id' => $this->userId,
                'updated_at' => now(),
            ]);

            return (int) $exists->id;
        }

        $payload = [
            'development_id' => $developmentId,
            'identificador' => $identifier,
            'manzana' => 'MZ-01',
            'status_id' => $this->lotVendidoStatusId,
            'usuario_genero_id' => $this->userId,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $payload = $this->addAuditColumns('lots', $payload);

        $lotId = $this->safeInsertGetId('lots', $payload);

        DB::table('lot_offices')->updateOrInsert(
            [
                'lot_id' => $lotId,
                'office_id' => $officeId,
            ],
            [
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        return $lotId;
    }

    protected function contract(
        array $scenario,
        int $clientId,
        int $developmentId,
        int $officeId,
        int $paymentMethodId,
        int $lotId
    ): int {
        $existing = DB::table('contracts')
            ->where('numero_referencia', $scenario['ref'])
            ->first();

        if ($existing) {
            DB::table('payment_schedules')
                ->where('contract_id', $existing->id)
                ->update(['charge_id' => null]);

            DB::table('charges')
                ->where('contract_id', $existing->id)
                ->delete();

            DB::table('payment_schedules')
                ->where('contract_id', $existing->id)
                ->delete();

            DB::table('contract_lots')
                ->where('contract_id', $existing->id)
                ->delete();

            DB::table('contracts')
                ->where('id', $existing->id)
                ->delete();
        }

        $months = (int) $scenario['months'];
        $monthly = (float) $scenario['monthly'];
        $amount = $months * $monthly;

        $payload = [
            'numero_referencia' => $scenario['ref'],
            'client_id' => $clientId,
            'seller_id' => $this->sellerId,
            'development_id' => $developmentId,
            'office_id' => $officeId,
            'contract_payment_type_id' => $scenario['payment_type_id'],
            'payment_method_id' => $paymentMethodId,
            'status_id' => $this->contractVigenteStatusId,
            'importe' => $amount,
            'monto_pago_inicial' => 0,
            'saldo_financiado' => $amount,
            'cuota_mensual' => $monthly,
            'meses' => $months,
            'fecha_emision' => now()->toDateString(),
            'observacion' => $scenario['name'],
            'contract_property_type' => 'E',
            'contract_document_data' => json_encode([
                'ciudad_firma' => 'TEHUACAN PUEBLA',
                'direccion_comprador' => 'DOMICILIO DE PRUEBA',
                'telefono_comprador' => '2380000000',
            ], JSON_UNESCAPED_UNICODE),
            'usuario_genero_id' => $this->userId,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $payload = $this->addAuditColumns('contracts', $payload);

        $contractId = $this->safeInsertGetId('contracts', $payload);

        DB::table('contract_lots')->insert([
            'contract_id' => $contractId,
            'lot_id' => $lotId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $contractId;
    }

    protected function schedule(int $contractId, array $scenario): void
    {
        $months = (int) $scenario['months'];
        $monthly = (float) $scenario['monthly'];
        $start = now()->startOfMonth()->addMonths((int) $scenario['start_offset_months']);

        for ($i = 1; $i <= $months; $i++) {
            $dueDate = $start->copy()->addMonths($i - 1)->day(10);

            $amountPaid = 0;
            $status = 'PENDIENTE';

            if ($scenario['preset'] === 'current_paid_next_pending' && $i === 1) {
                $amountPaid = $monthly;
                $status = 'PAGADO';
            }

            if ($scenario['preset'] === 'ready_to_liquidate' && $i <= 2) {
                $amountPaid = $monthly;
                $status = 'PAGADO';
            }

            $payload = [
                'contract_id' => $contractId,
                'installment_number' => $i,
                'due_date' => $dueDate->toDateString(),
                'amount' => $monthly,
                'amount_paid' => $amountPaid,
                'late_fee_amount' => 0,
                'late_fee_applied' => false,
                'status' => $status,
                'usuario_genero_id' => $this->userId,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $payload = $this->addAuditColumns('payment_schedules', $payload);
            $payload = $this->filterPayloadByTableColumns('payment_schedules', $payload);

            DB::table('payment_schedules')->insert($payload);
        }
    }
}