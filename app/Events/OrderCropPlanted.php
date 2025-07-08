<?php

namespace App\Events;

use App\Models\Order;
use App\Models\Crop;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderCropPlanted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The order instance.
     *
     * @var \App\Models\Order
     */
    public $order;

    /**
     * The crop instance.
     *
     * @var \App\Models\Crop
     */
    public $crop;

    /**
     * Create a new event instance.
     *
     * @param \App\Models\Order $order
     * @param \App\Models\Crop $crop
     * @return void
     */
    public function __construct(Order $order, Crop $crop)
    {
        $this->order = $order;
        $this->crop = $crop;
    }
}