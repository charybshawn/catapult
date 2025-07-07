<?php

namespace App\Providers;

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

class ActivityLogServiceProvider extends ServiceProvider
{
    /**
     * Register services.
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
     * Bootstrap services.
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
     * Register model observers for automatic activity logging
     */
    protected function registerModelObservers(): void
    {
        // Get models that should be automatically logged
        $models = config('activitylog.auto_log_models', [
            \App\Models\User::class,
            \App\Models\TimeCard::class,
            \App\Models\Crop::class,
            \App\Models\Product::class,
            \App\Models\Recipe::class,
            \App\Models\Order::class,
            \App\Models\Consumable::class,
            \App\Models\Supplier::class,
        ]);

        foreach ($models as $model) {
            if (class_exists($model)) {
                $model::observe(\App\Observers\ActivityLogObserver::class);
            }
        }
    }

    /**
     * Schedule maintenance tasks
     */
    protected function scheduleTasks(): void
    {
        $schedule = $this->app->make(\Illuminate\Console\Scheduling\Schedule::class);

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