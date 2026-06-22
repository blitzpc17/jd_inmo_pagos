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
        Schema::table('supplier_payments', function (Blueprint $table) {
            $table->unsignedBigInteger('development_id')->nullable()->after('supplier_id');
            $table->integer('plazo')->nullable()->after('development_id');
            $table->date('fecha_inicio')->nullable()->after('plazo');
            $table->date('fecha_fin')->nullable()->after('fecha_inicio');
            $table->decimal('enganche', 15, 2)->default(0)->after('fecha_fin');
        });

        Schema::table('supplier_payment_concepts', function (Blueprint $table) {
            $table->date('fecha')->nullable()->after('concepto');
            $table->unsignedBigInteger('payment_method_id')->nullable()->after('importe');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('supplier_payment_concepts', function (Blueprint $table) {
            $table->dropColumn(['fecha', 'payment_method_id']);
        });

        Schema::table('supplier_payments', function (Blueprint $table) {
            $table->dropColumn(['development_id', 'plazo', 'fecha_inicio', 'fecha_fin', 'enganche']);
        });
    }
};
