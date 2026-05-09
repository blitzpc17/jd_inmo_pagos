<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ContractPaymentTypeSeeder extends Seeder
{
    public function run(): void
    {
        $activeId = DB::table('statuses as s')
            ->join('processes as p', 'p.id', '=', 's.process_id')
            ->where('p.clave', 'GENERAL')
            ->where('s.clave', 'ACTIVE')
            ->value('s.id');

        $rows = [
            'Contado',
            'Crédito',
        ];

        foreach ($rows as $nombre) {
            DB::table('contract_payment_types')->updateOrInsert(
                ['nombre' => $nombre],
                [
                    'status_id' => $activeId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}