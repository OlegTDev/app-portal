<?php

use App\Http\Controllers\Api\EmployeeSearchController;
use App\Http\Controllers\NewsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes (authenticated)
|--------------------------------------------------------------------------
|
| API endpoints available to authenticated users.
|
*/

Route::middleware('auth:portal')->group(function () {
    // Employee search
    Route::get('/api/employees/search', EmployeeSearchController::class)->name('api.employees.search');
});
