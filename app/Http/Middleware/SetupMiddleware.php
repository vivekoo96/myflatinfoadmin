<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

use \Auth;

class SetupMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::User();
        if($user->otp_status != 'Verified'){
            abort(response()->json(
            [
                'error' => 'otp is not verified',
                'redirect' => 'resend_otp_screen',
            ], 422));
        }
        if($user->password == ''){
            abort(response()->json(
            [
                'error' => 'password is not set',
                'redirect' => 'password_screen',
            ], 422));
        }
        if($user->profile_status == 'Pending'){
            abort(response()->json(
            [
                'error' => 'profile is not set',
                'redirect' => 'setup_screen',
            ], 422));
        }

        if($user->status != 'Active'){
            abort(response()->json(
            [
                'error' => 'Accont is not active, please contact us',
                'redirect' => 'inactive_screen',
            ], 422));
        }
        return $next($request);
    }
}
