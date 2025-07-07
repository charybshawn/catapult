<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Harvest Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains all harvest-related configuration values including
    | yield calculations, recommendations, and harvest data analysis.
    |
    */

    /**
     * Yield calculation configuration.
     */
    'yield' => [
        /**
         * Number of months of historical data to consider for yield calculations.
         */
        'history_months' => (int) env('HARVEST_HISTORY_MONTHS', 6),

        /**
         * Decay factor for exponential weighting of historical data.
         * Weight = e^(-days/decay_factor)
         */
        'decay_factor' => (int) env('HARVEST_DECAY_FACTOR', 30),

        /**
         * Threshold percentages for yield recommendations.
         */
        'thresholds' => [
            /**
             * Percentage difference considered "matching well".
             */
            'matching_well' => (float) env('HARVEST_THRESHOLD_MATCHING', 5.0),

            /**
             * Percentage over expected to trigger "significantly exceeds" recommendation.
             */
            'significantly_over' => (float) env('HARVEST_THRESHOLD_OVER', 15.0),

            /**
             * Percentage under expected to trigger "below expectations" recommendation.
             */
            'significantly_under' => (float) env('HARVEST_THRESHOLD_UNDER', -15.0),
        ],
    ],

    /**
     * Harvest tracking configuration.
     */
    'tracking' => [
        /**
         * Track individual tray weights.
         */
        'track_tray_weights' => env('HARVEST_TRACK_TRAY_WEIGHTS', true),

        /**
         * Track harvest quality metrics.
         */
        'track_quality' => env('HARVEST_TRACK_QUALITY', true),

        /**
         * Default quality grade if not specified.
         */
        'default_quality_grade' => env('HARVEST_DEFAULT_QUALITY', 'A'),
    ],

    /**
     * Harvest planning configuration.
     */
    'planning' => [
        /**
         * Default buffer percentage for harvest planning.
         */
        'default_buffer_percentage' => (float) env('HARVEST_DEFAULT_BUFFER', 10.0),

        /**
         * Use weighted yield for planning calculations.
         */
        'use_weighted_yield' => env('HARVEST_USE_WEIGHTED_YIELD', true),
    ],

    /**
     * Harvest notification configuration.
     */
    'notifications' => [
        /**
         * Send notifications when harvest is ready.
         */
        'notify_harvest_ready' => env('HARVEST_NOTIFY_READY', true),

        /**
         * Hours before expected harvest to send notification.
         */
        'advance_notice_hours' => (int) env('HARVEST_ADVANCE_NOTICE_HOURS', 24),
    ],
];