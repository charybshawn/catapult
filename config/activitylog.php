<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Activity Log Settings
    |--------------------------------------------------------------------------
    |
    | These settings control the behavior of the activity logging system.
    |
    */

    'enabled' => env('ACTIVITY_LOG_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Retention Settings
    |--------------------------------------------------------------------------
    |
    | How long to keep activity logs before purging them.
    |
    */

    'retention_days' => env('ACTIVITY_LOG_RETENTION_DAYS', 90),
    'archive_enabled' => env('ACTIVITY_LOG_ARCHIVE_ENABLED', false),
    'archive_after_days' => env('ACTIVITY_LOG_ARCHIVE_AFTER_DAYS', 30),

    /*
    |--------------------------------------------------------------------------
    | Auto-Log Models
    |--------------------------------------------------------------------------
    |
    | These models will automatically log all CRUD operations.
    |
    */

    'auto_log_models' => [
        \App\Models\User::class,
        \App\Models\TimeCard::class,
        \App\Models\Crop::class,
        \App\Models\Product::class,
        \App\Models\Recipe::class,
        \App\Models\Order::class,
        \App\Models\Consumable::class,
        \App\Models\Supplier::class,
        \App\Models\Category::class,
        \App\Models\SeedVariation::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Ignored Routes
    |--------------------------------------------------------------------------
    |
    | These routes will not be logged by the activity middleware.
    |
    */

    'ignored_routes' => [
        'debugbar.*',
        'horizon.*',
        'telescope.*',
        '_ignition.*',
        'livewire.*',
        'sanctum.*',
        'broadcasting.*',
    ],

    /*
    |--------------------------------------------------------------------------
    | Ignored Fields
    |--------------------------------------------------------------------------
    |
    | These fields will be removed from logged properties.
    |
    */

    'ignored_fields' => [
        'password',
        'password_confirmation',
        'remember_token',
        'api_token',
        'secret',
        'token',
        '_token',
        'csrf_token',
    ],

    /*
    |--------------------------------------------------------------------------
    | API Logging
    |--------------------------------------------------------------------------
    |
    | Settings specific to API request logging.
    |
    */

    'api' => [
        'enabled' => env('ACTIVITY_LOG_API_ENABLED', true),
        'log_request_body' => env('ACTIVITY_LOG_API_REQUEST_BODY', true),
        'log_response_body' => env('ACTIVITY_LOG_API_RESPONSE_BODY', false),
        'max_body_length' => 10000,
    ],

    /*
    |--------------------------------------------------------------------------
    | Query Logging
    |--------------------------------------------------------------------------
    |
    | Settings for database query logging.
    |
    */

    'queries' => [
        'enabled' => env('ACTIVITY_LOG_QUERIES_ENABLED', false),
        'slow_query_threshold' => 1000, // milliseconds
        'log_bindings' => false,
        'ignored_tables' => [
            'activity_log',
            'activity_log_statistics',
            'sessions',
            'cache',
            'jobs',
            'failed_jobs',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Background Job Logging
    |--------------------------------------------------------------------------
    |
    | Settings for background job logging.
    |
    */

    'jobs' => [
        'enabled' => env('ACTIVITY_LOG_JOBS_ENABLED', true),
        'log_payload' => false,
        'log_exceptions' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Settings
    |--------------------------------------------------------------------------
    |
    | Settings to optimize performance.
    |
    */

    'queue_activities' => env('ACTIVITY_LOG_QUEUE', false),
    'queue_connection' => env('ACTIVITY_LOG_QUEUE_CONNECTION', 'database'),
    'cache_ttl' => 300, // 5 minutes

    /*
    |--------------------------------------------------------------------------
    | Dashboard Settings
    |--------------------------------------------------------------------------
    |
    | Settings for the activity log dashboard.
    |
    */

    'dashboard' => [
        'items_per_page' => 50,
        'max_export_rows' => 10000,
        'chart_days' => 30,
        'realtime_refresh' => 30, // seconds
    ],
];