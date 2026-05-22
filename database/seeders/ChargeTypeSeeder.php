<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ChargeTypeSeeder extends Seeder
{
    public function run(): void
    {
        $activeStatusId = $this->getGeneralActiveStatusId();

        $types = [
            [
                'nombre' => 'MENSUALIDAD',
                'descripcion' => 'Pago exacto de la mensualidad vigente.',
                'is_collection_enabled' => true,
            ],
            [
                'nombre' => 'PAGO PARCIAL',
                'descripcion' => 'Pago parcial de la mensualidad vigente.',
                'is_collection_enabled' => true,
            ],
            [
                'nombre' => 'PAGO PARCIAL ADELANTADO',
                'descripcion' => 'Fracción adelantada de una mensualidad futura.',
                'is_collection_enabled' => true,
            ],
            [
                'nombre' => 'MENSUALIDAD ADELANTADA',
                'descripcion' => 'Pago completo de una mensualidad futura.',
                'is_collection_enabled' => true,
            ],
            [
                'nombre' => 'RECARGO',
                'descripcion' => 'Cobro adicional del 10% acumulado por mensualidad vencida.',
                'is_collection_enabled' => true,
            ],
            [
                'nombre' => 'MENSUALIDAD ATRASADA',
                'descripcion' => 'Pago de mensualidad vencida que debe cubrirse junto con su recargo.',
                'is_collection_enabled' => true,
            ],
            [
                'nombre' => 'LIQUIDACION',
                'descripcion' => 'Pago total del saldo pendiente del contrato.',
                'is_collection_enabled' => true,
            ],
        ];

        /*
         * Deshabilitamos todos los tipos para el nuevo módulo de cobros,
         * pero NO los eliminamos ni los damos de baja para no afectar procesos anteriores.
         */
        if (Schema::hasColumn('charge_types', 'is_collection_enabled')) {
            DB::table('charge_types')->update([
                'is_collection_enabled' => false,
                'updated_at' => now(),
            ]);
        }

        foreach ($types as $type) {
            $payload = [
                'updated_at' => now(),
            ];

            if (Schema::hasColumn('charge_types', 'descripcion')) {
                $payload['descripcion'] = $type['descripcion'];
            }

            if (Schema::hasColumn('charge_types', 'is_collection_enabled')) {
                $payload['is_collection_enabled'] = $type['is_collection_enabled'];
            }

            if (Schema::hasColumn('charge_types', 'status_id')) {
                $payload['status_id'] = $activeStatusId;
            }

            if (Schema::hasColumn('charge_types', 'fecha_baja')) {
                $payload['fecha_baja'] = null;
            }

            /*
             * updateOrInsert actualiza también created_at si lo mandamos directo.
             * Por eso primero buscamos si existe.
             */
            $exists = DB::table('charge_types')
                ->where('nombre', $type['nombre'])
                ->exists();

            if (!$exists) {
                $payload['created_at'] = now();
            }

            DB::table('charge_types')->updateOrInsert(
                ['nombre' => $type['nombre']],
                $payload
            );
        }

        DB::table('global_variables')->updateOrInsert(
            ['nombre' => 'COLLECTION_RULES'],
            [
                'valor' => json_encode([
                    'late_fee_percent' => 10,
                    'max_continuous_late_months' => 3,
                    'finalize_on_fourth_month' => true,
                    'allow_contract_statuses' => ['VIGENTE'],
                    'blocked_contract_statuses' => ['LIQUIDADO', 'FINALIZADO', 'CANCELADO'],
                ], JSON_UNESCAPED_UNICODE),
                'updated_at' => now(),
            ] + (
                DB::table('global_variables')->where('nombre', 'COLLECTION_RULES')->exists()
                    ? []
                    : ['created_at' => now()]
            )
        );
    }

    protected function getGeneralActiveStatusId(): int
    {
        $statusId = DB::table('statuses as s')
            ->join('processes as p', 'p.id', '=', 's.process_id')
            ->where('p.clave', 'GENERAL')
            ->where('s.clave', 'ACTIVE')
            ->value('s.id');

        if (!$statusId) {
            throw new \RuntimeException(
                'No existe el estado ACTIVE para el proceso GENERAL. Debes crearlo antes de ejecutar ChargeTypeSeeder.'
            );
        }

        return (int) $statusId;
    }
}