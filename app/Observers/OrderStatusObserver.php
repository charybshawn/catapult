<?php

namespace App\Observers;

use App\Models\Order;
use App\Models\OrderStatus;
use App\Events\OrderPacked;
use Illuminate\Support\Facades\Log;

class OrderStatusObserver
{
    /**
     * Handle the Order "updating" event.
     *
     * @param \App\Models\Order $order
     * @return void
     */
    public function updating(Order $order)
    {
        // Check if status is changing
        if ($order->isDirty('status_id')) {
            $oldStatusId = $order->getOriginal('status_id');
            $newStatusId = $order->status_id;
            
            $oldStatus = $oldStatusId ? OrderStatus::find($oldStatusId) : null;
            $newStatus = OrderStatus::find($newStatusId);
            
            if ($newStatus) {
                // Handle status-specific business logic
                $this->handleStatusTransition($order, $oldStatus, $newStatus);
            }
        }
    }
    
    /**
     * Handle the Order "updated" event.
     *
     * @param \App\Models\Order $order
     * @return void
     */
    public function updated(Order $order)
    {
        // Check if status changed
        if ($order->wasChanged('status_id')) {
            $oldStatusId = $order->getOriginal('status_id');
            $newStatusId = $order->status_id;
            
            $oldStatus = $oldStatusId ? OrderStatus::find($oldStatusId) : null;
            $newStatus = OrderStatus::find($newStatusId);
            
            if ($newStatus) {
                // Send notifications for important status changes
                $this->sendStatusNotifications($order, $oldStatus, $newStatus);
                
                // Update related records
                $this->updateRelatedRecords($order, $oldStatus, $newStatus);
                
                // Trigger specific events for status changes
                $this->triggerStatusEvents($order, $oldStatus, $newStatus);
            }
        }
    }
    
    /**
     * Handle status-specific business logic during transition.
     *
     * @param \App\Models\Order $order
     * @param \App\Models\UnifiedOrderStatus|null $oldStatus
     * @param \App\Models\UnifiedOrderStatus $newStatus
     * @return void
     */
    protected function handleStatusTransition(Order $order, ?UnifiedOrderStatus $oldStatus, UnifiedOrderStatus $newStatus)
    {
        // Update legacy status fields for backward compatibility
        $this->syncLegacyStatuses($order, $newStatus);
        
        // Handle specific status transitions
        switch ($newStatus->code) {
            case OrderStatus::STATUS_CONFIRMED:
                // Set confirmed timestamp if not already set
                if (!$order->confirmed_at) {
                    $order->confirmed_at = now();
                }
                break;
                
            case OrderStatus::STATUS_CANCELLED:
                // Set cancelled timestamp
                if (!$order->cancelled_at) {
                    $order->cancelled_at = now();
                }
                break;
                
            case OrderStatus::STATUS_DELIVERED:
                // Set delivered timestamp
                if (!$order->delivered_at) {
                    $order->delivered_at = now();
                }
                break;
        }
    }
    
    /**
     * Sync legacy status fields for backward compatibility.
     *
     * @param \App\Models\Order $order
     * @param \App\Models\UnifiedOrderStatus $unifiedStatus
     * @return void
     */
    protected function syncLegacyStatuses(Order $order, UnifiedOrderStatus $unifiedStatus)
    {
        // Map status to legacy order status
        $orderStatusMapping = [
            OrderStatus::STATUS_DRAFT => 'draft',
            OrderStatus::STATUS_PENDING => 'pending',
            OrderStatus::STATUS_CONFIRMED => 'confirmed',
            OrderStatus::STATUS_GROWING => 'processing',
            OrderStatus::STATUS_READY_TO_HARVEST => 'processing',
            OrderStatus::STATUS_HARVESTING => 'processing',
            OrderStatus::STATUS_PACKING => 'processing',
            OrderStatus::STATUS_READY_FOR_DELIVERY => 'processing',
            OrderStatus::STATUS_OUT_FOR_DELIVERY => 'processing',
            OrderStatus::STATUS_DELIVERED => 'completed',
            OrderStatus::STATUS_CANCELLED => 'cancelled',
            OrderStatus::STATUS_TEMPLATE => 'template',
        ];
        
        if (isset($orderStatusMapping[$unifiedStatus->code])) {
            $orderStatus = \App\Models\OrderStatus::where('code', $orderStatusMapping[$unifiedStatus->code])->first();
            if ($orderStatus && $order->status_id !== $orderStatus->id) {
                $order->status_id = $orderStatus->id;
            }
        }
        
        // Map status to legacy crop status
        if ($unifiedStatus->isProductionStage()) {
            $cropStatusMapping = [
                OrderStatus::STATUS_GROWING => 'growing',
                OrderStatus::STATUS_READY_TO_HARVEST => 'ready_to_harvest',
                OrderStatus::STATUS_HARVESTING => 'harvesting',
            ];
            
            if (isset($cropStatusMapping[$unifiedStatus->code])) {
                $cropStatus = \App\Models\CropStatus::where('code', $cropStatusMapping[$unifiedStatus->code])->first();
                if ($cropStatus && $order->crop_status_id !== $cropStatus->id) {
                    $order->crop_status_id = $cropStatus->id;
                }
            }
        }
        
        // Map status to legacy fulfillment status
        if ($unifiedStatus->isFulfillmentStage()) {
            $fulfillmentStatusMapping = [
                OrderStatus::STATUS_PACKING => 'packing',
                OrderStatus::STATUS_READY_FOR_DELIVERY => 'ready_for_delivery',
                OrderStatus::STATUS_OUT_FOR_DELIVERY => 'out_for_delivery',
                OrderStatus::STATUS_DELIVERED => 'delivered',
            ];
            
            if (isset($fulfillmentStatusMapping[$unifiedStatus->code])) {
                $fulfillmentStatus = \App\Models\FulfillmentStatus::where('code', $fulfillmentStatusMapping[$unifiedStatus->code])->first();
                if ($fulfillmentStatus && $order->fulfillment_status_id !== $fulfillmentStatus->id) {
                    $order->fulfillment_status_id = $fulfillmentStatus->id;
                }
            }
        }
    }
    
