<?php

namespace App\Actions\Order;

use App\Models\CropStage;
use App\Models\Order;
use App\Models\OrderStatus;
use App\Events\OrderPacked;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Log;

/**
 * Manages comprehensive order status transitions for agricultural operations.
 * 
 * Handles complete status change workflows including timestamp updates,
 * business logic execution, related record updates, event triggering,
 * and user notifications. Ensures proper agricultural order lifecycle
 * management with crop integration and invoice coordination.
 * 
 * @business_domain Agricultural Order Lifecycle and Status Management
 * @status_workflow Complete transition handling with business rule enforcement
 * @production_integration Coordinates order status with crop cultivation stages
 * 
 * @architecture Extracted from OrderStatusObserver to work WITH Filament patterns
 * @filament_integration Designed for EditRecord page context integration
 * 
 * @author Catapult System
 * @since 1.0.0
 */
class HandleOrderStatusTransitionAction
{
    /**
     * Execute comprehensive order status transition with complete workflow management.
     * 
     * Orchestrates multi-step status change process including timestamp updates,
     * status-specific business logic, related record synchronization, event
     * triggering, audit logging, and user notification. Ensures agricultural
     * order transitions maintain data consistency and business rule compliance.
     * 
     * @business_process Complete Order Status Transition Workflow
     * @agricultural_context Status transitions integrated with crop production stages
     * @coordination_management Synchronizes orders, crops, invoices, and notifications
     * 
     * @param Order $order The agricultural order undergoing status transition
     * @param OrderStatus|null $oldStatus Previous order status (null for new orders)
     * @param OrderStatus $newStatus Target status for the transition
     * @param EditRecord|null $page Optional Filament page context for user notifications
     * 
     * @workflow_steps:
     *   1. Update status-specific timestamps (confirmed_at, cancelled_at, delivered_at)
     *   2. Execute status-specific business logic (cancellation, delivery handling)
     *   3. Update related records (crops, invoices, associated data)
     *   4. Trigger appropriate system events (OrderPacked, etc.)
     *   5. Log status change for audit trail
     *   6. Send Filament notification if page context available
     * 
     * @business_rules Status-specific logic for cancellation cleanup and delivery completion
     * @event_integration Triggers system events for status-dependent workflows
     * @audit_logging Comprehensive logging of status changes with user attribution
     * 
     * @usage Called from OrderResource EditRecord hooks during status updates
     * @notification_system Contextual user feedback through Filament interface
     */
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
        $harvestedStage = CropStage::findByCode('harvested');
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
            $harvestedStage = CropStage::findByCode('harvested');
            $harvestingStage = CropStage::findByCode('harvesting');
            
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