<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

use \Auth;
use Carbon\Carbon;

class VehicleMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::User();
        $building = $user->building_id ? \App\Models\Building::find($user->building_id) : null;
        
        // Check selected role (if user has one)
        if($user->selected_role_id && $user->selectedRole){
            if($user->selectedRole->slug == 'BA' || $user->selectedRole->slug == 'president' || $user->selectedRole->slug == 'security'){
                return $next($request);
            }
        }
        
        // For other users, check building permission
        if ($building && $building->hasPermission('Vehicle')) {
            return $next($request);
        }

        return redirect('permission-denied')->with('error','Permission denied');
    }
}
