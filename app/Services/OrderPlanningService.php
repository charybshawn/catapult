<?php

namespace App\Services;

use App\Models\Order;
use App\Models\CropPlan;
use App\Models\User;
use App\Notifications\OrderCannotBeFulfilled;
use App\Services\AggregatedCropPlanService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service for managing crop plan generation and updates for orders
 */
class OrderPlanningService
{
    protected CropPlanningService $cropPlanningService;

    public function __construct(CropPlanningService $cropPlanningService)
    {
        $this->cropPlanningService = $cropPlanningService;
    }

    /**
     * Generate crop plans for an order
     * 
     * @param Order $order
     * @return array ['success' => bool, 'plans' => Collection, 'issues' => array]
     */
    public function generatePlansForOrder(Order $order): array
    {
        try {
            // Check if order requires crop production
            if (!$order->requiresCropProduction()) {
                return [
                    'success' => true,
                    'plans' => collect(),
                    'issues' => ['Order does not require crop production']
                ];
            }

            // Validate delivery feasibility first
            $feasibility = $this->validateDeliveryFeasibility($order);
            if (!$feasibility['feasible']) {
                // Send notification about infeasibility
                $this->notifyDeliveryInfeasible($order, $feasibility['issues']);
                
                return [
                    'success' => false,
                    'plans' => collect(),
                    'issues' => $feasibility['issues']
                ];
            }

            DB::beginTransaction();

            // Generate crop plans
            $cropPlans = $this->cropPlanningService->generatePlanFromOrder($order);

            // Associate plans with the order
            foreach ($cropPlans as $plan) {
                $plan->order_id = $order->id;
                $plan->save();
            }

            // Create aggregated plans
            $aggregationService = app(AggregatedCropPlanService::class);
            $aggregatedPlans = $aggregationService->processAndAggregatePlans($cropPlans);
            
            // Return the individual crop plans (they are now linked to aggregated plans)
            $finalPlans = $cropPlans;

            DB::commit();

            Log::info('Generated crop plans for order', [
                'order_id' => $order->id,
                'plans_count' => $cropPlans->count()
            ]);

            return [
                'success' => true,
                'plans' => $finalPlans,
                'issues' => []
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to generate crop plans for order', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'plans' => collect(),
                'issues' => ['Failed to generate crop plans: ' . $e->getMessage()]
            ];
        }
    }

    /**
     * Update crop plans when an order changes
     * 
     * @param Order $order
     * @return array ['success' => bool, 'message' => string]
     */
    public function updatePlansForOrder(Order $order): array
    {
        try {
            // Check if order has any active crop plans
            $activePlans = $order->cropPlans()
                ->whereHas('status', function ($query) {
                    $query->whereIn('code', ['draft', 'active']);
                })
                ->get();

            if ($activePlans->isEmpty()) {
                return [
                    'success' => true,
                    'message' => 'No active crop plans to update'
                ];
            }

            // Check if any crops have already been created
            $hasGeneratedCrops = $activePlans->filter(function ($plan) {
                return $plan->crops()->exists();
            })->isNotEmpty();

            if ($hasGeneratedCrops) {
                return [
                    'success' => false,
                    'message' => 'Cannot update plans - crops have already been generated'
                ];
            }

            DB::beginTransaction();

            // Cancel existing draft plans
            foreach ($activePlans as $plan) {
                if ($plan->isDraft()) {
                    $plan->cancel();
                    $plan->update(['notes' => 'Cancelled due to order update']);
                }
            }

            // Generate new plans
            $result = $this->generatePlansForOrder($order);

            DB::commit();

            return [
                'success' => $result['success'],
                'message' => $result['success'] 
                    ? 'Crop plans updated successfully' 
                    : 'Failed to update crop plans: ' . implode(', ', $result['issues'])
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update crop plans for order', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to update crop plans: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Validate if an order can be fulfilled by the delivery date
     * 
     * @param Order $order
     * @return array ['feasible' => bool, 'issues' => array]
     */
    public function validateDeliveryFeasibility(Order $order): array
    {
        $timing = $this->cropPlanningService->validateOrderTiming($order);
        
        return [
            'feasible' => $timing['valid'],
            'issues' => $timing['issues']
        ];
    }

    /**
     * Check if order has associated crop plans
     * 
     * @param Order $order
     * @return bool
     */
    public function orderHasPlans(Order $order): bool
    {
        return $order->cropPlans()->exists();
    }

    /**
     * Get active crop plans for an order
     * 
     * @param Order $order
     * @return Collection
     */
    public function getActivePlansForOrder(Order $order): Collection
    {
        return $order->cropPlans()
            ->with(['recipe', 'status', 'variety'])
            ->whereHas('status', function ($query) {
                $query->whereIn('code', ['draft', 'active']);
            })
            ->orderBy('plant_by_date')
            ->get();
    }

    /**
     * Check if all crop plans for an order are approved
     * 
     * @param Order $order
     * @return bool
     */
    public function allPlansApproved(Order $order): bool
    {
        $plans = $order->cropPlans;
        
        if ($plans->isEmpty()) {
            return false;
        }

        return $plans->every(function ($plan) {
            return $plan->isApproved();
        });
    }

    /**
     * Approve all draft crop plans for an order
     * 
     * @param Order $order
     * @param User|null $approvedBy
     * @return array ['success' => bool, 'message' => string]
     */
    public function approveAllPlansForOrder(Order $order, ?User $approvedBy = null): array
    {
        try {
            $draftPlans = $order->cropPlans()
                ->whereHas('status', function ($query) {
                    $query->where('code', 'draft');
                })
                ->get();

            if ($draftPlans->isEmpty()) {
                return [
                    'success' => false,
                    'message' => 'No draft plans to approve'
                ];
            }

            DB::beginTransaction();

            foreach ($draftPlans as $plan) {
                $plan->approve($approvedBy);
            }

            DB::commit();

            return [
                'success' => true,
                'message' => "Approved {$draftPlans->count()} crop plans"
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => 'Failed to approve plans: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Send notification that order cannot be fulfilled
     * 
     * @param Order $order
     * @param array $issues
     * @return void
     */
    protected function notifyDeliveryInfeasible(Order $order, array $issues): void
    {
        // Get users to notify (could be order creator, managers, etc.)
        $usersToNotify = collect();
        
        // Add order creator
        if ($order->user) {
            $usersToNotify->push($order->user);
        }

        // Add users with manager role
        $managers = User::role(['admin', 'manager'])->get();
        $usersToNotify = $usersToNotify->merge($managers)->unique('id');

        // Send notifications
        foreach ($usersToNotify as $user) {
            $user->notify(new OrderCannotBeFulfilled($order, $issues));
        }
    }

    /**
     * Get summary of crop plans for an order
     * 
     * @param Order $order
     * @return array
     */
    public function getPlansSummary(Order $order): array
    {
        $plans = $order->cropPlans()->with(['recipe', 'status', 'variety'])->get();

        return [
            'total_plans' => $plans->count(),
            'draft_plans' => $plans->where('status.code', 'draft')->count(),
            'active_plans' => $plans->where('status.code', 'active')->count(),
            'completed_plans' => $plans->where('status.code', 'completed')->count(),
            'cancelled_plans' => $plans->where('status.code', 'cancelled')->count(),
            'total_trays' => $plans->sum('trays_needed'),
            'earliest_planting' => $plans->min('plant_by_date'),
            'latest_planting' => $plans->max('plant_by_date'),
        ];
    }
}