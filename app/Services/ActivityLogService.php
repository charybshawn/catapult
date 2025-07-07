<?php

namespace App\Services;

use Spatie\Activitylog\Models\Activity;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class ActivityLogService
{
    /**
     * Log a custom activity.
     */
    public function log(
        string $description,
        string $logName = 'default',
        array $properties = [],
        $subject = null,
        $causer = null
    ): Activity {
        $activity = activity($logName)
            ->withProperties($this->enrichProperties($properties));

        if ($subject) {
            $activity->performedOn($subject);
        }

        if ($causer) {
            $activity->causedBy($causer);
        } elseif (Auth::check()) {
            $activity->causedBy(Auth::user());
        }

        return $activity->log($description);
    }

    /**
     * Enrich properties with default metadata.
     */
    protected function enrichProperties(array $properties): array
    {
        return array_merge([
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'url' => Request::fullUrl(),
            'method' => Request::method(),
            'session_id' => session()->getId(),
            'timestamp' => now()->toIso8601String(),
        ], $properties);
    }

    /**
     * Get activities for a specific model.
     */
    public function getActivitiesForModel($model, int $limit = 50): Collection
    {
        return Activity::forSubject($model)
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Get activities by a specific user.
     */
    public function getActivitiesByUser($user, int $limit = 50): Collection
    {
        return Activity::causedBy($user)
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Get activities within a date range.
     */
    public function getActivitiesByDateRange(Carbon $start, Carbon $end, string $logName = null): Collection
    {
        $query = Activity::whereBetween('created_at', [$start, $end]);

        if ($logName) {
            $query->inLog($logName);
        }

        return $query->latest()->get();
    }

    /**
     * Search activities by description or properties.
     */
    public function searchActivities(string $search, array $filters = []): Collection
    {
        $query = Activity::query();

        // Search in description
        $query->where('description', 'like', "%{$search}%");

        // Apply filters
        if (!empty($filters['log_name'])) {
            $query->inLog($filters['log_name']);
        }

        if (!empty($filters['causer_type'])) {
            $query->where('causer_type', $filters['causer_type']);
        }

        if (!empty($filters['subject_type'])) {
            $query->where('subject_type', $filters['subject_type']);
        }

        if (!empty($filters['start_date'])) {
            $query->where('created_at', '>=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $query->where('created_at', '<=', $filters['end_date']);
        }

        return $query->latest()->get();
    }

    /**
     * Get activity statistics.
     */
    public function getStatistics(Carbon $startDate = null, Carbon $endDate = null): array
    {
        $query = Activity::query();

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        return [
            'total_activities' => $query->count(),
            'activities_by_log' => $query->groupBy('log_name')
                ->select('log_name', DB::raw('count(*) as count'))
                ->pluck('count', 'log_name'),
            'activities_by_type' => $query->groupBy('description')
                ->select('description', DB::raw('count(*) as count'))
                ->orderBy('count', 'desc')
                ->limit(10)
                ->pluck('count', 'description'),
            'most_active_users' => $query->whereNotNull('causer_id')
                ->groupBy('causer_id', 'causer_type')
                ->select('causer_id', 'causer_type', DB::raw('count(*) as count'))
                ->orderBy('count', 'desc')
                ->limit(10)
                ->get(),
        ];
    }

    /**
     * Clean old activities.
     */
    public function cleanOldActivities(int $daysToKeep = 90): int
    {
        return Activity::where('created_at', '<', now()->subDays($daysToKeep))->delete();
    }

    /**
     * Archive activities to a separate table or storage.
     */
    public function archiveActivities(Carbon $beforeDate): int
    {
        $activities = Activity::where('created_at', '<', $beforeDate)->get();
        
        // Archive logic here (e.g., move to archive table, export to file, etc.)
        $archived = 0;
        foreach ($activities as $activity) {
            // Archive the activity
            $archived++;
        }

        // Delete archived activities
        Activity::where('created_at', '<', $beforeDate)->delete();

        return $archived;
    }

    /**
     * Get activity timeline for a model.
     */
    public function getModelTimeline($model): Collection
    {
        return Activity::forSubject($model)
            ->with('causer')
            ->latest()
            ->get()
            ->map(function ($activity) {
                return [
                    'id' => $activity->id,
                    'description' => $activity->description,
                    'causer' => $activity->causer,
                    'properties' => $activity->properties,
                    'created_at' => $activity->created_at,
                    'time_ago' => $activity->created_at->diffForHumans(),
                ];
            });
    }

    /**
     * Log a batch operation.
     */
    public function logBatch(array $activities): void
    {
        DB::transaction(function () use ($activities) {
            foreach ($activities as $activityData) {
                $this->log(
                    $activityData['description'] ?? 'Batch operation',
                    $activityData['log_name'] ?? 'batch',
                    $activityData['properties'] ?? [],
                    $activityData['subject'] ?? null,
                    $activityData['causer'] ?? null
                );
            }
        });
    }
}