<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use \Auth;

class AdminMiddleware
{

    public function handle($request, Closure $next)
    {
        if(Auth::User() && Auth::User()->role == 'BA' &&  Auth::User()->status == 'Active' || Auth::User() && Auth::User()->hasAnyRole()){
            return $next($request);
        }
        return redirect('/');
    }
}
