<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MetricsService;
use App\Models\Activity;
use App\Models\ActivityLogStatistic;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ActivityLogStats extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'activitylog:stats 
                            {--period=day : Statistics period (hour, day, week, month)}
                            {--from= : Start date}
                            {--to= : End date}
                            {--save : Save statistics to database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate and display activity log statistics';

    protected MetricsService $metricsService;

    /**
     * Create a new command instance.
     */
    public function __construct(MetricsService $metricsService)
    {
        parent::__construct();
        $this->metricsService = $metricsService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $period = $this->option('period');
        $from = $this->option('from') ? Carbon::parse($this->option('from')) : Carbon::now()->subDays(30);
        $to = $this->option('to') ? Carbon::parse($this->option('to')) : Carbon::now();
        $save = $this->option('save');

        $this->info("Generating activity statistics from {$from->format('Y-m-d')} to {$to->format('Y-m-d')}");

        // Get overall statistics
        $stats = $this->generateStatistics($from, $to, $period);

        // Display statistics
        $this->displayStatistics($stats);

        // Save to database if requested
        if ($save) {
            $this->saveStatistics($stats, $period);
            $this->info('Statistics saved to database.');
        }

        return Command::SUCCESS;
    }

    /**
     * Generate statistics for the given period
     */
    protected function generateStatistics(Carbon $from, Carbon $to, string $period): array
    {
        $query = Activity::whereBetween('created_at', [$from, $to]);

        return [
            'summary' => $this->getSummaryStats($query->clone()),
            'by_user' => $this->getUserStats($query->clone()),
            'by_type' => $this->getTypeStats($query->clone()),
            'by_model' => $this->getModelStats($query->clone()),
            'by_time' => $this->getTimeStats($query->clone(), $period),
            'top_actions' => $this->getTopActions($query->clone()),
            'error_rate' => $this->getErrorStats($query->clone()),
        ];
    }

    /**
     * Get summary statistics
     */
    protected function getSummaryStats($query): array
    {
        $minDate = $query->clone()->min('created_at');
        $daysDiff = $minDate ? Carbon::now()->diffInDays(Carbon::parse($minDate)) : 1;
        
        return [
            'total_activities' => $query->clone()->count(),
            'unique_users' => $query->clone()->distinct('causer_id')->count('causer_id'),
            'unique_models' => $query->clone()->distinct('subject_type')->count('subject_type'),
            'average_per_day' => $query->clone()->count() / max(1, $daysDiff),
        ];
    }

    /**
     * Get user statistics
     */
    protected function getUserStats($query): array
    {
        return $query->select('causer_id', 'causer_type', DB::raw('COUNT(*) as count'))
            ->whereNotNull('causer_id')
            ->groupBy('causer_id', 'causer_type')
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->map(function ($stat) {
                $user = $stat->causer_type::find($stat->causer_id);
                return [
                    'user' => $user?->name ?? 'Unknown',
                    'email' => $user?->email ?? '-',
                    'count' => $stat->count,
                ];
            })
            ->toArray();
    }

    /**
     * Get statistics by log type
     */
    protected function getTypeStats($query): array
    {
        return $query->select('log_name', DB::raw('COUNT(*) as count'))
            ->groupBy('log_name')
            ->orderByDesc('count')
            ->get()
            ->pluck('count', 'log_name')
            ->toArray();
    }

    /**
     * Get statistics by model
     */
    protected function getModelStats($query): array
    {
        return $query->select('subject_type', DB::raw('COUNT(*) as count'))
            ->whereNotNull('subject_type')
            ->groupBy('subject_type')
            ->orderByDesc('count')
            ->get()
            ->map(function ($stat) {
                return [
                    'model' => class_basename($stat->subject_type),
                    'full_class' => $stat->subject_type,
                    'count' => $stat->count,
                ];
            })
            ->toArray();
    }

    /**
     * Get time-based statistics
     */
    protected function getTimeStats($query, string $period): array
    {
        $groupBy = match($period) {
            'hour' => "DATE_FORMAT(created_at, '%Y-%m-%d %H:00:00')",
            'day' => "DATE(created_at)",
            'week' => "YEARWEEK(created_at)",
            'month' => "DATE_FORMAT(created_at, '%Y-%m')",
            default => "DATE(created_at)",
        };

        return $query->select(DB::raw("{$groupBy} as period"), DB::raw('COUNT(*) as count'))
            ->groupBy('period')
            ->orderBy('period')
            ->get()
            ->pluck('count', 'period')
            ->toArray();
    }

    /**
     * Get top actions
     */
    protected function getTopActions($query): array
    {
        return $query->select('event', 'description', DB::raw('COUNT(*) as count'))
            ->groupBy('event', 'description')
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->toArray();
    }

    /**
     * Get error statistics
     */
    protected function getErrorStats($query): array
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
            'total_errors' => $errors,
            'error_rate' => $total > 0 ? round(($errors / $total) * 100, 2) : 0,
        ];
    }

    /**
     * Display statistics in console
     */
    protected function displayStatistics(array $stats): void
    {
        // Summary
        $this->info('=== Summary ===');
        $this->table(
            ['Metric', 'Value'],
            collect($stats['summary'])->map(function ($value, $key) {
                return [str_replace('_', ' ', ucfirst($key)), $value];
            })
        );

        // Top Users
        $this->newLine();
        $this->info('=== Top Users ===');
        $this->table(
            ['User', 'Email', 'Activities'],
            $stats['by_user']
        );

        // By Type
        $this->newLine();
        $this->info('=== Activity Types ===');
        $this->table(
            ['Type', 'Count'],
            collect($stats['by_type'])->map(function ($count, $type) {
                return [$type ?: 'default', $count];
            })
        );

        // By Model
        $this->newLine();
        $this->info('=== Activity by Model ===');
        $this->table(
            ['Model', 'Count'],
            collect($stats['by_model'])->map(function ($stat) {
                return [$stat['model'], $stat['count']];
            })
        );

        // Top Actions
        $this->newLine();
        $this->info('=== Top Actions ===');
        $this->table(
            ['Event', 'Description', 'Count'],
            $stats['top_actions']
        );

        // Error Rate
        $this->newLine();
        $this->info('=== Error Statistics ===');
        $this->line("Total Errors: {$stats['error_rate']['total_errors']}");
        $this->line("Error Rate: {$stats['error_rate']['error_rate']}%");
    }

    /**
     * Save statistics to database
     */
    protected function saveStatistics(array $stats, string $period): void
    {
        ActivityLogStatistic::create([
            'period_type' => $period,
            'period_start' => Carbon::now()->startOf($period),
            'period_end' => Carbon::now()->endOf($period),
            'metrics' => $stats,
            'calculated_at' => now(),
        ]);
    }
}