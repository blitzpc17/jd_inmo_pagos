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
            $table->json('partner_percentages')->nullable()->after('num_socios');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('creditor_vouchers', function (Blueprint $table) {
            $table->dropColumn('partner_percentages');
        });
    }
};
