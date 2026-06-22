<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

DB::table('contracts')->where('id', 16)->update([
    'status_id' => 12, // VIGENTE
    'updated_at' => now(),
]);

DB::table('payment_schedules')->where('contract_id', 16)->update([
    'status' => 'PENDIENTE'
]);

$collectionService = app(\App\Services\ContractCollectionService::class);
$collectionService->refreshScheduleStatuses(16);

dump("Reactivated 16");
