<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class PerformanceTrackingMiddleware
{
    protected array $metrics = [];
    protected float $startTime;
    protected int $startMemory;

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip performance tracking for certain paths
        if ($this->shouldSkip($request)) {
            return $next($request);
        }

        // Initialize tracking
        $this->startTracking($request);

        // Process request
        $response = $next($request);

        // Complete tracking
        $this->completeTracking($request, $response);

        // Add performance headers
        $this->addPerformanceHeaders($response);

        return $response;
    }

    /**
     * Start performance tracking.
     */
    protected function startTracking(Request $request): void
    {
        $this->startTime = microtime(true);
        $this->startMemory = memory_get_usage(true);

        // Initialize metrics
        $this->metrics = [
            'start_time' => $this->startTime,
            'start_memory' => $this->startMemory,
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'route' => $request->route()?->getName(),
            'controller' => $request->route()?->getActionName(),
            'db_queries' => 0,
            'cache_hits' => 0,
            'cache_misses' => 0,
        ];

        // Store in request for access by other components
        $request->attributes->set('performance_metrics', $this->metrics);
    }

    /**
     * Complete performance tracking.
     */
    protected function completeTracking(Request $request, Response $response): void
    {
        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);
        $peakMemory = memory_get_peak_usage(true);

        // Calculate metrics
        $duration = ($endTime - $this->startTime) * 1000; // Convert to milliseconds
        $memoryUsed = $endMemory - $this->startMemory;

        // Get additional metrics from request
        $metrics = $request->attributes->get('performance_metrics', $this->metrics);

        // Update metrics
        $metrics['duration_ms'] = $duration;
        $metrics['memory_used'] = $memoryUsed;
        $metrics['peak_memory'] = $peakMemory;
        $metrics['status_code'] = $response->getStatusCode();
        $metrics['response_size'] = strlen($response->getContent());

        // Log performance data
        $this->logPerformanceData($metrics);

        // Update aggregate statistics
        $this->updateAggregateStats($request, $metrics);

        // Check for performance issues
        $this->checkPerformanceThresholds($metrics);
    }

    /**
     * Log performance data.
     */
    protected function logPerformanceData(array $metrics): void
    {
        activity('performance_tracking')
            ->withProperties($metrics)
            ->log('Request performance tracked');
    }

    /**
     * Update aggregate statistics.
     */
    protected function updateAggregateStats(Request $request, array $metrics): void
    {
        $route = $request->route()?->getName() ?? 'unknown';
        $hour = now()->format('Y-m-d-H');
        $key = "performance_stats_{$route}_{$hour}";

        Cache::lock($key . '_lock', 5)->get(function () use ($key, $metrics) {
            $stats = Cache::get($key, [
                'count' => 0,
                'total_duration' => 0,
                'min_duration' => PHP_INT_MAX,
                'max_duration' => 0,
                'total_memory' => 0,
                'errors' => 0,
            ]);

            $stats['count']++;
            $stats['total_duration'] += $metrics['duration_ms'];
            $stats['min_duration'] = min($stats['min_duration'], $metrics['duration_ms']);
            $stats['max_duration'] = max($stats['max_duration'], $metrics['duration_ms']);
            $stats['total_memory'] += $metrics['memory_used'];

            if ($metrics['status_code'] >= 500) {
                $stats['errors']++;
            }

            Cache::put($key, $stats, 3600); // Keep for 1 hour
        });
    }

    /**
     * Check performance thresholds and log warnings.
     */
    protected function checkPerformanceThresholds(array $metrics): void
    {
        $thresholds = config('logging.performance_thresholds', [
            'duration_ms' => 1000,
            'memory_mb' => 128,
            'queries' => 50,
        ]);

        $warnings = [];

        // Check duration
        if ($metrics['duration_ms'] > $thresholds['duration_ms']) {
            $warnings[] = sprintf(
                'Slow request: %.2fms (threshold: %dms)',
                $metrics['duration_ms'],
                $thresholds['duration_ms']
            );
        }

        // Check memory
        $memoryMb = $metrics['peak_memory'] / 1024 / 1024;
        if ($memoryMb > $thresholds['memory_mb']) {
            $warnings[] = sprintf(
                'High memory usage: %.2fMB (threshold: %dMB)',
                $memoryMb,
                $thresholds['memory_mb']
            );
        }

        // Check query count
        if ($metrics['db_queries'] > $thresholds['queries']) {
            $warnings[] = sprintf(
                'Too many queries: %d (threshold: %d)',
                $metrics['db_queries'],
                $thresholds['queries']
            );
        }

        // Log warnings if any
        if (!empty($warnings)) {
            activity('performance_warning')
                ->withProperties([
                    'url' => $metrics['url'],
                    'warnings' => $warnings,
                    'metrics' => $metrics,
                ])
                ->log('Performance threshold exceeded');
        }
    }

    /**
     * Add performance headers to response.
     */
    protected function addPerformanceHeaders(Response $response): void
    {
        if (config('logging.expose_performance_headers', false)) {
            $response->headers->set('X-Response-Time', round($this->metrics['duration_ms'] ?? 0, 2) . 'ms');
            $response->headers->set('X-Memory-Usage', $this->formatBytes($this->metrics['memory_used'] ?? 0));
            $response->headers->set('X-DB-Queries', $this->metrics['db_queries'] ?? 0);
        }
    }

    /**
     * Check if tracking should be skipped.
     */
    protected function shouldSkip(Request $request): bool
    {
        $skipPaths = config('logging.performance_skip_paths', [
            'horizon/*',
        ]);

        foreach ($skipPaths as $pattern) {
            if ($request->is($pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Format bytes to human readable format.
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        return round($bytes / pow(1024, $pow), 2) . ' ' . $units[$pow];
    }

    /**
     * Get performance report for a route.
     */
    public static function getPerformanceReport(string $route, string $period = 'day'): array
    {
        $report = [];
        $hours = $period === 'day' ? 24 : ($period === 'week' ? 168 : 720);

        for ($i = 0; $i < $hours; $i++) {
            $hour = now()->subHours($i)->format('Y-m-d-H');
            $key = "performance_stats_{$route}_{$hour}";
            
            if ($stats = Cache::get($key)) {
                $stats['hour'] = $hour;
                $stats['avg_duration'] = $stats['count'] > 0 
                    ? $stats['total_duration'] / $stats['count'] 
                    : 0;
                $stats['avg_memory'] = $stats['count'] > 0 
                    ? $stats['total_memory'] / $stats['count'] 
                    : 0;
                $stats['error_rate'] = $stats['count'] > 0 
                    ? ($stats['errors'] / $stats['count']) * 100 
                    : 0;
                
                $report[] = $stats;
            }
        }

        return array_reverse($report);
    }
}