<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

use \Auth;
use Carbon\Carbon;

class SocietyFundMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::User();
        $building = $user->building_id ? \App\Models\Building::find($user->building_id) : null;
        
        // Check selected role (if user has one)
        if($user->selected_role_id && $user->selectedRole){
            if($user->selectedRole->slug == 'BA' || $user->selectedRole->slug == 'president' || $user->selectedRole->slug == 'accounts'){
                return $next($request);
            }
        }
        
        // Also allow if user has feature permission
        if ($user && $user->hasPermission('feature.societyfund')) {
            return $next($request);
        }

        // Also allow if the building has the Society fund feature enabled
        if ($building && $building->hasPermission('Society fund')) {
            return $next($request);
        }

        return redirect('permission-denied')->with('error','Permission denied');
    }
}
