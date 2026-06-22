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
        Schema::table('creditor_vouchers', function (Blueprint $table) {
            $table->decimal('enganche', 12, 2)->default(0)->after('total');
            $table->integer('num_socios')->default(2)->after('enganche');
            $table->date('fecha_inicio')->nullable()->after('meses');
            $table->date('fecha_fin')->nullable()->after('fecha_inicio');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('creditor_vouchers', function (Blueprint $table) {
            $table->dropColumn(['enganche', 'num_socios', 'fecha_inicio', 'fecha_fin']);
        });
    }
};
