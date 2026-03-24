<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * These middleware are run during every request to your application.
     *
     * @var array
     */
    protected $middleware = [
        
        // \App\Http\Middleware\TrustHosts::class,
        \App\Http\Middleware\TrustProxies::class,
        \App\Http\Middleware\PreventRequestsDuringMaintenance::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \App\Http\Middleware\TrimStrings::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
        \Fruitcake\Cors\HandleCors::class,
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array
     */
    protected $middlewareGroups = [
        'web' => [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            // \Illuminate\Session\Middleware\AuthenticateSession::class,.
              \App\Http\Middleware\SetCurrentBuilding::class,
                \App\Http\Middleware\CheckBuildingUserStatus::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \App\Http\Middleware\SessionExpired::class,
        ],

        'api' => [
            'throttle:api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],
    ];

    /**
     * The application's route middleware.
     *
     * These middleware may be assigned to groups or used individually.
     *
     * @var array
     */
    protected $routeMiddleware = [
        'auth' => \App\Http\Middleware\Authenticate::class,
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
        'can' => \Illuminate\Auth\Middleware\Authorize::class,
        'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
        'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
        'signed' => \Illuminate\Routing\Middleware\ValidateSignature::class,
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
        'admin' => \App\Http\Middleware\AdminMiddleware::class,
        'setup' => \App\Http\Middleware\SetupMiddleware::class,
        'gate' => \App\Http\Middleware\GateMiddleware::class,
        'building' => \App\Http\Middleware\BuildingMiddleware::class,
        'flat' => \App\Http\Middleware\FlatMiddleware::class,
        'security' => \App\Http\Middleware\SecurityMiddleware::class,
        'department' => \App\Http\Middleware\DepartmentMiddleware::class,
        'accounts' => \App\Http\Middleware\AccountsMiddleware::class,
        
        'societyfund' => \App\Http\Middleware\SocietyFundMiddleware::class,
        'maintenance' => \App\Http\Middleware\MaintenanceMiddleware::class,
        'corpusfund' => \App\Http\Middleware\CorpusFundMiddleware::class,
        
        'event' => \App\Http\Middleware\EventMiddleware::class,
        'facility' => \App\Http\Middleware\FacilityMiddleware::class,
        'parcel' => \App\Http\Middleware\ParcelMiddleware::class,
        'noticeboard' => \App\Http\Middleware\NoticeboardMiddleware::class,
        
        'essential' => \App\Http\Middleware\EssentialMiddleware::class,
        
        'staff' => \App\Http\Middleware\StaffMiddleware::class,
        'familymember' => \App\Http\Middleware\FamilyMemberMiddleware::class,
        'visitor' => \App\Http\Middleware\VisitorMiddleware::class,
        
        'vehicle' => \App\Http\Middleware\VehicleMiddleware::class,
        'issue' => \App\Http\Middleware\IssueMiddleware::class,
        'classified' => \App\Http\Middleware\ClassifiedMiddleware::class,

        //'cors' => \App\Http\Middleware\CorsMiddleware::class,
    ];
}
