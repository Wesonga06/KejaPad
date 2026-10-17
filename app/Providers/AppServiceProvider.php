<?php

namespace App\Providers;

use App\Models\User;
use App\Services\SettingsService;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        // Super-admin-only abilities used by the platform administration pages
        // in addition to the role middleware on their routes.
        Gate::define('manage-settings', fn (User $user): bool => $user->isSuperAdmin());
        Gate::define('manage-payments', fn (User $user): bool => $user->isSuperAdmin());
        Gate::define('manage-sms', fn (User $user): bool => $user->isSuperAdmin());
        Gate::define('view-system-reports', fn (User $user): bool => $user->isSuperAdmin());

        // Apply administrator overrides stored in the settings table over the
        // environment defaults. Never allowed to break boot (for example while
        // the table is still being migrated).
        try {
            $this->app->make(SettingsService::class)->applyToConfig();
        } catch (Throwable) {
            // Settings unavailable - fall back to environment configuration.
        }
    }
}
