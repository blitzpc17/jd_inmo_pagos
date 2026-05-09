<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ChargeTypeSeeder extends Seeder
{
    public function run(): void
    {
        $activeId = DB::table('statuses')
            ->join('processes', 'processes.id', '=', 'statuses.process_id')
            ->where('processes.clave', 'GENERAL')
            ->where('statuses.clave', 'ACTIVE')
            ->value('statuses.id');

        $items = ['Abono', 'Enganche', 'Recargo', 'Apartado', 'Pago total'];

        foreach ($items as $item) {
            DB::table('charge_types')->updateOrInsert(
                ['nombre' => $item],
                [
                    'status_id' => $activeId,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }
}