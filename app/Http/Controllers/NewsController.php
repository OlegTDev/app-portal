<?php

namespace App\Http\Controllers;

use App\Models\News;
use Inertia\Inertia;
use Inertia\Response;

class NewsController extends Controller
{
    /**
     * Display a listing of published news.
     */
    public function index(): Response
    {
        $news = News::query()
            ->published()
            ->with(['category', 'creator'])
            ->latest('published_at')
            ->paginate(12);

        return Inertia::render('News/Index', [
            'news' => $news,
        ]);
    }

    /**
     * Display the specified published news.
     */
    public function show(string $id): Response
    {
        $news = News::query()
            ->published()
            ->with(['category', 'creator', 'media'])
            ->where('id', $id)
            ->firstOrFail();

        // Increment visits
        $news->increment('visits_count');

        return Inertia::render('News/Show', [
            'news' => $news,
        ]);
    }

    /**
     * Toggle like on news.
     */
    public function like(string $id): \Symfony\Component\HttpFoundation\JsonResponse
    {
        $news = News::findOrFail($id);
        $employee = auth()->user('portal')?->employee;

        if (!$employee) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $like = \App\Models\Like::where('employee_id', $employee->id)
            ->where('likable_type', News::class)
            ->where('likable_id', $news->id)
            ->first();

        if ($like) {
            $like->delete();
            $liked = false;
        } else {
            \App\Models\Like::create([
                'employee_id' => $employee->id,
                'likable_type' => News::class,
                'likable_id' => $news->id,
            ]);
            $liked = true;
        }

        $news->touch();
        $news->refresh();

        return response()->json([
            'liked' => $liked,
            'likes_count' => $news->likes_count,
        ]);
    }
}
