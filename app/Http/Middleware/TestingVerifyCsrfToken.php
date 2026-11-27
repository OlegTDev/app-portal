<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TestingVerifyCsrfToken extends Middleware
{
    /**
     * Handle an incoming request.
     *
     * In testing environment, always pass without CSRF validation.
     */
    public function handle(mixed $request, Closure $next): Response
    {
        // Skip all CSRF checks in testing
        if (app()->environment('testing')) {
            return $next($request);
        }

        return parent::handle($request, $next);
    }
}
