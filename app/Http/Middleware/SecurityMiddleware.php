<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

use \Auth;

class SecurityMiddleware
{

    public function handle(Request $request, Closure $next)
    {
        if(Auth::User() && Auth::User()->security_paid == 1){
            return $next($request);
        }
        return redirect('/vendor/subscribe');
    }
}
