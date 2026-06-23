<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$menus = DB::table('menus')->whereIn('clave', [
    'authorizers_module', 
    'bulk_modifications_module', 
    'acreedores_root', 
    'creditors_module', 
    'creditor_payments_module', 
    'creditor_voucher_payments_module'
])->get();

$sql = "-- INSERTS PARA MENÚS NUEVOS\n";
foreach ($menus as $m) {
    $ruta = $m->ruta ? "'".$m->ruta."'" : "NULL";
    $parent = $m->parent_id ? $m->parent_id : "NULL";
    $icono = $m->icono ? "'".$m->icono."'" : "NULL";
    
    $sql .= "INSERT INTO menus (id, nombre, clave, ruta, icono, parent_id, orden, es_menu, status_id, created_at, updated_at) VALUES ({$m->id}, '{$m->nombre}', '{$m->clave}', {$ruta}, {$icono}, {$parent}, {$m->orden}, true, 1, NOW(), NOW()) ON CONFLICT (id) DO NOTHING;\n";
    
    // permissions for admin role
    $sql .= "INSERT INTO role_menu_permissions (role_id, menu_id, can_view, can_create, can_update, can_delete, created_at, updated_at) VALUES (1, {$m->id}, true, true, true, true, NOW(), NOW()) ON CONFLICT DO NOTHING;\n";
}

file_put_contents('menus_sql.sql', $sql);
echo "SQL generated in menus_sql.sql\n";
