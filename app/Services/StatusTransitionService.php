<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderStatus;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Agricultural order status transition management service for production workflows.
 * 
 * Manages complex order status transitions throughout the agricultural production
 * lifecycle, from order confirmation through crop growing, harvest, packing, and
 * delivery. Enforces business rules specific to microgreens production operations
 * and maintains status integrity across the supply chain.
 * 
 * @business_domain Agricultural order fulfillment and production status management
 * @agricultural_workflow Tracks orders from confirmation through delivery
 * @production_integration Links order status with crop growing stages and harvest timing
 * @business_rules Enforces agricultural constraints and payment requirements
 * 
 * @example
 * // Validate and perform status transition
 * $validator = new StatusTransitionService();
 * $result = $validator->transitionTo($order, 'growing', ['trigger' => 'crops_planted']);
 * if ($result['success']) {
 *     // Status updated successfully
 * }
 * 
 * // Handle agricultural business events
 * $validator->handleBusinessEvent($order, 'crop.planted', ['crop_id' => $cropId]);
 * 
 * @features
 * - Agricultural business rule enforcement
 * - Crop production status synchronization
 * - Payment and delivery coordination
 * - Bulk status updates for batch operations
 * - Comprehensive audit logging
 * 
 * @see Order For order model and relationships
 * @see OrderStatus For status definitions and transitions
 * @see CropStageValidationService For crop stage coordination
 */
class StatusTransitionService
{
    /**
     * Validate agricultural order status transition against business rules.
     * 
     * Performs comprehensive validation including basic transition rules,
     * agricultural production constraints, crop readiness requirements,
     * and payment obligations. Essential for maintaining order integrity
     * throughout the microgreens production and fulfillment pipeline.
     * 
     * @business_validation Enforces agricultural production and payment rules
     * @agricultural_constraints Validates crop readiness and production stages
     * @order_integrity Prevents invalid status progressions
     * @production_workflow Ensures proper agricultural workflow adherence
     * 
     * @param Order $order Order instance with current status and relationships
     * @param string $targetStatusCode Desired order status code
     * @return array Validation result with success flag and reason
     * 
     * @validation_response
     * ['valid' => bool, 'reason' => string|null]
     * - valid: Whether transition is permitted
     * - reason: Explanation if transition is blocked
     * 
     * @example
     * $validation = $this->validateTransition($order, 'packing');
     * if (!$validation['valid']) {
     *     throw new ValidationException($validation['reason']);
     * }
     */
    public function validateTransition(Order $order, string $targetStatusCode): array
    {
        $currentStatus = $order->unifiedStatus;
        $targetStatus = OrderStatus::findByCode($targetStatusCode);
        
        if (!$currentStatus) {
            return ['valid' => false, 'reason' => 'Order has no current status'];
        }
        
        if (!$targetStatus) {
            return ['valid' => false, 'reason' => 'Invalid target status code'];
        }
        
        // Check if transition is generally valid
        if (!OrderStatus::isValidTransition($currentStatus->code, $targetStatusCode)) {
            return ['valid' => false, 'reason' => 'Invalid status transition from ' . $currentStatus->name . ' to ' . $targetStatus->name];
        }
        
        // Apply business-specific validation rules
        $businessValidation = $this->validateBusinessRules($order, $currentStatus, $targetStatus);
        if (!$businessValidation['valid']) {
            return $businessValidation;
        }
        
        return ['valid' => true, 'reason' => null];
    }
    
