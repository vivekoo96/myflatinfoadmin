<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use \Auth;

class PollMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::User();

        // Allow BA direct role
        if ($user->role == 'BA') {
            return $next($request);
        }

        // Allow users with a president/committee role assigned via building
        if ($user->selected_role_id && $user->selectedRole) {
            if ($user->selectedRole->slug == 'president') {
                return $next($request);
            }
        }

        return redirect('permission-denied')->with('error', 'Permission denied!');
    }
}
