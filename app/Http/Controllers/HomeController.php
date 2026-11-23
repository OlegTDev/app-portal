<?php

namespace App\Http\Controllers;

use App\Models\News;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    /**
     * Display the home page.
     */
    public function __invoke(): Response
    {
        return Inertia::render('Home', [
            'recentNews' => News::query()
                ->published()
                ->onMainPage()
                ->orderBy('pinned_at', 'desc')
                ->orderBy('published_at', 'desc')
                ->limit(5)
                ->get(['id', 'title', 'summary', 'published_at'])
                ->toArray(),
        ]);
    }
}
