<?php

namespace App\Listeners;

use App\Events\AllCropsReady;
use App\Services\StatusTransitionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class AllCropsReadyListener implements ShouldQueue
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
     * @param \App\Events\AllCropsReady $event
     * @return void
     */
    public function handle(AllCropsReady $event)
    {
        $order = $event->order;
        
        // Transition to ready to harvest status
        $this->statusService->handleBusinessEvent($order, 'crops.ready', [
            'total_crops' => $order->crops()->count(),
            'ready_crops' => $order->crops()->where('is_ready_to_harvest', true)->count()
        ]);
    }
}