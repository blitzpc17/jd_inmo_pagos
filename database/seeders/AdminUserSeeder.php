<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $activeId = DB::table('statuses')
            ->join('processes', 'processes.id', '=', 'statuses.process_id')
            ->where('processes.clave', 'GENERAL')
            ->where('statuses.clave', 'ACTIVE')
            ->value('statuses.id');

        $positionId = DB::table('positions')->where('nombre', 'Administrador')->value('id');
        $roleId = DB::table('roles')->where('nombre', 'Admin')->value('id');

        DB::table('personal')->updateOrInsert(
            ['email' => 'admin@admin.com'],
            [
                'nombres' => 'Administrador',
                'apellidos' => 'General',
                'telefono' => '0000000000',
                'direccion' => 'Sistema',
                'position_id' => $positionId,
                'status_id' => $activeId,
                'fecha_baja' => null,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        $personalId = DB::table('personal')->where('email', 'admin@admin.com')->value('id');

        DB::table('users')->updateOrInsert(
            ['alias' => 'admin'],
            [
                'password' => Hash::make('Admin123*'),
                'personal_id' => $personalId,
                'role_id' => $roleId,
                'status_id' => $activeId,
                'remember_token' => null,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }
}