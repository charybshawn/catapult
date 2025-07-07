<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MetricsService
{
    protected ActivityLogService $activityService;
    protected CacheService $cacheService;

    public function __construct(ActivityLogService $activityService, CacheService $cacheService)
    {
        $this->activityService = $activityService;
        $this->cacheService = $cacheService;
    }

    /**
     * Get activity metrics for a specific user
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
     * Get system-wide metrics
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
     * Get model-specific metrics
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
     * Get activities grouped by type
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
     * Get activities grouped by day
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
     * Get most active hours
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
     * Get error rate
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
     * Get performance metrics
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
     * Get slow queries
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
     * Get background job statistics
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