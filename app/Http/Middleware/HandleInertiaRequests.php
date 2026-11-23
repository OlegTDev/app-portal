<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class HandleInertiaRequests
{
    /**
     * Share common data with Inertia pages.
     */
    public function handle(Request $request, Closure $next): Response
    {
        Inertia::share([
            'auth' => function () use ($request) {
                return [
                    'user' => $request->user('portal')
                        ? [
                            'id' => $request->user('portal')->id,
                            'username' => $request->user('portal')->employee?->username,
                            'full_name' => $request->user('portal')->employee?->full_name,
                            'photo_path' => $request->user('portal')->employee?->photo_path,
                            'roles' => $request->user('portal')->roles->pluck('name')->toArray(),
                            'permissions' => $request->user('portal')->getAllPermissions()->pluck('name')->toArray(),
                        ]
                        : null,
                ];
            },
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ]);

        return $next($request);
    }
}
