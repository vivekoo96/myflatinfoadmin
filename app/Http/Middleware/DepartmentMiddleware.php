<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

use \Auth;
use Carbon\Carbon;

class DepartmentMiddleware
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
        // if($user->department_id == ''){
        //     abort(response()->json(
        //     [
        //         'error' => 'department is not selected',
        //         'redirect' => 'select_department_screen',
        //     ], 422));
        // }

        // if(!$user->department){
        //     abort(response()->json(
        //     [
        //         'error' => 'department not found',
        //         'redirect' => 'select_department_screen',
        //     ], 422));
        // }
        // if($user->department && $user->department->building && $user->department->building->status != 'Active'){
        //     abort(response()->json(
        //     [
        //         'error' => 'Building is not Active',
        //         'redirect' => 'select_department_screen',
        //     ], 422));
        // }

        // if ($user->department && $user->department->building && $user->department->building->valid_till < Carbon::now()) {
        //     abort(response()->json(
        //         [
        //             'error' => 'Building validity is expired',
        //             'redirect' => 'select_flat_screen',
        //         ], 422));
        // }
        
        return $next($request);
    }
}
