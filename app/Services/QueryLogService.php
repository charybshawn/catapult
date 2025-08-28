<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

/**
 * Agricultural database query monitoring and performance analysis service.
 * 
 * Provides comprehensive query logging, slow query detection, and performance
 * analysis specifically for agricultural database operations. Monitors crop management,
 * order processing, and inventory queries to identify optimization opportunities
 * and ensure reliable agricultural system performance.
 *
 * @business_domain Agricultural database performance monitoring
 * @related_services ActivityLogService, StatisticsService
 * @used_by Database performance monitoring, optimization analysis, debugging
 * @performance_focus Slow query detection and duplicate query identification
 * @agricultural_context Monitors agricultural model queries for optimization
 */
class QueryLogService
{
    protected array $queries = [];
    protected bool $enabled = false;

    /**
     * Initialize query logging service with configuration-based enablement.
     * 
     * Sets up query logging based on application configuration for
     * agricultural database performance monitoring.
     */
    public function __construct()
    {
        $this->enabled = config('logging.enable_query_logging', false);
    }

    /**
     * Log agricultural database query with performance metrics.
     * 
     * Records database queries for agricultural operations including execution time,
     * memory usage, and model context. Automatically detects slow queries that
     * could impact agricultural system performance during critical operations.
     *
     * @param string $sql Raw SQL query executed
     * @param array $bindings Query parameter bindings
     * @param float $time Execution time in milliseconds
     * @param string|null $model Associated agricultural model (Crop, Product, Order)
     * @return void
     * @agricultural_context Monitors agricultural model query performance
     * @slow_query_detection Automatically logs queries exceeding threshold
     */
    public function logQuery(string $sql, array $bindings, float $time, ?string $model = null): void
    {
        if (!$this->enabled) {
            return;
        }

        $query = [
            'sql' => $sql,
            'bindings' => $bindings,
            'time' => $time,
            'model' => $model,
            'timestamp' => microtime(true),
            'memory' => memory_get_usage(true),
        ];

        $this->queries[] = $query;

        // Log slow queries
        if ($time > config('logging.slow_query_threshold', 1000)) {
            $this->logSlowQuery($query);
        }

        // Log to activity log if enabled
        if (config('logging.persist_query_logs', false)) {
            $this->persistQuery($query);
        }
    }

    /**
     * Log custom agricultural operation query with detailed context.
     * 
     * Records complex agricultural queries with descriptive context for
     * operations like crop planning calculations, inventory aggregations,
     * or order processing workflows.
     *
     * @param string $description Human-readable operation description
     * @param string $sql Raw SQL query executed
     * @param array $bindings Query parameter bindings
     * @param float|null $time Execution time in milliseconds
     * @param string|null $model Associated agricultural model
     * @param mixed $modelId Specific model instance ID
     * @return void
     * @agricultural_context Documents complex agricultural operations for analysis
     */
    public function logCustomQuery(
        string $description,
        string $sql,
        array $bindings,
        ?float $time = null,
        ?string $model = null,
        $modelId = null
    ): void {
        activity('custom_query')
            ->withProperties([
                'description' => $description,
                'sql' => $sql,
                'bindings' => $bindings,
                'time' => $time,
                'model' => $model,
                'model_id' => $modelId,
            ])
            ->log($description);
    }

    /**
     * Log complex agricultural workflow operations with comprehensive metrics.
     * 
     * Records multi-query agricultural operations like crop plan generation,
     * recurring order processing, or inventory synchronization with performance
     * metrics and context for optimization analysis.
     *
     * @param string $operation Operation name (e.g., 'crop_plan_generation')
     * @param array $details Operation-specific details and metrics
     * @param string|null $model Primary agricultural model involved
     * @param mixed $modelId Specific model instance ID
     * @return void
     * @agricultural_context Tracks complex agricultural workflow performance
     */
    public function logComplexOperation(
        string $operation,
        array $details,
        ?string $model = null,
        $modelId = null
    ): void {
        activity('complex_query')
            ->withProperties([
                'operation' => $operation,
                'details' => $details,
                'model' => $model,
                'model_id' => $modelId,
                'query_count' => count($this->queries),
                'total_time' => array_sum(array_column($this->queries, 'time')),
            ])
            ->log("Complex operation: {$operation}");
    }

    /**
     * Log a slow query.
     */
    protected function logSlowQuery(array $query): void
    {
        Log::warning('Slow query detected', [
            'sql' => $query['sql'],
            'bindings' => $query['bindings'],
            'time' => $query['time'],
            'model' => $query['model'],
        ]);

        activity('slow_query')
            ->withProperties($query)
            ->log('Slow query executed');
    }

    /**
     * Persist query to activity log.
     */
    protected function persistQuery(array $query): void
    {
        activity('database_query')
            ->withProperties($query)
            ->log('Database query executed');
    }

