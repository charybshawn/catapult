<?php

namespace App\Traits\Logging;

use App\Services\QueryLogService;
use Illuminate\Support\Facades\DB;

trait LogsQueries
{
    /**
     * Boot the logs queries trait.
     */
    public static function bootLogsQueries(): void
    {
        if (config('logging.enable_query_logging', false)) {
            DB::listen(function ($query) {
                app(QueryLogService::class)->logQuery(
                    $query->sql,
                    $query->bindings,
                    $query->time,
                    static::class
                );
            });
        }
    }

    /**
     * Log a custom query with context.
     */
    public function logQuery(string $description, string $sql, array $bindings = [], float $time = null): void
    {
        app(QueryLogService::class)->logCustomQuery(
            $description,
            $sql,
            $bindings,
            $time,
            static::class,
            $this->getKey()
        );
    }

    /**
     * Log a complex query operation.
     */
    public function logComplexQuery(string $operation, array $details): void
    {
        app(QueryLogService::class)->logComplexOperation(
            $operation,
            $details,
            static::class,
            $this->getKey()
        );
    }

    /**
     * Log query performance metrics.
     */
    public function logQueryPerformance(string $queryType, float $executionTime, int $rowsAffected = 0): void
    {
        activity('query_performance')
            ->performedOn($this)
            ->withProperties([
                'query_type' => $queryType,
                'execution_time' => $executionTime,
                'rows_affected' => $rowsAffected,
                'model' => static::class,
                'threshold_exceeded' => $executionTime > config('logging.slow_query_threshold', 1000),
            ])
            ->log("Query performance: {$queryType}");
    }

    /**
     * Enable query logging for a specific operation.
     */
    public function withQueryLogging(callable $callback)
    {
        $originalState = config('logging.enable_query_logging');
        config(['logging.enable_query_logging' => true]);

        try {
            return $callback();
        } finally {
            config(['logging.enable_query_logging' => $originalState]);
        }
    }

    /**
     * Get query statistics for this model instance.
     */
    public function getQueryStatistics(): array
    {
        return app(QueryLogService::class)->getStatisticsForModel(
            static::class,
            $this->getKey()
        );
    }
}