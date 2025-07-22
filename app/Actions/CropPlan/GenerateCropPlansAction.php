<?php

namespace App\Actions\CropPlan;

use App\Services\CropPlanningService;
use App\Models\Order;
use Illuminate\Support\Collection;

/**
 * Pure business logic for generating crop plans
 * NOT a Filament class - independent business logic called FROM Filament hooks
 */
class GenerateCropPlansAction
{
    protected CropPlanningService $cropPlanningService;

    public function __construct(CropPlanningService $cropPlanningService)
    {
        $this->cropPlanningService = $cropPlanningService;
    }

    /**
     * Execute crop plan generation for orders in the next 30 days
     * 
     * @return array Generation results including success status, counts, and data
     */
    public function execute(): array
    {
        try {
            $startDate = now()->toDateString();
            $endDate = now()->addDays(30)->toDateString();
            
            // Get orders available for crop plan generation
            $orders = $this->getOrdersForGeneration($startDate, $endDate);
            
            // Generate crop plans for orders in the date range
            $cropPlans = $this->cropPlanningService->generateIndividualPlansForAllOrders($startDate, $endDate);
            
            return $this->buildSuccessResult($startDate, $endDate, $orders, $cropPlans);
            
        } catch (\Exception $e) {
            return $this->buildErrorResult($e);
        }
    }

    /**
     * Get orders eligible for crop plan generation
     */
    protected function getOrdersForGeneration(string $startDate, string $endDate): Collection
    {
        return Order::with(['customer', 'status'])
            ->where('harvest_date', '>=', $startDate)
            ->where('harvest_date', '<=', $endDate)
            ->where('is_recurring', false)
            ->whereHas('status', function ($query) {
                $query->whereIn('code', ['draft', 'pending', 'confirmed', 'in_production']);
            })
            ->get();
    }

    /**
     * Build success result array for session storage and modal display
     */
    protected function buildSuccessResult(string $startDate, string $endDate, Collection $orders, Collection $cropPlans): array
    {
        return [
            'success' => true,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'order_count' => $orders->count(),
            'plan_count' => $cropPlans->count(),
            'orders' => $orders,
            'plans' => $cropPlans,
            'plans_by_order' => $cropPlans->groupBy('order_id'),
            'variety_breakdown' => $cropPlans->groupBy('variety.common_name')->map->count(),
        ];
    }

    /**
     * Build error result array
     */
    protected function buildErrorResult(\Exception $e): array
    {
        return [
            'success' => false,
            'error' => $e->getMessage(),
        ];
    }
}