<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GlobalVariableSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('global_variables')->updateOrInsert(
            ['nombre' => 'FOLIO_VENDEDOR'],
            [
                'valor' => json_encode([
                    'longitud' => 4,
                    'prefijo' => '',
                    'consecutivo' => 1,
                    'tipo' => 'NUMERICO',
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}