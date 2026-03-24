<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use \Auth;

class AccountsMiddleware
{

    public function handle($request, Closure $next)
    {
        // if(Auth::User() && Auth::User()->hasRole('accounts') ){
        //     return $next($request);
        // }
        
        if(Auth::User()){
            return $next($request);
        }
        abort(response()->json(
            [
                'error' => 'this user is not belongs to accounts ??',
                'redirect' => 'login_screen',
            ], 422));
    }
}
