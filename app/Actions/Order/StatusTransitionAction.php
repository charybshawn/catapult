<?php

namespace App\Actions\Order;

use App\Models\Order;
use App\Models\OrderStatus;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;

/**
 * Handle order status transitions with activity logging
 */
class StatusTransitionAction
{
    /**
     * Update order status with proper activity logging and validation
     */
    public function execute(Order $order, int $newStatusId): void
    {
        $oldStatus = $order->status;
        $newStatus = OrderStatus::find($newStatusId);
        
        if (!$newStatus) {
            Log::error('Invalid status ID provided for order status transition', [
                'order_id' => $order->id,
                'status_id' => $newStatusId
            ]);
            return;
        }
        
        // Validate the transition is allowed
        if (!OrderStatus::isValidTransition($oldStatus?->code ?? 'unknown', $newStatus->code)) {
            Log::warning('Invalid status transition attempted', [
                'order_id' => $order->id,
                'old_status' => $oldStatus?->code ?? 'unknown',
                'new_status' => $newStatus->code
            ]);
            return;
        }
        
        // Update the order status
        $order->update(['status_id' => $newStatusId]);
        
        // Log the status change activity
        activity()
            ->performedOn($order)
            ->withProperties([
                'old_status' => $oldStatus?->name ?? 'Unknown',
                'old_status_code' => $oldStatus?->code ?? 'unknown',
                'old_stage' => $oldStatus?->stage ?? 'unknown',
                'new_status' => $newStatus->name,
                'new_status_code' => $newStatus->code,
                'new_stage' => $newStatus->stage,
                'changed_by' => auth()->user()->name ?? 'System'
            ])
            ->log('Unified order status changed');
            
        // Send notification
        $this->sendStatusUpdateNotification($order, $newStatus);
    }
    
    /**
     * Send notification for status update
     */
    protected function sendStatusUpdateNotification(Order $order, OrderStatus $newStatus): void
    {
        Notification::make()
            ->title('Order Status Updated')
            ->body("Order #{$order->id} status changed to: {$newStatus->name} ({$newStatus->stage_display})")
            ->success()
            ->send();
    }
}