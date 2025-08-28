<?php

namespace App\Traits\Logging;

use Exception;
use App\Services\BulkOperationLogService;
use Illuminate\Support\Str;

/**
 * Logs Bulk Operations Trait
 * 
 * Comprehensive bulk operation logging for agricultural Eloquent models providing
 * detailed tracking of mass operations essential for agricultural data management
 * and performance monitoring.
 * 
 * @logging_trait Bulk operation logging for agricultural mass data operations
 * @agricultural_use Bulk operation tracking for agricultural data processing (crop batch updates, inventory adjustments, order processing)
 * @performance Performance monitoring for agricultural bulk operations and data processing
 * @audit_trail Detailed audit trails for agricultural mass operations and data changes
 * 
 * Key features:
 * - Progress tracking for long-running agricultural bulk operations
 * - Success/failure metrics for agricultural mass data processing
 * - Error logging with context for agricultural bulk operation troubleshooting
 * - Batch operation logging for agricultural entity updates, deletions, and creation
 * - Automatic logging wrapper for agricultural bulk operation workflows
 * 
 * @package App\Traits\Logging
 * @author Shawn
 * @since 2024
 */
trait LogsBulkOperations
{
    /**
     * Log the start of a bulk operation for agricultural data processing.
     * 
     * @agricultural_context Initiate logging for agricultural bulk operations (crop batch processing, inventory updates)
     * @param string $operation Agricultural operation type (bulk_harvest, inventory_adjustment, price_update)
     * @param int $totalItems Total number of agricultural items to process
     * @param array $metadata Additional agricultural operation metadata and context
     * @return string Operation ID for tracking agricultural bulk operation progress
     */
    public function logBulkOperationStart(string $operation, int $totalItems, array $metadata = []): string
    {
        $operationId = Str::uuid()->toString();
        
        app(BulkOperationLogService::class)->logOperationStart(
            $operationId,
            $operation,
            static::class,
            $totalItems,
            $metadata
        );

        return $operationId;
    }

    /**
     * Log progress of a bulk operation.
     */
    public function logBulkOperationProgress(string $operationId, int $processedItems, array $details = []): void
    {
        app(BulkOperationLogService::class)->logOperationProgress(
            $operationId,
            $processedItems,
            $details
        );
    }

    /**
     * Log completion of a bulk operation.
     */
    public function logBulkOperationComplete(
        string $operationId, 
        int $successCount, 
        int $failureCount = 0, 
        array $summary = []
    ): void {
        app(BulkOperationLogService::class)->logOperationComplete(
            $operationId,
            $successCount,
            $failureCount,
            $summary
        );
    }

    /**
     * Log a bulk operation error.
     */
    public function logBulkOperationError(string $operationId, string $error, array $context = []): void
    {
        app(BulkOperationLogService::class)->logOperationError(
            $operationId,
            $error,
            $context
        );
    }

    /**
     * Log a batch update operation for agricultural entities.
     * 
     * @agricultural_context Batch update logging for agricultural entity mass modifications
     * @param array $ids IDs of agricultural entities being updated
     * @param array $attributes Agricultural attributes being modified in batch operation
     * @param array $results Results of agricultural batch update operation
     * @return void
     */
    public static function logBatchUpdate(array $ids, array $attributes, array $results = []): void
    {
        activity('batch_update')
            ->on(new static)
            ->withProperties([
                'model' => static::class,
                'ids' => $ids,
                'attributes' => array_keys($attributes),
                'count' => count($ids),
                'results' => $results,
            ])
            ->log('Batch update performed');
    }

    /**
     * Log a batch delete operation.
     */
    public static function logBatchDelete(array $ids, array $results = []): void
    {
        activity('batch_delete')
            ->on(new static)
            ->withProperties([
                'model' => static::class,
                'ids' => $ids,
                'count' => count($ids),
                'results' => $results,
            ])
            ->log('Batch delete performed');
    }

    /**
     * Log a batch creation operation.
     */
    public static function logBatchCreate(array $records, array $results = []): void
    {
        activity('batch_create')
            ->on(new static)
            ->withProperties([
                'model' => static::class,
                'count' => count($records),
                'fields' => !empty($records) ? array_keys($records[0]) : [],
                'results' => $results,
            ])
            ->log('Batch create performed');
    }

    /**
     * Execute a bulk operation with automatic logging for agricultural workflows.
     * 
     * @agricultural_context Automated logging wrapper for agricultural bulk operations
     * @param string $operation Agricultural bulk operation identifier
     * @param callable $callback Agricultural bulk operation callback function
     * @param array $metadata Agricultural operation metadata and context
     * @return mixed Bulk operation results with comprehensive logging
     * @error_handling Automatically logs failures for agricultural bulk operation troubleshooting
     */
    public static function withBulkLogging(string $operation, callable $callback, array $metadata = [])
    {
        $instance = new static;
        $operationId = $instance->logBulkOperationStart($operation, 0, $metadata);

        try {
            $result = $callback($operationId);
            
            $successCount = $result['success'] ?? 0;
            $failureCount = $result['failure'] ?? 0;
            $summary = $result['summary'] ?? [];
            
            $instance->logBulkOperationComplete($operationId, $successCount, $failureCount, $summary);
            
            return $result;
        } catch (Exception $e) {
            $instance->logBulkOperationError($operationId, $e->getMessage(), [
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}