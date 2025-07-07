<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Inventory Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains all inventory-related configuration values including
    | thresholds for lot depletion alerts and inventory checks.
    |
    */

    /**
     * Low stock threshold percentage (0-100).
     * When inventory falls below this percentage, low stock alerts are triggered.
     */
    'low_stock_threshold' => (float) env('LOW_STOCK_THRESHOLD', 15.0),

    /**
     * Enable automatic depletion checking.
     * When enabled, the system will automatically check for depleted lots and send alerts.
     */
    'depletion_check_enabled' => env('DEPLETION_CHECK_ENABLED', true),

    /**
     * Maximum lot size in grams (for validation).
     */
    'max_lot_size' => (int) env('MAX_LOT_SIZE', 10000),

    /**
     * FIFO (First In, First Out) system configuration.
     */
    'fifo' => [
        /**
         * Enable strict FIFO enforcement.
         * When enabled, older inventory must be consumed before newer inventory.
         */
        'strict_enforcement' => env('FIFO_STRICT_ENFORCEMENT', true),

        /**
         * Allow partial consumption from lots.
         */
        'allow_partial_consumption' => env('FIFO_ALLOW_PARTIAL', true),
    ],

    /**
     * Inventory alert configuration.
     */
    'alerts' => [
        /**
         * Send email alerts for lot depletion.
         */
        'email_on_depletion' => env('INVENTORY_EMAIL_ON_DEPLETION', true),

        /**
         * Send email alerts for low stock.
         */
        'email_on_low_stock' => env('INVENTORY_EMAIL_ON_LOW_STOCK', true),

        /**
         * Days before expiration to send alerts.
         */
        'expiration_warning_days' => (int) env('INVENTORY_EXPIRATION_WARNING_DAYS', 30),
    ],
];