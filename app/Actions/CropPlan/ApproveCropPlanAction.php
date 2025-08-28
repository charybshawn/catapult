<?php

namespace App\Actions\CropPlan;

use Exception;
use App\Models\CropPlan;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Manages crop plan approval workflows for agricultural production planning.
 * 
 * Handles approval of agricultural production plans including single plan approval
 * and bulk operations for efficient production management. Validates plan readiness,
 * manages approval state transitions, and provides detailed result reporting
 * for agricultural facility workflow management.
 * 
 * @business_domain Agricultural Production Planning Approval Workflow
 * @approval_management Single and bulk crop plan approval with validation
 * @production_workflow Plan approval for agricultural facility operations
 * 
 * @architecture Pure business logic class - NOT a Filament component
 * @usage Called FROM Filament resource hooks and action handlers
 * 
 * @author Catapult System
 * @since 1.0.0
 */
class ApproveCropPlanAction
{
    /**
     * Approve a single crop plan with business rule validation.
     * 
     * Validates crop plan readiness for approval based on agricultural production
     * requirements and business rules, then processes approval with user attribution.
     * Ensures plan meets all criteria before advancing to approved production status
     * for facility workflow execution.
     * 
     * @business_process Single Crop Plan Approval Workflow
     * @agricultural_context Production plan validation for microgreens cultivation
     * @approval_tracking User attribution for production planning accountability
     * 
     * @param CropPlan $cropPlan The agricultural production plan to approve
     * @param User $user The system user approving the production plan
     * @return array Structured approval result with success/error details
     * 
     * @validation_rules Calls cropPlan.canBeApproved() for business rule validation
     * @state_transition Updates plan status from pending to approved with user tracking
     * @result_format Returns standardized success/error array structure
     * 
     * @approval_criteria Plan must have valid dates, quantities, and resource allocation
     * @user_tracking Records approval user for audit trail and accountability
     * 
     * @usage Called from Filament resource approval actions and bulk operations
     * @database_impact Updates crop_plans table with approved status and user reference
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
     * Approve multiple crop plans in bulk operation with error handling.
     * 
     * Processes batch approval of agricultural production plans with individual
     * validation and error tracking. Attempts approval for each eligible plan
     * while collecting detailed error information for failed approvals.
     * Provides comprehensive reporting for production management oversight.
     * 
     * @business_process Bulk Crop Plan Approval Workflow
     * @agricultural_context Mass production plan approval for facility efficiency
     * @error_resilience Individual plan validation with comprehensive error tracking
     * 
     * @param Collection $cropPlans Collection of CropPlan instances for bulk approval
     * @param User $user The system user processing the bulk approval operation
     * @return array Comprehensive bulk operation results with success counts and errors
     * 
     * @validation_processing Validates each plan individually with canBeApproved() checks
     * @error_collection Tracks specific failures with plan IDs and error messages
     * @partial_success Allows partial completion with detailed success/failure reporting
     * 
     * @result_structure Returns approved count, success status, and detailed error list
     * @business_continuity Failed approvals don't block successful plan approvals
     * 
     * @usage Called from Filament bulk action handlers for production planning efficiency
     * @performance_impact Processes plans sequentially with individual error handling
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
                } catch (Exception $e) {
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
     * Build standardized success result array for approval operations.
     * 
     * Creates consistent success response structure for both single and bulk
     * approval operations. Includes success status, user message, approved count,
     * and any partial failure details for comprehensive operation reporting.
     * 
     * @param string $message User-friendly success message for UI display
     * @param int $approvedCount Number of crop plans successfully approved
     * @param array $errors Optional array of error messages for partial failures
     * @return array Standardized success result structure
     * 
     * @result_format Consistent structure: success, message, approved_count, errors
     * @ui_integration Provides user-friendly messages for Filament notification display
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
     * Build standardized error result array for failed approval operations.
     * 
     * Creates consistent error response structure for approval operation failures.
     * Provides standardized format for UI error handling and user notification
     * in agricultural production planning workflows.
     * 
     * @param string $error User-friendly error message describing failure reason
     * @return array Standardized error result structure
     * 
     * @result_format Consistent structure: success=false, error message
     * @ui_integration Provides error messages for Filament notification display
     */
    protected function buildErrorResult(string $error): array
    {
        return [
            'success' => false,
            'error' => $error,
        ];
    }
}