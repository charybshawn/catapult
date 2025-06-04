<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Filament\Widgets\AnalyticsStatsOverview;
use App\Filament\Widgets\SalesRevenueChart;
use App\Filament\Widgets\ProductPerformanceChart;

class Analytics extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    
    protected static ?string $navigationGroup = 'Analytics & Reports';
    
    protected static ?int $navigationSort = 1;
    
    protected static ?string $title = 'Analytics Dashboard';

    protected static string $view = 'filament.pages.analytics';
    
    protected function getHeaderWidgets(): array
    {
        return [
            AnalyticsStatsOverview::class,
            SalesRevenueChart::class,
            ProductPerformanceChart::class,
        ];
    }
}
