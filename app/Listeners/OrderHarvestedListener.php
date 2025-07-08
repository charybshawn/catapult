<?php

namespace App\Listeners;

use App\Events\OrderHarvested;
use App\Services\StatusTransitionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class OrderHarvestedListener implements ShouldQueue
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
     * @param \App\Events\OrderHarvested $event
     * @return void
     */
    public function handle(OrderHarvested $event)
    {
        $order = $event->order;
        
        // Transition to packing status
        $this->statusService->handleBusinessEvent($order, 'harvest.completed', [
            'harvested_crops' => $order->crops()->where('current_stage', 'harvested')->count(),
            'total_crops' => $order->crops()->count()
        ]);
    }
}