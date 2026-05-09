<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ProcessSeeder::class,
            GeneralStatusSeeder::class,
            PositionSeeder::class,
            RoleSeeder::class,
            ChargeTypeSeeder::class,
            ContractPaymentTypeSeeder::class,
            MenuSeeder::class,
            AdminUserSeeder::class,
            RoleMenuPermissionSeeder::class,
        ]);
    }
}