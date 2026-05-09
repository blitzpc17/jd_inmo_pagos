<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        $activeId = DB::table('statuses as s')
            ->join('processes as p', 'p.id', '=', 's.process_id')
            ->where('p.clave', 'GENERAL')
            ->where('s.clave', 'ACTIVE')
            ->value('s.id');

        $items = [
            ['nombre' => 'Dashboard', 'clave' => 'dashboard', 'ruta' => 'dashboard', 'icono' => 'fa-solid fa-gauge-high', 'parent' => null, 'orden' => 1],

            ['nombre' => 'Catálogos', 'clave' => 'catalogos_root', 'ruta' => null, 'icono' => 'fa-solid fa-folder-open', 'parent' => null, 'orden' => 2],

            ['nombre' => 'Procesos', 'clave' => 'processes', 'ruta' => 'catalogos/processes', 'icono' => 'fa-solid fa-diagram-project', 'parent' => 'catalogos_root', 'orden' => 1],
            ['nombre' => 'Estados', 'clave' => 'statuses', 'ruta' => 'catalogos/statuses', 'icono' => 'fa-solid fa-signal', 'parent' => 'catalogos_root', 'orden' => 2],
            ['nombre' => 'Puestos', 'clave' => 'positions', 'ruta' => 'catalogos/positions', 'icono' => 'fa-solid fa-briefcase', 'parent' => 'catalogos_root', 'orden' => 3],
            ['nombre' => 'Roles', 'clave' => 'roles', 'ruta' => 'catalogos/roles', 'icono' => 'fa-solid fa-user-tag', 'parent' => 'catalogos_root', 'orden' => 4],
            ['nombre' => 'Tipos de cobro', 'clave' => 'charge_types', 'ruta' => 'catalogos/charge_types', 'icono' => 'fa-solid fa-money-bill-wave', 'parent' => 'catalogos_root', 'orden' => 5],
            ['nombre' => 'Tipos pago contrato', 'clave' => 'contract_payment_types', 'ruta' => 'catalogos/contract_payment_types', 'icono' => 'fa-solid fa-file-invoice-dollar', 'parent' => 'catalogos_root', 'orden' => 6],
            ['nombre' => 'Oficinas', 'clave' => 'offices', 'ruta' => 'catalogos/offices', 'icono' => 'fa-solid fa-building', 'parent' => 'catalogos_root', 'orden' => 7],
            ['nombre' => 'Formas de pago', 'clave' => 'payment_methods', 'ruta' => 'catalogos/payment_methods', 'icono' => 'fa-solid fa-credit-card', 'parent' => 'catalogos_root', 'orden' => 8],
            ['nombre' => 'Socios', 'clave' => 'partners', 'ruta' => 'catalogos/partners', 'icono' => 'fa-solid fa-handshake', 'parent' => 'catalogos_root', 'orden' => 9],
            ['nombre' => 'Menú', 'clave' => 'menus', 'ruta' => 'catalogos/menus', 'icono' => 'fa-solid fa-list', 'parent' => 'catalogos_root', 'orden' => 10],

            ['nombre' => 'Usuarios', 'clave' => 'users_module', 'ruta' => 'usuarios', 'icono' => 'fa-solid fa-users', 'parent' => null, 'orden' => 3],
            ['nombre' => 'Permisos', 'clave' => 'permissions_module', 'ruta' => 'permisos', 'icono' => 'fa-solid fa-key', 'parent' => null, 'orden' => 4],
        ];

        foreach ($items as $item) {
            $parentId = null;

            if (!empty($item['parent'])) {
                $parentId = DB::table('menus')->where('clave', $item['parent'])->value('id');
            }

            DB::table('menus')->updateOrInsert(
                ['clave' => $item['clave']],
                [
                    'nombre' => $item['nombre'],
                    'ruta' => $item['ruta'],
                    'icono' => $item['icono'],
                    'parent_id' => $parentId,
                    'orden' => $item['orden'],
                    'es_menu' => true,
                    'status_id' => $activeId,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }
}