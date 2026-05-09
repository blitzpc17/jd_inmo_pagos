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



Route::get('/', fn () => redirect()->route('login'));

Route::get('/login', [LoginController::class, 'create'])->name('login');
Route::post('/login', [LoginController::class, 'store'])->name('login.store');
Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

Route::middleware(['auth.custom', 'share.menu'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

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

    // módulo principal de lotificaciones
    Route::get('/', [DevelopmentController::class, 'index'])->name('index');
    Route::get('/datatable', [DevelopmentController::class, 'datatable'])->name('datatable');
    Route::get('/options', [DevelopmentController::class, 'options'])->name('options');
    Route::post('/', [DevelopmentController::class, 'store'])->name('store');
    Route::get('/{id}', [DevelopmentController::class, 'show'])->name('show');
    Route::put('/{id}', [DevelopmentController::class, 'update'])->name('update');
    Route::delete('/{id}', [DevelopmentController::class, 'destroy'])->name('destroy');

    // módulo hijo de lotes por lotificación
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
        Route::get('/{id}', [ContractController::class, 'show'])->name('show');
    });

    Route::prefix('cobros')->name('cobros.')->group(function () {
        Route::get('/', [ChargeController::class, 'index'])->name('index');
        Route::get('/datatable', [ChargeController::class, 'datatable'])->name('datatable');
        Route::get('/options', [ChargeController::class, 'options'])->name('options');

        Route::get('/contract/{contractId}/summary', [ChargeController::class, 'contractSummary'])->name('contract.summary');
        Route::post('/', [ChargeController::class, 'store'])->name('store');
    });

   

    Route::prefix('asignacion-lotificaciones')->name('asignacion_lotificaciones.')->group(function () {
        Route::get('/', [DevelopmentAssignmentController::class, 'index'])->name('index');
        Route::get('/options', [DevelopmentAssignmentController::class, 'options'])->name('options');

        Route::get('/role/{roleId}', [DevelopmentAssignmentController::class, 'roleAssignments'])->name('role.assignments');
        Route::post('/role/{roleId}', [DevelopmentAssignmentController::class, 'saveRoleAssignments'])->name('role.save');

        Route::get('/user/{userId}', [DevelopmentAssignmentController::class, 'userAssignments'])->name('user.assignments');
        Route::post('/user/{userId}', [DevelopmentAssignmentController::class, 'saveUserAssignments'])->name('user.save');
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
    
});