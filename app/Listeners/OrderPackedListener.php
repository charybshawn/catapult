<?php

namespace App\Listeners;

use App\Events\OrderPacked;
use App\Services\StatusTransitionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Agricultural order packaging completion event listener for delivery workflow.
 * 
 * Handles OrderPacked events to manage order status transitions based on packaging
 * completion and payment status. Implements business logic for determining whether
 * orders can proceed to delivery or must await payment completion in agricultural
 * microgreens order fulfillment workflow.
 * 
 * @business_domain Agricultural order packaging and payment workflow integration
 * @agricultural_process Packaging completion milestone with payment validation
 * @workflow_automation Payment-dependent order status transitions
 * @queue_processing Asynchronous processing to avoid blocking packaging operations
 */
class OrderPackedListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Service for managing agricultural order status transitions with payment integration.
     * 
     * Handles complex business logic for order status transitions that depend on
     * both packaging completion and payment status in agricultural order fulfillment.
     *
     * @var StatusTransitionService Service managing order workflow with payment validation
     */
    protected $statusService;

    /**
     * Create the order packaging completion event listener with status service.
     * 
     * Initializes the listener with required service for managing agricultural
     * order status transitions based on packaging and payment completion.
     *
     * @param StatusTransitionService $statusService Service for order workflow management
     * @return void
     */
    public function __construct(StatusTransitionService $statusService)
    {
        $this->statusService = $statusService;
    }

    /**
     * Handle order packaging completion event with payment-dependent workflow logic.
     * 
     * Processes packaging completion events by evaluating payment status and business
     * rules to determine appropriate order status transition. Orders proceed to delivery
     * if payment requirements are satisfied, otherwise remain in packing status.
     * 
     * @param OrderPacked $event Event containing packaged order information
     * @return void
     * 
     * @business_process Payment-dependent order workflow for agricultural fulfillment
     * @payment_validation Checks payment status before delivery phase transition
     * @conditional_workflow Different paths based on payment requirements and status
     */
    public function handle(OrderPacked $event)
    {
        $order = $event->order;

        // Evaluate payment status and determine appropriate workflow transition
        $eventData = [
            'is_paid' => $order->isPaid(),
            'total_amount' => $order->totalAmount(),
            'remaining_balance' => $order->remainingBalance()
        ];

        // Transition to delivery-ready if payment requirements are satisfied
        if (!$order->requiresImmediateInvoicing() || $order->isPaid()) {
            $this->statusService->handleBusinessEvent($order, 'packing.completed', $eventData);
        }
        // Order remains in packing status awaiting payment completion
    }
}