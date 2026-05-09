<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $activeId = DB::table('statuses')
            ->join('processes', 'processes.id', '=', 'statuses.process_id')
            ->where('processes.clave', 'GENERAL')
            ->where('statuses.clave', 'ACTIVE')
            ->value('statuses.id');

        $roles = ['Admin', 'Gerente', 'Capturista', 'Cobranza'];

        foreach ($roles as $role) {
            DB::table('roles')->updateOrInsert(
                ['nombre' => $role],
                [
                    'status_id' => $activeId,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }
}