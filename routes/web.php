<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CatalogController;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/login', [LoginController::class, 'create'])->name('login');
Route::post('/login', [LoginController::class, 'store'])->name('login.store');
Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

Route::middleware(['auth.custom'])->group(function () {
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
});