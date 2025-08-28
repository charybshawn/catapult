<?php

namespace App\Filament\Resources\HarvestResource\Widgets;

use App\Models\Harvest;
use App\Models\MasterCultivar;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Collection;

/**
 * Agricultural harvest trends analytics widget for production performance tracking.
 *
 * Provides comprehensive visualization of microgreens harvest trends across varieties
 * showing actual harvest dates and weights. Displays top-performing varieties by total 
 * harvest weight or individual variety performance on specific harvest dates to support 
 * agricultural production planning and variety optimization decisions.
 *
 * @filament_widget Chart widget for harvest performance analytics
 * @business_domain Agricultural harvest tracking and variety performance analysis
 * @analytics_context Daily harvest tracking with variety-specific or comparative displays
 * @harvest_metrics Weight-based performance tracking in grams showing actual harvest dates
 * @production_planning Supports variety selection and capacity planning decisions
 */
class HarvestTrendsChart extends ChartWidget
{
    /** @var string Widget heading for harvest trends display */
    protected ?string $heading = 'Daily Harvest by Variety';
    
    /** @var array Responsive column span configuration */
    protected int | string | array $columnSpan = 'full';
    
    /** @var string|null Current variety filter selection */
    public ?string $filter = 'all';
    
    /** @var string Polling interval for reactive chart updates */
    protected ?string $pollingInterval = '30s';
    
    /**
     * Generate variety filter options for harvest trend analysis.
     *
     * Creates filter dropdown with all varieties that have harvest records
     * plus an "All Varieties" option for comparative analysis. Uses full
     * variety names including common name and cultivar for clear identification.
     *
     * @return array|null Filter options with variety IDs and display names
     * @business_context Only includes varieties with actual harvest data
     * @display_format Uses full variety names for user-friendly selection
     */
    protected function getFilters(): ?array
    {
        $varieties = MasterCultivar::with('masterSeedCatalog')
            ->whereHas('harvests')
            ->get()
            ->mapWithKeys(function ($cultivar) {
                return [$cultivar->id => $cultivar->full_name];
            })
            ->toArray();
            
        return ['all' => 'All Varieties'] + $varieties;
    }
    
    /**
     * Generate comprehensive harvest trend data for agricultural analytics.
     *
     * Creates daily trend visualization showing all varieties with harvests
     * (comparative mode) or individual variety performance (focused mode).
     * Processes harvest data with daily aggregation for strategic production
     * planning and complete variety performance analysis.
     *
     * @return array Chart.js compatible dataset with harvest trend data
     * @business_logic All varieties with harvests for complete analysis
     * @agricultural_analytics Daily harvest tracking showing actual harvest dates
     * @production_metrics Weight-based performance tracking in grams per actual harvest date
     */
    protected function getData(): array
    {
        $dates = collect();
        $datasets = collect();
        
        // Get last 60 days of dates with actual harvests
        $harvestDates = Harvest::where('harvest_date', '>=', Carbon::now()->subDays(60))
            ->orderBy('harvest_date')
            ->pluck('harvest_date')
            ->map(fn($date) => $date->format('Y-m-d'))
            ->unique()
            ->values();
            
        // Create labels from actual harvest dates
        $dates = $harvestDates->map(function($date) {
            return [
                'label' => Carbon::parse($date)->format('M j'),
                'date' => $date,
            ];
        });
        
        if ($this->filter === 'all') {
            // Show all varieties with harvests
            $allVarieties = Harvest::with('masterCultivar.masterSeedCatalog')
                ->where('harvest_date', '>=', Carbon::now()->subDays(60))
                ->selectRaw('master_cultivar_id, SUM(total_weight_grams) as total_weight')
                ->groupBy('master_cultivar_id')
                ->orderByDesc('total_weight')
                ->get();
                
            $colors = [
                'rgb(59, 130, 246)',   // Blue
                'rgb(16, 185, 129)',   // Green
                'rgb(245, 158, 11)',   // Amber
                'rgb(239, 68, 68)',    // Red
                'rgb(139, 92, 246)',   // Purple
            ];
            
            foreach ($allVarieties as $index => $harvest) {
                $variety = $harvest->masterCultivar;
                $dailyData = $this->getDailyDataForVariety($variety->id, $dates);
                
                $datasets->push([
                    'label' => $variety->full_name,
                    'data' => $dailyData,
                    'borderColor' => $colors[$index % count($colors)],
                    'backgroundColor' => $colors[$index % count($colors)],
                    'borderWidth' => 1,
                ]);
            }
        } else {
            // Show single variety
            $variety = MasterCultivar::find($this->filter);
            if ($variety) {
                $dailyData = $this->getDailyDataForVariety($variety->id, $dates);
                
                $datasets->push([
                    'label' => $variety->full_name,
                    'data' => $dailyData,
                    'borderColor' => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgb(59, 130, 246)',
                    'borderWidth' => 1,
                ]);
            }
        }
        
        return [
            'datasets' => $datasets->toArray(),
            'labels' => $dates->pluck('label')->toArray(),
        ];
    }
    
    /**
     * Extract daily harvest data for specific variety trend analysis.
     *
     * Gets harvest weights by actual harvest date for individual variety performance
     * tracking. Provides precise daily data points for trend visualization
     * showing exactly when harvests occurred in microgreens operations.
     *
     * @param int $varietyId Master cultivar ID for data extraction
     * @param Collection $dates Collection of actual harvest dates
     * @return array Daily harvest totals in grams for chart plotting
     * @aggregation_logic Sums total_weight_grams for each actual harvest date
     */
    protected function getDailyDataForVariety(int $varietyId, Collection $dates): array
    {
        return $dates->map(function ($dateInfo) use ($varietyId) {
            return Harvest::where('master_cultivar_id', $varietyId)
                ->whereDate('harvest_date', $dateInfo['date'])
                ->sum('total_weight_grams');
        })->toArray();
    }
    
    /**
     * Get chart type for harvest trends visualization.
     *
     * @return string Chart.js chart type identifier for bar chart display
     */
    protected function getType(): string
    {
        return 'bar';
    }
    
    /**
     * Configure chart display options for harvest trends visualization.
     *
     * Sets up comprehensive chart configuration with legend positioning,
     * axis labeling for agricultural context (weight in grams, week periods),
     * and interaction modes optimized for trend analysis and comparison.
     *
     * @return array Chart.js configuration options for harvest analytics
     * @agricultural_context Y-axis shows weight in grams, X-axis shows weeks
     * @interaction_features Index mode for multi-variety comparison support
     */
    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'title' => [
                        'display' => true,
                        'text' => 'Weight (grams)',
                    ],
                ],
                'x' => [
                    'title' => [
                        'display' => true,
                        'text' => 'Harvest Date',
                    ],
                ],
            ],
            'interaction' => [
                'intersect' => false,
                'mode' => 'index',
            ],
            'maintainAspectRatio' => false,
        ];
    }
}