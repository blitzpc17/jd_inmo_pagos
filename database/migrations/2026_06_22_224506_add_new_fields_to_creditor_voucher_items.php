<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('creditor_voucher_items', function (Blueprint $table) {
            $table->date('fecha_pago_programada')->nullable()->after('fecha_recibido');
            $table->decimal('cantidad_a_pagar', 12, 2)->default(0)->after('cantidad');
            $table->decimal('interes_pagado', 12, 2)->default(0)->after('cantidad_a_pagar');
            $table->text('observaciones')->nullable()->after('interes_pagado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('creditor_voucher_items', function (Blueprint $table) {
            $table->dropColumn(['fecha_pago_programada', 'cantidad_a_pagar', 'interes_pagado', 'observaciones']);
        });
    }
};
