<?php

namespace App\Actions\CropPlan;

use App\Models\CropPlan;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Pure business logic for approving crop plans (single and bulk operations)
 * NOT a Filament class - independent business logic called FROM Filament hooks
 */
class ApproveCropPlanAction
{
    /**
     * Approve a single crop plan
     * 
     * @param CropPlan $cropPlan The crop plan to approve
     * @param User $user The user approving the plan
     * @return array Approval result
     */
    public function approveSingle(CropPlan $cropPlan, User $user): array
    {
        if (!$cropPlan->canBeApproved()) {
            return $this->buildErrorResult('Crop plan cannot be approved in its current state');
        }

        $cropPlan->approve($user);

        return $this->buildSuccessResult('Crop plan approved successfully', 1);
    }

    /**
     * Approve multiple crop plans in bulk
     * 
     * @param Collection $cropPlans Collection of crop plans to approve
     * @param User $user The user approving the plans
     * @return array Bulk approval results including count of approved plans
     */
    public function approveBulk(Collection $cropPlans, User $user): array
    {
        $approved = 0;
        $errors = [];

        foreach ($cropPlans as $cropPlan) {
            if ($cropPlan->canBeApproved()) {
                try {
                    $cropPlan->approve($user);
                    $approved++;
                } catch (\Exception $e) {
                    $errors[] = "Failed to approve plan #{$cropPlan->id}: " . $e->getMessage();
                }
            } else {
                $errors[] = "Plan #{$cropPlan->id} cannot be approved in its current state";
            }
        }

        if ($approved === 0 && !empty($errors)) {
            return $this->buildErrorResult('No plans could be approved. ' . implode('; ', $errors));
        }

        $message = "Approved {$approved} crop plans";
        if (!empty($errors)) {
            $message .= '. Some plans could not be approved: ' . implode('; ', $errors);
        }

        return $this->buildSuccessResult($message, $approved, $errors);
    }

    /**
     * Build success result array
     */
    protected function buildSuccessResult(string $message, int $approvedCount, array $errors = []): array
    {
        return [
            'success' => true,
            'message' => $message,
            'approved_count' => $approvedCount,
            'errors' => $errors,
        ];
    }

    /**
     * Build error result array
     */
    protected function buildErrorResult(string $error): array
    {
        return [
            'success' => false,
            'error' => $error,
        ];
    }
}