<?php

namespace App\Filament\Resources\HarvestResource\Widgets;

use App\Models\Harvest;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

/**
 * Comprehensive harvest statistics widget for agricultural operations monitoring.
 *
 * Provides detailed harvest analytics including weekly totals, daily averages,
 * per-harvest weights, today's performance, variety diversity, and historical
 * counts. Designed to complement the variety comparison widget with comprehensive
 * performance metrics for microgreens production optimization.
 *
 * @filament_widget Stats overview widget for comprehensive harvest analytics
 * @business_domain Agricultural harvest tracking and production performance
 * @operational_context Monday-Sunday standard week alignment for consistency
 * @performance_metrics Weekly totals, daily averages, harvest weights, variety counts
 * @dashboard_updates Real-time polling every 10 seconds for current awareness
 */
class HarvestTotalsStats extends BaseWidget
{
    /** @var string Polling interval for real-time harvest monitoring */
    protected ?string $pollingInterval = '10s';
    
    /** @var array Responsive column span configuration */
    protected int | string | array $columnSpan = [
        'default' => 'full',
        'lg' => 1,
    ];
    
    /** @var array Custom CSS classes for compact display */
    protected array $extraAttributes = [
        'class' => 'harvest-stats-compact',
    ];

    /**
     * Generate comprehensive harvest statistics for agricultural operations.
     *
     * Calculates and displays detailed harvest analytics including weekly totals,
     * daily averages, per-harvest weights, today's performance, variety diversity,
     * and historical counts. Provides comprehensive metrics for production
     * monitoring, performance tracking, and operational decision-making.
     *
     * @return array Filament Stat components with comprehensive harvest analytics
     * @business_logic Standard week (Mon-Sun) for chart consistency and clarity
     * @performance_analysis Week-over-week trends, daily patterns, variety diversity
     * @operational_metrics Weekly/daily totals, harvest weights, variety counts, trends
     */
    protected function getStats(): array
    {
        $currentWeekStart = Carbon::now()->startOfWeek(Carbon::MONDAY);
        $currentWeekEnd = Carbon::now()->endOfWeek(Carbon::SUNDAY);

        $lastWeekStart = $currentWeekStart->copy()->subWeek();
        $lastWeekEnd = $currentWeekEnd->copy()->subWeek();

        // Get current week stats
        $currentWeekHarvests = Harvest::whereBetween('harvest_date', [$currentWeekStart, $currentWeekEnd])
            ->get();

        // Get last week stats for comparison
        $lastWeekTotal = Harvest::whereBetween('harvest_date', [$lastWeekStart, $lastWeekEnd])
            ->sum('total_weight_grams');

        $currentWeekTotal = $currentWeekHarvests->sum('total_weight_grams');

        // Calculate week-over-week change
        $weekChange = $lastWeekTotal > 0
            ? (($currentWeekTotal - $lastWeekTotal) / $lastWeekTotal) * 100
            : 0;

        // Get total harvests for debugging
        $totalHarvests = Harvest::count();
        $thisWeekHarvests = $currentWeekHarvests->count();

        // Calculate additional metrics
        $avgHarvestWeight = $thisWeekHarvests > 0 ? $currentWeekTotal / $thisWeekHarvests : 0;
        $dailyAverage = $currentWeekTotal / 7; // 7 days in week
        
        // Get yesterday's harvest for comparison
        $yesterday = Carbon::yesterday();
        $yesterdayHarvest = Harvest::whereDate('harvest_date', $yesterday)->sum('total_weight_grams');
        $todayHarvest = Harvest::whereDate('harvest_date', Carbon::today())->sum('total_weight_grams');
        
        // Get variety count for this week
        $varietyCount = $currentWeekHarvests->pluck('master_cultivar_id')->unique()->count();
        
        return [
            Stat::make('Total Harvest This Week', number_format($currentWeekTotal / 1000, 2).' kg')
                ->description($weekChange >= 0
                    ? '+'.number_format(abs($weekChange), 1).'% from last week'
                    : number_format($weekChange, 1).'% from last week')
                ->descriptionIcon($weekChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($weekChange >= 0 ? 'success' : 'danger')
                ->chart($this->getWeeklyChart()),

            Stat::make('Daily Average', number_format($dailyAverage / 1000, 2).' kg')
                ->description('This week\'s daily average')
                ->icon('heroicon-o-calendar-days'),

            Stat::make('Average per Harvest', number_format($avgHarvestWeight, 0).' g')
                ->description($thisWeekHarvests.' harvest entries this week')
                ->icon('heroicon-o-scale'),

            Stat::make('Today\'s Harvest', number_format($todayHarvest / 1000, 2).' kg')
                ->description($yesterdayHarvest > 0 
                    ? 'Yesterday: '.number_format($yesterdayHarvest / 1000, 2).' kg'
                    : 'No harvest yesterday')
                ->icon('heroicon-o-sun'),

            Stat::make('Varieties This Week', $varietyCount)
                ->description('Different cultivars harvested')
                ->icon('heroicon-o-beaker'),

            Stat::make('Total Harvests', $totalHarvests)
                ->description('All time records')
                ->icon('heroicon-o-clipboard-document-list'),
        ];
    }

    /**
     * Generate weekly harvest trend data for sparkline chart visualization.
     *
     * Creates 7-week harvest trend data converted to kilograms for consistent
     * display. Supports trend analysis and performance pattern identification
     * in agricultural harvest operations.
     *
     * @return array Weekly harvest totals in kilograms for chart display
     * @chart_data 7 weeks of historical data for trend visualization
     * @agricultural_context Uses Mon-Sun standard week definition for consistency
     */
    protected function getWeeklyChart(): array
    {
        $data = [];

        // Get data for the last 7 weeks (Monday-Sunday weeks)
        for ($i = 6; $i >= 0; $i--) {
            $weekStart = Carbon::now()->startOfWeek(Carbon::MONDAY)->subWeeks($i);
            $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);

            $weekTotal = Harvest::whereBetween('harvest_date', [$weekStart, $weekEnd])
                ->sum('total_weight_grams');

            $data[] = round($weekTotal / 1000, 2); // Convert to kg
        }

        return $data;
    }
}