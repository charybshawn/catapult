<?php

namespace App\Services;

use Exception;
use App\Models\CropPlan;
use App\Models\CropPlanStatus;
use App\Models\Recipe;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * Service for aggregating crop plans to optimize planting operations
 */
class CropPlanAggregationService
{
    /**
     * Find and aggregate all plans for same variety/date
     * 
     * @param Carbon $harvestDate
     * @param int $varietyId Master seed catalog ID
     * @return Collection Collection of aggregatable plans
     */
    public function aggregatePlansForHarvestDate(Carbon $harvestDate, int $varietyId): Collection
    {
        // Find all draft or active plans for this variety and harvest date
        $plans = CropPlan::whereDate('expected_harvest_date', $harvestDate)
            ->whereHas('recipe', function ($query) use ($varietyId) {
                $query->whereHas('lotConsumables', function ($q) use ($varietyId) {
                    $q->where('master_seed_catalog_id', $varietyId);
                });
            })
            ->whereHas('status', function ($query) {
                $query->whereIn('code', ['draft', 'active']);
            })
            ->with(['recipe', 'order', 'status'])
            ->get();

        // Group by recipe to ensure we're using the same growing parameters
        $groupedPlans = $plans->groupBy('recipe_id');

        return $groupedPlans->map(function ($recipePlans) {
            return [
                'recipe' => $recipePlans->first()->recipe,
                'plans' => $recipePlans,
                'total_trays' => $recipePlans->sum('trays_needed'),
                'total_grams' => $recipePlans->sum('grams_needed'),
                'plant_by_date' => $recipePlans->first()->plant_by_date,
                'harvest_date' => $recipePlans->first()->expected_harvest_date,
                'order_count' => $recipePlans->count(),
                'orders' => $recipePlans->pluck('order')->unique('id')
            ];
        });
    }

    /**
     * Add new plan quantities to existing aggregated plan
     * 
     * @param CropPlan $newPlan
     * @return CropPlan|null The aggregated plan if aggregation occurred
     */
    public function addToExistingPlan(CropPlan $newPlan): ?CropPlan
    {
        // Don't aggregate if plan is already approved or completed
        if (!in_array($newPlan->status->code, ['draft'])) {
            return null;
        }

        // Find existing plans that can be aggregated with this one
        $existingPlan = $this->findAggregatablePlan($newPlan);

        if (!$existingPlan) {
            return null;
        }

        DB::transaction(function () use ($existingPlan, $newPlan) {
            // Update the existing plan with aggregated values
            $existingPlan->update([
                'trays_needed' => $existingPlan->trays_needed + $newPlan->trays_needed,
                'grams_needed' => $existingPlan->grams_needed + $newPlan->grams_needed,
                'admin_notes' => $this->updateAggregationNotes(
                    $existingPlan->admin_notes,
                    $newPlan->order_id,
                    $newPlan->trays_needed
                )
            ]);

            // Update calculation details to track aggregation
            $calculationDetails = $existingPlan->calculation_details ?? [];
            $calculationDetails['aggregated_orders'] = array_merge(
                $calculationDetails['aggregated_orders'] ?? [$existingPlan->order_id],
                [$newPlan->order_id]
            );
            $calculationDetails['aggregation_history'] = array_merge(
                $calculationDetails['aggregation_history'] ?? [],
                [[
                    'date' => now()->toDateTimeString(),
                    'order_id' => $newPlan->order_id,
                    'trays_added' => $newPlan->trays_needed,
                    'grams_added' => $newPlan->grams_needed
                ]]
            );
            
            $existingPlan->update([
                'calculation_details' => $calculationDetails
            ]);

            // Cancel the new plan since it's been aggregated
            $cancelledStatus = CropPlanStatus::where('code', 'cancelled')->first();
            $newPlan->update([
                'status_id' => $cancelledStatus->id,
                'admin_notes' => "Aggregated into plan #{$existingPlan->id}"
            ]);

            Log::info('Aggregated crop plan', [
                'aggregated_plan_id' => $existingPlan->id,
                'cancelled_plan_id' => $newPlan->id,
                'new_total_trays' => $existingPlan->trays_needed
            ]);
        });

        return $existingPlan;
    }

