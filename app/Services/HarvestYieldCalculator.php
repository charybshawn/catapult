<?php

namespace App\Services;

use App\Models\Consumable;
use App\Models\Harvest;
use App\Models\Recipe;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class HarvestYieldCalculator
{
    /**
     * Calculate weighted average yield per tray for a recipe based on harvest data.
     * Uses exponential decay weighting to favor more recent harvests.
     * Matches by recipe AND seed lot number for accuracy.
     */
    public function calculateWeightedYieldForRecipe(Recipe $recipe): ?float
    {
        // Find harvests that match this recipe's seed variety and lot
        $relevantHarvests = $this->getRelevantHarvests($recipe);

        if ($relevantHarvests->isEmpty()) {
            return null; // No harvest data available
        }

        $weightedSum = 0;
        $totalWeight = 0;
        $now = Carbon::now();

        foreach ($relevantHarvests as $harvest) {
            $daysSinceHarvest = $now->diffInDays($harvest->harvest_date);

            // Exponential decay: weight = e^(-days/decay_factor)
            $decayFactor = config('harvest.yield.decay_factor', 30);
            $weight = exp(-$daysSinceHarvest / $decayFactor);

            $yieldPerTray = $harvest->average_weight_per_tray;

            $weightedSum += $yieldPerTray * $weight;
            $totalWeight += $weight;
        }

        return $totalWeight > 0 ? $weightedSum / $totalWeight : null;
    }

    /**
     * Get harvest data that matches the recipe's seed variety and current seed lot.
     * Filters to last 6 months to keep data relevant.
     */
    private function getRelevantHarvests(Recipe $recipe): Collection
    {
        // Get the current seed consumable for this recipe
        $seedConsumable = $this->getCurrentSeedConsumable($recipe);

        if (! $seedConsumable) {
            return collect();
        }

        $historyMonths = config('harvest.yield.history_months', 6);
        $sixMonthsAgo = Carbon::now()->subMonths($historyMonths);

        // Find harvests that match the recipe's variety
        // Match by common name and cultivar name
        return Harvest::with('masterCultivar.masterSeedCatalog')
            ->where('harvest_date', '>=', $sixMonthsAgo)
            ->whereHas('masterCultivar.masterSeedCatalog', function ($query) use ($recipe) {
                $query->where('common_name', $recipe->common_name);
                if ($recipe->cultivar_name) {
                    $query->where('cultivar_name', $recipe->cultivar_name);
                }
            })
            ->orderBy('harvest_date', 'desc')
            ->get();
    }

    /**
     * Get the current active seed consumable for a recipe.
     * @deprecated Use lot-based methods instead
     */
    private function getCurrentSeedConsumable(Recipe $recipe): ?Consumable
    {
        // First try to get from lot_number (new system)
        if ($recipe->lot_number) {
            return $recipe->availableLotConsumables()->first();
        }
        
        // Fallback to deprecated seed_consumable_id for backward compatibility
        if (! $recipe->seed_consumable_id) {
            return null;
        }

        return Consumable::where('id', $recipe->seed_consumable_id)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Get yield statistics for a recipe including historical data.
     */
    public function getYieldStats(Recipe $recipe): array
    {
        $harvests = $this->getRelevantHarvests($recipe);
        $weightedYield = $this->calculateWeightedYieldForRecipe($recipe);

        if ($harvests->isEmpty()) {
            return [
                'weighted_yield' => null,
                'harvest_count' => 0,
                'date_range' => null,
                'min_yield' => null,
                'max_yield' => null,
                'avg_yield' => null,
                'recipe_expected' => $recipe->expected_yield_grams,
                'recommendation' => 'No harvest data available. Using recipe expected yield.',
            ];
        }

        $yields = $harvests->pluck('average_weight_per_tray');

        return [
            'weighted_yield' => $weightedYield,
            'harvest_count' => $harvests->count(),
            'date_range' => [
                'oldest' => $harvests->last()->harvest_date->format('M j, Y'),
                'newest' => $harvests->first()->harvest_date->format('M j, Y'),
            ],
            'min_yield' => $yields->min(),
            'max_yield' => $yields->max(),
            'avg_yield' => round($yields->avg(), 2),
            'recipe_expected' => $recipe->expected_yield_grams,
            'recommendation' => $this->getRecommendation($weightedYield, $recipe->expected_yield_grams),
        ];
    }

    /**
     * Get a recommendation based on weighted yield vs recipe expected yield.
     */
    private function getRecommendation(float $weightedYield, float $expectedYield): string
    {
        $difference = (($weightedYield - $expectedYield) / $expectedYield) * 100;

        $matchingWell = config('harvest.yield.thresholds.matching_well', 5.0);
        $significantlyOver = config('harvest.yield.thresholds.significantly_over', 15.0);
        $significantlyUnder = config('harvest.yield.thresholds.significantly_under', -15.0);

        if (abs($difference) < $matchingWell) {
            return 'Harvest data matches recipe expectations well.';
        } elseif ($difference > $significantlyOver) {
            return 'Recent harvests significantly exceed expectations. Consider updating recipe yield.';
        } elseif ($difference < $significantlyUnder) {
            return 'Recent harvests are below expectations. Consider reviewing growing conditions.';
        } elseif ($difference > 0) {
            return 'Recent harvests are above expectations.';
        } else {
            return 'Recent harvests are below expectations.';
        }
    }

    /**
     * Calculate effective yield for planning (includes buffer).
     */
    public function calculatePlanningYield(Recipe $recipe): float
    {
        $weightedYield = $this->calculateWeightedYieldForRecipe($recipe);
        $baseYield = $weightedYield ?? $recipe->expected_yield_grams;

        // Apply buffer percentage (use config default if not set on recipe)
        $bufferPercentage = $recipe->buffer_percentage ?? config('harvest.planning.default_buffer_percentage', 10.0);
        $bufferMultiplier = 1 + ($bufferPercentage / 100);

        return $baseYield / $bufferMultiplier;
    }
}
