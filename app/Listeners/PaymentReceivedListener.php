<?php

namespace App\Listeners;

use App\Events\PaymentReceived;
use App\Services\StatusTransitionService;
use App\Models\OrderStatus;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Payment completion event listener for agricultural order workflow automation.
 * 
 * Handles PaymentReceived events to automatically progress orders from packing
 * status to delivery-ready status when payment requirements are satisfied.
 * Critical component in agricultural order fulfillment that ensures orders
 * only proceed to delivery after successful payment completion.
 * 
 * @business_domain Payment processing workflow for agricultural order fulfillment
 * @financial_integration Payment completion milestone for order progression
 * @workflow_automation Payment-triggered order status transitions
 * @queue_processing Asynchronous processing to avoid blocking payment operations
 */
class PaymentReceivedListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Service for managing agricultural order status transitions based on payment events.
     * 
     * Handles complex business logic for order progression when payments are completed,
     * ensuring orders transition appropriately through agricultural fulfillment workflow.
     *
     * @var StatusTransitionService Service managing payment-triggered order transitions
     */
    protected $statusService;

    /**
     * Create the payment completion event listener with status transition service.
     * 
     * Initializes the listener with required service for managing agricultural
     * order status transitions triggered by payment completion events.
     *
     * @param StatusTransitionService $statusService Service for payment-triggered workflows
     * @return void
     */
    public function __construct(StatusTransitionService $statusService)
    {
        $this->statusService = $statusService;
    }

    /**
     * Handle payment completion event and evaluate order status transition eligibility.
     * 
     * Processes payment completion events by validating payment status and triggering
     * order progression for packed orders that are now fully paid. Ensures orders
     * only advance to delivery when payment obligations are satisfied.
     * 
     * @param PaymentReceived $event Event containing order and completed payment details
     * @return void
     * 
     * @business_process Payment-triggered agricultural order workflow progression
     * @payment_validation Ensures payment completion before delivery authorization
     * @conditional_transition Only processes orders in appropriate packing status
     */
    public function handle(PaymentReceived $event)
    {
        $order = $event->order;
        $payment = $event->payment;
        
        // Validate payment completion before processing order transition
        if (!$payment->isCompleted()) {
            return;
        }
        
        // Process order transition if payment obligations are now satisfied
        if ($order->isPaid()) {
            // Advance packed orders to delivery-ready status upon payment completion
            if ($order->unifiedStatus && $order->unifiedStatus->code === OrderStatus::STATUS_PACKING) {
                $this->statusService->handleBusinessEvent($order, 'payment.received', [
                    'payment_id' => $payment->id,
                    'payment_amount' => $payment->amount,
                    'total_paid' => $order->payments()->where('status', 'completed')->sum('amount'),
                    'order_total' => $order->totalAmount()
                ]);
            }
        }
    }
}