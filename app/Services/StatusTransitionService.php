<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderStatus;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class StatusTransitionService
{
    /**
     * Validate if a status transition is allowed for an order.
     *
     * @param Order $order
     * @param string $targetStatusCode
     * @return array ['valid' => bool, 'reason' => string|null]
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
     * Validate business-specific rules for status transitions.
     *
     * @param Order $order
     * @param UnifiedOrderStatus $currentStatus
     * @param UnifiedOrderStatus $targetStatus
     * @return array ['valid' => bool, 'reason' => string|null]
     */
    protected function validateBusinessRules(Order $order, UnifiedOrderStatus $currentStatus, UnifiedOrderStatus $targetStatus): array
    {
        // Template orders have limited transitions
        if ($currentStatus->code === OrderStatus::STATUS_TEMPLATE) {
            if (!in_array($targetStatus->code, [OrderStatus::STATUS_CANCELLED])) {
                return ['valid' => false, 'reason' => 'Template orders can only be cancelled'];
            }
        }
        
        // Orders with crops can't skip production stages
        if ($order->requiresCropProduction()) {
            // Can't go directly from pre-production to fulfillment
            if ($currentStatus->isPreProductionStage() && $targetStatus->isFulfillmentStage()) {
                return ['valid' => false, 'reason' => 'Orders with crops must go through production stages'];
            }
            
            // Can't go to packing until crops are harvested
            if ($targetStatus->code === OrderStatus::STATUS_PACKING) {
                $allCropsHarvested = $order->crops->every(fn($crop) => $crop->current_stage === 'harvested');
                if (!$allCropsHarvested) {
                    return ['valid' => false, 'reason' => 'All crops must be harvested before packing'];
                }
            }
        }
        
        // Can't move to ready_for_delivery without payment for certain order types
        if ($targetStatus->code === OrderStatus::STATUS_READY_FOR_DELIVERY) {
            if ($order->requiresImmediateInvoicing() && !$order->isPaid()) {
                return ['valid' => false, 'reason' => 'Order must be paid before marking as ready for delivery'];
            }
        }
        
        // Can't transition from final statuses
        if ($currentStatus->is_final) {
            return ['valid' => false, 'reason' => 'Cannot transition from a final status'];
        }
        
        return ['valid' => true, 'reason' => null];
    }
    
    /**
     * Perform a status transition with validation and logging.
     *
     * @param Order $order
     * @param string $targetStatusCode
     * @param array $context Additional context for logging
     * @return array ['success' => bool, 'message' => string, 'order' => Order|null]
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
     * Get valid next statuses for an order.
     *
     * @param Order $order
     * @return Collection
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
     * Bulk update order statuses with validation.
     *
     * @param array $orderIds
     * @param string $targetStatusCode
     * @param array $context
     * @return array ['successful' => array, 'failed' => array]
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
     * Automatically update order status based on business events.
     *
     * @param Order $order
     * @param string $event
     * @param array $eventData
     * @return void
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
     * Determine the appropriate status based on a business event.
     *
     * @param Order $order
     * @param string $event
     * @param array $eventData
     * @return string|null
     */
    protected function determineStatusFromEvent(Order $order, string $event, array $eventData): ?string
    {
        switch ($event) {
            case 'order.confirmed':
                return OrderStatus::STATUS_CONFIRMED;
                
            case 'crop.planted':
                // Check if all required crops are planted
                if ($this->areAllCropsPlanted($order)) {
                    return OrderStatus::STATUS_GROWING;
                }
                break;
                
            case 'crops.ready':
                return OrderStatus::STATUS_READY_TO_HARVEST;
                
            case 'harvest.completed':
                return OrderStatus::STATUS_PACKING;
                
            case 'packing.completed':
                // Check if payment is required
                if ($order->requiresImmediateInvoicing() && !$order->isPaid()) {
                    // Stay in packing until paid
                    return null;
                }
                return OrderStatus::STATUS_READY_FOR_DELIVERY;
                
            case 'payment.received':
                // If order is packed and now paid, move to ready for delivery
                if ($order->unifiedStatus->code === OrderStatus::STATUS_PACKING) {
                    return OrderStatus::STATUS_READY_FOR_DELIVERY;
                }
                break;
                
            case 'delivery.started':
                return OrderStatus::STATUS_OUT_FOR_DELIVERY;
                
            case 'delivery.completed':
                return OrderStatus::STATUS_DELIVERED;
        }
        
        return null;
    }
    
    /**
     * Check if all required crops for an order are planted.
     *
     * @param Order $order
     * @return bool
     */
    protected function areAllCropsPlanted(Order $order): bool
    {
        if (!$order->requiresCropProduction()) {
            return false;
        }
        
        $requiredCrops = $order->cropPlans()->count();
        $plantedCrops = $order->crops()->whereNotNull('planting_at')->count();
        
        return $requiredCrops > 0 && $plantedCrops >= $requiredCrops;
    }
    
    /**
     * Log a status transition.
     *
     * @param Order $order
     * @param UnifiedOrderStatus|null $previousStatus
     * @param UnifiedOrderStatus $newStatus
     * @param array $context
     * @return void
     */
    protected function logTransition(Order $order, ?UnifiedOrderStatus $previousStatus, UnifiedOrderStatus $newStatus, array $context): void
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
     * Get the status history for an order from the activity log.
     *
     * @param Order $order
     * @return Collection
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