<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\NewsController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return Inertia::render('Home', [
        'recentNews' => \App\Models\News::query()
            ->published()
            ->onMainPage()
            ->orderBy('pinned_at', 'desc')
            ->orderBy('published_at', 'desc')
            ->limit(5)
            ->get(['id', 'title', 'summary', 'published_at'])
            ->toArray(),
    ]);
})->name('home');

// News routes (public)
Route::get('/news', [NewsController::class, 'index'])->name('news.index');
Route::get('/news/{id}', [NewsController::class, 'show'])->name('news.show');
Route::post('/news/{id}/like', [NewsController::class, 'like'])->name('news.like');

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/

Route::get('/login', [LoginController::class, 'create'])->name('login');
Route::post('/login', [LoginController::class, 'store'])->name('login.store');
Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

/*
|--------------------------------------------------------------------------
| API Routes (authenticated)
|--------------------------------------------------------------------------
*/

Route::middleware('auth:portal')->group(function () {
    // Employee search
    Route::get('/api/employees/search', function (\Illuminate\Http\Request $request) {
        $query = $request->input('q', '');

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $employees = \App\Models\Employee::where('is_active', true)
            ->whereRaw("search_vector @@ plainto_tsquery('russian', ?)", [$query])
            ->orderByRaw("ts_rank(search_vector, plainto_tsquery('russian', ?)) DESC", [$query])
            ->limit(20)
            ->get(['id', 'full_name', 'position', 'phone', 'phone_additional',
                   'office', 'email', 'department_name', 'organization_code', 'photo_path']);

        return response()->json($employees);
    })->name('api.employees.search');
});

/*
|--------------------------------------------------------------------------
| Admin Routes (authenticated + authorized)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:portal'])->prefix('admin')->name('admin.')->group(function () {
    // Dashboard
    Route::get('/dashboard', function () {
        return Inertia::render('Admin/Dashboard');
    })->name('dashboard');

    // News management
    Route::prefix('news')->name('news.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\NewsController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Admin\NewsController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Admin\NewsController::class, 'store'])->name('store');
        Route::get('/{id}', [\App\Http\Controllers\Admin\NewsController::class, 'show'])->name('show');
        Route::put('/{id}', [\App\Http\Controllers\Admin\NewsController::class, 'update'])->name('update');
        Route::delete('/{id}', [\App\Http\Controllers\Admin\NewsController::class, 'destroy'])->name('destroy');
    });

    // Menu management
    Route::get('/menu', function () {
        return Inertia::render('Admin/Menu/Index');
    })->name('menu.index');

    // Departments management
    Route::get('/departments', function () {
        return Inertia::render('Admin/Departments/Index');
    })->name('departments.index');
});
