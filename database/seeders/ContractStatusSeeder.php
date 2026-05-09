<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ContractStatusSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('processes')->updateOrInsert(
            ['clave' => 'CONTRACT_STATUS'],
            [
                'nombre' => 'Estados de contratos',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $processId = DB::table('processes')
            ->where('clave', 'CONTRACT_STATUS')
            ->value('id');

        $statuses = [
            ['clave' => 'VIGENTE', 'nombre' => 'Vigente'],
            ['clave' => 'LIQUIDADO', 'nombre' => 'Liquidado'],
            ['clave' => 'CANCELADO', 'nombre' => 'Cancelado'],
            ['clave' => 'FINALIZADO', 'nombre' => 'Finalizado'],
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