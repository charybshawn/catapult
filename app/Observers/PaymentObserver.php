<?php

namespace App\Observers;

use App\Models\Payment;
use App\Models\PaymentStatus;
use App\Events\PaymentReceived;

class PaymentObserver
{
    /**
     * Handle the Payment "created" event.
     *
     * @param \App\Models\Payment $payment
     * @return void
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
     * Handle the Payment "updated" event.
     *
     * @param \App\Models\Payment $payment
     * @return void
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