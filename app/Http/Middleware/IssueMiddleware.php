<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

use \Auth;
use Carbon\Carbon;

class IssueMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::User();
        $building = $user->building_id ? \App\Models\Building::find($user->building_id) : null;
        
        // Check selected role (if user has one)
        if($user->selected_role_id && $user->selectedRole){
            if($user->selectedRole->slug == 'BA' || $user->selectedRole->slug == 'president' || $user->selectedRole->slug == 'issue'){
                return $next($request);
            }
        }
        
        // Also allow if user has permission
        if ($user && $user->hasPermission('custom.issuetracking')) {
            return $next($request);
        }
        
        // Also check building permission
        if ($building && $building->hasPermission('Issue')) {
            return $next($request);
        }

        return redirect('permission-denied')->with('error','Permission denied');
    }
}
