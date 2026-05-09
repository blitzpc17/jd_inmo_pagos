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
        Route::get('/', [DevelopmentController::class, 'index'])->name('index');
        Route::get('/datatable', [DevelopmentController::class, 'datatable'])->name('datatable');
        Route::get('/options', [DevelopmentController::class, 'options'])->name('options');
        Route::post('/', [DevelopmentController::class, 'store'])->name('store');
        Route::get('/{id}', [DevelopmentController::class, 'show'])->name('show');
        Route::put('/{id}', [DevelopmentController::class, 'update'])->name('update');
        Route::delete('/{id}', [DevelopmentController::class, 'destroy'])->name('destroy');

        Route::get('/{developmentId}/detalle', [DevelopmentLotController::class, 'index'])->name('detalle');
        Route::get('/{developmentId}/lots/datatable', [DevelopmentLotController::class, 'datatable'])->name('lots.datatable');
        Route::get('/{developmentId}/lots/options', [DevelopmentLotController::class, 'options'])->name('lots.options');

        Route::post('/{developmentId}/lots', [DevelopmentLotController::class, 'store'])->name('lots.store');
        Route::get('/{developmentId}/lots/{lotId}', [DevelopmentLotController::class, 'show'])->name('lots.show');
        Route::put('/{developmentId}/lots/{lotId}', [DevelopmentLotController::class, 'update'])->name('lots.update');
        Route::delete('/{developmentId}/lots/{lotId}', [DevelopmentLotController::class, 'destroy'])->name('lots.destroy');

        Route::post('/{developmentId}/lots/generate', [DevelopmentLotController::class, 'generate'])->name('lots.generate');
        Route::post('/{developmentId}/lots/bulk-update', [DevelopmentLotController::class, 'bulkUpdate'])->name('lots.bulk-update');
    });
    
});