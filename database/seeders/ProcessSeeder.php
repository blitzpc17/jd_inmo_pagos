<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProcessSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $processes = [
            ['clave' => 'GENERAL', 'nombre' => 'General'],
            ['clave' => 'POSITIONS', 'nombre' => 'Puestos'],
            ['clave' => 'ROLES', 'nombre' => 'Roles'],
            ['clave' => 'CHARGE_TYPES', 'nombre' => 'Tipos de cobro'],
            ['clave' => 'CONTRACT_PAYMENT_TYPES', 'nombre' => 'Tipos de pago de contrato'],
            ['clave' => 'PERSONAL', 'nombre' => 'Personal'],
            ['clave' => 'USERS', 'nombre' => 'Usuarios'],
            ['clave' => 'SELLERS', 'nombre' => 'Vendedores'],
            ['clave' => 'OFFICES', 'nombre' => 'Oficinas'],
            ['clave' => 'PAYMENT_METHODS', 'nombre' => 'Formas de pago'],
            ['clave' => 'PARTNERS', 'nombre' => 'Socios'],
            ['clave' => 'DEVELOPMENTS', 'nombre' => 'Lotificaciones'],
            ['clave' => 'LOTS', 'nombre' => 'Lotes'],
            ['clave' => 'CLIENTS', 'nombre' => 'Clientes'],
            ['clave' => 'RESERVATIONS', 'nombre' => 'Apartados'],
            ['clave' => 'CONTRACTS', 'nombre' => 'Contratos'],
            ['clave' => 'CHARGES', 'nombre' => 'Cobros'],
            ['clave' => 'SUPPLIERS', 'nombre' => 'Proveedores'],
            ['clave' => 'SUPPLIER_PAYMENTS', 'nombre' => 'Pagos a proveedores'],
            ['clave' => 'MENUS', 'nombre' => 'Menús'],
        ];

        foreach ($processes as $row) {
            DB::table('processes')->updateOrInsert(
                ['clave' => $row['clave']],
                [
                    'nombre' => $row['nombre'],
                    'updated_at' => $now,
                    'created_at' => DB::raw("COALESCE((SELECT created_at FROM processes WHERE clave = '{$row['clave']}'), NOW())")
                ]
            );
        }
    }
}