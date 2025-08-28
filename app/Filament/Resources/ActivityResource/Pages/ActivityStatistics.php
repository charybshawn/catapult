<?php

namespace App\Filament\Resources\ActivityResource\Pages;

use Filament\Actions\Action;
use App\Filament\Resources\ActivityResource;
use App\Services\MetricsService;
use Filament\Resources\Pages\Page;
use Filament\Actions;
use Illuminate\Contracts\View\View;
use Carbon\Carbon;

/**
 * Activity statistics page for comprehensive agricultural system analytics.
 *
 * Provides detailed statistical analysis of system activities including user
 * actions, API requests, background jobs, and agricultural operations. Features
 * customizable time periods, interactive filtering, data export capabilities,
 * and comprehensive metrics visualization for operational intelligence.
 *
 * @filament_page Custom page for activity statistics and system analytics
 * @business_domain Agricultural system monitoring and performance analysis
 * @analytics_features Customizable date ranges, real-time refresh, data export
 * @operational_intelligence System performance metrics and user activity patterns
 * @monitoring_context Supports agricultural operations analysis and optimization
 */
class ActivityStatistics extends Page
{
    /** @var string Associated Filament resource class */
    protected static string $resource = ActivityResource::class;

    /** @var string Blade view template for statistics display */
    protected string $view = 'filament.resources.activity-resource.pages.activity-statistics';
    
    /** @var string Page title for activity statistics */
    protected static ?string $title = 'Activity Statistics';
    
    /** @var string Navigation icon for statistics page */
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-chart-bar';
    
    /** @var array Current statistics data for display */
    public array $stats = [];
    
    /** @var string Selected time period for analytics */
    public string $period = 'week';
    
    /** @var string|null Start date for statistics range */
    public ?string $from = null;
    
    /** @var string|null End date for statistics range */
    public ?string $to = null;
    
    /** @var MetricsService Service for system metrics calculation */
    protected MetricsService $metricsService;
    
    /**
     * Bootstrap the page with metrics service dependency injection.
     *
     * @param MetricsService $metricsService Service for system metrics calculation
     */
    public function boot(MetricsService $metricsService): void
    {
        $this->metricsService = $metricsService;
    }
    
    /**
     * Initialize page with default date range and load initial statistics.
     *
     * Sets up 30-day default date range and triggers initial statistics
     * loading for immediate display of agricultural system activity metrics.
     */
    public function mount(): void
    {
        $this->from = now()->subDays(30)->format('Y-m-d');
        $this->to = now()->format('Y-m-d');
        $this->loadStatistics();
    }
    
    /**
     * Load comprehensive system statistics based on current date range.
     *
     * Retrieves detailed activity metrics from the metrics service using
     * the configured date range for comprehensive agricultural system
     * analysis and operational intelligence.
     */
    public function loadStatistics(): void
    {
        $from = $this->from ? Carbon::parse($this->from) : null;
        $to = $this->to ? Carbon::parse($this->to) : null;
        
        $this->stats = $this->metricsService->getSystemMetrics($from, $to);
    }
    
    /**
     * Handle property updates with automatic statistics refresh.
     *
     * Responds to changes in date range or period selection by automatically
     * refreshing statistics data to maintain current analytics display.
     *
     * @param string $property Updated property name
     */
    public function updated($property): void
    {
        if (in_array($property, ['from', 'to', 'period'])) {
            $this->loadStatistics();
        }
    }
    
    /**
     * Configure header actions for statistics page functionality.
     *
     * Provides manual refresh capability and data export functionality
     * for comprehensive agricultural system analytics and reporting
     * workflow support.
     *
     * @return array Filament actions for statistics page operations
     * @workflow_features Manual refresh and JSON export capabilities
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Refresh')
                ->icon('heroicon-o-arrow-path')
                ->action('loadStatistics'),
            Action::make('export')
                ->label('Export Stats')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(function () {
                    return response()->json($this->stats)
                        ->header('Content-Disposition', 'attachment; filename="activity-stats-' . now()->format('Y-m-d') . '.json"');
                }),
        ];
    }
    
    /**
     * Prepare view data for statistics page template rendering.
     *
     * Aggregates current statistics, period selection, and date range
     * for comprehensive display in the agricultural system analytics
     * template.
     *
     * @return array Complete data package for statistics page display
     */
    public function getViewData(): array
    {
        return [
            'stats' => $this->stats,
            'period' => $this->period,
            'from' => $this->from,
            'to' => $this->to,
        ];
    }
}