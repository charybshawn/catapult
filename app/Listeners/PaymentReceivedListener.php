<?php

namespace App\Listeners;

use App\Events\PaymentReceived;
use App\Services\StatusTransitionService;
use App\Models\OrderStatus;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class PaymentReceivedListener implements ShouldQueue
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
     * @param \App\Events\PaymentReceived $event
     * @return void
     */
    public function handle(PaymentReceived $event)
    {
        $order = $event->order;
        $payment = $event->payment;
        
        // Only process if payment is completed
        if (!$payment->isCompleted()) {
            return;
        }
        
        // Check if order is now fully paid
        if ($order->isPaid()) {
            // If order is in packing status and now paid, move to ready for delivery
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