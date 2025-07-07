<?php

namespace App\Filament\Widgets;

use App\Models\ActivityLogApiRequest;
use App\Models\ActivityLogQuery;
use App\Models\ActivityLogBackgroundJob;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class SystemPerformanceWidget extends ChartWidget
{
    protected static ?string $heading = 'System Performance';
    
    protected static ?int $sort = 4;
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $endDate = now();
        $startDate = now()->subHours(24);
        
        // Get API response times
        $apiTimes = ActivityLogApiRequest::select(
                DB::raw("DATE_FORMAT(created_at, '%Y-%m-%d %H:00:00') as hour"),
                DB::raw('AVG(response_time) as avg_time'),
                DB::raw('MAX(response_time) as max_time')
            )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();
        
        // Get slow queries
        $slowQueries = ActivityLogQuery::select(
                DB::raw("DATE_FORMAT(created_at, '%Y-%m-%d %H:00:00') as hour"),
                DB::raw('COUNT(*) as count')
            )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('execution_time', '>', 1000)
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->pluck('count', 'hour');
        
        // Build hourly labels
        $labels = [];
        $avgTimes = [];
        $maxTimes = [];
        $slowQueryCounts = [];
        
        for ($i = 23; $i >= 0; $i--) {
            $hour = now()->subHours($i);
            $hourKey = $hour->format('Y-m-d H:00:00');
            $labels[] = $hour->format('H:00');
            
            $apiData = $apiTimes->firstWhere('hour', $hourKey);
            $avgTimes[] = $apiData ? round($apiData->avg_time, 2) : 0;
            $maxTimes[] = $apiData ? round($apiData->max_time, 2) : 0;
            $slowQueryCounts[] = $slowQueries->get($hourKey, 0);
        }
        
        return [
            'datasets' => [
                [
                    'label' => 'Avg Response Time (ms)',
                    'data' => $avgTimes,
                    'borderColor' => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'yAxisID' => 'y',
                ],
                [
                    'label' => 'Max Response Time (ms)',
                    'data' => $maxTimes,
                    'borderColor' => 'rgb(251, 146, 60)',
                    'backgroundColor' => 'rgba(251, 146, 60, 0.1)',
                    'yAxisID' => 'y',
                ],
                [
                    'label' => 'Slow Queries',
                    'data' => $slowQueryCounts,
                    'borderColor' => 'rgb(239, 68, 68)',
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                    'yAxisID' => 'y1',
                    'type' => 'bar',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
            ],
            'scales' => [
                'y' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'left',
                    'title' => [
                        'display' => true,
                        'text' => 'Response Time (ms)',
                    ],
                ],
                'y1' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'right',
                    'title' => [
                        'display' => true,
                        'text' => 'Slow Query Count',
                    ],
                    'grid' => [
                        'drawOnChartArea' => false,
                    ],
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}