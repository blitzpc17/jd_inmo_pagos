<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ChargeTypeSeeder extends Seeder
{
    public function run(): void
    {
        $activeId = DB::table('statuses as s')
            ->join('processes as p', 'p.id', '=', 's.process_id')
            ->where('p.clave', 'GENERAL')
            ->where('s.clave', 'ACTIVE')
            ->value('s.id');

        $rows = [
            'APARTADO_INICIAL' => 'Apartado inicial',
            'COMPLEMENTO_APARTADO' => 'Complemento de apartado',
            'ENGANCHE' => 'Enganche',
            'MENSUALIDAD' => 'Mensualidad',
            'MENSUALIDAD_ADELANTADA' => 'Mensualidad adelantada',
            'RECARGO' => 'Recargo',
            'LIQUIDACION_CONTADO' => 'Liquidación contado',
            'ABONO_CAPITAL' => 'Abono a capital',
            'OTRO' => 'Otro',
        ];

        foreach ($rows as $clave => $nombre) {
            DB::table('charge_types')->updateOrInsert(
                ['nombre' => $nombre],
                [
                    'status_id' => $activeId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}