
<?php

return [

    'processes' => [
        'title' => 'Procesos',
        'table' => 'processes',
        'primary' => 'id',
        'soft_field' => null,
        'fields' => [
            'clave' => ['label' => 'Clave', 'type' => 'text', 'required' => true],
            'nombre' => ['label' => 'Nombre', 'type' => 'text', 'required' => true],
        ],
        'datatable' => ['id', 'clave', 'nombre', 'created_at'],
        'filters' => [],
        'default_order' => ['id', 'desc'],
    ],

    'statuses' => [
        'title' => 'Estados',
        'table' => 'statuses',
        'primary' => 'id',
        'soft_field' => null,
        'fields' => [
            'process_id' => [
                'label' => 'Proceso',
                'type' => 'select',
                'required' => true,
                'source' => [
                    'table' => 'processes',
                    'value' => 'id',
                    'text' => 'nombre',
                    'order_by' => 'nombre',
                ],
            ],
            'clave' => ['label' => 'Clave', 'type' => 'text', 'required' => true],
            'nombre' => ['label' => 'Nombre', 'type' => 'text', 'required' => true],
        ],
        'datatable' => ['id', 'process_name', 'clave', 'nombre', 'created_at'],
        'joins' => [
            ['table' => 'processes', 'alias' => 'p', 'first' => 'statuses.process_id', 'operator' => '=', 'second' => 'p.id'],
        ],
        'selects' => [
            'statuses.*',
            'p.nombre as process_name',
        ],
        'default_order' => ['statuses.id', 'desc'],
    ],

    'positions' => [
        'title' => 'Puestos',
        'table' => 'positions',
        'primary' => 'id',
        'soft_field' => null,
        'fields' => [
            'nombre' => ['label' => 'Nombre', 'type' => 'text', 'required' => true],
            'status_id' => [
                'label' => 'Estado',
                'type' => 'general_status',
                'required' => true,
            ],
        ],
        'datatable' => ['id', 'nombre', 'status_name', 'created_at'],
        'joins' => [
            ['table' => 'statuses', 'alias' => 's', 'first' => 'positions.status_id', 'operator' => '=', 'second' => 's.id'],
        ],
        'selects' => [
            'positions.*',
            's.nombre as status_name',
        ],
        'default_order' => ['positions.id', 'desc'],
    ],

    'roles' => [
        'title' => 'Roles',
        'table' => 'roles',
        'primary' => 'id',
        'soft_field' => null,
        'fields' => [
            'nombre' => ['label' => 'Nombre', 'type' => 'text', 'required' => true],
            'status_id' => [
                'label' => 'Estado',
                'type' => 'general_status',
                'required' => true,
            ],
        ],
        'datatable' => ['id', 'nombre', 'status_name', 'created_at'],
        'joins' => [
            ['table' => 'statuses', 'alias' => 's', 'first' => 'roles.status_id', 'operator' => '=', 'second' => 's.id'],
        ],
        'selects' => [
            'roles.*',
            's.nombre as status_name',
        ],
        'default_order' => ['roles.id', 'desc'],
    ],

    'charge_types' => [
        'title' => 'Tipos de cobro',
        'table' => 'charge_types',
        'primary' => 'id',
        'soft_field' => null,
        'fields' => [
            'nombre' => ['label' => 'Nombre', 'type' => 'text', 'required' => true],
            'status_id' => [
                'label' => 'Estado',
                'type' => 'general_status',
                'required' => true,
            ],
        ],
        'datatable' => ['id', 'nombre', 'status_name', 'created_at'],
        'joins' => [
            ['table' => 'statuses', 'alias' => 's', 'first' => 'charge_types.status_id', 'operator' => '=', 'second' => 's.id'],
        ],
        'selects' => [
            'charge_types.*',
            's.nombre as status_name',
        ],
        'default_order' => ['charge_types.id', 'desc'],
    ],

    'contract_payment_types' => [
        'title' => 'Tipos de pago de contrato',
        'table' => 'contract_payment_types',
        'primary' => 'id',
        'soft_field' => null,
        'fields' => [
            'nombre' => ['label' => 'Nombre', 'type' => 'text', 'required' => true],
            'status_id' => [
                'label' => 'Estado',
                'type' => 'general_status',
                'required' => true,
            ],
        ],
        'datatable' => ['id', 'nombre', 'status_name', 'created_at'],
        'joins' => [
            ['table' => 'statuses', 'alias' => 's', 'first' => 'contract_payment_types.status_id', 'operator' => '=', 'second' => 's.id'],
        ],
        'selects' => [
            'contract_payment_types.*',
            's.nombre as status_name',
        ],
        'default_order' => ['contract_payment_types.id', 'desc'],
    ],

    'offices' => [
        'title' => 'Oficinas',
        'table' => 'offices',
        'primary' => 'id',
        'soft_field' => 'fecha_baja',
        'fields' => [
            'nombre' => ['label' => 'Nombre', 'type' => 'text', 'required' => true],
            'color' => ['label' => 'Color', 'type' => 'text', 'required' => false],
            'status_id' => [
                'label' => 'Estado',
                'type' => 'general_status',
                'required' => true,
            ],
        ],
        'datatable' => ['id', 'nombre', 'color', 'status_name', 'created_at'],
        'joins' => [
            ['table' => 'statuses', 'alias' => 's', 'first' => 'offices.status_id', 'operator' => '=', 'second' => 's.id'],
        ],
        'selects' => [
            'offices.*',
            's.nombre as status_name',
        ],
        'default_order' => ['offices.id', 'desc'],
    ],

    'payment_methods' => [
        'title' => 'Formas de pago',
        'table' => 'payment_methods',
        'primary' => 'id',
        'soft_field' => null,
        'fields' => [
            'nombre' => ['label' => 'Nombre', 'type' => 'text', 'required' => true],
            'office_id' => [
                'label' => 'Oficina',
                'type' => 'select',
                'required' => true,
                'source' => [
                    'table' => 'offices',
                    'value' => 'id',
                    'text' => 'nombre',
                    'order_by' => 'nombre',
                ],
            ],
            'status_id' => [
                'label' => 'Estado',
                'type' => 'general_status',
                'required' => true,
            ],
        ],
        'datatable' => ['id', 'nombre', 'office_name', 'status_name', 'created_at'],
        'joins' => [
            ['table' => 'offices', 'alias' => 'o', 'first' => 'payment_methods.office_id', 'operator' => '=', 'second' => 'o.id'],
            ['table' => 'statuses', 'alias' => 's', 'first' => 'payment_methods.status_id', 'operator' => '=', 'second' => 's.id'],
        ],
        'selects' => [
            'payment_methods.*',
            'o.nombre as office_name',
            's.nombre as status_name',
        ],
        'default_order' => ['payment_methods.id', 'desc'],
    ],

    'partners' => [
        'title' => 'Socios',
        'table' => 'partners',
        'primary' => 'id',
        'soft_field' => 'fecha_baja',
        'fields' => [
            'nombre' => ['label' => 'Nombre', 'type' => 'text', 'required' => true],
            'color' => ['label' => 'Color', 'type' => 'text', 'required' => false],
            'status_id' => [
                'label' => 'Estado',
                'type' => 'general_status',
                'required' => true,
            ],
        ],
        'datatable' => ['id', 'nombre', 'color', 'status_name', 'created_at'],
        'joins' => [
            ['table' => 'statuses', 'alias' => 's', 'first' => 'partners.status_id', 'operator' => '=', 'second' => 's.id'],
        ],
        'selects' => [
            'partners.*',
            's.nombre as status_name',
        ],
        'default_order' => ['partners.id', 'desc'],
    ],

    'menus' => [
        'title' => 'Menú',
        'table' => 'menus',
        'primary' => 'id',
        'soft_field' => null,
        'fields' => [
            'nombre' => ['label' => 'Nombre', 'type' => 'text', 'required' => true],
            'clave' => ['label' => 'Clave', 'type' => 'text', 'required' => true],
            'ruta' => ['label' => 'Ruta', 'type' => 'text', 'required' => false],
            'icono' => ['label' => 'Ícono', 'type' => 'text', 'required' => false],
            'parent_id' => [
                'label' => 'Menú padre',
                'type' => 'select_nullable',
                'required' => false,
                'source' => [
                    'table' => 'menus',
                    'value' => 'id',
                    'text' => 'nombre',
                    'order_by' => 'nombre',
                ],
            ],
            'orden' => ['label' => 'Orden', 'type' => 'number', 'required' => true],
            'es_menu' => ['label' => 'Es menú', 'type' => 'checkbox', 'required' => false],
            'status_id' => [
                'label' => 'Estado',
                'type' => 'general_status',
                'required' => true,
            ],
        ],
        'datatable' => ['id', 'nombre', 'clave', 'ruta', 'parent_name', 'orden', 'status_name'],
        'joins' => [
            ['table' => 'menus', 'alias' => 'pm', 'first' => 'menus.parent_id', 'operator' => '=', 'second' => 'pm.id'],
            ['table' => 'statuses', 'alias' => 's', 'first' => 'menus.status_id', 'operator' => '=', 'second' => 's.id'],
        ],
        'selects' => [
            'menus.*',
            'pm.nombre as parent_name',
            's.nombre as status_name',
        ],
        'default_order' => ['menus.orden', 'asc'],
    ],

];