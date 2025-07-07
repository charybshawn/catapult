<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class ActivityLogStatistic extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'activity_log_statistics';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'date',
        'period_type',
        'log_name',
        'event_type',
        'model_type',
        'user_id',
        'total_activities',
        'unique_users',
        'unique_ips',
        'activity_breakdown',
        'hourly_distribution',
        'top_users',
        'top_actions',
        'top_models',
        'avg_execution_time_ms',
        'max_execution_time_ms',
        'total_execution_time_ms',
        'avg_memory_usage_mb',
        'max_memory_usage_mb',
        'total_queries',
        'error_count',
        'warning_count',
        'severity_breakdown',
        'response_status_breakdown',
        'browser_breakdown',
        'os_breakdown',
        'device_breakdown',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date' => 'date',
        'activity_breakdown' => 'array',
        'hourly_distribution' => 'array',
        'top_users' => 'array',
        'top_actions' => 'array',
        'top_models' => 'array',
        'severity_breakdown' => 'array',
        'response_status_breakdown' => 'array',
        'browser_breakdown' => 'array',
        'os_breakdown' => 'array',
        'device_breakdown' => 'array',
        'avg_execution_time_ms' => 'float',
        'max_execution_time_ms' => 'float',
        'total_execution_time_ms' => 'float',
        'avg_memory_usage_mb' => 'float',
        'max_memory_usage_mb' => 'float',
    ];

    /**
     * Get the user associated with the statistics.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to filter by period type.
     */
    public function scopeForPeriod($query, string $periodType)
    {
        return $query->where('period_type', $periodType);
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeDateBetween($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [
            Carbon::parse($startDate)->startOfDay(),
            Carbon::parse($endDate)->endOfDay(),
        ]);
    }

    /**
     * Scope to filter by log name.
     */
    public function scopeForLog($query, string $logName)
    {
        return $query->where('log_name', $logName);
    }

    /**
     * Scope to filter by event type.
     */
    public function scopeForEvent($query, string $eventType)
    {
        return $query->where('event_type', $eventType);
    }

    /**
     * Scope to filter by model type.
     */
    public function scopeForModel($query, string $modelType)
    {
        return $query->where('model_type', $modelType);
    }

    /**
     * Generate statistics for a specific date and period.
     */
    public static function generateStatistics(Carbon $date, string $periodType = 'daily'): void
    {
        $startDate = match ($periodType) {
            'daily' => $date->copy()->startOfDay(),
            'weekly' => $date->copy()->startOfWeek(),
            'monthly' => $date->copy()->startOfMonth(),
            default => $date->copy()->startOfDay(),
        };

        $endDate = match ($periodType) {
            'daily' => $date->copy()->endOfDay(),
            'weekly' => $date->copy()->endOfWeek(),
            'monthly' => $date->copy()->endOfMonth(),
            default => $date->copy()->endOfDay(),
        };

        // Get distinct combinations of log_name, event_type, and model_type
        $combinations = Activity::query()
            ->dateBetween($startDate, $endDate)
            ->select('log_name', 'event as event_type', 'subject_type as model_type')
            ->distinct()
            ->get();

        // Generate statistics for each combination
        foreach ($combinations as $combo) {
            $stats = Activity::getStatistics($startDate, $endDate, [
                'log_name' => $combo->log_name,
                'event' => $combo->event_type,
                'model_type' => $combo->model_type,
            ]);

            static::updateOrCreate(
                [
                    'date' => $date->toDateString(),
                    'period_type' => $periodType,
                    'log_name' => $combo->log_name,
                    'event_type' => $combo->event_type,
                    'model_type' => $combo->model_type,
                    'user_id' => null,
                ],
                [
                    'total_activities' => $stats['total_activities'],
                    'unique_users' => $stats['unique_users'],
                    'unique_ips' => $stats['unique_ips'],
                    'activity_breakdown' => $stats['event_breakdown'],
                    'hourly_distribution' => $stats['hourly_distribution'],
                    'top_users' => $stats['top_users'],
                    'avg_execution_time_ms' => $stats['performance']['avg_execution_time_ms'],
                    'max_execution_time_ms' => $stats['performance']['max_execution_time_ms'],
                    'total_execution_time_ms' => $stats['performance']['total_execution_time_ms'],
                    'avg_memory_usage_mb' => $stats['performance']['avg_memory_usage_mb'],
                    'max_memory_usage_mb' => $stats['performance']['max_memory_usage_mb'],
                    'total_queries' => $stats['performance']['total_queries'],
                    'error_count' => $stats['error_count'],
                    'warning_count' => $stats['warning_count'],
                    'severity_breakdown' => $stats['severity_breakdown'],
                    'response_status_breakdown' => $stats['response_status_breakdown'],
                ]
            );
        }

        // Also generate overall statistics (no filters)
        $overallStats = Activity::getStatistics($startDate, $endDate);
        
        static::updateOrCreate(
            [
                'date' => $date->toDateString(),
                'period_type' => $periodType,
                'log_name' => null,
                'event_type' => null,
                'model_type' => null,
                'user_id' => null,
            ],
            [
                'total_activities' => $overallStats['total_activities'],
                'unique_users' => $overallStats['unique_users'],
                'unique_ips' => $overallStats['unique_ips'],
                'activity_breakdown' => $overallStats['event_breakdown'],
                'hourly_distribution' => $overallStats['hourly_distribution'],
                'top_users' => $overallStats['top_users'],
                'avg_execution_time_ms' => $overallStats['performance']['avg_execution_time_ms'],
                'max_execution_time_ms' => $overallStats['performance']['max_execution_time_ms'],
                'total_execution_time_ms' => $overallStats['performance']['total_execution_time_ms'],
                'avg_memory_usage_mb' => $overallStats['performance']['avg_memory_usage_mb'],
                'max_memory_usage_mb' => $overallStats['performance']['max_memory_usage_mb'],
                'total_queries' => $overallStats['performance']['total_queries'],
                'error_count' => $overallStats['error_count'],
                'warning_count' => $overallStats['warning_count'],
                'severity_breakdown' => $overallStats['severity_breakdown'],
                'response_status_breakdown' => $overallStats['response_status_breakdown'],
            ]
        );
    }

    /**
     * Get comparison data between two periods.
     */
    public static function getComparison(Carbon $currentStart, Carbon $currentEnd, Carbon $previousStart, Carbon $previousEnd): array
    {
        $currentStats = static::query()
            ->dateBetween($currentStart, $currentEnd)
            ->whereNull('log_name')
            ->whereNull('event_type')
            ->whereNull('model_type')
            ->selectRaw('
                SUM(total_activities) as total_activities,
                SUM(unique_users) as unique_users,
                SUM(unique_ips) as unique_ips,
                AVG(avg_execution_time_ms) as avg_execution_time_ms,
                SUM(error_count) as error_count,
                SUM(warning_count) as warning_count
            ')
            ->first();

        $previousStats = static::query()
            ->dateBetween($previousStart, $previousEnd)
            ->whereNull('log_name')
            ->whereNull('event_type')
            ->whereNull('model_type')
            ->selectRaw('
                SUM(total_activities) as total_activities,
                SUM(unique_users) as unique_users,
                SUM(unique_ips) as unique_ips,
                AVG(avg_execution_time_ms) as avg_execution_time_ms,
                SUM(error_count) as error_count,
                SUM(warning_count) as warning_count
            ')
            ->first();

        $comparison = [];
        
        foreach (['total_activities', 'unique_users', 'unique_ips', 'avg_execution_time_ms', 'error_count', 'warning_count'] as $metric) {
            $current = $currentStats->$metric ?? 0;
            $previous = $previousStats->$metric ?? 0;
            
            $change = $previous > 0 ? (($current - $previous) / $previous) * 100 : 0;
            
            $comparison[$metric] = [
                'current' => $current,
                'previous' => $previous,
                'change' => round($change, 2),
                'trend' => $change > 0 ? 'up' : ($change < 0 ? 'down' : 'stable'),
            ];
        }

        return $comparison;
    }
}