<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\BuildingUser;
use Auth;

class CheckBuildingUserStatus
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
        // Only check for authenticated users
        if (Auth::check()) {
            $user = Auth::user();
            $currentBuildingId = session('current_building_id') ?? $user->building_id;
            
            // If this user is a Building Admin (BA), check the top-level users.status
            if (isset($user->role) && strtoupper($user->role) === 'BA') {
                if (!isset($user->status) || $user->status !== 'Active') {
                    Auth::logout();
                    return redirect()->route('login')->with('error', 'Your account is inactive. Please contact the administrator.');
                }
            } else {
                // Check if user has any active role in the current building (non-BA users)
                $activeRole = BuildingUser::where('user_id', $user->id)
                    ->where('building_id', $currentBuildingId)
                    ->where('status', 'Active')
                    ->exists();

                // If user has no active role in current building, logout
                if (!$activeRole) {
                    Auth::logout();
                    return redirect()->route('login')->with('error', 'Your account is inactive. Please contact the administrator.');
                }
            }
        }
        
        return $next($request);
    }
}
