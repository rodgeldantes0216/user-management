<?php

namespace App\Providers;

use App\Support\Navigation;
use App\Support\PermissionRegistry;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

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
        PermissionRegistry::syncAndRegister();

        View::share('navigationItems', Navigation::items());
    }
}
