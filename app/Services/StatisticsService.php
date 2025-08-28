<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Spatie\Activitylog\Models\Activity;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

/**
 * Comprehensive agricultural operation statistics and analytics service.
 * 
 * Provides detailed statistical analysis of agricultural activities, system performance,
 * and operational trends for farm management decision-making. Generates comprehensive
 * reports covering activity patterns, user productivity, model interactions,
 * and system performance metrics with agricultural business context.
 *
 * @business_domain Agricultural analytics and operational statistics
 * @related_services CacheService, ActivityLogService
 * @used_by Analytics dashboard, performance monitoring, management reports
 * @caching Extensive 5-minute caching for expensive statistical calculations
 */
class StatisticsService
{
    /**
     * Get comprehensive overview of agricultural operation activity statistics.
     * 
     * Provides high-level metrics about farm activity including total operations,
     * active users, activity diversity, and daily averages. Essential for
     * understanding overall farm productivity and operational patterns.
     *
     * @param Carbon|null $startDate Start date (defaults to 30 days ago)
     * @param Carbon|null $endDate End date (defaults to now)
     * @return array Overview metrics including:
     *   - total_activities: Total agricultural activities
     *   - unique_users: Active team members count
     *   - activity_types: Variety of operations performed
     *   - most_active_log: Primary operation category
     *   - daily_average: Average activities per day
     * @caching 5-minute cache for dashboard performance
     * @agricultural_context Used for farm productivity analysis and staffing insights
     */
    public function getOverview(Carbon $startDate = null, Carbon $endDate = null): array
    {
        $startDate = $startDate ?? now()->subDays(30);
        $endDate = $endDate ?? now();

        return Cache::remember(
            "activity_overview_{$startDate->format('Y-m-d')}_{$endDate->format('Y-m-d')}",
            300,
            function () use ($startDate, $endDate) {
                $query = Activity::whereBetween('created_at', [$startDate, $endDate]);

                return [
                    'total_activities' => $query->count(),
                    'unique_users' => $query->whereNotNull('causer_id')->distinct('causer_id')->count('causer_id'),
                    'activity_types' => $query->distinct('log_name')->count('log_name'),
                    'most_active_log' => $query->groupBy('log_name')
                        ->select('log_name', DB::raw('count(*) as count'))
                        ->orderBy('count', 'desc')
                        ->first(),
                    'daily_average' => $query->count() / $startDate->diffInDays($endDate),
                ];
            }
        );
    }

    /**
     * Analyze agricultural activity trends over time for operational planning.
     * 
     * Generates time-series data showing activity patterns across specified
     * intervals. Critical for identifying seasonal trends, peak operation
     * periods, and optimizing agricultural workflow scheduling.
     *
     * @param Carbon $startDate Start date for trend analysis
     * @param Carbon $endDate End date for trend analysis
     * @param string $interval Time interval ('day', 'hour', 'week')
     * @return array Time-series data with activity counts per interval
     * @caching 5-minute cache for chart rendering performance
     * @agricultural_context Used for seasonal planning and resource allocation
     */
    public function getActivityTrends(
        Carbon $startDate,
        Carbon $endDate,
        string $interval = 'day'
    ): array {
        $cacheKey = "activity_trends_{$startDate->format('Y-m-d')}_{$endDate->format('Y-m-d')}_{$interval}";
        
        return Cache::remember($cacheKey, 300, function () use ($startDate, $endDate, $interval) {
            $period = CarbonPeriod::create($startDate, "1 {$interval}", $endDate);
            $trends = [];

            foreach ($period as $date) {
                $nextDate = $date->copy()->add($interval, 1);
                
                $count = Activity::whereBetween('created_at', [$date, $nextDate])->count();
                
                $trends[] = [
                    'date' => $date->format('Y-m-d H:i:s'),
                    'count' => $count,
                ];
            }

            return $trends;
        });
    }

