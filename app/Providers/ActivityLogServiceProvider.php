<?php

namespace App\Providers;

use App\Models\User;
use App\Models\TimeCard;
use App\Models\Crop;
use App\Models\Product;
use App\Models\Recipe;
use App\Models\Order;
use App\Models\Consumable;
use App\Models\Supplier;
use App\Observers\ActivityLogObserver;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;
use App\Services\ActivityLogService;
use App\Services\CacheService;
use App\Services\MetricsService;
use App\Services\RetentionService;
use App\Http\Middleware\ActivityLogMiddleware;
use App\Http\Middleware\ApiActivityMiddleware;
use App\Console\Commands\ActivityLogPurge;
use App\Console\Commands\ActivityLogExport;
use App\Console\Commands\ActivityLogStats;
use App\Console\Commands\ActivityLogMaintenance;
use App\Listeners\ActivityEventListener;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use App\Models\Activity;
use App\Policies\ActivityPolicy;

/**
 * Activity logging service provider for comprehensive agricultural operation tracking.
 * Configures automatic logging of all farm management activities including crop lifecycle,
 * inventory changes, order processing, and user actions for audit and analysis purposes.
 *
 * @business_domain Agricultural operation auditing and farm activity tracking
 * @activity_scope Crop management, inventory transactions, order fulfillment, user actions
 * @logging_features Automatic model observers, event listeners, scheduled maintenance
 * @retention_policy Configurable data retention with archival support
 * @performance_optimization Cached metrics and statistics for activity analysis
 */
class ActivityLogServiceProvider extends ServiceProvider
{
    /**
     * Register activity logging services and dependencies for agricultural operation tracking.
     * Configures comprehensive logging infrastructure to track all farm management activities
     * including crop lifecycle events, inventory changes, and user interactions.
     *
     * @service_registration ActivityLogService, CacheService, MetricsService, RetentionService
     * @middleware_aliases activity.log for web requests, activity.api for API calls
     * @dependency_injection Proper constructor injection with configuration parameters
     * @return void
     */
    public function register(): void
    {
        // Register the main activity log service as a singleton
        $this->app->singleton(ActivityLogService::class, function ($app) {
            return new ActivityLogService();
        });

        // Register supporting services
        $this->app->singleton(CacheService::class, function ($app) {
            return new CacheService(
                cache: $app->make('cache.store'),
                prefix: 'activity_log'
            );
        });

        $this->app->singleton(MetricsService::class, function ($app) {
            return new MetricsService(
                activityService: $app->make(ActivityLogService::class),
                cacheService: $app->make(CacheService::class)
            );
        });

        $this->app->singleton(RetentionService::class, function ($app) {
            return new RetentionService(
                retentionDays: config('activitylog.retention_days', 90),
                archiveEnabled: config('activitylog.archive_enabled', false)
            );
        });

        // Register middleware aliases
        $this->app['router']->aliasMiddleware('activity.log', ActivityLogMiddleware::class);
        $this->app['router']->aliasMiddleware('activity.api', ApiActivityMiddleware::class);
    }

    /**
     * Bootstrap activity logging configuration for agricultural operation monitoring.
     * Configures automatic activity tracking, model observers for farm entities,
     * scheduled maintenance tasks, and policy-based access control for audit logs.
     *
     * @event_registration Global event listener for comprehensive activity capture
     * @model_observers Automatic logging for User, Crop, Product, Order, Consumable operations
     * @scheduled_tasks Daily log purge, weekly statistics, monthly maintenance
     * @access_control Activity policy registration for audit log security
     * @configuration_publishing Activity log configuration files and migrations
     * @return void
     */
    public function boot(): void
    {
        // Register event listeners
        Event::listen('*', ActivityEventListener::class);

        // Register model observers
        $this->registerModelObservers();

        // Register policies
        Gate::policy(Activity::class, ActivityPolicy::class);

        // Register console commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                ActivityLogPurge::class,
                ActivityLogExport::class,
                ActivityLogStats::class,
                ActivityLogMaintenance::class,
            ]);

            // Schedule maintenance tasks
            $this->scheduleTasks();
        }

        // Publish configuration
        $this->publishes([
            __DIR__.'/../../config/activitylog.php' => config_path('activitylog.php'),
        ], 'activity-log-config');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations/activity_log');
    }

    /**
     * Register model observers for automatic agricultural activity logging.
     * Attaches ActivityLogObserver to key farm management models to capture
     * all create, update, and delete operations for comprehensive audit trails.
     *
     * @agricultural_models User, TimeCard, Crop, Product, Recipe, Order, Consumable, Supplier
     * @observer_pattern Automatic attachment of ActivityLogObserver to configured models
     * @configuration_driven Models list from activitylog.auto_log_models config
     * @safety_check Class existence verification before observer registration
     * @return void
     */
    protected function registerModelObservers(): void
    {
        // Get models that should be automatically logged
        $models = config('activitylog.auto_log_models', [
            User::class,
            TimeCard::class,
            Crop::class,
            Product::class,
            Recipe::class,
            Order::class,
            Consumable::class,
            Supplier::class,
        ]);

        foreach ($models as $model) {
            if (class_exists($model)) {
                $model::observe(ActivityLogObserver::class);
            }
        }
    }

    /**
     * Schedule maintenance tasks for agricultural activity log management.
     * Configures automated cleanup, statistics generation, and database optimization
     * to maintain performance while preserving important farm operation audit trails.
     *
     * @daily_cleanup Log purge at 3 AM to remove expired activity records
     * @weekly_statistics Statistics generation on Sundays for farm operation analysis
     * @monthly_maintenance Database optimization and archival of old agricultural data
     * @overlap_protection WithoutOverlapping prevents concurrent execution conflicts
     * @return void
     */
    protected function scheduleTasks(): void
    {
        $schedule = $this->app->make(Schedule::class);

        // Daily cleanup of old logs
        $schedule->command('activitylog:purge')
            ->daily()
            ->at('03:00')
            ->withoutOverlapping();

        // Weekly statistics generation
        $schedule->command('activitylog:stats --save')
            ->weekly()
            ->sundays()
            ->at('04:00');

        // Monthly maintenance (optimize tables, archive old data)
        $schedule->command('activitylog:maintenance')
            ->monthly()
            ->at('05:00');
    }
}