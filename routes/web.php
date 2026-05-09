<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PermissionController;

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
});