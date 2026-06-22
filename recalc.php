<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$vouchers = DB::table('creditor_vouchers')->get();
foreach ($vouchers as $v) {
    $tp = DB::table('creditor_voucher_items')->where('creditor_voucher_id', $v->id)->sum('cantidad');
    DB::table('creditor_vouchers')->where('id', $v->id)->update([
        'saldo_pendiente' => max(0, $v->total - $v->enganche - $tp)
    ]);
}
echo "Done!\n";
