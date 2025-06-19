<?php

namespace App\Filament\Resources\HarvestResource\Widgets;

use App\Models\Harvest;
use App\Models\MasterCultivar;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class WeeklyHarvestStats extends BaseWidget
{
    protected static ?string $pollingInterval = '10s';

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
                $varietyName = $cultivar ? $cultivar->cultivar_name : 'Unknown';

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
            $stats[] = Stat::make(
                $variety['name'],
                number_format($variety['weight'] / 1000, 2).' kg'
            )
                ->description($variety['trays'].' trays • '.number_format($variety['weight'] / $variety['trays'], 1).'g avg')
                ->icon('heroicon-o-beaker');
        }

        return $stats;
    }

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
