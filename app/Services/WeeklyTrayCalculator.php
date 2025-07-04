<?php

namespace App\Services;

use App\Models\Order;
use App\Models\ProductMix;
use App\Models\SeedEntry;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class WeeklyTrayCalculator
{
    /**
     * Calculate the total trays needed for each variety for a given week.
     *
     * @param Carbon $weekStart The start of the week
     * @return array Array of [variety_id => total_trays_needed]
     */
    public function calculateWeeklyTrays(Carbon $weekStart): array
    {
        $weekEnd = $weekStart->copy()->endOfWeek();
        
        // Get all orders for the week
        $orders = Order::whereBetween('delivery_date', [$weekStart, $weekEnd])
            ->where('status', '!=', 'cancelled')
            ->with(['items.recipe.seedEntry', 'items.productMix'])
            ->get();
            
        $varietyTrays = [];
        
        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                if ($item->product_mix_id) {
                    // Handle mix items
                    $mix = $item->productMix;
                    $mixTrays = $this->calculateMixTrays($mix, $item->quantity);
                    
                    foreach ($mixTrays as $varietyId => $trays) {
                        $varietyTrays[$varietyId] = ($varietyTrays[$varietyId] ?? 0) + $trays;
                    }
                } else {
                    // Handle single variety items
                    $varietyId = $item->recipe->seed_entry_id;
                    $varietyTrays[$varietyId] = ($varietyTrays[$varietyId] ?? 0) + $item->quantity;
                }
            }
        }
        
        return $varietyTrays;
    }
    
    /**
     * Calculate trays needed for each variety in a mix.
     *
     * @param ProductMix $mix The product mix
     * @param int $totalTrays Total trays needed for this mix
     * @return array Array of [variety_id => trays_needed]
     */
    protected function calculateMixTrays(ProductMix $mix, int $totalTrays): array
    {
        return $mix->calculateVarietyTrays($totalTrays);
    }
    
    /**
     * Get a summary of the weekly tray requirements.
     *
     * @param Carbon $weekStart The start of the week
     * @return Collection Collection of variety tray requirements with details
     */
    public function getWeeklySummary(Carbon $weekStart): Collection
    {
        $varietyTrays = $this->calculateWeeklyTrays($weekStart);
        
        return collect($varietyTrays)->map(function ($trays, $varietyId) {
            $seedEntry = SeedEntry::find($varietyId);
            return [
                'variety_id' => $varietyId,
                'variety_name' => $seedEntry ? $seedEntry->name : 'Unknown',
                'trays_needed' => $trays,
                'crop_type' => $seedEntry ? $seedEntry->crop_type : 'Unknown',
            ];
        })->sortBy('variety_name');
    }
} 