<?php

namespace App\Listeners;

use App\Events\OrderPacked;
use App\Services\StatusTransitionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class OrderPackedListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * The status transition service.
     *
     * @var \App\Services\StatusTransitionService
     */
    protected $statusService;

    /**
     * Create the event listener.
     *
     * @param \App\Services\StatusTransitionService $statusService
     * @return void
     */
    public function __construct(StatusTransitionService $statusService)
    {
        $this->statusService = $statusService;
    }

    /**
     * Handle the event.
     *
     * @param \App\Events\OrderPacked $event
     * @return void
     */
    public function handle(OrderPacked $event)
    {
        $order = $event->order;
        
        // Check payment status and transition accordingly
        $eventData = [
            'is_paid' => $order->isPaid(),
            'total_amount' => $order->totalAmount(),
            'remaining_balance' => $order->remainingBalance()
        ];
        
        // If order doesn't require immediate payment or is already paid, move to ready for delivery
        if (!$order->requiresImmediateInvoicing() || $order->isPaid()) {
            $this->statusService->handleBusinessEvent($order, 'packing.completed', $eventData);
        }
        // Otherwise, the order stays in packing status until payment is received
    }
}