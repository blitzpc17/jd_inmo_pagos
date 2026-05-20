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
                ], JSON_UNESCAPED_UNICODE),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        DB::table('global_variables')->updateOrInsert(
            ['nombre' => 'CONTRACT_PROPERTY_TYPES'],
            [
                'valor' => json_encode([
                    [
                        'id' => 'E',
                        'descripcion' => 'EJIDO',
                    ],
                    [
                        'id' => 'P',
                        'descripcion' => 'PROPIEDAD',
                    ],
                ], JSON_UNESCAPED_UNICODE),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        DB::table('global_variables')->updateOrInsert(
            ['nombre' => 'BRANDING_PDF'],
            [
                'valor' => json_encode([
                    'company_name' => 'JD Inmobiliaria',
                    'company_subtitle' => 'DOCUMENTOS OFICIALES',
                    'logo_path' => 'assets/images/logo.png',
                    'footer_text' => 'Este documento fue generado por el sistema.',
                    'address_line' => 'VISITANOS EN 3 ORIENTE #736 VOL. RICARDO FLORES MAGON TEHUACAN PUEBLA.',
                    'phone_line' => 'TELEFONO 238 289 0712',
                ], JSON_UNESCAPED_UNICODE),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}