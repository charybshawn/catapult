<?php

namespace App\Listeners;

use App\Services\QueryLogService;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\Log;

class DatabaseQueryListener
{
    protected QueryLogService $queryLogService;

    public function __construct(QueryLogService $queryLogService)
    {
        $this->queryLogService = $queryLogService;
    }

    /**
     * Handle the query executed event.
     */
    public function handle(QueryExecuted $event): void
    {
        if (!config('logging.enable_query_logging', false)) {
            return;
        }

        // Skip logging for activity log queries to prevent infinite loops
        if (str_contains($event->sql, 'activity_log')) {
            return;
        }

        try {
            // Get the model if this query is from Eloquent
            $model = $this->extractModelFromQuery($event);
            
            $this->queryLogService->logQuery(
                $event->sql,
                $event->bindings,
                $event->time,
                $model
            );

            // Log exceptionally slow queries
            if ($event->time > config('logging.critical_query_threshold', 5000)) {
                Log::critical('Critical slow query detected', [
                    'sql' => $event->sql,
                    'time' => $event->time,
                    'connection' => $event->connectionName,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to log database query', [
                'error' => $e->getMessage(),
                'sql' => $event->sql,
            ]);
        }
    }

    /**
     * Extract model information from the query.
     */
    protected function extractModelFromQuery(QueryExecuted $event): ?string
    {
        // This is a simplified extraction - you might need to enhance this
        // based on your specific query patterns
        
        $sql = strtolower($event->sql);
        
        // Try to extract table name from common query patterns
        if (preg_match('/from\s+[`"]?(\w+)[`"]?/i', $sql, $matches)) {
            $table = $matches[1];
            
            // Convert table name to model name (simplified)
            $modelName = str_replace('_', '', ucwords($table, '_'));
            
            // Check if model exists
            $fullModelName = "App\\Models\\{$modelName}";
            if (class_exists($fullModelName)) {
                return $fullModelName;
            }
        }

        return null;
    }

    /**
     * Determine if events should be logged.
     */
    public function shouldLog(): bool
    {
        // Skip logging in testing environment unless explicitly enabled
        if (app()->environment('testing') && !config('logging.enable_query_logging_in_tests', false)) {
            return false;
        }

        // Skip logging for console commands unless explicitly enabled
        if (app()->runningInConsole() && !config('logging.enable_query_logging_in_console', false)) {
            return false;
        }

        return true;
    }
}