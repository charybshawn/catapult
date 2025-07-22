<?php

namespace App\Actions\Order;

use App\Models\User;

/**
 * Business logic for bulk approval operations on crop plans
 * Extracted for better code organization following single responsibility
 */
class BulkApprovePlansAction
{
    /**
     * Approve multiple crop plans in bulk
     */
    public function execute($plans, User $user): array
    {
        $results = $this->processBulkApproval($plans, $user);
        
        if ($results['approved'] === 0) {
            return [
                'success' => false,
                'message' => 'No plans could be approved.',
                'type' => 'warning'
            ];
        }

        return [
            'success' => true,
            'message' => $this->formatBulkMessage($results),
            'approved' => $results['approved'],
            'failed' => $results['failed'],
            'type' => 'success'
        ];
    }

    /**
     * Process bulk approval and return counts
     */
    private function processBulkApproval($plans, User $user): array
    {
        $approved = 0;
        $failed = 0;

        foreach ($plans as $plan) {
            if ($plan->canBeApproved()) {
                $plan->approve($user);
                $approved++;
            } else {
                $failed++;
            }
        }

        return ['approved' => $approved, 'failed' => $failed];
    }

    /**
     * Format bulk approval message
     */
    private function formatBulkMessage(array $results): string
    {
        $message = "Approved {$results['approved']} crop plans.";
        if ($results['failed'] > 0) {
            $message .= " {$results['failed']} plans could not be approved.";
        }
        return $message;
    }
}