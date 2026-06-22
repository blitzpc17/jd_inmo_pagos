<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add action column to modification_request_items
        if (!Schema::hasColumn('modification_request_items', 'action')) {
            Schema::table('modification_request_items', function (Blueprint $table) {
                $table->string('action')->default('MODIFICAR');
            });
        }

        // 2. Add authorizers_module to menus table
        $activeId = DB::table('statuses as s')
            ->join('processes as p', 'p.id', '=', 's.process_id')
            ->where('p.clave', 'GENERAL')
            ->where('s.clave', 'ACTIVE')
            ->value('s.id');

        $parentId = DB::table('menus')->where('clave', 'cobranza_root')->value('id');

        if ($parentId) {
            // Check if menu already exists to prevent duplicate key violations
            $existingMenu = DB::table('menus')->where('clave', 'authorizers_module')->first();
            if (!$existingMenu) {
                $menuId = DB::table('menus')->insertGetId([
                    'nombre' => 'Autorizantes',
                    'clave' => 'authorizers_module',
                    'ruta' => 'autorizantes',
                    'icono' => 'fa-solid fa-user-shield',
                    'parent_id' => $parentId,
                    'orden' => 9,
                    'es_menu' => true,
                    'status_id' => $activeId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Give permissions to Admin role
                $adminRoleId = DB::table('roles')->where('nombre', 'Admin')->value('id');
                if ($adminRoleId) {
                    DB::table('role_menu_permissions')->updateOrInsert(
                        [
                            'role_id' => $adminRoleId,
                            'menu_id' => $menuId,
                        ],
                        [
                            'can_view' => true,
                            'can_create' => true,
                            'can_update' => true,
                            'can_delete' => true,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                }
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('modification_request_items', 'action')) {
            Schema::table('modification_request_items', function (Blueprint $table) {
                $table->dropColumn('action');
            });
        }

        $menuId = DB::table('menus')->where('clave', 'authorizers_module')->value('id');
        if ($menuId) {
            DB::table('role_menu_permissions')->where('menu_id', $menuId)->delete();
            DB::table('user_menu_permissions')->where('menu_id', $menuId)->delete();
            DB::table('menus')->where('id', $menuId)->delete();
        }
    }
};
