<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Authenticate extends Middleware
{
    protected function redirectTo($request)
    {
        if (! $request->expectsJson()) {
            return route('login');
        }
    }

    // Add new method 
    protected function unauthenticated($request, array $guards)
    {
        abort(response()->json(
            [
                'error' => 'UnAuthenticated',
            ], 401));
    }
}
