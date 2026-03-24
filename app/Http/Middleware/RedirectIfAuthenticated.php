<?php

namespace App\Http\Middleware;

use App\Providers\RouteServiceProvider;
use Closure;
use Illuminate\Support\Facades\Auth;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string[]|null  ...$guards
     * @return mixed
     */
    public function handle($request, Closure $next, ...$guards)
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                if(Auth::User()->role == 'BA' || Auth::User()->hasAnyRole()){
                    if(Auth::User()->status == 'Inactive'){
                        Auth::logout();
                        return redirect('/');
                    }
                    return redirect('/building-option');
                }
                if(Auth::User()->role == 'vendor'){
                    return redirect('vendor/dashboard');
                }
            }
            return $next($request);
        }

        return $next($request);
    }
}
