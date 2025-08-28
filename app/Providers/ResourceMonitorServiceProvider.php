<?php

namespace App\Providers;

use App\Services\ResourceMonitorService;
use Illuminate\Support\ServiceProvider;

/**
 * Resource monitoring service provider for agricultural system performance tracking.
 * Registers monitoring service to track system resources, database performance,
 * and application metrics critical to farm operations and production efficiency.
 *
 * @business_domain Agricultural system monitoring and performance optimization
 * @monitoring_scope Database performance, memory usage, API response times
 * @service_pattern Singleton registration for consistent monitoring across requests
 * @agricultural_context Ensures reliable performance during critical farm operations
 */
class ResourceMonitorServiceProvider extends ServiceProvider
{
    /**
     * Register resource monitoring service for agricultural system performance tracking.
     * Configures singleton monitoring service to track system health and performance
     * metrics essential for reliable farm management operations.
     *
     * @service_registration ResourceMonitorService as singleton
     * @monitoring_purpose Track system performance during farm operations
     * @reliability_focus Ensures stable performance for critical agricultural workflows
     * @return void
     */
    public function register(): void
    {
        $this->app->singleton(ResourceMonitorService::class, function ($app) {
            return new ResourceMonitorService();
        });
    }

    /**
     * Bootstrap resource monitoring configuration for agricultural system tracking.
     * Currently no additional bootstrap configuration required as monitoring
     * service is configured through singleton registration and used on-demand.
     *
     * @bootstrap_future Placeholder for potential monitoring middleware registration
     * @monitoring_config Future configuration for automatic performance tracking
     * @agricultural_context Ready for farm-specific monitoring enhancements
     * @return void
     */
    public function boot(): void
    {
        // No additional bootstrap configuration required
        // ResourceMonitorService configured through singleton registration
    }
}
