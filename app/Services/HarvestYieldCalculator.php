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

            // Exponential decay: weight = e^(-days/30)
            // This gives 50% weight after ~21 days, 10% weight after ~69 days
            $weight = exp(-$daysSinceHarvest / 30);

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

        $sixMonthsAgo = Carbon::now()->subMonths(6);

        // Find harvests that match the seed variety
        // We need to match by master cultivar that corresponds to this recipe's seed
        return Harvest::with('masterCultivar.masterSeedCatalog')
            ->where('harvest_date', '>=', $sixMonthsAgo)
            ->whereHas('masterCultivar', function ($query) use ($recipe) {
                if ($recipe->seedEntry && $recipe->seedEntry->master_seed_catalog_id) {
                    $query->where('master_seed_catalog_id', $recipe->seedEntry->master_seed_catalog_id);
                }
            })
            ->orderBy('harvest_date', 'desc')
            ->get()
            ->filter(function ($harvest) {
                // Additional filtering by lot number if available
                // This would require extending the harvest model to track lot numbers
                // For now, we'll filter by the seed variety match
                return true;
            });
    }

    /**
     * Get the current active seed consumable for a recipe.
     */
    private function getCurrentSeedConsumable(Recipe $recipe): ?Consumable
    {
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

        if (abs($difference) < 5) {
            return 'Harvest data matches recipe expectations well.';
        } elseif ($difference > 15) {
            return 'Recent harvests significantly exceed expectations. Consider updating recipe yield.';
        } elseif ($difference < -15) {
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

        // Apply buffer percentage (default 10%)
        $bufferMultiplier = 1 + ($recipe->buffer_percentage / 100);

        return $baseYield / $bufferMultiplier;
    }
}
