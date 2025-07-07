<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Crop Management Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains all crop-related configuration values including
    | stage durations, growth parameters, and crop lifecycle settings.
    |
    */

    /**
     * Default stage durations in days.
     * These values are used as defaults when creating new recipes.
     */
    'stage_durations' => [
        /**
         * Germination stage duration in days.
         */
        'germination' => (int) env('CROP_GERMINATION_DAYS', 2),

        /**
         * Blackout stage duration in days.
         */
        'blackout' => (int) env('CROP_BLACKOUT_DAYS', 3),

        /**
         * Light stage duration in days.
         */
        'light' => (int) env('CROP_LIGHT_DAYS', 7),
    ],

    /**
     * Crop lifecycle configuration.
     */
    'lifecycle' => [
        /**
         * Enable automatic stage advancement.
         * When enabled, crops will automatically advance stages based on schedule.
         */
        'auto_advance' => env('CROP_AUTO_ADVANCE', true),

        /**
         * Maximum crop age in days before considered expired.
         */
        'max_age_days' => (int) env('CROP_MAX_AGE_DAYS', 30),

        /**
         * Default starting stage for new crops.
         */
        'default_start_stage' => env('CROP_DEFAULT_START_STAGE', 'germination'),
    ],

    /**
     * Crop alert configuration.
     */
    'alerts' => [
        /**
         * Enable stage transition alerts.
         */
        'stage_transition_enabled' => env('CROP_STAGE_ALERTS_ENABLED', true),

        /**
         * Hours before stage transition to send alert.
         */
        'advance_warning_hours' => (int) env('CROP_ADVANCE_WARNING_HOURS', 12),

        /**
         * Enable overdue alerts for missed transitions.
         */
        'overdue_alerts_enabled' => env('CROP_OVERDUE_ALERTS_ENABLED', true),

        /**
         * Hours after missed transition to send overdue alert.
         */
        'overdue_threshold_hours' => (int) env('CROP_OVERDUE_THRESHOLD_HOURS', 24),
    ],

    /**
     * Crop batch processing configuration.
     */
    'batch' => [
        /**
         * Enable batch processing for crops with same recipe and planting date.
         */
        'enabled' => env('CROP_BATCH_ENABLED', true),

        /**
         * Maximum number of crops to process in a single batch.
         */
        'max_size' => (int) env('CROP_BATCH_MAX_SIZE', 50),

        /**
         * Group crops by recipe and planting date for batch operations.
         */
        'group_by_recipe_date' => env('CROP_BATCH_GROUP_BY_RECIPE_DATE', true),
    ],

    /**
     * Crop watering configuration.
     */
    'watering' => [
        /**
         * Default watering suspension hours before harvest.
         */
        'default_suspension_hours' => (int) env('CROP_DEFAULT_WATER_SUSPENSION_HOURS', 24),

        /**
         * Enable automatic watering suspension.
         */
        'auto_suspend_enabled' => env('CROP_AUTO_SUSPEND_WATER', true),
    ],

    /**
     * Crop tracking configuration.
     */
    'tracking' => [
        /**
         * Track time in each stage.
         */
        'track_stage_time' => env('CROP_TRACK_STAGE_TIME', true),

        /**
         * Track total crop age.
         */
        'track_total_age' => env('CROP_TRACK_TOTAL_AGE', true),

        /**
         * Update frequency for time tracking in minutes.
         */
        'update_frequency_minutes' => (int) env('CROP_TIME_UPDATE_FREQUENCY', 60),
    ],

    /**
     * Crop validation configuration.
     */
    'validation' => [
        /**
         * Maximum trays per batch.
         */
        'max_trays_per_batch' => (int) env('CROP_MAX_TRAYS_PER_BATCH', 100),

        /**
         * Require recipe for new crops.
         */
        'require_recipe' => env('CROP_REQUIRE_RECIPE', true),

        /**
         * Validate stage transitions.
         */
        'validate_transitions' => env('CROP_VALIDATE_TRANSITIONS', true),
    ],
];