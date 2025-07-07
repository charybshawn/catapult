<?php

namespace App\Filament\Widgets;

use App\Models\Activity;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class ErrorRateWidget extends ChartWidget
{
    protected static ?string $heading = 'Error Rate Monitor';
    
    protected static ?int $sort = 5;
    
    protected int | string | array $columnSpan = '1/2';
    
    protected static ?string $maxHeight = '250px';

    protected function getData(): array
    {
        $endDate = now();
        $startDate = now()->subDays(7);
        
        $data = Activity::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN log_name = "error" OR event = "failed" THEN 1 ELSE 0 END) as errors')
            )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('date')
            ->get();
        
        $labels = [];
        $errorRates = [];
        $totals = [];
        
        foreach ($data as $day) {
            $labels[] = $day->date;
            $errorRate = $day->total > 0 ? round(($day->errors / $day->total) * 100, 2) : 0;
            $errorRates[] = $errorRate;
            $totals[] = $day->errors;
        }
        
        return [
            'datasets' => [
                [
                    'label' => 'Error Rate (%)',
                    'data' => $errorRates,
                    'borderColor' => 'rgb(239, 68, 68)',
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                    'yAxisID' => 'y',
                ],
                [
                    'label' => 'Total Errors',
                    'data' => $totals,
                    'borderColor' => 'rgb(251, 146, 60)',
                    'backgroundColor' => 'rgba(251, 146, 60, 0.1)',
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
                        'text' => 'Error Rate (%)',
                    ],
                    'min' => 0,
                    'max' => 100,
                ],
                'y1' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'right',
                    'title' => [
                        'display' => true,
                        'text' => 'Error Count',
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