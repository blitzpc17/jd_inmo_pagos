<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LotStatusSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('processes')->updateOrInsert(
            ['clave' => 'LOT_STATUS'],
            [
                'nombre' => 'Estados de lotes',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $processId = DB::table('processes')
            ->where('clave', 'LOT_STATUS')
            ->value('id');

        $statuses = [
            ['clave' => 'LIBRE', 'nombre' => 'Libre'],
            ['clave' => 'APARTADO', 'nombre' => 'Apartado'],
            ['clave' => 'VENDIDO', 'nombre' => 'Vendido'],
            ['clave' => 'LIQUIDADO', 'nombre' => 'Liquidado'],
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