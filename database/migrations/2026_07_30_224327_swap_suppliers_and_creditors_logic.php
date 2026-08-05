<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Drop foreign keys
        Schema::table('creditor_vouchers', function (Blueprint $table) {
            $table->dropForeign('fk_creditor_vouchers_creditor');
        });
        Schema::table('creditor_voucher_items', function (Blueprint $table) {
            $table->dropForeign('fk_creditor_voucher_items_voucher');
        });
        Schema::table('creditor_payment_schedules', function (Blueprint $table) {
            $table->dropForeign('creditor_payment_schedules_creditor_voucher_id_fkey');
        });
        Schema::table('supplier_payments', function (Blueprint $table) {
            $table->dropForeign('fk_supplier_payments_supplier');
        });
        Schema::table('supplier_payment_concepts', function (Blueprint $table) {
            $table->dropForeign('fk_supplier_payment_concepts_payment');
        });

        // 2. Rename tables using temp names
        Schema::rename('suppliers', 'temp_suppliers');
        Schema::rename('supplier_payments', 'temp_supplier_payments');
        Schema::rename('supplier_payment_concepts', 'temp_supplier_payment_concepts');

        Schema::rename('creditors', 'suppliers');
        Schema::rename('creditor_vouchers', 'supplier_vouchers');
        Schema::rename('creditor_voucher_items', 'supplier_voucher_items');
        Schema::rename('creditor_payment_schedules', 'supplier_payment_schedules');

        Schema::rename('temp_suppliers', 'creditors');
        Schema::rename('temp_supplier_payments', 'creditor_payments');
        Schema::rename('temp_supplier_payment_concepts', 'creditor_payment_concepts');

        // 3. Rename columns
        Schema::table('creditor_payments', function (Blueprint $table) {
            $table->renameColumn('supplier_id', 'creditor_id');
        });
        Schema::table('creditor_payment_concepts', function (Blueprint $table) {
            $table->renameColumn('supplier_payment_id', 'creditor_payment_id');
        });

        Schema::table('supplier_vouchers', function (Blueprint $table) {
            $table->renameColumn('creditor_id', 'supplier_id');
        });
        Schema::table('supplier_voucher_items', function (Blueprint $table) {
            $table->renameColumn('creditor_voucher_id', 'supplier_voucher_id');
        });
        Schema::table('supplier_payment_schedules', function (Blueprint $table) {
            $table->renameColumn('creditor_voucher_id', 'supplier_voucher_id');
        });

        // 4. Recreate foreign keys
        Schema::table('creditor_payments', function (Blueprint $table) {
            $table->foreign('creditor_id', 'fk_creditor_payments_creditor')->references('id')->on('creditors');
        });
        Schema::table('creditor_payment_concepts', function (Blueprint $table) {
            $table->foreign('creditor_payment_id', 'fk_creditor_payment_concepts_payment')->references('id')->on('creditor_payments');
        });

        Schema::table('supplier_vouchers', function (Blueprint $table) {
            $table->foreign('supplier_id', 'fk_supplier_vouchers_supplier')->references('id')->on('suppliers');
        });
        Schema::table('supplier_voucher_items', function (Blueprint $table) {
            $table->foreign('supplier_voucher_id', 'fk_supplier_voucher_items_voucher')->references('id')->on('supplier_vouchers');
        });
        Schema::table('supplier_payment_schedules', function (Blueprint $table) {
            $table->foreign('supplier_voucher_id', 'supplier_payment_schedules_supplier_voucher_id_fkey')->references('id')->on('supplier_vouchers');
        });

        // 5. Update Menus Table
        // Add temporary suffix to avoid unique constraint violations
        DB::table('menus')->whereIn('id', [28, 29, 30, 31, 32])->update([
            'clave' => DB::raw("CONCAT(clave, '_temp')")
        ]);

        // Old Suppliers (now Creditors)
        DB::table('menus')->where('id', 28)->update([
            'nombre' => 'Acreedores',
            'clave' => 'creditors_module',
            'ruta' => 'acreedores',
            'parent_id' => 6 // acreedores_root
        ]);
        DB::table('menus')->where('id', 29)->update([
            'nombre' => 'Pagos acreedores',
            'clave' => 'creditor_payments_module',
            'ruta' => 'pagos-acreedores',
            'parent_id' => 6 // acreedores_root
        ]);

        // Old Creditors (now Suppliers)
        DB::table('menus')->where('id', 30)->update([
            'nombre' => 'Proveedores',
            'clave' => 'suppliers_module',
            'ruta' => 'proveedores',
            'parent_id' => 5 // pagos_root
        ]);
        DB::table('menus')->where('id', 31)->update([
            'nombre' => 'Boletas proveedores',
            'clave' => 'supplier_vouchers_module',
            'ruta' => 'pagos-proveedores',
            'parent_id' => 5 // pagos_root
        ]);
        DB::table('menus')->where('id', 32)->update([
            'nombre' => 'Pagos boletas proveedores',
            'clave' => 'supplier_voucher_payments_module',
            'ruta' => 'abonos-proveedores',
            'parent_id' => 5 // pagos_root
        ]);

        // 6. Actualizar los prefijos históricos de las boletas generadas
        DB::table('supplier_vouchers')
            ->where('numero_referencia', 'like', 'BOL-ACR-%')
            ->update(['numero_referencia' => DB::raw("REPLACE(numero_referencia, 'BOL-ACR-', 'BOL-PROV-')")]);

        DB::table('creditor_payments')
            ->where('numero_referencia', 'like', 'BOL-PROV-%')
            ->update(['numero_referencia' => DB::raw("REPLACE(numero_referencia, 'BOL-PROV-', 'BOL-ACR-')")]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Drop the new foreign keys
        Schema::table('creditor_payments', function (Blueprint $table) {
            $table->dropForeign('fk_creditor_payments_creditor');
        });
        Schema::table('creditor_payment_concepts', function (Blueprint $table) {
            $table->dropForeign('fk_creditor_payment_concepts_payment');
        });

        Schema::table('supplier_vouchers', function (Blueprint $table) {
            $table->dropForeign('fk_supplier_vouchers_supplier');
        });
        Schema::table('supplier_voucher_items', function (Blueprint $table) {
            $table->dropForeign('fk_supplier_voucher_items_voucher');
        });
        Schema::table('supplier_payment_schedules', function (Blueprint $table) {
            $table->dropForeign('supplier_payment_schedules_supplier_voucher_id_fkey');
        });

        // 2. Rename columns back
        Schema::table('creditor_payments', function (Blueprint $table) {
            $table->renameColumn('creditor_id', 'supplier_id');
        });
        Schema::table('creditor_payment_concepts', function (Blueprint $table) {
            $table->renameColumn('creditor_payment_id', 'supplier_payment_id');
        });

        Schema::table('supplier_vouchers', function (Blueprint $table) {
            $table->renameColumn('supplier_id', 'creditor_id');
        });
        Schema::table('supplier_voucher_items', function (Blueprint $table) {
            $table->renameColumn('supplier_voucher_id', 'creditor_voucher_id');
        });
        Schema::table('supplier_payment_schedules', function (Blueprint $table) {
            $table->renameColumn('supplier_voucher_id', 'creditor_voucher_id');
        });

        // 3. Rename tables back (using temp)
        Schema::rename('creditors', 'temp_suppliers');
        Schema::rename('creditor_payments', 'temp_supplier_payments');
        Schema::rename('creditor_payment_concepts', 'temp_supplier_payment_concepts');

        Schema::rename('suppliers', 'creditors');
        Schema::rename('supplier_vouchers', 'creditor_vouchers');
        Schema::rename('supplier_voucher_items', 'creditor_voucher_items');
        Schema::rename('supplier_payment_schedules', 'creditor_payment_schedules');

        Schema::rename('temp_suppliers', 'suppliers');
        Schema::rename('temp_supplier_payments', 'supplier_payments');
        Schema::rename('temp_supplier_payment_concepts', 'supplier_payment_concepts');

        // 4. Recreate old foreign keys
        Schema::table('creditor_vouchers', function (Blueprint $table) {
            $table->foreign('creditor_id', 'fk_creditor_vouchers_creditor')->references('id')->on('creditors');
        });
        Schema::table('creditor_voucher_items', function (Blueprint $table) {
            $table->foreign('creditor_voucher_id', 'fk_creditor_voucher_items_voucher')->references('id')->on('creditor_vouchers');
        });
        Schema::table('creditor_payment_schedules', function (Blueprint $table) {
            $table->foreign('creditor_voucher_id', 'creditor_payment_schedules_creditor_voucher_id_fkey')->references('id')->on('creditor_vouchers');
        });
        Schema::table('supplier_payments', function (Blueprint $table) {
            $table->foreign('supplier_id', 'fk_supplier_payments_supplier')->references('id')->on('suppliers');
        });
        Schema::table('supplier_payment_concepts', function (Blueprint $table) {
            $table->foreign('supplier_payment_id', 'fk_supplier_payment_concepts_payment')->references('id')->on('supplier_payments');
        });
    }
};
