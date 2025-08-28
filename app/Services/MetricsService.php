<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Comprehensive metrics and analytics service for agricultural production monitoring.
 * 
 * Provides detailed activity tracking, performance monitoring, and statistical analysis
 * for agricultural operations including crop management, order processing, and user
 * activity patterns. Supports real-time dashboard metrics and historical trend analysis.
 *
 * @business_domain Agricultural operation analytics and performance monitoring
 * @related_services ActivityLogService, CacheService
 * @used_by Dashboard widgets, analytics pages, performance monitoring
 * @caching Aggressive caching (300-600s) for expensive metric calculations
 */
class MetricsService
{
    protected ActivityLogService $activityService;
    protected CacheService $cacheService;

    /**
     * Initialize metrics service with activity logging and caching capabilities.
     *
     * @param ActivityLogService $activityService Handles activity log queries
     * @param CacheService $cacheService Provides intelligent caching for metric calculations
     */
    public function __construct(ActivityLogService $activityService, CacheService $cacheService)
    {
        $this->activityService = $activityService;
        $this->cacheService = $cacheService;
    }

    /**
     * Get comprehensive activity metrics for a specific agricultural team member.
     * 
     * Analyzes user behavior patterns including crop management activities,
     * order processing frequency, and operational efficiency metrics.
     * Essential for farm labor management and productivity optimization.
     *
     * @param int $userId The user ID to analyze
     * @param Carbon|null $from Start date for analysis (defaults to unbounded)
     * @param Carbon|null $to End date for analysis (defaults to unbounded)
     * @return array Comprehensive user activity metrics including:
     *   - total_activities: Total activity count
     *   - activities_by_type: Breakdown by activity type (crop, order, system)
     *   - activities_by_day: Daily activity distribution
     *   - most_active_hours: Hourly activity patterns for shift optimization
     *   - affected_models: Agricultural entities user interacts with most
     *   - recent_activities: Latest 10 activities for context
     * @caching 5-minute cache for performance
     * @agricultural_context Used for labor efficiency analysis and shift planning
     */
    public function getUserMetrics(int $userId, ?Carbon $from = null, ?Carbon $to = null): array
    {
        $cacheKey = "user_metrics:{$userId}:" . ($from?->timestamp ?? 0) . ':' . ($to?->timestamp ?? 0);
        
        return $this->cacheService->remember($cacheKey, 300, function () use ($userId, $from, $to) {
            $query = Activity::where('causer_id', $userId)
                ->where('causer_type', User::class);

            if ($from) {
                $query->where('created_at', '>=', $from);
            }

            if ($to) {
                $query->where('created_at', '<=', $to);
            }

            return [
                'total_activities' => $query->count(),
                'activities_by_type' => $this->getActivitiesByType($query->clone()),
                'activities_by_day' => $this->getActivitiesByDay($query->clone(), $from, $to),
                'most_active_hours' => $this->getMostActiveHours($query->clone()),
                'affected_models' => $this->getAffectedModels($query->clone()),
                'recent_activities' => $this->getRecentActivities($query->clone(), 10),
            ];
        });
    }

    /**
     * Get system-wide agricultural operation metrics and performance indicators.
     * 
     * Provides comprehensive farm operation analytics including overall activity
     * patterns, user engagement, system performance, and error rates.
     * Critical for operational decision making and system optimization.
     *
     * @param Carbon|null $from Start date for analysis (defaults to unbounded)
     * @param Carbon|null $to End date for analysis (defaults to unbounded)
     * @return array System-wide metrics including:
     *   - total_activities: Overall system activity
     *   - unique_users: Active user count for staffing insights
     *   - activities_per_hour: Hourly load patterns
     *   - top_users: Most active team members
     *   - top_actions: Most common operations
     *   - error_rate: System reliability metrics
     *   - performance_metrics: Response times and optimization opportunities
     * @caching 10-minute cache for dashboard performance
     * @agricultural_context Used for farm operation efficiency and capacity planning
     */
    public function getSystemMetrics(?Carbon $from = null, ?Carbon $to = null): array
    {
        $cacheKey = "system_metrics:" . ($from?->timestamp ?? 0) . ':' . ($to?->timestamp ?? 0);
        
        return $this->cacheService->remember($cacheKey, 600, function () use ($from, $to) {
            $query = Activity::query();

            if ($from) {
                $query->where('created_at', '>=', $from);
            }

            if ($to) {
                $query->where('created_at', '<=', $to);
            }

            return [
                'total_activities' => $query->count(),
                'unique_users' => $query->distinct('causer_id')->count('causer_id'),
                'activities_per_hour' => $this->getActivitiesPerHour($query->clone()),
                'top_users' => $this->getTopUsers($query->clone(), 10),
                'top_actions' => $this->getTopActions($query->clone(), 10),
                'error_rate' => $this->getErrorRate($query->clone()),
                'performance_metrics' => $this->getPerformanceMetrics($query->clone()),
            ];
        });
    }

