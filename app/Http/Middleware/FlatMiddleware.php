<?php

namespace App\Http\Middleware;
use Illuminate\Support\Facades\Log;
use Closure;
use Illuminate\Http\Request;
use Auth;
use App\Helpers\AuthHelper;
use Carbon\Carbon;
use App\Models\BuildingUser;
use App\Models\Role;


class FlatMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $flat = AuthHelper::flat();
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'error' => 'This account is not registered with us'
            ], 422);
        }

$userRoleIds = BuildingUser::where('user_id', $user->id)
    ->where('status', 'Active')
    ->where('building_id', $flat->building_id)
    ->pluck('role_id');

$validUserRoleFound = Role::whereIn('id', $userRoleIds)
    ->where('type', 'user')
    ->exists();

if (!$validUserRoleFound) {
    return response()->json([
            'action'=>'logout',
            'alert_title' => 'Access Denied',
            'error' => 'Your user role access in this building is Inactive.'
    ], 422);
}
   


        if (empty($flat->id)) {
            return response()->json([
                'error' => 'Flat is not selected',
                'redirect' => 'select_flat_screen',
            ], 422);
        }

        if (!$flat || $flat->status != 'Active') {
            return response()->json([
                'error' => 'Flat is not active',
                'redirect' => 'select_flat_screen',
            ], 422);
        }

        if (!$flat->building || $flat->building->status != 'Active') {
            return response()->json([
                'error' => 'Building is not active',
                'redirect' => 'select_flat_screen',
            ], 422);
        }

        if (empty($flat->building->valid_till) || Carbon::parse($flat->building->valid_till)->isBefore(Carbon::today())) {
            return response()->json([
                'error' => 'Building validity is expired',
                'redirect' => 'select_flat_screen',
            ], 422);
        }

        return $next($request);
    }
}