    /**
     * Find an existing plan that can be aggregated with the new plan
     * 
     * @param CropPlan $newPlan
     * @return CropPlan|null
     */
    protected function findAggregatablePlan(CropPlan $newPlan): ?CropPlan
    {
        return CropPlan::where('recipe_id', $newPlan->recipe_id)
            ->whereDate('plant_by_date', $newPlan->plant_by_date)
            ->whereDate('expected_harvest_date', $newPlan->expected_harvest_date)
            ->where('id', '!=', $newPlan->id)
            ->whereHas('status', function ($query) {
                $query->where('code', 'draft');
            })
            ->first();
    }

    /**
     * Update aggregation notes
     * 
     * @param string|null $existingNotes
     * @param int $orderId
     * @param int $traysAdded
     * @return string
     */
    protected function updateAggregationNotes(?string $existingNotes, int $orderId, int $traysAdded): string
    {
        $timestamp = now()->format('Y-m-d H:i');
        $newNote = "[$timestamp] Aggregated {$traysAdded} trays from Order #{$orderId}";
        
        if ($existingNotes) {
            return $existingNotes . "\n" . $newNote;
        }
        
        return $newNote;
    }

    /**
     * Recalculate totals for an aggregated plan
     * 
     * @param int $aggregatedPlanId
     * @return CropPlan
     */
    public function recalculateAggregation(int $aggregatedPlanId): CropPlan
    {
        $plan = CropPlan::findOrFail($aggregatedPlanId);
        $calculationDetails = $plan->calculation_details ?? [];
        $aggregatedOrders = $calculationDetails['aggregated_orders'] ?? [$plan->order_id];

        // Recalculate totals from all associated orders
        $totalTrays = 0;
        $totalGrams = 0;
        $orderDetails = [];

        foreach ($aggregatedOrders as $orderId) {
            // Find all non-cancelled plans for this order and recipe
            $orderPlans = CropPlan::where('order_id', $orderId)
                ->where('recipe_id', $plan->recipe_id)
                ->whereHas('status', function ($query) {
                    $query->where('code', '!=', 'cancelled');
                })
                ->get();

            foreach ($orderPlans as $orderPlan) {
                $totalTrays += $orderPlan->trays_needed;
                $totalGrams += $orderPlan->grams_needed;
                $orderDetails[] = [
                    'order_id' => $orderId,
                    'trays' => $orderPlan->trays_needed,
                    'grams' => $orderPlan->grams_needed
                ];
            }
        }

        // Update the plan with recalculated values
        $plan->update([
            'trays_needed' => $totalTrays,
            'grams_needed' => $totalGrams,
            'calculation_details' => array_merge($calculationDetails, [
                'last_recalculation' => now()->toDateTimeString(),
                'order_details' => $orderDetails
            ])
        ]);

        Log::info('Recalculated aggregated plan', [
            'plan_id' => $plan->id,
            'total_trays' => $totalTrays,
            'total_grams' => $totalGrams,
            'order_count' => count($aggregatedOrders)
        ]);

        return $plan->fresh();
    }

