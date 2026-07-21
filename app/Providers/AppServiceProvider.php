<?php

namespace App\Providers;

use App\Models\Boat;
use App\Models\Rental;
use App\Models\User;
use App\Observers\BoatObserver;
use App\Observers\RentalObserver;
use App\Observers\UserObserver;
use App\Policies\BoatPolicy;
use App\Policies\RentalPolicy;
use App\Policies\WorkerPolicy;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(\App\Services\ActivityLogService::class);
        $this->app->singleton(\App\Services\NotificationService::class);
        $this->app->singleton(\App\Services\TimerService::class);
        $this->app->singleton(\App\Services\BoatStatusService::class);
        $this->app->singleton(\App\Services\DashboardService::class);
        $this->app->singleton(\App\Services\BackupService::class);
        $this->app->singleton(\App\Services\ReportService::class);
        $this->app->singleton(\App\Services\ExportService::class);
    }

    public function boot(): void
    {
        // Register observers
        Boat::observe(BoatObserver::class);
        Rental::observe(RentalObserver::class);
        User::observe(UserObserver::class);

        // Register policies
        Gate::policy(Boat::class, BoatPolicy::class);
        Gate::policy(Rental::class, RentalPolicy::class);
        Gate::policy(User::class, WorkerPolicy::class);

        // Rate limiting
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(300)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by($request->input('email') . '|' . $request->ip());
        });
    }
}
