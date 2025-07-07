<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Backup Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains all backup-related configuration values including
    | file size limits, retention policies, and backup paths.
    |
    */

    /**
     * Backup storage configuration.
     */
    'storage' => [
        /**
         * Default backup path relative to storage/app.
         * Standard location: storage/app/backups/database/
         */
        'path' => env('BACKUP_PATH', 'backups/database'),

        /**
         * Disk to use for backups.
         */
        'disk' => env('BACKUP_DISK', 'local'),
    ],

    /**
     * Backup size limits.
     */
    'limits' => [
        /**
         * Warning threshold for backup file size in MB.
         * A warning will be logged if backup exceeds this size.
         */
        'warning_size_mb' => (int) env('BACKUP_WARNING_SIZE_MB', 100),

        /**
         * Maximum allowed backup file size in MB.
         * Backups larger than this will fail.
         */
        'max_size_mb' => (int) env('BACKUP_MAX_SIZE_MB', 500),
    ],

    /**
     * Backup retention configuration.
     */
    'retention' => [
        /**
         * Number of backups to keep.
         * Older backups will be automatically deleted.
         */
        'keep_count' => (int) env('BACKUP_KEEP_COUNT', 10),

        /**
         * Number of days to keep backups.
         * Backups older than this will be deleted.
         */
        'keep_days' => (int) env('BACKUP_KEEP_DAYS', 30),

        /**
         * Enable automatic cleanup of old backups.
         */
        'auto_cleanup' => env('BACKUP_AUTO_CLEANUP', true),
    ],

    /**
     * Backup processing configuration.
     */
    'processing' => [
        /**
         * Exclude database views from backup.
         */
        'exclude_views' => env('BACKUP_EXCLUDE_VIEWS', true),

        /**
         * Enable compression for backup files.
         */
        'compress' => env('BACKUP_COMPRESS', false),

        /**
         * Timeout for backup operations in seconds.
         */
        'timeout' => (int) env('BACKUP_TIMEOUT', 300),
    ],

    /**
     * Backup scheduling configuration.
     */
    'schedule' => [
        /**
         * Enable automatic daily backups.
         */
        'daily_enabled' => env('BACKUP_DAILY_ENABLED', true),

        /**
         * Time to run daily backup (24-hour format).
         */
        'daily_time' => env('BACKUP_DAILY_TIME', '02:00'),

        /**
         * Enable automatic weekly backups.
         */
        'weekly_enabled' => env('BACKUP_WEEKLY_ENABLED', true),

        /**
         * Day of week for weekly backup (0 = Sunday, 6 = Saturday).
         */
        'weekly_day' => (int) env('BACKUP_WEEKLY_DAY', 0),
    ],

    /**
     * Backup notification configuration.
     */
    'notifications' => [
        /**
         * Send email on backup failure.
         */
        'email_on_failure' => env('BACKUP_EMAIL_ON_FAILURE', true),

        /**
         * Send email on successful backup.
         */
        'email_on_success' => env('BACKUP_EMAIL_ON_SUCCESS', false),

        /**
         * Email recipients for backup notifications.
         */
        'recipients' => env('BACKUP_EMAIL_RECIPIENTS', ''),
    ],
];