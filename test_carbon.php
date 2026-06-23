<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $fechaEmision = "2026-06-23";
    $baseDate = \Carbon\Carbon::parse($fechaEmision);
    echo "Parsed correctly: " . $baseDate->toDateString() . "\n";
    
    // Check missing fields array behavior
    $data = [];
    $fe = $data['fecha_emision'] ?? now()->toDateString();
    echo "Fallback: " . $fe . "\n";
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
