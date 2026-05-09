<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ChargeStatusSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('processes')->updateOrInsert(
            ['clave' => 'CHARGE_STATUS'],
            [
                'nombre' => 'Estados de cobros',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $processId = DB::table('processes')
            ->where('clave', 'CHARGE_STATUS')
            ->value('id');

        $statuses = [
            ['clave' => 'REGISTRADO', 'nombre' => 'Registrado'],
            ['clave' => 'APLICADO', 'nombre' => 'Aplicado'],
            ['clave' => 'CANCELADO', 'nombre' => 'Cancelado'],
        ];

        foreach ($statuses as $row) {
            DB::table('statuses')->updateOrInsert(
                [
                    'process_id' => $processId,
                    'clave' => $row['clave'],
                ],
                [
                    'nombre' => $row['nombre'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}