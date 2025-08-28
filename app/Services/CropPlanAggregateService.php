<?php

namespace App\Services;

use App\Models\CropPlan;
use App\Models\CropPlanAggregate;
use App\Models\Recipe;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Agricultural crop plan aggregation service for efficient batch production.
 * 
 * Combines individual crop plans into optimized batch aggregations for efficient
 * agricultural production. Consolidates multiple orders requiring the same variety
 * and harvest timing into single production batches, reducing complexity and
 * improving resource utilization in microgreens production operations.
 *
 * @business_domain Agricultural batch production planning and optimization
 * @related_models CropPlan, CropPlanAggregate, Recipe
 * @used_by Crop planning workflows, production scheduling, batch management
 * @optimization Reduces production complexity by batching similar requirements
 * @agricultural_context Optimizes growing space utilization and seed usage efficiency
 */
class CropPlanAggregateService
{
    /**
     * Process individual crop plans into optimized agricultural batch aggregations.
     * 
     * Groups crop plans by variety, plant date, and harvest date to create
     * efficient production batches. Reduces agricultural complexity by consolidating
     * multiple orders into single growing operations while maintaining traceability
     * to original customer orders.
     *
     * @param Collection $cropPlans Collection of individual crop plans to aggregate
     * @return Collection Aggregated crop plans for batch production
     * @agricultural_context Optimizes growing space and reduces seed waste through batching
     * @transaction Atomic operation ensures data consistency during aggregation
     */
    public function processAndAggregatePlans(Collection $cropPlans): Collection
    {
        $aggregatedPlans = collect();

        DB::transaction(function () use ($cropPlans, &$aggregatedPlans) {
            // Group plans by variety, plant date, and harvest date
            $grouped = $cropPlans->groupBy(function ($plan) {
                return sprintf(
                    '%s_%s_%s',
                    $plan->variety_id,
                    $plan->plant_by_date->format('Y-m-d'),
                    $plan->expected_harvest_date->format('Y-m-d')
                );
            });

            foreach ($grouped as $key => $plans) {
                list($varietyId, $plantDate, $harvestDate) = explode('_', $key);
                
                // Check if an aggregated plan already exists
                $aggregatedPlan = CropPlanAggregate::where('variety_id', $varietyId)
                    ->whereDate('plant_date', $plantDate)
                    ->whereDate('harvest_date', $harvestDate)
                    ->where('status', 'draft')
                    ->first();

                if (!$aggregatedPlan) {
                    // Create new aggregated plan
                    $firstPlan = $plans->first();
                    $aggregatedPlan = CropPlanAggregate::create([
                        'variety_id' => $varietyId,
                        'harvest_date' => $harvestDate,
                        'plant_date' => $plantDate,
                        'seed_soak_date' => $firstPlan->seed_soak_date,
                        'total_grams_needed' => 0,
                        'total_trays_needed' => 0,
                        'grams_per_tray' => $firstPlan->grams_per_tray,
                        'status' => 'draft',
                        'calculation_details' => [
                            'created_at' => now()->toIso8601String(),
                            'initial_plans' => []
                        ],
                        'created_by' => auth()->id() ?: $firstPlan->created_by,
                    ]);
                }

                // Update aggregated totals
                $totalGrams = $aggregatedPlan->total_grams_needed;
                $totalTrays = $aggregatedPlan->total_trays_needed;
                $details = $aggregatedPlan->calculation_details ?? [];

                foreach ($plans as $plan) {
                    $totalGrams += $plan->grams_needed;
                    $totalTrays += $plan->trays_needed;
                    
                    // Track which plans are included
                    $details['included_plans'][] = [
                        'crop_plan_id' => $plan->id,
                        'order_id' => $plan->order_id,
                        'grams' => $plan->grams_needed,
                        'trays' => $plan->trays_needed,
                        'added_at' => now()->toIso8601String()
                    ];

                    // Link individual plan to aggregated plan
                    $plan->update([
                        'aggregated_crop_plan_id' => $aggregatedPlan->id
                    ]);
                }

                // Update aggregated plan
                $aggregatedPlan->update([
                    'total_grams_needed' => $totalGrams,
                    'total_trays_needed' => $totalTrays,
                    'calculation_details' => $details
                ]);

                $aggregatedPlans->push($aggregatedPlan);

                Log::info('Created/updated aggregated crop plan', [
                    'aggregated_plan_id' => $aggregatedPlan->id,
                    'variety_id' => $varietyId,
                    'total_trays' => $totalTrays,
                    'total_grams' => $totalGrams,
                    'plans_count' => $plans->count()
                ]);
            }
        });

        return $aggregatedPlans;
    }

