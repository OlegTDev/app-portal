<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class MenuController extends Controller
{
    /**
     * Display the menu management page.
     */
    public function __invoke(): Response
    {
        return Inertia::render('Admin/Menu/Index');
    }
}
