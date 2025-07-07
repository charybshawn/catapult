<?php

namespace App\Models;

use Spatie\Activitylog\Models\Activity as SpatieActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class Activity extends SpatieActivity
{
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'properties' => 'collection',
        'context' => 'collection',
        'tags' => 'array',
        'execution_time_ms' => 'float',
        'memory_usage_mb' => 'float',
        'query_count' => 'integer',
        'response_status' => 'integer',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'log_name',
        'description',
        'subject_type',
        'subject_id',
        'event',
        'causer_type',
        'causer_id',
        'properties',
        'batch_uuid',
        'ip_address',
        'user_agent',
        'request_method',
        'request_url',
        'response_status',
        'execution_time_ms',
        'memory_usage_mb',
        'query_count',
        'context',
        'tags',
        'severity_level',
    ];

    /**
     * Get the queries associated with this activity.
     */
    public function queries(): HasMany
    {
        return $this->hasMany(ActivityLogQuery::class, 'activity_log_id');
    }

    /**
     * Get the API request associated with this activity.
     */
    public function apiRequest(): HasOne
    {
        return $this->hasOne(ActivityLogApiRequest::class, 'activity_log_id');
    }

    /**
     * Get the background job associated with this activity.
     */
    public function backgroundJob(): HasOne
    {
        return $this->hasOne(ActivityLogBackgroundJob::class, 'activity_log_id');
    }

    /**
     * Scope to filter by severity level.
     */
    public function scopeBySeverity(Builder $query, string|array $severity): Builder
    {
        if (is_array($severity)) {
            return $query->whereIn('severity_level', $severity);
        }
        
        return $query->where('severity_level', $severity);
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeDateBetween(Builder $query, $startDate, $endDate): Builder
    {
        return $query->whereBetween('created_at', [
            Carbon::parse($startDate)->startOfDay(),
            Carbon::parse($endDate)->endOfDay(),
        ]);
    }

    /**
     * Scope to filter by performance threshold.
     */
    public function scopeSlowQueries(Builder $query, float $thresholdMs = 1000): Builder
    {
        return $query->where('execution_time_ms', '>', $thresholdMs);
    }

    /**
     * Scope to filter by high memory usage.
     */
    public function scopeHighMemoryUsage(Builder $query, float $thresholdMb = 50): Builder
    {
        return $query->where('memory_usage_mb', '>', $thresholdMb);
    }

    /**
     * Scope to filter by tags.
     */
    public function scopeWithTags(Builder $query, array $tags): Builder
    {
        return $query->where(function ($q) use ($tags) {
            foreach ($tags as $tag) {
                $q->orWhereJsonContains('tags', $tag);
            }
        });
    }

    /**
     * Scope to filter by IP address.
     */
    public function scopeFromIpAddress(Builder $query, string $ipAddress): Builder
    {
        return $query->where('ip_address', $ipAddress);
    }

    /**
     * Scope to filter by response status.
     */
    public function scopeByResponseStatus(Builder $query, int|array $status): Builder
    {
        if (is_array($status)) {
            return $query->whereIn('response_status', $status);
        }
        
        return $query->where('response_status', $status);
    }

    /**
     * Scope to filter failed requests (4xx and 5xx status codes).
     */
    public function scopeFailedRequests(Builder $query): Builder
    {
        return $query->where('response_status', '>=', 400);
    }

    /**
     * Get statistics for a given period.
     */
    public static function getStatistics(Carbon $startDate, Carbon $endDate, array $filters = []): array
    {
        $query = static::query()
            ->dateBetween($startDate, $endDate);

        // Apply filters
        if (!empty($filters['log_name'])) {
            $query->inLog($filters['log_name']);
        }
        
        if (!empty($filters['event'])) {
            $query->where('event', $filters['event']);
        }
        
        if (!empty($filters['causer_id'])) {
            $query->causedBy($filters['causer_id']);
        }

        // Clone the query for different aggregations to avoid conflicts
        $baseQuery = clone $query;
        
        $totalActivities = $baseQuery->count();
        $uniqueUsers = (clone $query)->distinct('causer_id')->count('causer_id');
        $uniqueIps = (clone $query)->distinct('ip_address')->count('ip_address');

        // Performance metrics
        $performanceStats = (clone $query)->select([
            DB::raw('AVG(execution_time_ms) as avg_execution_time'),
            DB::raw('MAX(execution_time_ms) as max_execution_time'),
            DB::raw('SUM(execution_time_ms) as total_execution_time'),
            DB::raw('AVG(memory_usage_mb) as avg_memory_usage'),
            DB::raw('MAX(memory_usage_mb) as max_memory_usage'),
            DB::raw('SUM(query_count) as total_queries'),
        ])->first();

        // Severity breakdown
        $severityBreakdown = (clone $query)->select('severity_level', DB::raw('COUNT(*) as count'))
            ->groupBy('severity_level')
            ->pluck('count', 'severity_level')
            ->toArray();

        // Event breakdown
        $eventBreakdown = (clone $query)->select('event', DB::raw('COUNT(*) as count'))
            ->groupBy('event')
            ->orderByDesc('count')
            ->limit(10)
            ->pluck('count', 'event')
            ->toArray();

        // Hourly distribution
        $hourlyDistribution = (clone $query)->select(
                DB::raw('HOUR(created_at) as hour'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('hour')
            ->orderBy('hour')
            ->pluck('count', 'hour')
            ->toArray();

        // Top users
        $topUsers = (clone $query)->select('causer_id', 'causer_type', DB::raw('COUNT(*) as activity_count'))
            ->whereNotNull('causer_id')
            ->groupBy('causer_id', 'causer_type')
            ->orderByDesc('activity_count')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'user_id' => $item->causer_id,
                    'user_type' => $item->causer_type,
                    'activity_count' => $item->activity_count,
                ];
            })
            ->toArray();

        // Response status breakdown
        $responseStatusBreakdown = (clone $query)->select('response_status', DB::raw('COUNT(*) as count'))
            ->whereNotNull('response_status')
            ->groupBy('response_status')
            ->pluck('count', 'response_status')
            ->toArray();

        return [
            'total_activities' => $totalActivities,
            'unique_users' => $uniqueUsers,
            'unique_ips' => $uniqueIps,
            'performance' => [
                'avg_execution_time_ms' => round($performanceStats->avg_execution_time ?? 0, 2),
                'max_execution_time_ms' => round($performanceStats->max_execution_time ?? 0, 2),
                'total_execution_time_ms' => round($performanceStats->total_execution_time ?? 0, 2),
                'avg_memory_usage_mb' => round($performanceStats->avg_memory_usage ?? 0, 2),
                'max_memory_usage_mb' => round($performanceStats->max_memory_usage ?? 0, 2),
                'total_queries' => $performanceStats->total_queries ?? 0,
            ],
            'severity_breakdown' => $severityBreakdown,
            'event_breakdown' => $eventBreakdown,
            'hourly_distribution' => $hourlyDistribution,
            'top_users' => $topUsers,
            'response_status_breakdown' => $responseStatusBreakdown,
            'error_count' => $severityBreakdown['error'] ?? 0,
            'warning_count' => $severityBreakdown['warning'] ?? 0,
        ];
    }

    /**
     * Get trend data for charts.
     */
    public static function getTrendData(Carbon $startDate, Carbon $endDate, string $interval = 'day'): array
    {
        $dateFormat = match ($interval) {
            'hour' => '%Y-%m-%d %H:00:00',
            'day' => '%Y-%m-%d',
            'week' => '%Y-%u',
            'month' => '%Y-%m',
            default => '%Y-%m-%d',
        };

        return static::query()
            ->dateBetween($startDate, $endDate)
            ->select([
                DB::raw("DATE_FORMAT(created_at, '{$dateFormat}') as period"),
                DB::raw('COUNT(*) as total_activities'),
                DB::raw('COUNT(DISTINCT causer_id) as unique_users'),
                DB::raw('AVG(execution_time_ms) as avg_execution_time'),
                DB::raw('SUM(CASE WHEN severity_level = "error" THEN 1 ELSE 0 END) as error_count'),
            ])
            ->groupBy('period')
            ->orderBy('period')
            ->get()
            ->toArray();
    }

    /**
     * Clean up old activity logs based on configuration.
     */
    public static function cleanOldRecords(): int
    {
        $days = config('activitylog.delete_records_older_than_days', 365);
        
        return static::query()
            ->where('created_at', '<', Carbon::now()->subDays($days))
            ->delete();
    }

    /**
     * Get recent activities with eager loading.
     */
    public static function getRecentActivities(int $limit = 50, array $with = []): \Illuminate\Database\Eloquent\Collection
    {
        return static::query()
            ->with(array_merge(['causer', 'subject'], $with))
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Check if the activity indicates an error.
     */
    public function isError(): bool
    {
        return in_array($this->severity_level, ['error', 'critical', 'alert', 'emergency']) ||
               ($this->response_status >= 400);
    }

    /**
     * Check if the activity indicates a warning.
     */
    public function isWarning(): bool
    {
        return $this->severity_level === 'warning';
    }

    /**
     * Get a human-readable description of the severity level.
     */
    public function getSeverityLabel(): string
    {
        return match ($this->severity_level) {
            'debug' => 'Debug',
            'info' => 'Information',
            'notice' => 'Notice',
            'warning' => 'Warning',
            'error' => 'Error',
            'critical' => 'Critical',
            'alert' => 'Alert',
            'emergency' => 'Emergency',
            default => 'Unknown',
        };
    }

    /**
     * Get the severity color for UI display.
     */
    public function getSeverityColor(): string
    {
        return match ($this->severity_level) {
            'debug' => 'gray',
            'info' => 'blue',
            'notice' => 'indigo',
            'warning' => 'yellow',
            'error' => 'red',
            'critical' => 'red',
            'alert' => 'purple',
            'emergency' => 'red',
            default => 'gray',
        };
    }
}