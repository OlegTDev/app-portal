<?php

namespace App\Http\Middleware;

use App\Models\PortalUser;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class Access
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $key): Response
    {
        /** @var ?PortalUser $user */
        $user = $request->user('portal');

        if (!$user) {
            return redirect()->route('login');
        }

        if (!Gate::allows('access-group', $key)) {
            abort(403, 'Доступ запрещён');
        }

        return $next($request);
    }
}