    /**
     * Validate agricultural business rules for order status transitions.
     * 
     * Applies domain-specific validation rules including crop production
     * requirements, payment obligations, template order constraints,
     * and agricultural workflow integrity. Ensures status changes align
     * with microgreens production realities and business policies.
     * 
     * @agricultural_rules Enforces crop production and harvest requirements
     * @business_policies Validates payment and fulfillment constraints
     * @production_integrity Ensures proper agricultural workflow progression
     * @template_handling Special rules for template and recurring orders
     * 
     * @param Order $order Order instance being validated
     * @param OrderStatus $currentStatus Current order status instance
     * @param OrderStatus $targetStatus Desired target status instance
     * @return array Business rule validation result
     * 
     * @business_rules
     * - Template orders can only be cancelled
     * - Crop orders must go through production stages
     * - Packing requires all crops to be harvested
     * - Delivery readiness may require payment completion
     * - Final statuses cannot be changed
     */
    protected function validateBusinessRules(Order $order, OrderStatus $currentStatus, OrderStatus $targetStatus): array
    {
        // Template orders have restricted transitions for agricultural workflow integrity
        if ($currentStatus->code === OrderStatus::STATUS_TEMPLATE) {
            if (!in_array($targetStatus->code, [OrderStatus::STATUS_CANCELLED])) {
                return ['valid' => false, 'reason' => 'Template orders can only be cancelled, not converted to active production'];
            }
        }
        
        // Agricultural orders with crops must follow production workflow
        if ($order->requiresCropProduction()) {
            // Cannot skip agricultural production stages for crop orders
            if ($currentStatus->isPreProductionStage() && $targetStatus->isFulfillmentStage()) {
                return ['valid' => false, 'reason' => 'Orders requiring crop production must go through agricultural growing stages'];
            }
            
            // Packing stage requires all crops to be harvested and ready
            if ($targetStatus->code === OrderStatus::STATUS_PACKING) {
                $allCropsHarvested = $order->crops->every(fn($crop) => $crop->current_stage === 'harvested');
                if (!$allCropsHarvested) {
                    return ['valid' => false, 'reason' => 'All crops must be harvested before order can be packed for delivery'];
                }
            }
        }
        
        // Delivery readiness requires payment completion for immediate invoicing orders
        if ($targetStatus->code === OrderStatus::STATUS_READY_FOR_DELIVERY) {
            if ($order->requiresImmediateInvoicing() && !$order->isPaid()) {
                return ['valid' => false, 'reason' => 'Agricultural products cannot be marked ready for delivery until payment is received'];
            }
        }
        
        // Final statuses represent completed agricultural transactions
        if ($currentStatus->is_final) {
            return ['valid' => false, 'reason' => 'Cannot modify status of completed agricultural orders'];
        }
        
        return ['valid' => true, 'reason' => null];
    }
    
