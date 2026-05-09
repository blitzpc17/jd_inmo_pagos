<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleMenuPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $adminRoleId = DB::table('roles')->where('nombre', 'Admin')->value('id');

        $menus = DB::table('menus')->pluck('id');

        foreach ($menus as $menuId) {
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
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }
}