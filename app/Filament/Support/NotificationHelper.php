<?php

namespace App\Filament\Support;

use Filament\Notifications\Notification;

class NotificationHelper
{
    /**
     * Send a success notification
     */
    public static function success(string $title, ?string $body = null, bool $persistent = false): void
    {
        $notification = Notification::make()
            ->success()
            ->title($title);

        if ($body) {
            $notification->body($body);
        }

        if ($persistent) {
            $notification->persistent();
        }

        $notification->send();
    }

    /**
     * Send an error notification
     */
    public static function error(string $title, ?string $body = null, bool $persistent = false): void
    {
        $notification = Notification::make()
            ->danger()
            ->title($title);

        if ($body) {
            $notification->body($body);
        }

        if ($persistent) {
            $notification->persistent();
        }

        $notification->send();
    }

    /**
     * Send a warning notification
     */
    public static function warning(string $title, ?string $body = null, bool $persistent = false): void
    {
        $notification = Notification::make()
            ->warning()
            ->title($title);

        if ($body) {
            $notification->body($body);
        }

        if ($persistent) {
            $notification->persistent();
        }

        $notification->send();
    }

    /**
     * Send an info notification
     */
    public static function info(string $title, ?string $body = null, bool $persistent = false): void
    {
        $notification = Notification::make()
            ->info()
            ->title($title);

        if ($body) {
            $notification->body($body);
        }

        if ($persistent) {
            $notification->persistent();
        }

        $notification->send();
    }

    /**
     * Send a batch operation success notification
     */
    public static function batchSuccess(string $operation, int $successCount, ?int $totalCount = null, string $itemType = 'items'): void
    {
        $body = "Successfully {$operation} {$successCount} {$itemType}";

        if ($totalCount !== null && $totalCount !== $successCount) {
            $body .= " out of {$totalCount}";
        }

        $body .= '.';

        self::success(ucfirst($operation) . ' Complete', $body);
    }

    /**
     * Send a batch operation failure notification
     */
    public static function batchFailure(string $operation, int $failedCount, string $itemType = 'items'): void
    {
        self::error(
            ucfirst($operation) . ' Failed',
            "Failed to {$operation} {$failedCount} {$itemType}."
        );
    }

    /**
     * Send a batch operation mixed results notification
     */
    public static function batchMixed(string $operation, int $successCount, int $failedCount, int $skippedCount = 0, string $itemType = 'items'): void
    {
        $message = "Successfully {$operation} {$successCount} {$itemType}.";

        if ($failedCount > 0) {
            $message .= " Failed to {$operation} {$failedCount} {$itemType}.";
        }

        if ($skippedCount > 0) {
            $message .= " Skipped {$skippedCount} {$itemType}.";
        }

        if ($successCount > 0) {
            self::success(ucfirst($operation) . ' Complete', $message);
        } else {
            self::warning('No Changes Made', $message);
        }
    }

    /**
     * Send warnings notification for batch operations
     */
    public static function batchWarnings(array $warnings, int $maxDisplay = 5): void
    {
        if (empty($warnings)) {
            return;
        }

        $body = implode("\n", array_slice($warnings, 0, $maxDisplay));

        if (count($warnings) > $maxDisplay) {
            $body .= "\n...and " . (count($warnings) - $maxDisplay) . " more";
        }

        self::warning('Warnings', $body, true);
    }
}