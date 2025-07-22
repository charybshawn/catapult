<?php

namespace App\Actions\Order;

use App\Models\Order;
use App\Models\OrderStatus;
use App\Events\OrderPacked;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Log;

/**
 * Handle business logic when order status changes through Filament
 * Extracted from OrderStatusObserver to work WITH Filament patterns
 */
class HandleOrderStatusTransitionAction
{
    public function execute(Order $order, ?OrderStatus $oldStatus, OrderStatus $newStatus, ?EditRecord $page = null): void
    {
        // Update status timestamps
        $this->updateStatusTimestamps($order, $newStatus);
        
        // Handle status-specific business logic
        $this->handleStatusSpecificLogic($order, $oldStatus, $newStatus);
        
        // Update related records
        $this->updateRelatedRecords($order, $oldStatus, $newStatus);
        
        // Trigger events
        $this->triggerStatusEvents($order, $oldStatus, $newStatus);
        
        // Log the status change
        $this->logStatusChange($order, $oldStatus, $newStatus);
        
        // Send Filament notification if page context available
        if ($page) {
            $this->sendFilamentNotification($order, $oldStatus, $newStatus);
        }
    }

    private function updateStatusTimestamps(Order $order, OrderStatus $newStatus): void
    {
        switch ($newStatus->code) {
            case OrderStatus::STATUS_CONFIRMED:
                if (!$order->confirmed_at) {
                    $order->confirmed_at = now();
                }
                break;
                
            case OrderStatus::STATUS_CANCELLED:
                if (!$order->cancelled_at) {
                    $order->cancelled_at = now();
                }
                break;
                
            case OrderStatus::STATUS_DELIVERED:
                if (!$order->delivered_at) {
                    $order->delivered_at = now();
                }
                break;
        }
    }

    private function handleStatusSpecificLogic(Order $order, ?OrderStatus $oldStatus, OrderStatus $newStatus): void
    {
        // Handle cancellation cleanup
        if ($newStatus->code === OrderStatus::STATUS_CANCELLED) {
            $this->handleOrderCancellation($order);
        }
        
        // Handle delivery completion
        if ($newStatus->code === OrderStatus::STATUS_DELIVERED) {
            $this->handleOrderDelivery($order);
        }
    }

    private function handleOrderCancellation(Order $order): void
    {
        // Cancel any pending crops
        $harvestedStage = \App\Models\CropStage::findByCode('harvested');
        $order->crops()->where('current_stage_id', '!=', $harvestedStage?->id)->update([
            'cancelled_at' => now(),
            'cancellation_reason' => 'Order cancelled'
        ]);
        
        // Cancel any pending invoices
        if ($order->invoice && $order->invoice->status === 'pending') {
            $order->invoice->update(['status' => 'cancelled']);
        }
    }

    private function handleOrderDelivery(Order $order): void
    {
        // Update invoice status when order is delivered
        if ($order->invoice && $order->invoice->status !== 'paid') {
            // Check if order is paid
            if ($order->isPaid()) {
                $order->invoice->update(['status' => 'paid']);
            }
        }
    }

    private function updateRelatedRecords(Order $order, ?OrderStatus $oldStatus, OrderStatus $newStatus): void
    {
        // Update crop stages if moving to harvesting
        if ($newStatus->code === OrderStatus::STATUS_HARVESTING) {
            $harvestedStage = \App\Models\CropStage::findByCode('harvested');
            $harvestingStage = \App\Models\CropStage::findByCode('harvesting');
            
            $order->crops()->where('current_stage_id', '!=', $harvestedStage?->id)->each(function ($crop) use ($harvestingStage) {
                // Only update if crop is ready to harvest
                if ($crop->isReadyToHarvest() && $harvestingStage) {
                    $crop->update(['current_stage_id' => $harvestingStage->id]);
                }
            });
        }
    }

    private function triggerStatusEvents(Order $order, ?OrderStatus $oldStatus, OrderStatus $newStatus): void
    {
        // Trigger OrderPacked event when status changes to packing
        if ($newStatus->code === OrderStatus::STATUS_PACKING && 
            (!$oldStatus || $oldStatus->code !== OrderStatus::STATUS_PACKING)) {
            event(new OrderPacked($order));
        }
    }

    private function logStatusChange(Order $order, ?OrderStatus $oldStatus, OrderStatus $newStatus): void
    {
        Log::info('Order status changed via Filament', [
            'order_id' => $order->id,
            'old_status' => $oldStatus?->code,
            'new_status' => $newStatus->code,
            'user_id' => auth()->id()
        ]);
    }

    private function sendFilamentNotification(Order $order, ?OrderStatus $oldStatus, OrderStatus $newStatus): void
    {
        $notifiableStatuses = [
            OrderStatus::STATUS_CONFIRMED => 'Order Confirmed',
            OrderStatus::STATUS_READY_FOR_DELIVERY => 'Ready for Delivery',
            OrderStatus::STATUS_DELIVERED => 'Order Delivered',
            OrderStatus::STATUS_CANCELLED => 'Order Cancelled',
        ];
        
        if (isset($notifiableStatuses[$newStatus->code])) {
            Notification::make()
                ->title('Status Updated')
                ->body("Order #{$order->id} status changed to: {$notifiableStatuses[$newStatus->code]}")
                ->success()
                ->send();
        }
    }
}