    /**
     * Get all aggregated requirements for date range
     * 
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return Collection
     */
    public function getAggregatedRequirements(Carbon $startDate, Carbon $endDate): Collection
    {
        // Get all active/draft plans in date range
        $plans = CropPlan::whereBetween('plant_by_date', [$startDate, $endDate])
            ->whereHas('status', function ($query) {
                $query->whereIn('code', ['draft', 'active']);
            })
            ->with(['recipe', 'order', 'status'])
            ->get();

        // Group by plant date and recipe (which implicitly groups by variety)
        $grouped = $plans->groupBy(function ($plan) {
            return $plan->plant_by_date->format('Y-m-d') . '-' . $plan->recipe_id;
        });

        // Transform into aggregated requirements
        return $grouped->map(function ($plans, $key) {
            [$date, $recipeId] = explode('-', $key);
            $firstPlan = $plans->first();
            $recipe = $firstPlan->recipe;
            
            // Get the master seed catalog from the recipe's lot consumables
            $masterSeedCatalog = null;
            if ($recipe->lot_number) {
                $seedConsumable = $recipe->lotConsumables()
                    ->whereNotNull('master_seed_catalog_id')
                    ->with('masterSeedCatalog')
                    ->first();
                if ($seedConsumable) {
                    $masterSeedCatalog = $seedConsumable->masterSeedCatalog;
                }
            }
            
            return [
                'plant_date' => Carbon::parse($date),
                'variety' => $masterSeedCatalog,
                'recipe' => $recipe,
                'total_trays' => $plans->sum('trays_needed'),
                'total_grams_seed' => $plans->sum(function ($plan) {
                    return $plan->trays_needed * $plan->recipe->seed_density_grams_per_tray;
                }),
                'total_grams_yield' => $plans->sum('grams_needed'),
                'plan_count' => $plans->count(),
                'order_count' => $plans->pluck('order_id')->unique()->count(),
                'plans' => $plans,
                'can_aggregate' => $this->canPlansBeAggregated($plans)
            ];
        })->sortBy('plant_date')->values();
    }

    /**
     * Check if a collection of plans can be aggregated
     * 
     * @param Collection $plans
     * @return bool
     */
    protected function canPlansBeAggregated(Collection $plans): bool
    {
        // All plans must be draft status
        if (!$plans->every(fn($plan) => $plan->status->code === 'draft')) {
            return false;
        }

        // All plans must have same recipe
        if ($plans->pluck('recipe_id')->unique()->count() > 1) {
            return false;
        }

        // All plans must have same plant date
        if ($plans->pluck('plant_by_date')->map(fn($date) => $date->format('Y-m-d'))->unique()->count() > 1) {
            return false;
        }

        return true;
    }

    /**
     * Aggregate multiple plans into a single plan
     * 
     * @param Collection $plans Plans to aggregate
     * @return CropPlan The aggregated plan
     */
    public function aggregatePlans(Collection $plans): CropPlan
    {
        if (!$this->canPlansBeAggregated($plans)) {
            throw new Exception('Plans cannot be aggregated - different recipes, dates, or non-draft status');
        }

        $firstPlan = $plans->first();
        $planIds = $plans->pluck('id')->toArray();
        $orderIds = $plans->pluck('order_id')->unique()->toArray();

        DB::transaction(function () use ($plans, $firstPlan, $planIds, $orderIds) {
            // Update first plan to be the aggregated plan
            $firstPlan->update([
                'trays_needed' => $plans->sum('trays_needed'),
                'grams_needed' => $plans->sum('grams_needed'),
                'calculation_details' => [
                    'aggregated_orders' => $orderIds,
                    'aggregated_plans' => $planIds,
                    'aggregation_date' => now()->toDateTimeString(),
                    'original_details' => $firstPlan->calculation_details
                ],
                'admin_notes' => "Aggregated {$plans->count()} plans from orders: " . implode(', ', $orderIds)
            ]);

            // Cancel other plans
            $cancelledStatus = CropPlanStatus::where('code', 'cancelled')->first();
            $plans->where('id', '!=', $firstPlan->id)->each(function ($plan) use ($cancelledStatus, $firstPlan) {
                $plan->update([
                    'status_id' => $cancelledStatus->id,
                    'admin_notes' => "Aggregated into plan #{$firstPlan->id}"
                ]);
            });

            Log::info('Aggregated multiple plans', [
                'aggregated_plan_id' => $firstPlan->id,
                'plan_count' => $plans->count(),
                'total_trays' => $firstPlan->trays_needed
            ]);
        });

        return $firstPlan->fresh();
    }
}