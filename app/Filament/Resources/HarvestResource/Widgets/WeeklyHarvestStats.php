<?php

namespace App\Filament\Resources\HarvestResource\Widgets;

use App\Models\Harvest;
use App\Models\MasterCultivar;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

/**
 * Weekly harvest performance statistics widget for agricultural operations monitoring.
 *
 * Provides comprehensive weekly harvest analytics including total weight harvested,
 * tray counts, average yields per tray, week-over-week performance comparison,
 * and top variety breakdown. Uses agricultural week definition (Wednesday to Tuesday)
 * for operational alignment with microgreens production cycles.
 *
 * @filament_widget Stats overview widget for weekly harvest performance
 * @business_domain Agricultural harvest tracking and production analytics
 * @operational_context Wednesday-Tuesday agricultural week alignment
 * @performance_metrics Total weight, tray counts, averages, variety breakdown
 * @dashboard_updates Real-time polling every 10 seconds for current awareness
 */
class WeeklyHarvestStats extends BaseWidget
{
    /** @var string Polling interval for real-time harvest monitoring */
    protected ?string $pollingInterval = '10s';

    /**
     * Generate comprehensive weekly harvest statistics for agricultural operations.
     *
     * Calculates and displays weekly harvest performance including total weight,
     * tray counts, average yields, week-over-week comparisons, and top variety
     * contributions. Uses agricultural week definition (Wed-Tue) and provides
     * detailed variety breakdown for production optimization.
     *
     * @return array Filament Stat components with harvest analytics and trends
     * @business_logic Agricultural week (Wed-Tue) for operational alignment
     * @performance_analysis Week-over-week comparison and variety rankings
     * @operational_metrics Total weight (kg), tray counts, yield averages
     */
    protected function getStats(): array
    {
        $currentWeekStart = Carbon::now()->startOfWeek(Carbon::WEDNESDAY);
        $currentWeekEnd = Carbon::now()->endOfWeek(Carbon::TUESDAY);

        $lastWeekStart = $currentWeekStart->copy()->subWeek();
        $lastWeekEnd = $currentWeekEnd->copy()->subWeek();

        // Get current week stats
        $currentWeekHarvests = Harvest::whereBetween('harvest_date', [$currentWeekStart, $currentWeekEnd])
            ->with('masterCultivar.masterSeedCatalog')
            ->get();

        // Get last week stats for comparison
        $lastWeekTotal = Harvest::whereBetween('harvest_date', [$lastWeekStart, $lastWeekEnd])
            ->sum('total_weight_grams');

        $currentWeekTotal = $currentWeekHarvests->sum('total_weight_grams');
        $currentWeekTrays = $currentWeekHarvests->sum('tray_count');
        $avgPerTray = $currentWeekTrays > 0 ? $currentWeekTotal / $currentWeekTrays : 0;

        // Calculate week-over-week change
        $weekChange = $lastWeekTotal > 0
            ? (($currentWeekTotal - $lastWeekTotal) / $lastWeekTotal) * 100
            : 0;

        // Group by variety
        $varietyBreakdown = $currentWeekHarvests->groupBy('master_cultivar_id')
            ->map(function ($harvests, $cultivarId) {
                $cultivar = MasterCultivar::with('masterSeedCatalog')->find($cultivarId);
                $varietyName = $cultivar ? $cultivar->name : 'Unknown';

                return [
                    'name' => $varietyName,
                    'weight' => $harvests->sum('total_weight_grams'),
                    'trays' => $harvests->sum('tray_count'),
                ];
            })
            ->sortByDesc('weight')
            ->take(3);

        // Get total harvests for debugging
        $totalHarvests = Harvest::count();
        $thisWeekHarvests = $currentWeekHarvests->count();

        $stats = [
            Stat::make('Total Harvest This Week', number_format($currentWeekTotal / 1000, 2).' kg')
                ->description($weekChange >= 0
                    ? '+'.number_format(abs($weekChange), 1).'% from last week'
                    : number_format($weekChange, 1).'% from last week')
                ->descriptionIcon($weekChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($weekChange >= 0 ? 'success' : 'danger')
                ->chart($this->getWeeklyChart()),

            Stat::make('Trays Harvested', $currentWeekTrays)
                ->description('Week of '.$currentWeekStart->format('M j').' ('.$thisWeekHarvests.' entries)')
                ->icon('heroicon-o-rectangle-stack'),

            Stat::make('Average per Tray', number_format($avgPerTray, 1).' g')
                ->description('This week\'s average')
                ->icon('heroicon-o-scale'),

            Stat::make('Total Harvests', $totalHarvests)
                ->description('All time records')
                ->icon('heroicon-o-clipboard-document-list'),
        ];

        // Add top varieties
        foreach ($varietyBreakdown as $variety) {
            // Skip if name is null or empty
            if (empty($variety['name'])) {
                continue;
            }
            
            $stats[] = Stat::make(
                $variety['name'],
                number_format($variety['weight'] / 1000, 2).' kg'
            )
                ->description($variety['trays'].' trays • '.number_format($variety['weight'] / ($variety['trays'] ?: 1), 1).'g avg')
                ->icon('heroicon-o-beaker');
        }

        return $stats;
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
     * @agricultural_context Uses Wed-Tue agricultural week definition
     */
    protected function getWeeklyChart(): array
    {
        $data = [];

        // Get data for the last 7 weeks
        for ($i = 6; $i >= 0; $i--) {
            $weekStart = Carbon::now()->startOfWeek(Carbon::WEDNESDAY)->subWeeks($i);
            $weekEnd = $weekStart->copy()->endOfWeek(Carbon::TUESDAY);

            $weekTotal = Harvest::whereBetween('harvest_date', [$weekStart, $weekEnd])
                ->sum('total_weight_grams');

            $data[] = round($weekTotal / 1000, 2); // Convert to kg
        }

        return $data;
    }
}
