<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class DepartmentsController extends Controller
{
    /**
     * Display the departments management page.
     */
    public function __invoke(): Response
    {
        return Inertia::render('Admin/Departments/Index');
    }
}
