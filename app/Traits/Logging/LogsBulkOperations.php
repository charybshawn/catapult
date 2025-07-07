<?php

namespace App\Traits\Logging;

use App\Services\BulkOperationLogService;
use Illuminate\Support\Str;

trait LogsBulkOperations
{
    /**
     * Log the start of a bulk operation.
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
     * Log a batch update operation.
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
     * Execute a bulk operation with automatic logging.
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
        } catch (\Exception $e) {
            $instance->logBulkOperationError($operationId, $e->getMessage(), [
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}