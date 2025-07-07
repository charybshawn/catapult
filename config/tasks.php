<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Task System Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains all task-related configuration values including
    | memory limits, batch sizes, and processing configuration.
    |
    */

    /**
     * Memory limit for task processing in megabytes.
     * Used to prevent memory exhaustion during bulk operations.
     */
    'memory_limit_mb' => (int) env('TASK_MEMORY_LIMIT', 100),

    /**
     * Default batch size for processing tasks.
     * Controls how many items are processed in a single batch.
     */
    'batch_size' => (int) env('TASK_BATCH_SIZE', 100),

    /**
     * Task retry configuration.
     */
    'retry' => [
        /**
         * Maximum number of retry attempts for failed tasks.
         */
        'max_attempts' => (int) env('TASK_MAX_RETRIES', 3),

        /**
         * Delay between retry attempts in seconds.
         */
        'delay_seconds' => (int) env('TASK_RETRY_DELAY', 60),
    ],

    /**
     * Task scheduling configuration.
     */
    'scheduling' => [
        /**
         * Enable automatic task scheduling.
         */
        'enabled' => env('TASK_SCHEDULING_ENABLED', true),

        /**
         * Default timezone for task scheduling.
         */
        'timezone' => env('TASK_TIMEZONE', config('app.timezone', 'UTC')),

        /**
         * Look-ahead period for scheduling in days.
         */
        'lookahead_days' => (int) env('TASK_LOOKAHEAD_DAYS', 7),
    ],

    /**
     * Task notification configuration.
     */
    'notifications' => [
        /**
         * Send notifications for overdue tasks.
         */
        'notify_overdue' => env('TASK_NOTIFY_OVERDUE', true),

        /**
         * Hours before task is due to send reminder.
         */
        'reminder_hours' => (int) env('TASK_REMINDER_HOURS', 24),
    ],

    /**
     * Task types configuration.
     */
    'types' => [
        /**
         * Crop stage transition tasks.
         */
        'crop_stage' => [
            'enabled' => env('TASK_CROP_STAGE_ENABLED', true),
            'batch_process' => env('TASK_CROP_STAGE_BATCH', true),
        ],

        /**
         * Watering suspension tasks.
         */
        'water_suspension' => [
            'enabled' => env('TASK_WATER_SUSPENSION_ENABLED', true),
        ],

        /**
         * Inventory check tasks.
         */
        'inventory_check' => [
            'enabled' => env('TASK_INVENTORY_CHECK_ENABLED', true),
            'frequency_days' => (int) env('TASK_INVENTORY_CHECK_DAYS', 1),
        ],
    ],
];