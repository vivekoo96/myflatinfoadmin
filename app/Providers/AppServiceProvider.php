<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\Classified;
use App\Models\ClassifiedBuilding;
use App\Models\Building;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
         $helperPath = app_path('helpers.php');
        if (file_exists($helperPath)) {
            require_once $helperPath;
        }
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if (env('APP_ENV') !== 'local') {
            URL::forceScheme('https');
        }

        Paginator::useBootstrap();
        
         View::composer('*', function ($view) {
            if (!Auth::check()) {
                return;
            }

            $user = Auth::user();
            $building = $user->building;
            if (!$building) {
                $view->with('user_within_approved_count', 0);
                $view->with('user_all_approved_count', 0);
                return;
            }

            $buildingId = $building->id;
            $now = Carbon::now();

            // Use building-configured spans if present, default to 1 month
            $withinSpan = intval($building->within_for_month ?? 1);
            $allSpan = intval($building->all_for_month ?? 1);
            $withinSpan = max(1, $withinSpan);
            $allSpan = max(1, $allSpan);

            $startOfWithinWindow = $now->copy()->subMonths($withinSpan - 1)->startOfMonth();
            $startOfAllWindow = $now->copy()->subMonths($allSpan - 1)->startOfMonth();
            $endOfWindow = $now->copy()->endOfMonth();

            $userId = $user->id;

            // Per-user counts: each user has individual quota (not shared with other users)
            $within_used_user = Classified::where('building_id', $buildingId)
                ->where('category', 'Within Building')
                ->where('status', 'Approved')
                ->where('is_approved_on_creation', true)
                ->where('user_id', $userId)
                ->whereBetween('created_at', [$startOfWithinWindow, $endOfWindow])
                ->count();

            $all_used_user = Classified::where('building_id', $buildingId)
                ->where('category', 'All Buildings')
                ->where('status', 'Approved')
                ->where('is_approved_on_creation', true)
                ->where('user_id', $userId)
                ->whereBetween('created_at', [$startOfAllWindow, $endOfWindow])
                ->count();

            // For per-user quotas, "total" is just the user's count
            $within_used_total = $within_used_user;
            $all_used_total = $all_used_user;

            // Inject variables the view expects
            $view->with('within_used_user', $within_used_user);
            $view->with('all_used_user', $all_used_user);
            $view->with('within_used_total', $within_used_total);
            $view->with('all_used_total', $all_used_total);
        });
    }
}
