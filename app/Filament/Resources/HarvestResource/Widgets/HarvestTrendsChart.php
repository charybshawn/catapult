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
 * and time periods. Displays top-performing varieties by total harvest weight or
 * individual variety performance over 8-week periods to support agricultural
 * production planning and variety optimization decisions.
 *
 * @filament_widget Chart widget for harvest performance analytics
 * @business_domain Agricultural harvest tracking and variety performance analysis
 * @analytics_context 8-week trends with variety-specific or comparative displays
 * @harvest_metrics Weight-based performance tracking in grams for precision
 * @production_planning Supports variety selection and capacity planning decisions
 */
class HarvestTrendsChart extends ChartWidget
{
    /** @var string Widget heading for harvest trends display */
    protected ?string $heading = 'Harvest Trends by Variety';
    
    /** @var array Responsive column span configuration */
    protected int | string | array $columnSpan = [
        'default' => 'full',
        'lg' => 1,
    ];
    
    /** @var string|null Current variety filter selection */
    public ?string $filter = 'all';
    
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
     * Creates 8-week trend visualization showing either top 5 varieties by
     * total harvest (comparative mode) or individual variety performance
     * (focused mode). Processes harvest data with weekly aggregation for
     * strategic production planning and variety optimization.
     *
     * @return array Chart.js compatible dataset with harvest trend data
     * @business_logic Top 5 varieties by total weight for comparative analysis
     * @agricultural_analytics 8-week weekly aggregation for trend identification
     * @production_metrics Weight-based performance tracking in grams
     */
    protected function getData(): array
    {
        $weeks = collect();
        $datasets = collect();
        
        // Get last 8 weeks of data
        for ($i = 7; $i >= 0; $i--) {
            $weekStart = Carbon::now()->subWeeks($i)->startOfWeek();
            $weekEnd = Carbon::now()->subWeeks($i)->endOfWeek();
            $weeks->push([
                'label' => $weekStart->format('M j'),
                'start' => $weekStart,
                'end' => $weekEnd,
            ]);
        }
        
        if ($this->filter === 'all') {
            // Show top 5 varieties by total harvest
            $topVarieties = Harvest::with('masterCultivar.masterSeedCatalog')
                ->selectRaw('master_cultivar_id, SUM(total_weight_grams) as total_weight')
                ->groupBy('master_cultivar_id')
                ->orderByDesc('total_weight')
                ->limit(5)
                ->get();
                
            $colors = [
                'rgb(59, 130, 246)',   // Blue
                'rgb(16, 185, 129)',   // Green
                'rgb(245, 158, 11)',   // Amber
                'rgb(239, 68, 68)',    // Red
                'rgb(139, 92, 246)',   // Purple
            ];
            
            foreach ($topVarieties as $index => $harvest) {
                $variety = $harvest->masterCultivar;
                $weeklyData = $this->getWeeklyDataForVariety($variety->id, $weeks);
                
                $datasets->push([
                    'label' => $variety->full_name,
                    'data' => $weeklyData,
                    'borderColor' => $colors[$index % count($colors)],
                    'backgroundColor' => $colors[$index % count($colors)] . '20',
                    'tension' => 0.4,
                ]);
            }
        } else {
            // Show single variety
            $variety = MasterCultivar::find($this->filter);
            if ($variety) {
                $weeklyData = $this->getWeeklyDataForVariety($variety->id, $weeks);
                
                $datasets->push([
                    'label' => $variety->full_name,
                    'data' => $weeklyData,
                    'borderColor' => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgb(59, 130, 246, 0.2)',
                    'tension' => 0.4,
                    'fill' => true,
                ]);
            }
        }
        
        return [
            'datasets' => $datasets->toArray(),
            'labels' => $weeks->pluck('label')->toArray(),
        ];
    }
    
    /**
     * Extract weekly harvest data for specific variety trend analysis.
     *
     * Aggregates harvest weights by week for individual variety performance
     * tracking. Provides granular weekly data points for trend visualization
     * and production pattern analysis in microgreens operations.
     *
     * @param int $varietyId Master cultivar ID for data extraction
     * @param Collection $weeks Collection of week periods for aggregation
     * @return array Weekly harvest totals in grams for chart plotting
     * @aggregation_logic Sums total_weight_grams within each week period
     */
    protected function getWeeklyDataForVariety(int $varietyId, Collection $weeks): array
    {
        return $weeks->map(function ($week) use ($varietyId) {
            return Harvest::where('master_cultivar_id', $varietyId)
                ->whereBetween('harvest_date', [$week['start'], $week['end']])
                ->sum('total_weight_grams');
        })->toArray();
    }
    
    /**
     * Get chart type for harvest trends visualization.
     *
     * @return string Chart.js chart type identifier for line chart display
     */
    protected function getType(): string
    {
        return 'line';
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
                        'text' => 'Week Starting',
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