    /**
     * Get detailed metrics for specific agricultural entities (crops, products, orders).
     * 
     * Analyzes activity patterns for individual agricultural model types,
     * providing insights into how different farm operations are being managed
     * and where optimization opportunities exist.
     *
     * @param string $modelClass The model class to analyze (e.g., Crop::class, Product::class)
     * @param Carbon|null $from Start date for analysis (defaults to unbounded)
     * @param Carbon|null $to End date for analysis (defaults to unbounded)
     * @return array Model-specific metrics including:
     *   - total_activities: Activities for this model type
     *   - activities_by_event: Breakdown by CRUD operations
     *   - top_users: Users most active with this entity type
     *   - activity_timeline: Daily activity distribution
     *   - crud_breakdown: Create/update/delete operation analysis
     * @caching 10-minute cache for entity-specific dashboards
     * @agricultural_context Used for crop lifecycle analysis, product management insights
     */
    public function getModelMetrics(string $modelClass, ?Carbon $from = null, ?Carbon $to = null): array
    {
        $cacheKey = "model_metrics:" . class_basename($modelClass) . ':' . ($from?->timestamp ?? 0) . ':' . ($to?->timestamp ?? 0);
        
        return $this->cacheService->remember($cacheKey, 600, function () use ($modelClass, $from, $to) {
            $query = Activity::where('subject_type', $modelClass);

            if ($from) {
                $query->where('created_at', '>=', $from);
            }

            if ($to) {
                $query->where('created_at', '<=', $to);
            }

            return [
                'total_activities' => $query->count(),
                'activities_by_event' => $this->getActivitiesByEvent($query->clone()),
                'top_users' => $this->getTopUsersForModel($query->clone(), 10),
                'activity_timeline' => $this->getActivityTimeline($query->clone(), $from, $to),
                'crud_breakdown' => $this->getCrudBreakdown($query->clone()),
            ];
        });
    }

    /**
     * Group activities by log type (system, crop, order, api, etc.).
     * 
     * Categorizes farm activities to understand operational focus areas
     * and identify bottlenecks in agricultural workflows.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query Base activity query
     * @return array Activity counts keyed by log type
     * @agricultural_context Helps identify which farm operations are most active
     */
    protected function getActivitiesByType($query): array
    {
        return $query->select('log_name', DB::raw('COUNT(*) as count'))
            ->groupBy('log_name')
            ->orderByDesc('count')
            ->pluck('count', 'log_name')
            ->toArray();
    }

    /**
     * Get daily activity distribution for agricultural operation planning.
     * 
     * Creates a complete timeline with zero-filled gaps to identify
     * patterns in farm activity and optimal scheduling windows.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query Base activity query
     * @param Carbon|null $from Start date (defaults to 30 days ago)
     * @param Carbon|null $to End date (defaults to now)
     * @return array Daily activity counts with zero-filled missing days
     * @agricultural_context Used for identifying busy farm periods and planning crop schedules
     */
    protected function getActivitiesByDay($query, ?Carbon $from, ?Carbon $to): array
    {
        $from = $from ?? Carbon::now()->subDays(30);
        $to = $to ?? Carbon::now();

        $activities = $query->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date')
            ->toArray();

        // Fill in missing days with zero
        $result = [];
        $current = $from->copy();
        
        while ($current <= $to) {
            $dateStr = $current->format('Y-m-d');
            $result[$dateStr] = $activities[$dateStr] ?? 0;
            $current->addDay();
        }

        return $result;
    }

