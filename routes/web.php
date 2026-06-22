<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PermissionController;

use App\Http\Controllers\ClientController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\SellerController;
use App\Http\Controllers\SupplierController;

use App\Http\Controllers\DevelopmentController;
use App\Http\Controllers\DevelopmentLotController;

use App\Http\Controllers\ReservationController;

use App\Http\Controllers\ContractController;
use App\Http\Controllers\ReservationComplementController;

use App\Http\Controllers\ChargeController;

use App\Http\Controllers\DevelopmentAssignmentController;

use App\Http\Controllers\SupplierPaymentController;

use App\Http\Controllers\CreditorController;
use App\Http\Controllers\CreditorVoucherController;
use App\Http\Controllers\CreditorVoucherPaymentController;

use App\Http\Controllers\DevelopmentSummaryController;

use App\Http\Controllers\DevelopmentCollectionReportController;

use App\Http\Controllers\MonthlyCollectionReportController;
use App\Http\Controllers\CollectionEmailSettingsController;
use App\Http\Controllers\PaymentScheduleController;

Route::get('/', fn () => redirect()->route('login'));

Route::get('/login', [LoginController::class, 'create'])->name('login');
Route::post('/login', [LoginController::class, 'store'])->name('login.store');
Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

Route::middleware(['auth.custom', 'share.menu'])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/stats', [DashboardController::class, 'stats'])->name('dashboard.stats');

    Route::prefix('catalogos')->name('catalogos.')->group(function () {
        Route::get('/{catalog}', [CatalogController::class, 'index'])->name('index');
        Route::get('/{catalog}/datatable', [CatalogController::class, 'datatable'])->name('datatable');
        Route::post('/{catalog}', [CatalogController::class, 'store'])->name('store');
        Route::get('/{catalog}/{id}', [CatalogController::class, 'show'])->name('show');
        Route::put('/{catalog}/{id}', [CatalogController::class, 'update'])->name('update');
        Route::delete('/{catalog}/{id}', [CatalogController::class, 'destroy'])->name('destroy');
        Route::get('/{catalog}/select/options', [CatalogController::class, 'selectOptions'])->name('select.options');
    });

    Route::prefix('usuarios')->name('usuarios.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::get('/datatable', [UserController::class, 'datatable'])->name('datatable');
        Route::get('/create-options', [UserController::class, 'createOptions'])->name('create-options');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::get('/{id}', [UserController::class, 'show'])->name('show');
        Route::put('/{id}', [UserController::class, 'update'])->name('update');
        Route::delete('/{id}', [UserController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('permisos')->name('permisos.')->group(function () {
        Route::get('/', [PermissionController::class, 'index'])->name('index');

        Route::get('/roles/select', [PermissionController::class, 'rolesSelect'])->name('roles.select');
        Route::get('/usuarios/select', [PermissionController::class, 'usersSelect'])->name('users.select');

        Route::get('/roles/{roleId}/tree', [PermissionController::class, 'roleTree'])->name('roles.tree');
        Route::post('/roles/{roleId}/save', [PermissionController::class, 'saveRolePermissions'])->name('roles.save');

        Route::get('/usuarios/{userId}/tree', [PermissionController::class, 'userTree'])->name('users.tree');
        Route::post('/usuarios/{userId}/save', [PermissionController::class, 'saveUserPermissions'])->name('users.save');
    });

    Route::prefix('clientes')->name('clientes.')->group(function () {
        Route::get('/', [ClientController::class, 'index'])->name('index');
        Route::get('/datatable', [ClientController::class, 'datatable'])->name('datatable');
        Route::get('/options', [ClientController::class, 'options'])->name('options');
        Route::post('/', [ClientController::class, 'store'])->name('store');
        Route::get('/{id}', [ClientController::class, 'show'])->name('show');
        Route::put('/{id}', [ClientController::class, 'update'])->name('update');
        Route::delete('/{id}', [ClientController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('empleados')->name('empleados.')->group(function () {
        Route::get('/', [EmployeeController::class, 'index'])->name('index');
        Route::get('/datatable', [EmployeeController::class, 'datatable'])->name('datatable');
        Route::get('/options', [EmployeeController::class, 'options'])->name('options');
        Route::post('/', [EmployeeController::class, 'store'])->name('store');
        Route::get('/{id}', [EmployeeController::class, 'show'])->name('show');
        Route::put('/{id}', [EmployeeController::class, 'update'])->name('update');
        Route::delete('/{id}', [EmployeeController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('vendedores')->name('vendedores.')->group(function () {
        Route::get('/', [SellerController::class, 'index'])->name('index');
        Route::get('/datatable', [SellerController::class, 'datatable'])->name('datatable');
        Route::get('/options', [SellerController::class, 'options'])->name('options');
        Route::post('/', [SellerController::class, 'store'])->name('store');
        Route::get('/{id}', [SellerController::class, 'show'])->name('show');
        Route::put('/{id}', [SellerController::class, 'update'])->name('update');
        Route::delete('/{id}', [SellerController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('proveedores')->name('proveedores.')->group(function () {
        Route::get('/', [SupplierController::class, 'index'])->name('index');
        Route::get('/datatable', [SupplierController::class, 'datatable'])->name('datatable');
        Route::get('/options', [SupplierController::class, 'options'])->name('options');
        Route::post('/', [SupplierController::class, 'store'])->name('store');
        Route::get('/{id}', [SupplierController::class, 'show'])->name('show');
        Route::put('/{id}', [SupplierController::class, 'update'])->name('update');
        Route::delete('/{id}', [SupplierController::class, 'destroy'])->name('destroy');
    });


    Route::prefix('lotificaciones')->name('lotificaciones.')->group(function () {

    // REPORTES / RESUMEN
    Route::get('/resumen-general', [DevelopmentSummaryController::class, 'index'])
        ->name('summary.index');

    Route::get('/resumen-general/data', [DevelopmentSummaryController::class, 'data'])
        ->name('summary.data');

    Route::get('/resumen-general/export', [DevelopmentSummaryController::class, 'export'])
        ->name('summary.export');

    // REPORTES / COBRANZA
    Route::get('/reporte-cobranza', [DevelopmentCollectionReportController::class, 'index'])
        ->name('collection_report.index');

    Route::get('/reporte-cobranza/data', [DevelopmentCollectionReportController::class, 'data'])
        ->name('collection_report.data');

    Route::get('/reporte-cobranza/export', [DevelopmentCollectionReportController::class, 'export'])
        ->name('collection_report.export');

    //REPORTES / COBROS MENSUALES
    Route::get('/reporte-cobros-mensuales', [MonthlyCollectionReportController::class, 'index'])
            ->name('monthly_collection_report.index');

    Route::get('/reporte-cobros-mensuales/data', [MonthlyCollectionReportController::class, 'data'])
            ->name('monthly_collection_report.data');

    Route::get('/reporte-cobros-mensuales/export', [MonthlyCollectionReportController::class, 'export'])
            ->name('monthly_collection_report.export');
            




    // CRUD LOTIFICACIONES
    Route::get('/', [DevelopmentController::class, 'index'])->name('index');
    Route::get('/datatable', [DevelopmentController::class, 'datatable'])->name('datatable');
    Route::get('/options', [DevelopmentController::class, 'options'])->name('options');
    Route::post('/', [DevelopmentController::class, 'store'])->name('store');

    // DETALLE / LOTES EN INGLÉS
    Route::get('/{development}/lots/options', [DevelopmentLotController::class, 'options'])
        ->whereNumber('development')
        ->name('lots.options.en');

    Route::get('/{development}/detalle', [DevelopmentLotController::class, 'index'])
        ->whereNumber('development')
        ->name('lots.index.en');

    Route::get('/{development}/lots/datatable', [DevelopmentLotController::class, 'datatable'])
        ->whereNumber('development')
        ->name('lots.datatable.en');

    // MISMA URL, DISTINTO MÉTODO
    Route::get('/{development}/lots/{lotId}', [DevelopmentLotController::class, 'show'])
        ->whereNumber('development')
        ->whereNumber('lotId')
        ->name('lots.show.en');

    Route::put('/{development}/lots/{lotId}', [DevelopmentLotController::class, 'update'])
        ->whereNumber('development')
        ->whereNumber('lotId')
        ->name('lots.update.en');

    Route::delete('/{development}/lots/{lotId}', [DevelopmentLotController::class, 'destroy'])
        ->whereNumber('development')
        ->whereNumber('lotId')
        ->name('lots.destroy.en');

    Route::post('/{development}/lots', [DevelopmentLotController::class, 'store'])
        ->whereNumber('development')
        ->name('lots.store.en');

    Route::post('/{development}/lots/generate', [DevelopmentLotController::class, 'generate'])
        ->whereNumber('development')
        ->name('lots.generate.en');

    Route::post('/{development}/lots/bulk-update', [DevelopmentLotController::class, 'bulkUpdate'])
        ->whereNumber('development')
        ->name('lots.bulk_update.en');

    // BLOQUE VIEJO / EN ESPAÑOL
    Route::prefix('{developmentId}/lotes')->name('lots.')->group(function () {
        Route::get('/', [DevelopmentLotController::class, 'index'])->name('index');
        Route::get('/datatable', [DevelopmentLotController::class, 'datatable'])->name('datatable');
        Route::get('/options', [DevelopmentLotController::class, 'options'])->name('options');

        Route::post('/generate', [DevelopmentLotController::class, 'generate'])->name('generate');
        Route::post('/generate-bulk', [DevelopmentLotController::class, 'generateBulk'])->name('generate.bulk');

        Route::get('/{lotId}', [DevelopmentLotController::class, 'show'])->name('show');
        Route::put('/{lotId}', [DevelopmentLotController::class, 'update'])->name('update');
        Route::post('/bulk-update', [DevelopmentLotController::class, 'bulkUpdate'])->name('bulk-update');
        Route::delete('/{lotId}', [DevelopmentLotController::class, 'destroy'])->name('destroy');
    });

    // CRUD DINÁMICO AL FINAL
    Route::get('/{id}', [DevelopmentController::class, 'show'])
        ->whereNumber('id')
        ->name('show');

    Route::put('/{id}', [DevelopmentController::class, 'update'])
        ->whereNumber('id')
        ->name('update');

    Route::delete('/{id}', [DevelopmentController::class, 'destroy'])
        ->whereNumber('id')
        ->name('destroy');
   
});


    Route::prefix('apartados')->name('apartados.')->group(function () {
        Route::get('/', [ReservationController::class, 'index'])->name('index');
        Route::get('/datatable', [ReservationController::class, 'datatable'])->name('datatable');
        Route::get('/options', [ReservationController::class, 'options'])->name('options');
        Route::get('/development/{developmentId}/lots', [ReservationController::class, 'developmentLots'])->name('development.lots');
        Route::post('/', [ReservationController::class, 'store'])->name('store');
        Route::get('/{id}', [ReservationController::class, 'show'])->name('show');
        Route::delete('/{id}', [ReservationController::class, 'destroy'])->name('destroy');

        Route::post('/{id}/close-status', [ReservationController::class, 'closeStatus'])->name('close-status');
    });

    Route::prefix('apartados-complementos')->name('apartados_complementos.')->group(function () {
        Route::get('/', [ReservationComplementController::class, 'index'])->name('index');
        Route::get('/datatable', [ReservationComplementController::class, 'datatable'])->name('datatable');
        Route::get('/options', [ReservationComplementController::class, 'options'])->name('options');
        Route::post('/', [ReservationComplementController::class, 'store'])->name('store');
    });


   Route::prefix('contratos')->name('contratos.')->group(function () {
    Route::get('/', [ContractController::class, 'index'])->name('index');
    Route::get('/datatable', [ContractController::class, 'datatable'])->name('datatable');
    Route::get('/options', [ContractController::class, 'options'])->name('options');

    Route::get('/client/{clientId}/reservations', [ContractController::class, 'clientReservations'])->name('client.reservations');
    Route::get('/client/{clientId}/developments', [ContractController::class, 'clientDevelopments'])->name('client.developments');

    Route::get('/development/{developmentId}/lots', [ContractController::class, 'developmentLots'])->name('development.lots');
    Route::get('/development/{developmentId}/offices', [ContractController::class, 'developmentOffices'])->name('development.offices');

    Route::get('/office/{officeId}/payment-methods', [ContractController::class, 'officePaymentMethods'])->name('office.payment-methods');

    Route::get('/reservation/{reservationId}', [ContractController::class, 'reservationData'])->name('reservation.data');
    Route::get('/seller/{sellerId}', [ContractController::class, 'sellerData'])->name('seller.data');

    Route::post('/', [ContractController::class, 'store'])->name('store');

    Route::get('/{id}/document-data', [ContractController::class, 'documentData'])->name('document-data');
    Route::post('/{id}/document-data', [ContractController::class, 'saveDocumentData'])->name('document-data.save');

    Route::get('/{id}/documento', [ContractController::class, 'document'])->name('document');

    Route::get('/{id}', [ContractController::class, 'show'])->name('show');
    Route::put('/{id}', [ContractController::class, 'update'])->name('update');
});

 Route::prefix('cobros')
    ->name('cobros.')
    ->middleware(['auth.custom', 'share.menu'])
    ->group(function () {
        Route::get('/', [ChargeController::class, 'index'])->name('index');

        Route::get('/options', [ChargeController::class, 'options'])->name('options');
        Route::get('/clients', [ChargeController::class, 'clients'])->name('clients');

        Route::get('/client/{clientId}/contracts', [ChargeController::class, 'clientContracts'])->name('client.contracts');

        Route::get('/contract/{contractId}/preview', [ChargeController::class, 'preview'])->name('contract.preview');

        Route::get('/contract/{contractId}/offices', [ChargeController::class, 'contractOffices'])->name('contract.offices');

        Route::get('/contract/{contractId}/office/{officeId}/payment-methods', [ChargeController::class, 'officePaymentMethods'])
            ->name('contract.office.payment-methods');

        Route::post('/contract/{contractId}/charge', [ChargeController::class, 'store'])->name('store');

        Route::get('/group/{paymentGroupUuid}', [ChargeController::class, 'paymentGroup'])->name('payment.group');

        Route::get('/schedule/{scheduleId}/charges', [ChargeController::class, 'scheduleCharges'])->name('schedule.charges');

        Route::get('/{id}/receipt', [ChargeController::class, 'receipt'])->name('receipt');
    });
   


    // =====================================================
    // ASIGNACIÓN DE LOTIFICACIONES POR ROL / USUARIO
    // =====================================================
    Route::prefix('asignacion-lotificaciones')
        ->name('development-assignments.')
        ->group(function () {
            Route::get('/', [DevelopmentAssignmentController::class, 'index'])
                ->name('index');

            Route::get('/options', [DevelopmentAssignmentController::class, 'options'])
                ->name('options');

            Route::get('/developments', [DevelopmentAssignmentController::class, 'developments'])
                ->name('developments');

            Route::get('/role/{roleId}', [DevelopmentAssignmentController::class, 'roleAssignments'])
                ->name('role.assignments');

            Route::post('/role/{roleId}', [DevelopmentAssignmentController::class, 'saveRoleAssignments'])
                ->name('role.save');

            Route::get('/user/{userId}', [DevelopmentAssignmentController::class, 'userAssignments'])
                ->name('user.assignments');

            Route::post('/user/{userId}', [DevelopmentAssignmentController::class, 'saveUserAssignments'])
                ->name('user.save');
        });


    Route::prefix('calendario-pagos')->name('calendario_pagos.')->group(function () {
        Route::get('/', [PaymentScheduleController::class, 'index'])->name('index');
        Route::get('/options', [PaymentScheduleController::class, 'options'])->name('options');
        Route::get('/contract/{contractId}', [PaymentScheduleController::class, 'byContract'])->name('by-contract');
    });


    Route::prefix('pagos-proveedores')->name('pagos_proveedores.')->group(function () {
        Route::get('/', [SupplierPaymentController::class, 'index'])->name('index');
        Route::get('/datatable', [SupplierPaymentController::class, 'datatable'])->name('datatable');
        Route::get('/options', [SupplierPaymentController::class, 'options'])->name('options');
        Route::post('/', [SupplierPaymentController::class, 'store'])->name('store');
        Route::get('/{id}', [SupplierPaymentController::class, 'show'])->name('show');
    });


    Route::prefix('acreedores')->name('acreedores.')->group(function () {
        Route::get('/', [CreditorController::class, 'index'])->name('index');
        Route::get('/datatable', [CreditorController::class, 'datatable'])->name('datatable');
        Route::post('/', [CreditorController::class, 'store'])->name('store');
        Route::get('/{id}', [CreditorController::class, 'show'])->name('show');
        Route::put('/{id}', [CreditorController::class, 'update'])->name('update');
        Route::delete('/{id}', [CreditorController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('pagos-acreedores')->name('pagos_acreedores.')->group(function () {
        Route::get('/', [CreditorVoucherController::class, 'index'])->name('index');
        Route::get('/datatable', [CreditorVoucherController::class, 'datatable'])->name('datatable');
        Route::get('/options', [CreditorVoucherController::class, 'options'])->name('options');
        Route::post('/', [CreditorVoucherController::class, 'store'])->name('store');
        Route::get('/{id}', [CreditorVoucherController::class, 'show'])->name('show');
    });

    Route::prefix('abonos-acreedores')->name('abonos_acreedores.')->group(function () {
        Route::get('/', [CreditorVoucherPaymentController::class, 'index'])->name('index');
        Route::get('/options', [CreditorVoucherPaymentController::class, 'options'])->name('options');
        Route::get('/creditor/{creditorId}/vouchers', [CreditorVoucherPaymentController::class, 'creditorVouchers'])->name('creditor.vouchers');
        Route::get('/voucher/{voucherId}/summary', [CreditorVoucherPaymentController::class, 'voucherSummary'])->name('voucher.summary');
        Route::post('/', [CreditorVoucherPaymentController::class, 'store'])->name('store');
    });


    Route::get('/cobros/{id}/recibo', [ChargeController::class, 'receipt'])->name('cobros.receipt');
    Route::get('/abonos-acreedores/recibo/{itemId}', [CreditorVoucherPaymentController::class, 'receipt'])->name('abonos_acreedores.receipt');
    Route::get('/pagos-proveedores/{id}/recibo', [SupplierPaymentController::class, 'receipt'])->name('pagos_proveedores.receipt');


    Route::prefix('configuracion-cobranza')
    ->name('collection-email-settings.')
    ->middleware(['auth.custom', 'share.menu'])
    ->group(function () {
        Route::get('/correos', [CollectionEmailSettingsController::class, 'index'])->name('index');
        Route::post('/correos', [CollectionEmailSettingsController::class, 'update'])->name('update');
    });

    // =====================================================
    // MODIFICACIONES MASIVAS Y AUTORIZACIONES (SOLICITUDES)
    // =====================================================
    Route::prefix('modificaciones-masivas')
        ->name('bulk-modifications.')
        ->middleware(['auth.custom', 'share.menu'])
        ->group(function () {
            Route::get('/', [\App\Http\Controllers\BulkModificationController::class, 'index'])->name('index');
            Route::get('/datatable', [\App\Http\Controllers\BulkModificationController::class, 'datatable'])->name('datatable');
            Route::post('/', [\App\Http\Controllers\BulkModificationController::class, 'store'])->name('store');
            Route::get('/options', [\App\Http\Controllers\BulkModificationController::class, 'options'])->name('options');
            Route::get('/search-records', [\App\Http\Controllers\BulkModificationController::class, 'searchRecords'])->name('search-records');
            Route::get('/record-details', [\App\Http\Controllers\BulkModificationController::class, 'recordDetails'])->name('record-details');
            Route::get('/{id}', [\App\Http\Controllers\BulkModificationController::class, 'show'])->whereNumber('id')->name('show');
            Route::post('/{id}/approve', [\App\Http\Controllers\BulkModificationController::class, 'approve'])->whereNumber('id')->name('approve');
            Route::post('/{id}/reject', [\App\Http\Controllers\BulkModificationController::class, 'reject'])->whereNumber('id')->name('reject');

            // Cascading Search Endpoints
            Route::get('/clients', [\App\Http\Controllers\BulkModificationController::class, 'getClients'])->name('clients');
            Route::get('/client/{clientId}/contracts', [\App\Http\Controllers\BulkModificationController::class, 'getClientContracts'])->whereNumber('clientId')->name('client-contracts');
            Route::get('/contract/{contractId}/charges', [\App\Http\Controllers\BulkModificationController::class, 'getContractCharges'])->whereNumber('contractId')->name('contract-charges');
            Route::get('/client/{clientId}/reservations', [\App\Http\Controllers\BulkModificationController::class, 'getClientReservations'])->whereNumber('clientId')->name('client-reservations');
        });

    // =====================================================
    // GESTIÓN DE AUTORIZANTES (MODULO STANDALONE)
    // =====================================================
    Route::prefix('autorizantes')
        ->name('authorizers.')
        ->middleware(['auth.custom', 'share.menu'])
        ->group(function () {
            Route::get('/', [\App\Http\Controllers\AuthorizerController::class, 'index'])->name('index');
            Route::get('/datatable', [\App\Http\Controllers\AuthorizerController::class, 'datatable'])->name('datatable');
            Route::post('/', [\App\Http\Controllers\AuthorizerController::class, 'store'])->name('store');
            Route::delete('/{id}', [\App\Http\Controllers\AuthorizerController::class, 'destroy'])->whereNumber('id')->name('destroy');
        });
    
});