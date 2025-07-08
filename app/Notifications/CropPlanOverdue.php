<?php

namespace App\Notifications;

use App\Models\CropPlan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Carbon\Carbon;

class CropPlanOverdue extends Notification implements ShouldQueue
{
    use Queueable;

    public CropPlan $cropPlan;

    /**
     * Create a new notification instance.
     */
    public function __construct(CropPlan $cropPlan)
    {
        $this->cropPlan = $cropPlan;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $variety = $this->cropPlan->variety?->name ?? $this->cropPlan->recipe?->name ?? 'Unknown Variety';
        $customerName = $this->cropPlan->order?->customer?->business_name ?? 
                       $this->cropPlan->order?->customer?->contact_name ?? 
                       'Unknown Customer';
        $daysOverdue = Carbon::now()->diffInDays($this->cropPlan->plant_by_date);
        
        $message = $daysOverdue === 1 
            ? "1 day overdue for Order #{$this->cropPlan->order_id}"
            : "{$daysOverdue} days overdue for Order #{$this->cropPlan->order_id}";

        return [
            'icon' => 'heroicon-o-exclamation-circle',
            'title' => "OVERDUE: {$variety}",
            'message' => "{$message} - {$this->cropPlan->trays_needed} trays needed for {$customerName}. Delivery date at risk!",
            'crop_plan_id' => $this->cropPlan->id,
            'order_id' => $this->cropPlan->order_id,
            'plant_by_date' => $this->cropPlan->plant_by_date->format('M j, Y'),
            'days_overdue' => $daysOverdue,
            'delivery_date' => $this->cropPlan->delivery_date->format('M j, Y'),
            'trays_needed' => $this->cropPlan->trays_needed,
            'variety' => $variety,
            'customer' => $customerName,
            'url' => route('filament.admin.resources.orders.edit', ['record' => $this->cropPlan->order_id]),
        ];
    }
}