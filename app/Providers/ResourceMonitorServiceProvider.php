<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class ResourceMonitorServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(\App\Services\ResourceMonitorService::class, function ($app) {
            return new \App\Services\ResourceMonitorService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
