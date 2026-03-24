<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class SetCurrentBuilding
{
    /**
     * Handle an incoming request.
     * Set the current building and selected role from session into the
     * in-memory Auth user so controllers that reference Auth::user()->building_id
     * behave per browser session without persisting to DB.
     */
    public function handle($request, Closure $next)
    {
        if (Auth::check()) {
            $user = Auth::user();

            $sessionBuilding = session('current_building_id');
            if (!empty($sessionBuilding)) {
                $user->building_id = (int) $sessionBuilding;
            }

            $sessionRole = session('selected_role_id');
            if (!empty($sessionRole)) {
                $user->selected_role_id = $sessionRole;
            }

            // Update the runtime user object for this request only
            Auth::setUser($user);
        }

        return $next($request);
    }
}