    /**
     * Analyze hourly activity patterns for agricultural labor optimization.
     * 
     * Identifies peak operational hours to optimize staffing, equipment usage,
     * and agricultural task scheduling for maximum efficiency.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query Base activity query
     * @return array Activity counts by hour (0-23)
     * @agricultural_context Used for shift planning and greenhouse operation scheduling
     */
    protected function getMostActiveHours($query): array
    {
        return $query->select(DB::raw('HOUR(created_at) as hour'), DB::raw('COUNT(*) as count'))
            ->groupBy('hour')
            ->orderBy('hour')
            ->pluck('count', 'hour')
            ->toArray();
    }

    /**
     * Get affected models
     */
    protected function getAffectedModels($query): array
    {
        return $query->select('subject_type', DB::raw('COUNT(*) as count'))
            ->whereNotNull('subject_type')
            ->groupBy('subject_type')
            ->orderByDesc('count')
            ->get()
            ->mapWithKeys(function ($item) {
                return [class_basename($item->subject_type) => $item->count];
            })
            ->toArray();
    }

    /**
     * Get recent activities
     */
    protected function getRecentActivities($query, int $limit): Collection
    {
        return $query->with(['causer', 'subject'])
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Get activities per hour for the last 24 hours
     */
    protected function getActivitiesPerHour($query): array
    {
        $since = Carbon::now()->subHours(24);
        
        return $query->where('created_at', '>=', $since)
            ->select(DB::raw("DATE_FORMAT(created_at, '%Y-%m-%d %H:00:00') as hour"), DB::raw('COUNT(*) as count'))
            ->groupBy('hour')
            ->orderBy('hour')
            ->pluck('count', 'hour')
            ->toArray();
    }

    /**
     * Get top users by activity count
     */
    protected function getTopUsers($query, int $limit): Collection
    {
        return $query->select('causer_id', 'causer_type', DB::raw('COUNT(*) as count'))
            ->whereNotNull('causer_id')
            ->groupBy('causer_id', 'causer_type')
            ->orderByDesc('count')
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                $user = $item->causer_type::find($item->causer_id);
                return [
                    'user' => $user,
                    'count' => $item->count,
                ];
            });
    }

    /**
     * Get top actions
     */
    protected function getTopActions($query, int $limit): Collection
    {
        return $query->select('event', 'description', DB::raw('COUNT(*) as count'))
            ->groupBy('event', 'description')
            ->orderByDesc('count')
            ->limit($limit)
            ->get();
    }

    /**
     * Calculate system error rate for agricultural operation reliability.
     * 
     * Monitors system health and identifies potential issues that could
     * disrupt critical agricultural processes like crop monitoring or
     * order fulfillment.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query Base activity query
     * @return array Error statistics including total, errors, and rate percentage
     * @agricultural_context Critical for ensuring reliable crop management systems
     */
    protected function getErrorRate($query): array
    {
        $total = $query->count();
        $errors = $query->clone()
            ->where(function ($q) {
                $q->where('log_name', 'error')
                  ->orWhere('event', 'failed')
                  ->orWhereJsonContains('properties->status', 'error');
            })
            ->count();

        return [
            'total' => $total,
            'errors' => $errors,
            'rate' => $total > 0 ? round(($errors / $total) * 100, 2) : 0,
        ];
    }

    /**
     * Analyze system performance metrics for agricultural operation efficiency.
     * 
     * Monitors response times, identifies slow operations, and tracks background
     * job performance to ensure agricultural systems run smoothly during
     * critical periods like harvest or order fulfillment.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query Base activity query
     * @return array Performance metrics including API response times and job stats
     * @agricultural_context Ensures crop monitoring and order processing systems perform optimally
     */
    protected function getPerformanceMetrics($query): array
    {
        // Get average response times from API activities
        $apiMetrics = $query->clone()
            ->where('log_name', 'api')
            ->whereNotNull('properties->duration')
            ->select(DB::raw('AVG(JSON_EXTRACT(properties, "$.duration")) as avg_duration'))
            ->first();

        return [
            'average_api_response_time' => $apiMetrics->avg_duration ?? 0,
            'slow_queries' => $this->getSlowQueries($query->clone()),
            'background_job_stats' => $this->getBackgroundJobStats($query->clone()),
        ];
    }

    /**
     * Get activities by event
     */
    protected function getActivitiesByEvent($query): array
    {
        return $query->select('event', DB::raw('COUNT(*) as count'))
            ->groupBy('event')
            ->orderByDesc('count')
            ->pluck('count', 'event')
            ->toArray();
    }

    /**
     * Get top users for a specific model
     */
    protected function getTopUsersForModel($query, int $limit): Collection
    {
        return $query->select('causer_id', 'causer_type', DB::raw('COUNT(*) as count'))
            ->whereNotNull('causer_id')
            ->groupBy('causer_id', 'causer_type')
            ->orderByDesc('count')
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                $user = $item->causer_type::find($item->causer_id);
                return [
                    'user' => $user,
                    'count' => $item->count,
                ];
            });
    }

    /**
     * Get activity timeline
     */
    protected function getActivityTimeline($query, ?Carbon $from, ?Carbon $to): array
    {
        $from = $from ?? Carbon::now()->subDays(7);
        $to = $to ?? Carbon::now();

        return $query->whereBetween('created_at', [$from, $to])
            ->select(DB::raw('DATE(created_at) as date'), 'event', DB::raw('COUNT(*) as count'))
            ->groupBy('date', 'event')
            ->orderBy('date')
            ->get()
            ->groupBy('date')
            ->map(function ($items) {
                return $items->pluck('count', 'event')->toArray();
            })
            ->toArray();
    }

    /**
     * Get CRUD operation breakdown
     */
    protected function getCrudBreakdown($query): array
    {
        $events = ['created', 'updated', 'deleted', 'restored'];
        $breakdown = [];

        foreach ($events as $event) {
            $breakdown[$event] = $query->clone()->where('event', $event)->count();
        }

        $breakdown['other'] = $query->clone()->whereNotIn('event', $events)->count();

        return $breakdown;
    }

    /**
     * Identify slow database queries that could impact agricultural operations.
     * 
     * Monitors for queries taking over 1 second that could delay critical
     * agricultural processes like crop status updates or inventory management.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query Base activity query
     * @return array Top 10 slowest queries with duration and context
     * @agricultural_context Prevents delays in time-sensitive crop management operations
     */
    protected function getSlowQueries($query): array
    {
        return $query->clone()
            ->where('log_name', 'query')
            ->where('properties->duration', '>', 1000) // Queries taking more than 1 second
            ->select('description', 'properties->duration as duration', 'created_at')
            ->orderByDesc('duration')
            ->limit(10)
            ->get()
            ->toArray();
    }

    /**
     * Analyze background job performance for agricultural automation reliability.
     * 
     * Monitors automated tasks like crop plan generation, recurring orders,
     * and inventory updates to ensure agricultural workflows continue smoothly.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query Base activity query
     * @return array Job statistics including success rate and average duration
     * @agricultural_context Critical for automated crop scheduling and order processing
     */
    protected function getBackgroundJobStats($query): array
    {
        $jobQuery = $query->clone()->where('log_name', 'job');

        return [
            'total' => $jobQuery->count(),
            'successful' => $jobQuery->clone()->where('event', 'processed')->count(),
            'failed' => $jobQuery->clone()->where('event', 'failed')->count(),
            'average_duration' => $jobQuery->clone()
                ->whereNotNull('properties->duration')
                ->avg(DB::raw('JSON_EXTRACT(properties, "$.duration")')),
        ];
    }
}