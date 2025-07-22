<?php

namespace App\Actions\Order;

use App\Models\Order;
use App\Models\User;
use App\Services\OrderPlanningService;

/**
 * Business logic for approving all draft crop plans for an order
 * Extracted from CropPlansRelationManager for clean architecture
 */
class ApproveAllPlansAction
{
    public function __construct(
        private OrderPlanningService $orderPlanningService
    ) {}

    public function execute(Order $order, User $user): array
    {
        // Business validation - check if there are draft plans to approve
        if (!$this->hasDraftPlans($order)) {
            return [
                'success' => false,
                'message' => 'No draft plans found to approve.',
                'type' => 'warning'
            ];
        }

        // Business validation - ensure order is not in final state
        if ($order->isInFinalState()) {
            return [
                'success' => false,
                'message' => 'Cannot approve plans for orders in final state.',
                'type' => 'warning'
            ];
        }

        // Execute the approval process
        $result = $this->orderPlanningService->approveAllPlansForOrder($order, $user);

        if ($result['success']) {
            return [
                'success' => true,
                'message' => $result['message'],
                'type' => 'success'
            ];
        }

        return [
            'success' => false,
            'message' => $result['message'],
            'type' => 'warning'
        ];
    }

    /**
     * Check if order has draft plans available for approval
     */
    public function hasDraftPlans(Order $order): bool
    {
        return $order->cropPlans()
            ->whereHas('status', fn($q) => $q->where('code', 'draft'))
            ->exists();
    }

    /**
     * Check if all plans can be approved
     */
    public function canApproveAll(Order $order): bool
    {
        return $this->hasDraftPlans($order) && !$order->isInFinalState();
    }
}