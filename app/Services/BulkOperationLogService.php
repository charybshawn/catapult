<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BulkOperationLogService
{
    /**
     * Log the start of a bulk operation.
     */
    public function logOperationStart(
        string $operationId,
        string $operation,
        string $model,
        int $totalItems,
        array $metadata = []
    ): void {
        activity('bulk_operation_start')
            ->withProperties([
                'operation_id' => $operationId,
                'operation' => $operation,
                'model' => $model,
                'total_items' => $totalItems,
                'metadata' => $metadata,
                'started_at' => now()->toIso8601String(),
            ])
            ->log("Bulk operation started: {$operation}");

        // Store operation state
        Cache::put("bulk_op_{$operationId}", [
            'operation' => $operation,
            'model' => $model,
            'total_items' => $totalItems,
            'processed_items' => 0,
            'success_count' => 0,
            'failure_count' => 0,
            'started_at' => microtime(true),
            'metadata' => $metadata,
        ], 3600);
    }

    /**
     * Log progress of a bulk operation.
     */
    public function logOperationProgress(
        string $operationId,
        int $processedItems,
        array $details = []
    ): void {
        $state = Cache::get("bulk_op_{$operationId}");
        
        if (!$state) {
            return;
        }

        $state['processed_items'] = $processedItems;
        $progress = ($processedItems / $state['total_items']) * 100;

        activity('bulk_operation_progress')
            ->withProperties([
                'operation_id' => $operationId,
                'operation' => $state['operation'],
                'processed_items' => $processedItems,
                'total_items' => $state['total_items'],
                'progress_percentage' => $progress,
                'details' => $details,
            ])
            ->log("Bulk operation progress: {$progress}%");

        Cache::put("bulk_op_{$operationId}", $state, 3600);
    }

    /**
     * Log completion of a bulk operation.
     */
    public function logOperationComplete(
        string $operationId,
        int $successCount,
        int $failureCount = 0,
        array $summary = []
    ): void {
        $state = Cache::get("bulk_op_{$operationId}");
        
        if (!$state) {
            return;
        }

        $duration = microtime(true) - $state['started_at'];
        $totalProcessed = $successCount + $failureCount;

        activity('bulk_operation_complete')
            ->withProperties([
                'operation_id' => $operationId,
                'operation' => $state['operation'],
                'model' => $state['model'],
                'total_items' => $state['total_items'],
                'processed_items' => $totalProcessed,
                'success_count' => $successCount,
                'failure_count' => $failureCount,
                'success_rate' => $totalProcessed > 0 ? ($successCount / $totalProcessed) * 100 : 0,
                'duration' => $duration,
                'items_per_second' => $duration > 0 ? $totalProcessed / $duration : 0,
                'summary' => $summary,
                'completed_at' => now()->toIso8601String(),
            ])
            ->log("Bulk operation completed: {$state['operation']}");

        // Update statistics
        $this->updateOperationStatistics($state['operation'], $state['model'], $successCount, $failureCount, $duration);

        // Clean up cached state
        Cache::forget("bulk_op_{$operationId}");
    }

    /**
     * Log a bulk operation error.
     */
    public function logOperationError(
        string $operationId,
        string $error,
        array $context = []
    ): void {
        $state = Cache::get("bulk_op_{$operationId}");

        activity('bulk_operation_error')
            ->withProperties([
                'operation_id' => $operationId,
                'operation' => $state['operation'] ?? 'unknown',
                'error' => $error,
                'context' => $context,
                'occurred_at' => now()->toIso8601String(),
            ])
            ->log("Bulk operation error: {$error}");

        if ($state) {
            $state['failure_count'] = ($state['failure_count'] ?? 0) + 1;
            Cache::put("bulk_op_{$operationId}", $state, 3600);
        }
    }

    /**
     * Log item processing within a bulk operation.
     */
    public function logItemProcessed(
        string $operationId,
        $itemId,
        bool $success,
        array $details = []
    ): void {
        activity('bulk_operation_item')
            ->withProperties([
                'operation_id' => $operationId,
                'item_id' => $itemId,
                'success' => $success,
                'details' => $details,
            ])
            ->log($success ? 'Item processed successfully' : 'Item processing failed');
    }

    /**
     * Update operation statistics.
     */
    protected function updateOperationStatistics(
        string $operation,
        string $model,
        int $successCount,
        int $failureCount,
        float $duration
    ): void {
        $key = "bulk_stats_{$operation}_{$model}_" . now()->format('Y-m-d');
        $stats = Cache::get($key, [
            'total_operations' => 0,
            'total_items' => 0,
            'success_items' => 0,
            'failure_items' => 0,
            'total_duration' => 0,
            'min_duration' => null,
            'max_duration' => null,
        ]);

        $stats['total_operations']++;
        $stats['total_items'] += $successCount + $failureCount;
        $stats['success_items'] += $successCount;
        $stats['failure_items'] += $failureCount;
        $stats['total_duration'] += $duration;
        $stats['min_duration'] = $stats['min_duration'] === null 
            ? $duration 
            : min($stats['min_duration'], $duration);
        $stats['max_duration'] = $stats['max_duration'] === null 
            ? $duration 
            : max($stats['max_duration'], $duration);

        Cache::put($key, $stats, 86400); // Store for 24 hours
    }

    /**
     * Get bulk operation statistics.
     */
    public function getOperationStatistics(
        string $operation = null,
        string $model = null,
        Carbon $date = null
    ): array {
        $date = $date ?? now();
        
        if ($operation && $model) {
            $key = "bulk_stats_{$operation}_{$model}_" . $date->format('Y-m-d');
            $stats = Cache::get($key, []);
            
            if (!empty($stats) && $stats['total_items'] > 0) {
                $stats['success_rate'] = ($stats['success_items'] / $stats['total_items']) * 100;
                $stats['average_duration'] = $stats['total_duration'] / $stats['total_operations'];
                $stats['average_items_per_operation'] = $stats['total_items'] / $stats['total_operations'];
            }
            
            return $stats;
        }

        // Get all operations from activities
        $operations = activity()
            ->inLog('bulk_operation_complete')
            ->whereDate('created_at', $date)
            ->get()
            ->map(function ($activity) {
                return [
                    'operation' => $activity->properties['operation'],
                    'model' => $activity->properties['model'],
                ];
            })
            ->unique(function ($item) {
                return $item['operation'] . $item['model'];
            });

        $allStats = [];
        foreach ($operations as $op) {
            $key = "{$op['operation']} - {$op['model']}";
            $allStats[$key] = $this->getOperationStatistics($op['operation'], $op['model'], $date);
        }

        return $allStats;
    }

    /**
     * Get active bulk operations.
     */
    public function getActiveOperations(): array
    {
        $activeOps = [];
        $keys = Cache::get('bulk_op_*');

        foreach ($keys as $key) {
            $state = Cache::get($key);
            if ($state) {
                $operationId = str_replace('bulk_op_', '', $key);
                $duration = microtime(true) - $state['started_at'];
                
                $activeOps[] = [
                    'operation_id' => $operationId,
                    'operation' => $state['operation'],
                    'model' => $state['model'],
                    'progress' => ($state['processed_items'] / $state['total_items']) * 100,
                    'processed_items' => $state['processed_items'],
                    'total_items' => $state['total_items'],
                    'duration' => $duration,
                    'items_per_second' => $duration > 0 ? $state['processed_items'] / $duration : 0,
                    'estimated_completion' => $this->estimateCompletion($state, $duration),
                ];
            }
        }

        return $activeOps;
    }

    /**
     * Estimate completion time for an operation.
     */
    protected function estimateCompletion(array $state, float $currentDuration): ?Carbon
    {
        if ($state['processed_items'] == 0 || $currentDuration == 0) {
            return null;
        }

        $itemsPerSecond = $state['processed_items'] / $currentDuration;
        $remainingItems = $state['total_items'] - $state['processed_items'];
        $remainingSeconds = $remainingItems / $itemsPerSecond;

        return now()->addSeconds($remainingSeconds);
    }

    /**
     * Get bulk operation history.
     */
    public function getOperationHistory(int $limit = 50, string $operation = null): array
    {
        $query = activity()
            ->inLog('bulk_operation_complete')
            ->latest()
            ->limit($limit);

        if ($operation) {
            $query->where('properties->operation', $operation);
        }

        return $query->get()->map(function ($activity) {
            $props = $activity->properties;
            return [
                'operation_id' => $props['operation_id'],
                'operation' => $props['operation'],
                'model' => $props['model'],
                'total_items' => $props['total_items'],
                'success_count' => $props['success_count'],
                'failure_count' => $props['failure_count'],
                'success_rate' => $props['success_rate'],
                'duration' => $props['duration'],
                'items_per_second' => $props['items_per_second'],
                'completed_at' => $activity->created_at,
            ];
        })->toArray();
    }
}