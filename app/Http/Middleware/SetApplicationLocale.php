<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetApplicationLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        app()->setLocale(config('app.locale'));

        return $next($request);
    }
}
