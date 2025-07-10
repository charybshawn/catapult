<?php

namespace App\Filament\Resources\HarvestResource\Widgets;

use App\Models\Harvest;
use App\Models\MasterCultivar;
use Carbon\Carbon;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;

class WeeklyVarietyComparison extends Widget
{
    protected static string $view = 'filament.resources.harvest-resource.widgets.weekly-variety-comparison';
    
    protected int | string | array $columnSpan = [
        'default' => 'full',
        'lg' => 1,
    ];
    
    public function getVarietyComparisonData(): Collection
    {
        $thisWeekStart = Carbon::now()->startOfWeek();
        $thisWeekEnd = Carbon::now()->endOfWeek();
        $lastWeekStart = Carbon::now()->subWeek()->startOfWeek();
        $lastWeekEnd = Carbon::now()->subWeek()->endOfWeek();
        
        // Get this week's harvests by variety
        $thisWeekHarvests = Harvest::with('masterCultivar.masterSeedCatalog')
            ->whereBetween('harvest_date', [$thisWeekStart, $thisWeekEnd])
            ->selectRaw('master_cultivar_id, SUM(total_weight_grams) as total_weight')
            ->groupBy('master_cultivar_id')
            ->get()
            ->keyBy('master_cultivar_id');
            
        // Get last week's harvests by variety
        $lastWeekHarvests = Harvest::with('masterCultivar.masterSeedCatalog')
            ->whereBetween('harvest_date', [$lastWeekStart, $lastWeekEnd])
            ->selectRaw('master_cultivar_id, SUM(total_weight_grams) as total_weight')
            ->groupBy('master_cultivar_id')
            ->get()
            ->keyBy('master_cultivar_id');
            
        // Get all varieties that had harvests in either week
        $allVarietyIds = collect()
            ->merge($thisWeekHarvests->keys())
            ->merge($lastWeekHarvests->keys())
            ->unique();
            
        $varieties = MasterCultivar::with('masterSeedCatalog')
            ->whereIn('id', $allVarietyIds)
            ->get()
            ->keyBy('id');
            
        return $allVarietyIds->map(function ($varietyId) use ($thisWeekHarvests, $lastWeekHarvests, $varieties) {
            $variety = $varieties->get($varietyId);
            $thisWeekWeight = $thisWeekHarvests->get($varietyId)?->total_weight ?? 0;
            $lastWeekWeight = $lastWeekHarvests->get($varietyId)?->total_weight ?? 0;
            
            // Calculate percentage change
            $percentageChange = 0;
            if ($lastWeekWeight > 0) {
                $percentageChange = (($thisWeekWeight - $lastWeekWeight) / $lastWeekWeight) * 100;
            } elseif ($thisWeekWeight > 0) {
                $percentageChange = 100; // New variety this week
            }
            
            return [
                'variety_name' => $variety?->full_name ?? 'Unknown Variety',
                'this_week_total' => $thisWeekWeight,
                'last_week_total' => $lastWeekWeight,
                'percentage_change' => $percentageChange,
                'change_direction' => $percentageChange >= 0 ? 'up' : 'down',
            ];
        })->sortByDesc('this_week_total');
    }
}