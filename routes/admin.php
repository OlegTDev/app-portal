<?php

use App\Http\Controllers\Admin\DepartmentsController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\MenuController;
use App\Http\Controllers\Admin\NewsController as AdminNewsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Routes (authenticated + authorized)
|--------------------------------------------------------------------------
|
| Admin panel routes.
|
*/

Route::middleware(['auth:portal'])->prefix('admin')->name('admin.')->group(function () {
    // Dashboard
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // News management
    Route::prefix('news')->name('news.')->group(function () {
        Route::get('/', [AdminNewsController::class, 'index'])->name('index');
        Route::get('/create', [AdminNewsController::class, 'create'])->name('create');
        Route::post('/', [AdminNewsController::class, 'store'])->name('store');
        Route::get('/{id}', [AdminNewsController::class, 'show'])->name('show');
        Route::put('/{id}', [AdminNewsController::class, 'update'])->name('update');
        Route::delete('/{id}', [AdminNewsController::class, 'destroy'])->name('destroy');
    });

    // Menu management
    Route::get('/menu', [MenuController::class, 'index'])->name('menu.index');
    Route::post('/menu', [MenuController::class, 'store'])->name('menu.store');
    Route::put('/menu/{id}', [MenuController::class, 'update'])->name('menu.update');
    Route::delete('/menu/{id}', [MenuController::class, 'destroy'])->name('menu.destroy');
    Route::post('/menu/reorder', [MenuController::class, 'reorder'])->name('menu.reorder');

    // Departments management
    Route::get('/departments', DepartmentsController::class)->name('departments.index');
});
