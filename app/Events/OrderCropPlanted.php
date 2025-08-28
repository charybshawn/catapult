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
     * @var Order
     */
    public $order;

    /**
     * The crop instance.
     *
     * @var Crop
     */
    public $crop;

    /**
     * Create a new event instance.
     *
     * @param Order $order
     * @param Crop $crop
     * @return void
     */
    public function __construct(Order $order, Crop $crop)
    {
        $this->order = $order;
        $this->crop = $crop;
    }
}