    /**
     * Identify most frequent agricultural operations for workflow optimization.
     * 
     * Ranks agricultural activities by frequency to identify bottlenecks,
     * optimize workflows, and focus training on most common operations.
     * Essential for process improvement and efficiency analysis.
     *
     * @param int $limit Number of top activities to return
     * @param Carbon|null $startDate Start date (defaults to 30 days ago)
     * @return array Top activities with counts and descriptions
     * @caching 5-minute cache for performance
     * @agricultural_context Identifies most critical farm operations for optimization
     */
    public function getTopActivities(int $limit = 10, Carbon $startDate = null): array
    {
        $startDate = $startDate ?? now()->subDays(30);

        return Cache::remember(
            "top_activities_{$limit}_{$startDate->format('Y-m-d')}",
            300,
            function () use ($limit, $startDate) {
                return Activity::where('created_at', '>=', $startDate)
                    ->groupBy('description', 'log_name')
                    ->select('description', 'log_name', DB::raw('count(*) as count'))
                    ->orderBy('count', 'desc')
                    ->limit($limit)
                    ->get()
                    ->toArray();
            }
        );
    }

    /**
     * Analyze agricultural team member activity patterns and productivity.
     * 
     * Provides detailed insights into user behavior, productivity patterns,
     * and workload distribution across the agricultural team. Essential
     * for performance management and resource allocation decisions.
     *
     * @param Carbon|null $startDate Start date (defaults to 30 days ago)
     * @param Carbon|null $endDate End date (defaults to now)
     * @return array User statistics including:
     *   - most_active_users: Top performers with activity counts
     *   - user_activity_distribution: Workload balance across team
     *   - average_activities_per_user: Team productivity baseline
     * @caching 5-minute cache for team management dashboards
     * @agricultural_context Used for performance reviews and work assignment optimization
     */
    public function getUserStatistics(Carbon $startDate = null, Carbon $endDate = null): array
    {
        $startDate = $startDate ?? now()->subDays(30);
        $endDate = $endDate ?? now();

        return Cache::remember(
            "user_statistics_{$startDate->format('Y-m-d')}_{$endDate->format('Y-m-d')}",
            300,
            function () use ($startDate, $endDate) {
                $query = Activity::whereBetween('created_at', [$startDate, $endDate])
                    ->whereNotNull('causer_id');

                return [
                    'most_active_users' => $query->groupBy('causer_id', 'causer_type')
                        ->select('causer_id', 'causer_type', DB::raw('count(*) as activity_count'))
                        ->orderBy('activity_count', 'desc')
                        ->limit(10)
                        ->get(),
                    'user_activity_distribution' => $query->groupBy('causer_id')
                        ->select(DB::raw('count(*) as activity_count'), DB::raw('count(causer_id) as user_count'))
                        ->groupBy('activity_count')
                        ->orderBy('activity_count')
                        ->get(),
                    'average_activities_per_user' => $query->count() / $query->distinct('causer_id')->count('causer_id'),
                ];
            }
        );
    }

    /**
     * Analyze activity patterns across agricultural entities (crops, products, orders).
     * 
     * Examines how different agricultural models are being interacted with,
     * identifying which entities require most attention and what types of
     * operations are most common. Critical for operational focus and system optimization.
     *
     * @param Carbon|null $startDate Start date (defaults to 30 days ago)
     * @param Carbon|null $endDate End date (defaults to now)
     * @return array Model statistics including:
     *   - activities_by_model: Activity distribution across agricultural entities
     *   - crud_operations: Create/update/delete operation breakdown
     *   - most_modified_records: Entities requiring most maintenance
     * @caching 5-minute cache for operational analysis
     * @agricultural_context Shows which farm operations consume most management time
     */
    public function getModelStatistics(Carbon $startDate = null, Carbon $endDate = null): array
    {
        $startDate = $startDate ?? now()->subDays(30);
        $endDate = $endDate ?? now();

        return Cache::remember(
            "model_statistics_{$startDate->format('Y-m-d')}_{$endDate->format('Y-m-d')}",
            300,
            function () use ($startDate, $endDate) {
                $query = Activity::whereBetween('created_at', [$startDate, $endDate])
                    ->whereNotNull('subject_type');

                return [
                    'activities_by_model' => $query->groupBy('subject_type')
                        ->select('subject_type', DB::raw('count(*) as count'))
                        ->orderBy('count', 'desc')
                        ->get()
                        ->mapWithKeys(function ($item) {
                            return [class_basename($item->subject_type) => $item->count];
                        }),
                    'crud_operations' => $query->whereIn('description', ['created', 'updated', 'deleted'])
                        ->groupBy('description')
                        ->select('description', DB::raw('count(*) as count'))
                        ->pluck('count', 'description'),
                    'most_modified_records' => $query->groupBy('subject_id', 'subject_type')
                        ->select('subject_id', 'subject_type', DB::raw('count(*) as modification_count'))
                        ->orderBy('modification_count', 'desc')
                        ->limit(10)
                        ->get(),
                ];
            }
        );
    }

