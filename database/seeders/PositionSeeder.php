<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PositionSeeder extends Seeder
{
    public function run(): void
    {
        $activeId = DB::table('statuses')
            ->join('processes', 'processes.id', '=', 'statuses.process_id')
            ->where('processes.clave', 'GENERAL')
            ->where('statuses.clave', 'ACTIVE')
            ->value('statuses.id');

        DB::table('positions')->updateOrInsert(
            ['nombre' => 'Administrador'],
            [
                'status_id' => $activeId,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }
}