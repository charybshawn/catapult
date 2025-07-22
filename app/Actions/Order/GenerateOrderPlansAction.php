<?php

namespace App\Actions\Order;

use App\Models\Order;
use App\Services\OrderPlanningService;
use Illuminate\Database\Eloquent\Collection;

/**
 * Business logic for generating crop plans for an order
 * Extracted from CropPlansRelationManager for clean architecture
 */
class GenerateOrderPlansAction
{
    public function __construct(
        private OrderPlanningService $orderPlanningService
    ) {}

    public function execute(Order $order): array
    {
        // Business validation - check if plans already exist
        if ($order->cropPlans()->exists()) {
            return [
                'success' => false,
                'message' => 'This order already has crop plans. Use the update action to regenerate them.',
                'type' => 'warning'
            ];
        }

        // Business validation - ensure order requires crop production
        if (!$order->requiresCropProduction()) {
            return [
                'success' => false,
                'message' => 'This order does not require crop production.',
                'type' => 'warning'
            ];
        }

        // Business validation - ensure order is not in final state
        if ($order->isInFinalState()) {
            return [
                'success' => false,
                'message' => 'Cannot generate plans for orders in final state.',
                'type' => 'warning'
            ];
        }

        // Execute the plan generation
        $result = $this->orderPlanningService->generatePlansForOrder($order);

        if ($result['success']) {
            return [
                'success' => true,
                'message' => "Successfully generated {$result['plans']->count()} crop plans.",
                'plans' => $result['plans'],
                'type' => 'success'
            ];
        }

        return [
            'success' => false,
            'message' => implode(' ', $result['issues'] ?? ['Generation failed']),
            'type' => 'danger'
        ];
    }

    /**
     * Check if order is eligible for plan generation
     */
    public function canGenerate(Order $order): bool
    {
        return $order->requiresCropProduction() 
            && !$order->isInFinalState()
            && !$order->cropPlans()->exists();
    }
}