    /**
     * Recalculate agricultural batch aggregation based on current crop plan status.
     * 
     * Updates aggregated totals when individual crop plans are modified or cancelled.
     * Ensures batch production quantities accurately reflect current customer
     * requirements and maintains data integrity throughout the agricultural workflow.
     *
     * @param CropPlanAggregate $aggregatedPlan Batch aggregation to recalculate
     * @return CropPlanAggregate Updated aggregated plan with current totals
     * @agricultural_context Maintains accurate batch sizes for production planning
     */
    public function recalculateAggregation(CropPlanAggregate $aggregatedPlan): CropPlanAggregate
    {
        $cropPlans = $aggregatedPlan->cropPlans()
            ->whereHas('status', function ($q) {
                $q->where('code', '!=', 'cancelled');
            })
            ->get();

        $totalGrams = $cropPlans->sum('grams_needed');
        $totalTrays = $cropPlans->sum('trays_needed');

        $details = $aggregatedPlan->calculation_details ?? [];
        $details['last_recalculated'] = now()->toIso8601String();
        $details['plans_count'] = $cropPlans->count();

        $aggregatedPlan->update([
            'total_grams_needed' => $totalGrams,
            'total_trays_needed' => $totalTrays,
            'calculation_details' => $details
        ]);

        return $aggregatedPlan;
    }

    /**
     * Retrieve aggregated agricultural production requirements for planning period.
     * 
     * Provides comprehensive view of batch production requirements across specified
     * time period including variety needs, batch sizes, and timing requirements.
     * Essential for resource allocation and production capacity planning.
     *
     * @param Carbon $startDate Start of planning period
     * @param Carbon $endDate End of planning period
     * @return Collection Formatted aggregation data for production planning
     * @agricultural_context Supports weekly and monthly production planning cycles
     */
    public function getAggregatedRequirements(Carbon $startDate, Carbon $endDate): Collection
    {
        return CropPlanAggregate::with(['variety', 'cropPlans'])
            ->whereBetween('plant_date', [$startDate, $endDate])
            ->whereIn('status', ['draft', 'confirmed'])
            ->orderBy('plant_date')
            ->orderBy('variety_id')
            ->get()
            ->map(function ($plan) {
                return [
                    'id' => $plan->id,
                    'variety' => $plan->variety->full_name,
                    'plant_date' => $plan->plant_date->format('M j, Y'),
                    'seed_soak_date' => $plan->seed_soak_date?->format('M j, Y'),
                    'harvest_date' => $plan->harvest_date->format('M j, Y'),
                    'total_grams' => $plan->total_grams_needed,
                    'total_trays' => $plan->total_trays_needed,
                    'status' => $plan->status,
                    'plans_count' => $plan->cropPlans->count()
                ];
            });
    }

    /**
     * Remove individual crop plan from batch aggregation with cleanup.
     * 
     * Safely removes crop plan from batch aggregation when orders are cancelled
     * or modified. Recalculates batch totals and manages aggregate lifecycle
     * including cancellation of empty batches.
     *
     * @param CropPlan $cropPlan Individual crop plan to remove from batch
     * @return void
     * @agricultural_context Maintains batch integrity when individual orders change
     * @cleanup Automatically cancels empty batches after removal
     */
    public function removeFromAggregation(CropPlan $cropPlan): void
    {
        if (!$cropPlan->aggregated_crop_plan_id) {
            return;
        }

        $aggregatedPlan = $cropPlan->aggregatedCropPlan;
        
        // Remove link
        $cropPlan->update(['aggregated_crop_plan_id' => null]);
        
        // Recalculate
        $this->recalculateAggregation($aggregatedPlan);
        
        // If no more plans, mark aggregated plan as cancelled
        if ($aggregatedPlan->cropPlans()->count() === 0) {
            $aggregatedPlan->update(['status' => 'cancelled']);
        }
    }
}