    /**
     * Generate performance statistics for specific agricultural models.
     * 
     * Analyzes query performance for agricultural models (Crop, Product, Order)
     * including execution times, query counts, and slow query identification.
     * Essential for optimizing agricultural database performance.
     *
     * @param string $model Agricultural model class name
     * @param mixed $modelId Specific model instance ID (optional)
     * @return array Performance statistics including query counts and timing
     * @caching 5-minute cache for performance analysis
     * @agricultural_context Analyzes performance for agricultural model operations
     */
    public function getStatisticsForModel(string $model, $modelId = null): array
    {
        $cacheKey = "query_stats_{$model}" . ($modelId ? "_{$modelId}" : '');
        
        return Cache::remember($cacheKey, 300, function () use ($model, $modelId) {
            $activities = activity()
                ->inLog('database_query')
                ->where('properties->model', $model);

            if ($modelId) {
                $activities->where('properties->model_id', $modelId);
            }

            $queries = $activities->get();

            return [
                'total_queries' => $queries->count(),
                'total_time' => $queries->sum('properties.time'),
                'average_time' => $queries->avg('properties.time'),
                'slow_queries' => $queries->where('properties.time', '>', config('logging.slow_query_threshold', 1000))->count(),
                'query_types' => $this->categorizeQueries($queries),
            ];
        });
    }

    /**
     * Categorize queries by type.
     */
    protected function categorizeQueries(Collection $queries): array
    {
        $categories = [
            'select' => 0,
            'insert' => 0,
            'update' => 0,
            'delete' => 0,
            'other' => 0,
        ];

        foreach ($queries as $query) {
            $sql = strtolower($query->properties['sql'] ?? '');
            
            if (str_starts_with($sql, 'select')) {
                $categories['select']++;
            } elseif (str_starts_with($sql, 'insert')) {
                $categories['insert']++;
            } elseif (str_starts_with($sql, 'update')) {
                $categories['update']++;
            } elseif (str_starts_with($sql, 'delete')) {
                $categories['delete']++;
            } else {
                $categories['other']++;
            }
        }

        return $categories;
    }

    /**
     * Get current session queries.
     */
    public function getSessionQueries(): array
    {
        return $this->queries;
    }

    /**
     * Clear session queries.
     */
    public function clearSessionQueries(): void
    {
        $this->queries = [];
    }

    /**
     * Generate comprehensive agricultural database query performance report.
     * 
     * Creates detailed performance analysis including total execution times,
     * slow query identification, duplicate query detection, and model-specific
     * metrics for agricultural database optimization.
     *
     * @return array Comprehensive query performance report including:
     *   - total_queries: Total query count for session
     *   - total_time: Cumulative execution time
     *   - average_time: Average query execution time
     *   - slow_queries: Number of slow queries detected
     *   - memory_peak: Peak memory usage
     *   - queries_by_model: Agricultural model query breakdown
     *   - duplicate_queries: Potential optimization opportunities
     * @agricultural_context Provides agricultural database optimization insights
     */
    public function getQueryReport(): array
    {
        $totalQueries = count($this->queries);
        $totalTime = array_sum(array_column($this->queries, 'time'));
        $slowQueries = array_filter($this->queries, fn($q) => $q['time'] > config('logging.slow_query_threshold', 1000));

        return [
            'total_queries' => $totalQueries,
            'total_time' => $totalTime,
            'average_time' => $totalQueries > 0 ? $totalTime / $totalQueries : 0,
            'slow_queries' => count($slowQueries),
            'memory_peak' => memory_get_peak_usage(true),
            'queries_by_model' => $this->groupQueriesByModel(),
            'duplicate_queries' => $this->findDuplicateQueries(),
        ];
    }

    /**
     * Group queries by model.
     */
    protected function groupQueriesByModel(): array
    {
        $grouped = [];
        
        foreach ($this->queries as $query) {
            $model = $query['model'] ?? 'Unknown';
            if (!isset($grouped[$model])) {
                $grouped[$model] = [
                    'count' => 0,
                    'total_time' => 0,
                ];
            }
            $grouped[$model]['count']++;
            $grouped[$model]['total_time'] += $query['time'];
        }

        return $grouped;
    }

    /**
     * Identify duplicate queries for agricultural database optimization.
     * 
     * Detects repeated queries that could indicate N+1 problems or
     * inefficient data loading patterns in agricultural operations.
     * Essential for optimizing crop management and inventory queries.
     *
     * @return array Duplicate query analysis with execution counts and times
     * @agricultural_context Identifies optimization opportunities in agricultural queries
     */
    protected function findDuplicateQueries(): array
    {
        $queryHashes = [];
        $duplicates = [];

        foreach ($this->queries as $query) {
            $hash = md5($query['sql'] . serialize($query['bindings']));
            
            if (isset($queryHashes[$hash])) {
                $queryHashes[$hash]['count']++;
                $queryHashes[$hash]['total_time'] += $query['time'];
            } else {
                $queryHashes[$hash] = [
                    'sql' => $query['sql'],
                    'bindings' => $query['bindings'],
                    'count' => 1,
                    'total_time' => $query['time'],
                ];
            }
        }

        foreach ($queryHashes as $hash => $data) {
            if ($data['count'] > 1) {
                $duplicates[] = $data;
            }
        }

        return $duplicates;
    }

    /**
     * Enable query logging.
     */
    public function enable(): void
    {
        $this->enabled = true;
    }

    /**
     * Disable query logging.
     */
    public function disable(): void
    {
        $this->enabled = false;
    }
}