    /**
     * Analyze system performance metrics for agricultural operation reliability.
     * 
     * Monitors API response times, job processing, query performance, and
     * bulk operations to ensure agricultural systems perform optimally
     * during critical periods like harvest or order processing.
     *
     * @param Carbon|null $startDate Start date (defaults to 7 days ago)
     * @param Carbon|null $endDate End date (defaults to now)
     * @return array Performance metrics across all system components
     * @no_cache Real-time performance analysis
     * @agricultural_context Ensures reliable operation during time-sensitive agricultural activities
     */
    public function getPerformanceMetrics(Carbon $startDate = null, Carbon $endDate = null): array
    {
        $startDate = $startDate ?? now()->subDays(7);
        $endDate = $endDate ?? now();

        return [
            'api_performance' => $this->getApiPerformanceMetrics($startDate, $endDate),
            'job_performance' => $this->getJobPerformanceMetrics($startDate, $endDate),
            'query_performance' => $this->getQueryPerformanceMetrics($startDate, $endDate),
            'bulk_operation_performance' => $this->getBulkOperationPerformanceMetrics($startDate, $endDate),
        ];
    }

    /**
     * Analyze API response performance for agricultural application reliability.
     * 
     * Monitors API endpoint performance to ensure fast response times
     * for critical agricultural operations like crop updates and order processing.
     *
     * @param Carbon $startDate Start date for analysis
     * @param Carbon $endDate End date for analysis
     * @return array API performance metrics with percentiles and status codes
     * @agricultural_context Ensures responsive user experience during peak farm operations
     */
    protected function getApiPerformanceMetrics(Carbon $startDate, Carbon $endDate): array
    {
        $responses = Activity::inLog('api_response')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        if ($responses->isEmpty()) {
            return ['no_data' => true];
        }

        $responseTimes = $responses->pluck('properties.response_time')->filter();

        return [
            'total_requests' => $responses->count(),
            'average_response_time' => $responseTimes->avg(),
            'min_response_time' => $responseTimes->min(),
            'max_response_time' => $responseTimes->max(),
            'percentiles' => [
                '50th' => $this->calculatePercentile($responseTimes->toArray(), 50),
                '95th' => $this->calculatePercentile($responseTimes->toArray(), 95),
                '99th' => $this->calculatePercentile($responseTimes->toArray(), 99),
            ],
            'status_code_distribution' => $responses->groupBy('properties.status_code')
                ->map->count(),
        ];
    }

