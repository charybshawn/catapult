<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Filament\Widgets\AnalyticsStatsOverview;
use App\Filament\Widgets\SalesRevenueChart;
use App\Filament\Widgets\ProductPerformanceChart;

/**
 * Analytics Dashboard Page
 * 
 * Agricultural business analytics dashboard for microgreens farm operations.
 * Provides comprehensive performance metrics, sales analysis, and production
 * insights through integrated widget components.
 * 
 * @filament_page Analytics dashboard with agricultural business intelligence
 * @business_metrics Sales revenue, product performance, operational statistics
 * @agricultural_context Tracks farm profitability, variety performance, customer trends
 * @widget_integration Coordinates multiple analytics widgets in unified dashboard
 * 
 * @package App\Filament\Pages
 * @author Catapult Development Team
 * @version 1.0.0
 */
class Analytics extends Page
{
    /**
     * Navigation icon for analytics dashboard
     * 
     * @var string Heroicon chart-bar icon representing analytics
     */
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-chart-bar';
    
    /**
     * Navigation registration control
     * 
     * @var bool False to exclude from main navigation menu
     * @performance Reduces navigation queries for specialized dashboard
     */
    protected static bool $shouldRegisterNavigation = false;
    
    /**
     * Page title for analytics dashboard
     * 
     * @var string Display title for page header
     */
    protected static ?string $title = 'Analytics Dashboard';

    /**
     * Blade view template for analytics page
     * 
     * @var string Path to analytics dashboard template
     */
    protected string $view = 'filament.pages.analytics';
    
    /**
     * Get header widgets for analytics dashboard
     * 
     * Defines the analytics widgets to display in the dashboard header area.
     * Includes overview statistics, revenue trends, and product performance metrics
     * for comprehensive agricultural business intelligence.
     * 
     * @filament_widgets Header widget configuration for analytics display
     * @business_intelligence Provides sales, revenue, and performance metrics
     * @agricultural_analytics Farm-specific analytics for microgreens production
     * 
     * @return array Array of widget class names for dashboard display
     */
    protected function getHeaderWidgets(): array
    {
        return [
            AnalyticsStatsOverview::class,
            SalesRevenueChart::class,
            ProductPerformanceChart::class,
        ];
    }
}
