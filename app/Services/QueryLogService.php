<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class QueryLogService
{
    protected array $queries = [];
    protected bool $enabled = false;

    public function __construct()
    {
        $this->enabled = config('logging.enable_query_logging', false);
    }

    /**
     * Log a database query.
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
     * Log a custom query with context.
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
     * Log a complex operation.
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
     * Get query statistics for a model.
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
     * Get query report.
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
     * Find duplicate queries.
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