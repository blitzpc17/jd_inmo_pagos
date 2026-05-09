<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class GeneralStatusSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $generalId = DB::table('processes')->where('clave', 'GENERAL')->value('id');

        $statuses = [
            ['clave' => 'ACTIVE', 'nombre' => 'Activo'],
            ['clave' => 'INACTIVE', 'nombre' => 'Inactivo'],
        ];

        foreach ($statuses as $row) {
            DB::table('statuses')->updateOrInsert(
                [
                    'process_id' => $generalId,
                    'clave' => $row['clave'],
                ],
                [
                    'nombre' => $row['nombre'],
                    'updated_at' => $now,
                    'created_at' => DB::raw("NOW()"),
                ]
            );
        }
    }
}