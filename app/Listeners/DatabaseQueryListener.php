<?php

namespace App\Listeners;

use Exception;
use App\Services\QueryLogService;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\Log;

/**
 * Database query monitoring and performance analysis listener for agricultural system.
 * 
 * Monitors all database queries executed in the agricultural microgreens management
 * system for performance analysis, optimization opportunities, and critical slow
 * query detection. Provides detailed logging of query patterns, execution times,
 * and model associations for system optimization and troubleshooting.
 * 
 * @business_domain Database performance monitoring for agricultural operations
 * @performance_monitoring Query execution time tracking and slow query detection
 * @agricultural_context Monitors crop, order, inventory database performance
 * @optimization_support Identifies N+1 queries and performance bottlenecks
 */
class DatabaseQueryListener
{
    /**
     * Service for structured database query logging and analysis.
     * 
     * @var QueryLogService Service managing query performance data and analytics
     */
    protected QueryLogService $queryLogService;

    /**
     * Initialize database query listener with logging service.
     * 
     * @param QueryLogService $queryLogService Service for query logging and analysis
     */
    public function __construct(QueryLogService $queryLogService)
    {
        $this->queryLogService = $queryLogService;
    }

    /**
     * Handle database query execution event with performance monitoring.
     * 
     * Processes database queries for logging, performance analysis, and critical
     * slow query detection. Extracts model context for agricultural business
     * query categorization and monitors system performance patterns.
     * 
     * @param QueryExecuted $event Laravel query event with SQL and timing data
     * @return void
     * 
     * @performance_monitoring Query execution time and frequency tracking
     * @agricultural_analysis Categorizes queries by agricultural model types
     * @critical_alerting Logs exceptionally slow queries for immediate attention
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
        } catch (Exception $e) {
            Log::error('Failed to log database query', [
                'error' => $e->getMessage(),
                'sql' => $event->sql,
            ]);
        }
    }

    /**
     * Extract agricultural model information from database query for categorization.
     * 
     * Analyzes SQL queries to determine which agricultural model is being accessed
     * (crops, orders, products, etc.) for better query categorization and
     * performance monitoring by business domain.
     * 
     * @param QueryExecuted $event Query event containing SQL and binding data
     * @return string|null Fully qualified model class name or null if not determinable
     * 
     * @sql_analysis Pattern matching to identify table and corresponding model
     * @agricultural_context Maps database tables to agricultural business models
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
     * Determine if database queries should be logged based on environment and configuration.
     * 
     * Applies environment-specific logging rules to prevent overwhelming logs in
     * testing environments while maintaining production monitoring capabilities
     * for agricultural system performance analysis.
     * 
     * @return bool True if query logging is enabled, false to skip
     * 
     * @environment_aware Different logging behavior for testing vs production
     * @configuration_driven Respects system settings for query logging preferences
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