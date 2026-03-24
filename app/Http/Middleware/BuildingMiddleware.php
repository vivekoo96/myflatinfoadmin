<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

use \Auth;
use Carbon\Carbon;

class BuildingMiddleware
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
        
        if($user->status != 'Active'){
            return redirect('/building-option')->with('error','Your account is inactive. Please contact support.');
        }
        
        if($user->building_id == ''){
            return redirect('/building-option')->with('error','Building is not selected');
        }

       $building = \App\Models\Building::withTrashed()->find(Auth::user()->building_id);
        if($building && $building->status != 'Active'){
            return redirect('/building-option')->with('error','The selected Building is Inactive, Please select any others');
        }

        if ($building && (empty($building->valid_till) || Carbon::parse($building->valid_till)->isBefore(Carbon::today()))) {
            return redirect('/building-option')->with('error','Building validity is expired, Please select another one');
        }

        
        return $next($request);
    }
}