    /**
     * Analyze background job performance for agricultural automation reliability.
     * 
     * Monitors automated agricultural tasks like crop plan generation,
     * recurring orders, and inventory updates to ensure reliable operation
     * of critical farm automation systems.
     *
     * @param Carbon $startDate Start date for analysis
     * @param Carbon $endDate End date for analysis
     * @return array Job performance metrics including success rates and durations
     * @agricultural_context Critical for automated crop scheduling and order processing
     */
    protected function getJobPerformanceMetrics(Carbon $startDate, Carbon $endDate): array
    {
        $completed = Activity::inLog('job_completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        $failed = Activity::inLog('job_failed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        if ($completed->isEmpty()) {
            return ['no_data' => true];
        }

        $durations = $completed->pluck('properties.duration')->filter();

        return [
            'total_jobs' => $completed->count() + $failed,
            'completed_jobs' => $completed->count(),
            'failed_jobs' => $failed,
            'success_rate' => ($completed->count() / ($completed->count() + $failed)) * 100,
            'average_duration' => $durations->avg(),
            'min_duration' => $durations->min(),
            'max_duration' => $durations->max(),
            'jobs_by_class' => $completed->groupBy('properties.job_class')
                ->map->count()
                ->sortDesc()
                ->take(10),
        ];
    }

    /**
     * Get query performance metrics.
     */
    protected function getQueryPerformanceMetrics(Carbon $startDate, Carbon $endDate): array
    {
        $queries = Activity::inLog('database_query')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        if ($queries->isEmpty()) {
            return ['no_data' => true];
        }

        $executionTimes = $queries->pluck('properties.time')->filter();

        return [
            'total_queries' => $queries->count(),
            'average_execution_time' => $executionTimes->avg(),
            'slow_queries' => $queries->where('properties.time', '>', config('logging.slow_query_threshold', 1000))->count(),
            'queries_by_model' => $queries->groupBy('properties.model')
                ->map->count()
                ->sortDesc()
                ->take(10),
        ];
    }

    /**
     * Get bulk operation performance metrics.
     */
    protected function getBulkOperationPerformanceMetrics(Carbon $startDate, Carbon $endDate): array
    {
        $operations = Activity::inLog('bulk_operation_complete')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        if ($operations->isEmpty()) {
            return ['no_data' => true];
        }

        return [
            'total_operations' => $operations->count(),
            'total_items_processed' => $operations->sum('properties.processed_items'),
            'average_items_per_operation' => $operations->avg('properties.processed_items'),
            'average_success_rate' => $operations->avg('properties.success_rate'),
            'total_duration' => $operations->sum('properties.duration'),
            'average_items_per_second' => $operations->avg('properties.items_per_second'),
        ];
    }

    /**
     * Calculate percentile.
     */
    protected function calculatePercentile(array $values, int $percentile): float
    {
        if (empty($values)) {
            return 0;
        }

        sort($values);
        $index = ($percentile / 100) * (count($values) - 1);
        
        if (floor($index) == $index) {
            return $values[$index];
        }
        
        $lower = $values[floor($index)];
        $upper = $values[ceil($index)];
        
        return $lower + ($upper - $lower) * ($index - floor($index));
    }

    /**
     * Generate comprehensive agricultural operation analytics report.
     * 
     * Creates detailed statistical report combining all analytics components
     * for farm management review. Includes activity overview, trends, user
     * productivity, and system performance with customizable sections.
     *
     * @param Carbon $startDate Report period start date
     * @param Carbon $endDate Report period end date
     * @param array $options Report configuration options:
     *   - include_overview: Include activity overview (default: true)
     *   - include_trends: Include trend analysis (default: true)
     *   - include_top_activities: Include top activities (default: true)
     *   - include_user_stats: Include user statistics (default: true)
     *   - include_model_stats: Include model statistics (default: true)
     *   - include_performance: Include performance metrics (default: true)
     * @return array Comprehensive agricultural analytics report
     * @agricultural_context Used for management reviews and operational planning
     */
    public function generateReport(
        Carbon $startDate,
        Carbon $endDate,
        array $options = []
    ): array {
        $report = [
            'period' => [
                'start' => $startDate->format('Y-m-d H:i:s'),
                'end' => $endDate->format('Y-m-d H:i:s'),
            ],
            'generated_at' => now()->toIso8601String(),
        ];

        if ($options['include_overview'] ?? true) {
            $report['overview'] = $this->getOverview($startDate, $endDate);
        }

        if ($options['include_trends'] ?? true) {
            $report['trends'] = $this->getActivityTrends($startDate, $endDate, $options['trend_interval'] ?? 'day');
        }

        if ($options['include_top_activities'] ?? true) {
            $report['top_activities'] = $this->getTopActivities($options['top_activities_limit'] ?? 10, $startDate);
        }

        if ($options['include_user_stats'] ?? true) {
            $report['user_statistics'] = $this->getUserStatistics($startDate, $endDate);
        }

        if ($options['include_model_stats'] ?? true) {
            $report['model_statistics'] = $this->getModelStatistics($startDate, $endDate);
        }

        if ($options['include_performance'] ?? true) {
            $report['performance_metrics'] = $this->getPerformanceMetrics($startDate, $endDate);
        }

        return $report;
    }

    /**
     * Export agricultural statistics report to CSV for external analysis.
     * 
     * Creates CSV export of statistical data for import into external
     * analysis tools or sharing with agricultural consultants and management.
     *
     * @param array $report Generated statistics report data
     * @param string $filename Output filename for CSV export
     * @return string Path to generated CSV file
     * @agricultural_context Enables external analysis of farm operation data
     */
    public function exportToCsv(array $report, string $filename): string
    {
        $path = storage_path("app/exports/{$filename}");
        $handle = fopen($path, 'w');

        // Write headers and data based on report structure
        // This is a simplified version - expand based on needs
        fputcsv($handle, ['Metric', 'Value']);
        
        foreach ($report['overview'] ?? [] as $key => $value) {
            fputcsv($handle, [$key, is_array($value) ? json_encode($value) : $value]);
        }

        fclose($handle);

        return $path;
    }
}