<?php

namespace App\Observers;

use App\Models\Payment;
use App\Models\PaymentStatus;
use App\Events\PaymentReceived;

/**
 * Payment model lifecycle observer for agricultural order fulfillment automation.
 * 
 * Monitors payment model events to automatically trigger PaymentReceived business
 * events when payments are completed or updated to completed status. Essential
 * component for integrating payment processing with agricultural order workflow
 * automation and ensuring orders progress appropriately upon payment completion.
 * 
 * @business_domain Payment processing integration with agricultural order fulfillment
 * @financial_workflow Payment completion detection and business event triggering
 * @order_integration Links payment lifecycle to agricultural order progression
 * @event_driven Triggers business events for payment completion milestones
 */
class PaymentObserver
{
    /**
     * Handle payment model creation event with immediate completion detection.
     * 
     * Monitors newly created payment records and triggers PaymentReceived business
     * events for payments that are created in completed status. Supports payment
     * processing workflows where payments are immediately marked as completed.
     * 
     * @param Payment $payment Newly created payment model
     * @return void
     * 
     * @payment_workflow Handles payments created directly in completed status
     * @agricultural_integration Triggers order progression for immediate payments
     */
    public function created(Payment $payment)
    {
        // Trigger event for new completed payments
        if ($payment->isCompleted() && $payment->order_id) {
            $payment->load('order');
            event(new PaymentReceived($payment->order, $payment));
        }
    }
    
    /**
     * Handle payment model update event with status change detection.
     * 
     * Monitors payment status changes to detect transitions to completed status
     * and trigger PaymentReceived business events. Critical for payment processing
     * workflows where payments are initially pending and later marked as completed.
     * 
     * @param Payment $payment Updated payment model with potential status changes
     * @return void
     * 
     * @status_monitoring Detects payment completion status transitions
     * @workflow_automation Triggers agricultural order progression upon payment completion
     */
    public function updated(Payment $payment)
    {
        // Check if payment status changed to completed
        if ($payment->wasChanged('status_id')) {
            $completedStatusId = PaymentStatus::findByCode('completed')?->id;
            if ($payment->status_id === $completedStatusId && $payment->order_id) {
                $payment->load('order');
                event(new PaymentReceived($payment->order, $payment));
            }
        }
    }
}