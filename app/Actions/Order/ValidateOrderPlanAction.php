<?php

namespace App\Actions\Order;

use App\Models\CropPlan;
use App\Models\Order;
use App\Models\User;

/**
 * Business logic for validating individual crop plan operations
 * Extracted from CropPlansRelationManager for clean architecture
 */
class ValidateOrderPlanAction
{
    /**
     * Approve a single crop plan
     */
    public function approvePlan(CropPlan $plan, User $user): array
    {
        if (!$plan->canBeApproved()) {
            return [
                'success' => false,
                'message' => 'This plan cannot be approved in its current state.',
                'type' => 'warning'
            ];
        }

        $plan->approve($user);

        return [
            'success' => true,
            'message' => 'Crop plan has been approved and is ready for planting.',
            'type' => 'success'
        ];
    }

    /**
     * Cancel a crop plan
     */
    public function cancelPlan(CropPlan $plan): array
    {
        if (!$this->canCancelPlan($plan)) {
            return [
                'success' => false,
                'message' => 'This plan cannot be cancelled. It may have associated crops or be in the wrong state.',
                'type' => 'warning'
            ];
        }

        $plan->cancel();

        return [
            'success' => true,
            'message' => 'Crop plan has been cancelled.',
            'type' => 'success'
        ];
    }

    /**
     * Approve multiple crop plans in bulk
     */
    public function approveMultiplePlans($plans, User $user): array
    {
        return app(BulkApprovePlansAction::class)->execute($plans, $user);
    }

    /**
     * Check if a crop plan can be cancelled
     */
    public function canCancelPlan(CropPlan $plan): bool
    {
        return in_array($plan->status->code, ['draft', 'active']) 
            && $plan->crops()->count() === 0;
    }

    /**
     * Check if a crop plan can be edited
     */
    public function canEditPlan(CropPlan $plan): bool
    {
        return $plan->isDraft();
    }

    /**
     * Check if order plans are read-only
     */
    public function isReadOnly(Order $order): bool
    {
        return $order->isInFinalState();
    }
}