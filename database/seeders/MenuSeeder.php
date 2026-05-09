<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        $activeId = DB::table('statuses')
            ->join('processes', 'processes.id', '=', 'statuses.process_id')
            ->where('processes.clave', 'GENERAL')
            ->where('statuses.clave', 'ACTIVE')
            ->value('statuses.id');

        $menus = [
            [
                'nombre' => 'Dashboard',
                'clave' => 'dashboard',
                'ruta' => '/dashboard',
                'icono' => 'fa-solid fa-gauge-high',
                'parent' => null,
                'orden' => 1,
            ],
            [
                'nombre' => 'Seguridad',
                'clave' => 'seguridad',
                'ruta' => null,
                'icono' => 'fa-solid fa-shield-halved',
                'parent' => null,
                'orden' => 2,
            ],
            [
                'nombre' => 'Usuarios',
                'clave' => 'usuarios',
                'ruta' => '/usuarios',
                'icono' => 'fa-solid fa-users',
                'parent' => 'seguridad',
                'orden' => 1,
            ],
            [
                'nombre' => 'Roles',
                'clave' => 'roles',
                'ruta' => '/roles',
                'icono' => 'fa-solid fa-user-tag',
                'parent' => 'seguridad',
                'orden' => 2,
            ],
            [
                'nombre' => 'Menú',
                'clave' => 'menus',
                'ruta' => '/menus',
                'icono' => 'fa-solid fa-list',
                'parent' => 'seguridad',
                'orden' => 3,
            ],
            [
                'nombre' => 'Permisos',
                'clave' => 'permisos',
                'ruta' => '/permisos',
                'icono' => 'fa-solid fa-key',
                'parent' => 'seguridad',
                'orden' => 4,
            ],
            [
                'nombre' => 'Catálogos',
                'clave' => 'catalogos',
                'ruta' => null,
                'icono' => 'fa-solid fa-folder-open',
                'parent' => null,
                'orden' => 3,
            ],
            [
                'nombre' => 'Oficinas',
                'clave' => 'oficinas',
                'ruta' => '/oficinas',
                'icono' => 'fa-solid fa-building',
                'parent' => 'catalogos',
                'orden' => 1,
            ],
            [
                'nombre' => 'Socios',
                'clave' => 'socios',
                'ruta' => '/socios',
                'icono' => 'fa-solid fa-handshake',
                'parent' => 'catalogos',
                'orden' => 2,
            ],
            [
                'nombre' => 'Lotificaciones',
                'clave' => 'lotificaciones',
                'ruta' => '/lotificaciones',
                'icono' => 'fa-solid fa-map-location-dot',
                'parent' => 'catalogos',
                'orden' => 3,
            ],
            [
                'nombre' => 'Clientes',
                'clave' => 'clientes',
                'ruta' => '/clientes',
                'icono' => 'fa-solid fa-id-card',
                'parent' => 'catalogos',
                'orden' => 4,
            ],
            [
                'nombre' => 'Operación',
                'clave' => 'operacion',
                'ruta' => null,
                'icono' => 'fa-solid fa-briefcase',
                'parent' => null,
                'orden' => 4,
            ],
            [
                'nombre' => 'Apartados',
                'clave' => 'apartados',
                'ruta' => '/apartados',
                'icono' => 'fa-solid fa-file-signature',
                'parent' => 'operacion',
                'orden' => 1,
            ],
            [
                'nombre' => 'Contratos',
                'clave' => 'contratos',
                'ruta' => '/contratos',
                'icono' => 'fa-solid fa-file-contract',
                'parent' => 'operacion',
                'orden' => 2,
            ],
            [
                'nombre' => 'Cobros',
                'clave' => 'cobros',
                'ruta' => '/cobros',
                'icono' => 'fa-solid fa-cash-register',
                'parent' => 'operacion',
                'orden' => 3,
            ],
            [
                'nombre' => 'Proveedores',
                'clave' => 'proveedores',
                'ruta' => '/proveedores',
                'icono' => 'fa-solid fa-truck',
                'parent' => 'operacion',
                'orden' => 4,
            ],
            [
                'nombre' => 'Pagos proveedores',
                'clave' => 'pagos_proveedores',
                'ruta' => '/pagos-proveedores',
                'icono' => 'fa-solid fa-money-check-dollar',
                'parent' => 'operacion',
                'orden' => 5,
            ],
        ];

        foreach ($menus as $menu) {
            $parentId = null;

            if (!empty($menu['parent'])) {
                $parentId = DB::table('menus')->where('clave', $menu['parent'])->value('id');
            }

            DB::table('menus')->updateOrInsert(
                ['clave' => $menu['clave']],
                [
                    'nombre' => $menu['nombre'],
                    'ruta' => $menu['ruta'],
                    'icono' => $menu['icono'],
                    'parent_id' => $parentId,
                    'orden' => $menu['orden'],
                    'es_menu' => true,
                    'status_id' => $activeId,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }
}