    /**
     * Send notifications for important status changes.
     *
     * @param \App\Models\Order $order
     * @param \App\Models\UnifiedOrderStatus|null $oldStatus
     * @param \App\Models\UnifiedOrderStatus $newStatus
     * @return void
     */
    protected function sendStatusNotifications(Order $order, ?UnifiedOrderStatus $oldStatus, UnifiedOrderStatus $newStatus)
    {
        // Define which status changes should trigger notifications
        $notifiableStatuses = [
            OrderStatus::STATUS_CONFIRMED,
            OrderStatus::STATUS_READY_FOR_DELIVERY,
            OrderStatus::STATUS_OUT_FOR_DELIVERY,
            OrderStatus::STATUS_DELIVERED,
            OrderStatus::STATUS_CANCELLED,
        ];
        
        if (in_array($newStatus->code, $notifiableStatuses)) {
            // Check if customer should be notified
            if ($order->customer && $order->customer->email) {
                // Queue notification (assuming notification classes exist)
                // $order->customer->notify(new OrderStatusChanged($order, $oldStatus, $newStatus));
                
                Log::info('Order status notification queued', [
                    'order_id' => $order->id,
                    'customer_id' => $order->customer_id,
                    'old_status' => $oldStatus?->code,
                    'new_status' => $newStatus->code
                ]);
            }
        }
    }
    
    /**
     * Update related records based on status change.
     *
     * @param \App\Models\Order $order
     * @param \App\Models\UnifiedOrderStatus|null $oldStatus
     * @param \App\Models\UnifiedOrderStatus $newStatus
     * @return void
     */
    protected function updateRelatedRecords(Order $order, ?UnifiedOrderStatus $oldStatus, UnifiedOrderStatus $newStatus)
    {
        // Update crop stages if moving to harvesting
        if ($newStatus->code === OrderStatus::STATUS_HARVESTING) {
            $order->crops()->where('current_stage', '!=', 'harvested')->each(function ($crop) {
                // Only update if crop is ready to harvest
                if ($crop->isReadyToHarvest()) {
                    $crop->update(['current_stage' => 'harvesting']);
                }
            });
        }
        
        // Update invoice status when order is delivered
        if ($newStatus->code === OrderStatus::STATUS_DELIVERED) {
            if ($order->invoice && $order->invoice->status !== 'paid') {
                // Check if order is paid
                if ($order->isPaid()) {
                    $order->invoice->update(['status' => 'paid']);
                }
            }
        }
        
        // Handle cancellation cleanup
        if ($newStatus->code === OrderStatus::STATUS_CANCELLED) {
            // Cancel any pending crops
            $order->crops()->where('current_stage', '!=', 'harvested')->update([
                'cancelled_at' => now(),
                'cancellation_reason' => 'Order cancelled'
            ]);
            
            // Cancel any pending invoices
            if ($order->invoice && $order->invoice->status === 'pending') {
                $order->invoice->update(['status' => 'cancelled']);
            }
        }
    }
    
    /**
     * Trigger specific events based on status changes.
     *
     * @param \App\Models\Order $order
     * @param \App\Models\UnifiedOrderStatus|null $oldStatus
     * @param \App\Models\UnifiedOrderStatus $newStatus
     * @return void
     */
    protected function triggerStatusEvents(Order $order, ?UnifiedOrderStatus $oldStatus, UnifiedOrderStatus $newStatus)
    {
        // Trigger OrderPacked event when status changes to packing
        if ($newStatus->code === OrderStatus::STATUS_PACKING && 
            (!$oldStatus || $oldStatus->code !== OrderStatus::STATUS_PACKING)) {
            event(new OrderPacked($order));
        }
        
        // Note: Other events like OrderCropPlanted, AllCropsReady, and OrderHarvested
        // are triggered from the CropObserver based on crop changes
        // PaymentReceived is triggered from the PaymentObserver
    }
}