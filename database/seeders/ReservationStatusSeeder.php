<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ReservationStatusSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('processes')->updateOrInsert(
            ['clave' => 'RESERVATION_STATUS'],
            [
                'nombre' => 'Estados de apartados',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $processId = DB::table('processes')
            ->where('clave', 'RESERVATION_STATUS')
            ->value('id');

        $statuses = [
            ['clave' => 'VIGENTE', 'nombre' => 'Vigente'],
            ['clave' => 'VENCIDO', 'nombre' => 'Vencido'],
            ['clave' => 'APLICADO', 'nombre' => 'Aplicado'],
            ['clave' => 'SALDO_FAVOR', 'nombre' => 'Saldo a favor'],
            ['clave' => 'DEVOLUCION', 'nombre' => 'Devolución'],
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