    /**
     * Execute agricultural order status transition with validation and audit trail.
     * 
     * Performs validated status transition with database transaction safety,
     * comprehensive logging, and event broadcasting. Maintains data integrity
     * throughout agricultural production workflow transitions and provides
     * detailed response for error handling and success confirmation.
     * 
     * @transaction_safety Wrapped in database transaction for rollback protection
     * @audit_logging Complete transition history for agricultural traceability
     * @event_broadcasting Notifies other systems of status changes
     * @validation_enforcement Only proceeds with valid transitions
     * 
     * @param Order $order Order instance to transition
     * @param string $targetStatusCode Desired agricultural workflow status
     * @param array $context Additional context for audit trail and logging
     * @return array Comprehensive transition result with success details
     * 
     * @transition_response
     * ['success' => bool, 'message' => string, 'order' => Order|null]
     * - success: Whether transition completed successfully
     * - message: User-friendly status or error message
     * - order: Refreshed order instance or null on failure
     * 
     * @example
     * $result = $this->transitionTo($order, 'growing', [
     *     'trigger' => 'crop_batch_planted',
     *     'batch_id' => $batchId
     * ]);
     * if ($result['success']) {
     *     // Handle successful transition
     *     $updatedOrder = $result['order'];
     * }
     */
    public function transitionTo(Order $order, string $targetStatusCode, array $context = []): array
    {
        // Validate the transition
        $validation = $this->validateTransition($order, $targetStatusCode);
        if (!$validation['valid']) {
            return [
                'success' => false,
                'message' => $validation['reason'],
                'order' => null
            ];
        }
        
        $targetStatus = OrderStatus::findByCode($targetStatusCode);
        $previousStatus = $order->unifiedStatus;
        
        DB::beginTransaction();
        try {
            // Update the order status
            $order->unified_status_id = $targetStatus->id;
            $order->save();
            
            // Log the transition
            $this->logTransition($order, $previousStatus, $targetStatus, $context);
            
            // Fire status change event
            event('order.status.changed', [
                'order' => $order,
                'previous_status' => $previousStatus,
                'new_status' => $targetStatus,
                'context' => $context
            ]);
            
            DB::commit();
            
            return [
                'success' => true,
                'message' => 'Status updated successfully',
                'order' => $order->fresh()
            ];
            
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Status transition failed', [
                'order_id' => $order->id,
                'target_status' => $targetStatusCode,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to update status: ' . $e->getMessage(),
                'order' => null
            ];
        }
    }
    
    /**
     * Retrieve valid next agricultural status options for order progression.
     * 
     * Returns collection of order statuses that the order can legally transition
     * to based on current state, agricultural production requirements, and business
     * rules. Essential for UI status selection and automated workflow progression.
     * 
     * @workflow_options Provides available status transitions for operators
     * @business_filtered Only returns statuses permitted by agricultural rules
     * @ui_support Enables dynamic status selection in interfaces
     * @automation_ready Supports programmatic status advancement
     * 
     * @param Order $order Order instance to evaluate for valid transitions
     * @return Collection<OrderStatus> Filtered collection of permitted next statuses
     * 
     * @example
     * $validStatuses = $this->getValidNextStatuses($order);
     * foreach ($validStatuses as $status) {
     *     echo "Can transition to: {$status->name}";
     * }
     */
    public function getValidNextStatuses(Order $order): Collection
    {
        if (!$order->unifiedStatus) {
            return collect();
        }
        
        $validStatuses = OrderStatus::getValidNextStatuses($order->unifiedStatus->code);
        
        // Filter based on business rules
        return $validStatuses->filter(function ($status) use ($order) {
            $validation = $this->validateTransition($order, $status->code);
            return $validation['valid'];
        });
    }
    
    /**
     * Execute bulk agricultural order status transitions with individual validation.
     * 
     * Processes multiple orders through status transitions simultaneously while
     * maintaining individual validation and error handling. Essential for batch
     * operations in agricultural production workflows where multiple orders
     * reach production milestones together.
     * 
     * @batch_processing Handles multiple orders efficiently with individual validation
     * @agricultural_operations Supports batch harvest, packing, and delivery workflows  
     * @error_isolation Failed transitions don't affect successful ones
     * @audit_compliance Each transition maintains complete audit trail
     * 
     * @param array $orderIds Array of order database identifiers
     * @param string $targetStatusCode Target status for all orders
     * @param array $context Shared context information for audit logging
     * @return array Detailed results with successful and failed transitions
     * 
     * @bulk_response
     * [
     *   'successful' => [['order_id' => int, 'message' => string], ...],
     *   'failed' => [['order_id' => int, 'reason' => string], ...]
     * ]
     * 
     * @example
     * // Bulk transition harvested orders to packing
     * $results = $this->bulkTransition($orderIds, 'packing', [
     *     'harvest_batch' => $harvestBatchId,
     *     'operator' => 'harvest_crew_1'
     * ]);
     */
    public function bulkTransition(array $orderIds, string $targetStatusCode, array $context = []): array
    {
        $successful = [];
        $failed = [];
        
        $orders = Order::whereIn('id', $orderIds)->get();
        
        foreach ($orders as $order) {
            $result = $this->transitionTo($order, $targetStatusCode, array_merge($context, [
                'bulk_operation' => true,
                'total_orders' => count($orderIds)
            ]));
            
            if ($result['success']) {
                $successful[] = [
                    'order_id' => $order->id,
                    'message' => $result['message']
                ];
            } else {
                $failed[] = [
                    'order_id' => $order->id,
                    'reason' => $result['message']
                ];
            }
        }
        
        return [
            'successful' => $successful,
            'failed' => $failed
        ];
    }
    
    /**
     * Handle automated agricultural status transitions from business events.
     * 
     * Processes agricultural business events (crop planted, harvest completed,
     * payment received) to automatically advance order status through the
     * production workflow. Eliminates manual status updates for predictable
     * agricultural milestones and maintains workflow consistency.
     * 
     * @event_driven Responds to agricultural production and business milestones
     * @workflow_automation Reduces manual status management overhead
     * @agricultural_integration Links crop stages with order progression
     * @business_events Handles payment, delivery, and production triggers
     * 
     * @param Order $order Order instance affected by business event
     * @param string $event Business event identifier (crop.planted, harvest.completed, etc.)
     * @param array $eventData Event-specific data for context and validation
     * @return void Status transition attempted if event triggers status change
     * 
     * @supported_events
     * - order.confirmed: Order approved and ready for production
     * - crop.planted: Crop production initiated
     * - crops.ready: All crops mature and ready for harvest
     * - harvest.completed: Crops harvested and ready for processing
     * - packing.completed: Order packed and ready for delivery
     * - payment.received: Payment processed and order financially cleared
     * - delivery.started: Order dispatched for delivery
     * - delivery.completed: Order successfully delivered to customer
     * 
     * @example
     * // Automatically advance status when crops are planted
     * $this->handleBusinessEvent($order, 'crop.planted', [
     *     'crop_id' => $crop->id,
     *     'batch_id' => $batch->id
     * ]);
     */
    public function handleBusinessEvent(Order $order, string $event, array $eventData = []): void
    {
        $targetStatus = $this->determineStatusFromEvent($order, $event, $eventData);
        
        if ($targetStatus) {
            $this->transitionTo($order, $targetStatus, [
                'trigger_event' => $event,
                'event_data' => $eventData,
                'automatic' => true
            ]);
        }
    }
    
    /**
     * Map agricultural business events to appropriate order status transitions.
     * 
     * Analyzes business events in the context of current order state to determine
     * the appropriate target status. Implements business logic for agricultural
     * workflow progression and handles conditional transitions based on order
     * characteristics and production requirements.
     * 
     * @business_logic Maps events to status changes based on agricultural workflow
     * @conditional_transitions Considers order context and requirements
     * @agricultural_milestones Links production events to order progression
     * @internal Core logic for automated status management
     * 
     * @param Order $order Order instance to evaluate for status change
     * @param string $event Business event triggering potential status change
     * @param array $eventData Event-specific information for decision making
     * @return string|null Target status code or null if no transition appropriate
     */
    protected function determineStatusFromEvent(Order $order, string $event, array $eventData): ?string
    {
        switch ($event) {
            case 'order.confirmed':
                return OrderStatus::STATUS_CONFIRMED;
                
            case 'crop.planted':
                // Transition to growing only when all agricultural requirements met
                if ($this->areAllCropsPlanted($order)) {
                    return OrderStatus::STATUS_GROWING;
                }
                break;
                
            case 'crops.ready':
                // Agricultural milestone: crops mature and ready for harvest
                return OrderStatus::STATUS_READY_TO_HARVEST;
                
            case 'harvest.completed':
                // Agricultural completion: crops harvested and ready for processing
                return OrderStatus::STATUS_PACKING;
                
            case 'packing.completed':
                // Check payment requirements before delivery readiness
                if ($order->requiresImmediateInvoicing() && !$order->isPaid()) {
                    // Agricultural production complete but awaiting payment
                    return null;
                }
                return OrderStatus::STATUS_READY_FOR_DELIVERY;
                
            case 'payment.received':
                // Payment clears final barrier for packed agricultural orders
                if ($order->unifiedStatus->code === OrderStatus::STATUS_PACKING) {
                    return OrderStatus::STATUS_READY_FOR_DELIVERY;
                }
                break;
                
            case 'delivery.started':
                // Agricultural products dispatched to customer
                return OrderStatus::STATUS_OUT_FOR_DELIVERY;
                
            case 'delivery.completed':
                // Agricultural order fulfillment complete
                return OrderStatus::STATUS_DELIVERED;
        }
        
        return null;
    }
    
    /**
     * Validate completion of agricultural crop planting requirements for order.
     * 
     * Determines whether all crop plans associated with an order have been
     * fulfilled through actual crop planting. Critical for transitioning orders
     * from planning to active production status in agricultural workflows.
     * 
     * @agricultural_validation Ensures crop production requirements are met
     * @production_tracking Links crop plans to actual planted crops
     * @order_progression Gates status advancement on agricultural completion
     * @internal Supporting logic for crop production status validation
     * 
     * @param Order $order Order instance to evaluate for planting completion
     * @return bool Whether all required crops have been planted for the order
     */
    protected function areAllCropsPlanted(Order $order): bool
    {
        if (!$order->requiresCropProduction()) {
            return false; // Non-agricultural orders don't require crop validation
        }
        
        // Count planned vs actual agricultural production
        $requiredCrops = $order->cropPlans()->count();
        $plantedCrops = $order->crops()->whereNotNull('planting_at')->count();
        
        // All crop plans must be fulfilled with actual plantings
        return $requiredCrops > 0 && $plantedCrops >= $requiredCrops;
    }
    
    /**
     * Record comprehensive audit trail for agricultural order status transition.
     * 
     * Creates detailed log entries for status changes including previous and
     * new status information, context data, user attribution, and timestamps.
     * Essential for agricultural traceability, compliance, and operational
     * analysis of production workflows.
     * 
     * @audit_compliance Complete transition history for regulatory requirements
     * @traceability Links status changes to users, events, and context
     * @agricultural_records Maintains production workflow documentation
     * @activity_logging Integrates with Laravel activity log for persistence
     * 
     * @param Order $order Order instance undergoing status transition
     * @param OrderStatus|null $previousStatus Previous status or null for initial
     * @param OrderStatus $newStatus New status after transition
     * @param array $context Additional context information for audit trail
     * @return void Log entries created in system logs and activity log
     */
    protected function logTransition(Order $order, ?OrderStatus $previousStatus, OrderStatus $newStatus, array $context): void
    {
        $logData = [
            'order_id' => $order->id,
            'previous_status' => $previousStatus ? [
                'id' => $previousStatus->id,
                'code' => $previousStatus->code,
                'name' => $previousStatus->name,
                'stage' => $previousStatus->stage
            ] : null,
            'new_status' => [
                'id' => $newStatus->id,
                'code' => $newStatus->code,
                'name' => $newStatus->name,
                'stage' => $newStatus->stage
            ],
            'context' => $context,
            'user_id' => auth()->id(),
            'timestamp' => now()->toIso8601String()
        ];
        
        Log::info('Order status transition', $logData);
        
        // Also log in the order's activity log if available
        if (method_exists($order, 'activity')) {
            activity()
                ->performedOn($order)
                ->withProperties($logData)
                ->log('Status changed from ' . ($previousStatus?->name ?? 'none') . ' to ' . $newStatus->name);
        }
    }
    
    /**
     * Retrieve comprehensive agricultural order status change history.
     * 
     * Extracts complete timeline of status transitions from activity logs
     * including previous and new statuses, context information, user attribution,
     * and timestamps. Essential for agricultural traceability, order analysis,
     * and production workflow review.
     * 
     * @status_timeline Complete chronological history of order progression
     * @agricultural_traceability Tracks order through entire production lifecycle
     * @audit_retrieval Provides historical data for compliance and analysis
     * @activity_integration Leverages Laravel activity log for persistence
     * 
     * @param Order $order Order instance to retrieve status history for
     * @return Collection<array> Chronological collection of status transition records
     * 
     * @history_structure
     * [
     *   'id' => activity_id,
     *   'previous_status' => ['id' => int, 'code' => string, 'name' => string],
     *   'new_status' => ['id' => int, 'code' => string, 'name' => string],
     *   'context' => array,
     *   'user_id' => int|null,
     *   'created_at' => Carbon,
     *   'description' => string
     * ]
     * 
     * @example
     * $history = $this->getStatusHistory($order);
     * foreach ($history as $transition) {
     *     echo "Status changed from {$transition['previous_status']['name']} "
     *        . "to {$transition['new_status']['name']} "
     *        . "on {$transition['created_at']->format('Y-m-d H:i')}";
     * }
     */
    public function getStatusHistory(Order $order): Collection
    {
        if (!method_exists($order, 'activities')) {
            return collect();
        }
        
        return $order->activities()
            ->where('description', 'like', 'Status changed%')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($activity) {
                $properties = $activity->properties;
                return [
                    'id' => $activity->id,
                    'previous_status' => $properties['previous_status'] ?? null,
                    'new_status' => $properties['new_status'] ?? null,
                    'context' => $properties['context'] ?? [],
                    'user_id' => $properties['user_id'] ?? null,
                    'created_at' => $activity->created_at,
                    'description' => $activity->description
                ];
            });
    }
}