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
            // PRINCIPALES
            [
                'nombre' => 'Dashboard',
                'clave' => 'dashboard',
                'ruta' => 'dashboard',
                'icono' => 'fa-solid fa-gauge-high',
                'parent' => null,
                'orden' => 1,
            ],

            [
                'nombre' => 'Seguridad',
                'clave' => 'security_root',
                'ruta' => null,
                'icono' => 'fa-solid fa-shield-halved',
                'parent' => null,
                'orden' => 2,
            ],
            [
                'nombre' => 'Catálogos',
                'clave' => 'catalogos_root',
                'ruta' => null,
                'icono' => 'fa-solid fa-folder-open',
                'parent' => null,
                'orden' => 3,
            ],
            [
                'nombre' => 'Cobranza',
                'clave' => 'cobranza_root',
                'ruta' => null,
                'icono' => 'fa-solid fa-money-bill-wave',
                'parent' => null,
                'orden' => 4,
            ],
            [
                'nombre' => 'Pagos',
                'clave' => 'pagos_root',
                'ruta' => null,
                'icono' => 'fa-solid fa-wallet',
                'parent' => null,
                'orden' => 5,
            ],
            [
                'nombre' => 'Acreedores',
                'clave' => 'acreedores_root',
                'ruta' => null,
                'icono' => 'fa-solid fa-hand-holding-dollar',
                'parent' => null,
                'orden' => 6,
            ],

            // SEGURIDAD
            [
                'nombre' => 'Usuarios',
                'clave' => 'users_module',
                'ruta' => 'usuarios',
                'icono' => 'fa-solid fa-users',
                'parent' => 'security_root',
                'orden' => 1,
            ],
            [
                'nombre' => 'Roles',
                'clave' => 'roles_module',
                'ruta' => 'catalogos/roles',
                'icono' => 'fa-solid fa-user-tag',
                'parent' => 'security_root',
                'orden' => 2,
            ],
            [
                'nombre' => 'Permisos',
                'clave' => 'permissions_module',
                'ruta' => 'permisos',
                'icono' => 'fa-solid fa-key',
                'parent' => 'security_root',
                'orden' => 3,
            ],
            [
                'nombre' => 'Menú',
                'clave' => 'menus_module',
                'ruta' => 'catalogos/menus',
                'icono' => 'fa-solid fa-list',
                'parent' => 'security_root',
                'orden' => 4,
            ],
            [
                'nombre' => 'Asignación lotificaciones',
                'clave' => 'development_visibility_module',
                'ruta' => 'asignacion-lotificaciones',
                'icono' => 'fa-solid fa-map-location-dot',
                'parent' => 'security_root',
                'orden' => 5,
            ],

            // CATALOGOS
            [
                'nombre' => 'Procesos',
                'clave' => 'processes',
                'ruta' => 'catalogos/processes',
                'icono' => 'fa-solid fa-diagram-project',
                'parent' => 'catalogos_root',
                'orden' => 1,
            ],
            [
                'nombre' => 'Estados',
                'clave' => 'statuses',
                'ruta' => 'catalogos/statuses',
                'icono' => 'fa-solid fa-signal',
                'parent' => 'catalogos_root',
                'orden' => 2,
            ],
            [
                'nombre' => 'Puestos',
                'clave' => 'positions',
                'ruta' => 'catalogos/positions',
                'icono' => 'fa-solid fa-briefcase',
                'parent' => 'catalogos_root',
                'orden' => 3,
            ],
            [
                'nombre' => 'Tipos de cobro',
                'clave' => 'charge_types',
                'ruta' => 'catalogos/charge_types',
                'icono' => 'fa-solid fa-money-bill-wave',
                'parent' => 'catalogos_root',
                'orden' => 4,
            ],
            [
                'nombre' => 'Tipos pago contrato',
                'clave' => 'contract_payment_types',
                'ruta' => 'catalogos/contract_payment_types',
                'icono' => 'fa-solid fa-file-invoice-dollar',
                'parent' => 'catalogos_root',
                'orden' => 5,
            ],
            [
                'nombre' => 'Oficinas',
                'clave' => 'offices',
                'ruta' => 'catalogos/offices',
                'icono' => 'fa-solid fa-building',
                'parent' => 'catalogos_root',
                'orden' => 6,
            ],
            [
                'nombre' => 'Formas de pago',
                'clave' => 'payment_methods',
                'ruta' => 'catalogos/payment_methods',
                'icono' => 'fa-solid fa-credit-card',
                'parent' => 'catalogos_root',
                'orden' => 7,
            ],
            [
                'nombre' => 'Socios',
                'clave' => 'partners',
                'ruta' => 'catalogos/partners',
                'icono' => 'fa-solid fa-handshake',
                'parent' => 'catalogos_root',
                'orden' => 8,
            ],

            // COBRANZA
            [
                'nombre' => 'Lotificaciones',
                'clave' => 'developments_module',
                'ruta' => 'lotificaciones',
                'icono' => 'fa-solid fa-map-location-dot',
                'parent' => 'cobranza_root',
                'orden' => 1,
            ],
            [
                'nombre' => 'Clientes',
                'clave' => 'clients_module',
                'ruta' => 'clientes',
                'icono' => 'fa-solid fa-address-card',
                'parent' => 'cobranza_root',
                'orden' => 2,
            ],
            [
                'nombre' => 'Vendedores',
                'clave' => 'sellers_module',
                'ruta' => 'vendedores',
                'icono' => 'fa-solid fa-user-tie',
                'parent' => 'cobranza_root',
                'orden' => 3,
            ],
            [
                'nombre' => 'Apartados',
                'clave' => 'reservations_module',
                'ruta' => 'apartados',
                'icono' => 'fa-solid fa-bookmark',
                'parent' => 'cobranza_root',
                'orden' => 4,
            ],
            [
                'nombre' => 'Complementos apartados',
                'clave' => 'reservation_complements_module',
                'ruta' => 'apartados-complementos',
                'icono' => 'fa-solid fa-circle-plus',
                'parent' => 'cobranza_root',
                'orden' => 5,
            ],
            [
                'nombre' => 'Contratos',
                'clave' => 'contracts_module',
                'ruta' => 'contratos',
                'icono' => 'fa-solid fa-file-contract',
                'parent' => 'cobranza_root',
                'orden' => 6,
            ],
            [
                'nombre' => 'Cobros',
                'clave' => 'charges_module',
                'ruta' => 'cobros',
                'icono' => 'fa-solid fa-cash-register',
                'parent' => 'cobranza_root',
                'orden' => 7,
            ],
            [
                'nombre' => 'Calendario de pagos',
                'clave' => 'payment_schedule_module',
                'ruta' => 'calendario-pagos',
                'icono' => 'fa-solid fa-calendar-days',
                'parent' => 'cobranza_root',
                'orden' => 8,
            ],

            // PAGOS
            [
                'nombre' => 'Proveedores',
                'clave' => 'suppliers_module',
                'ruta' => 'proveedores',
                'icono' => 'fa-solid fa-truck',
                'parent' => 'pagos_root',
                'orden' => 1,
            ],
            [
                'nombre' => 'Pagos proveedores',
                'clave' => 'supplier_payments_module',
                'ruta' => 'pagos-proveedores',
                'icono' => 'fa-solid fa-money-check-dollar',
                'parent' => 'pagos_root',
                'orden' => 2,
            ],

            // ACREEDORES
            [
                'nombre' => 'Acreedores',
                'clave' => 'creditors_module',
                'ruta' => 'acreedores',
                'icono' => 'fa-solid fa-users-line',
                'parent' => 'acreedores_root',
                'orden' => 1,
            ],
            [
                'nombre' => 'Pagos acreedores',
                'clave' => 'creditor_payments_module',
                'ruta' => 'pagos-acreedores',
                'icono' => 'fa-solid fa-money-bills',
                'parent' => 'acreedores_root',
                'orden' => 2,
            ],
        ];

        foreach ($items as $item) {
            $parentId = null;

            if (!empty($item['parent'])) {
                $parentId = DB::table('menus')
                    ->where('clave', $item['parent'])
                    ->value('id');
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
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}