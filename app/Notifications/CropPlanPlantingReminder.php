<?php

namespace App\Notifications;

use App\Models\CropPlan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class CropPlanPlantingReminder extends Notification implements ShouldQueue
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
        $daysUntilPlanting = $this->cropPlan->days_until_planting;
        
        $message = $daysUntilPlanting === 0 
            ? "Plant today for Order #{$this->cropPlan->order_id}"
            : "Plant in {$daysUntilPlanting} days for Order #{$this->cropPlan->order_id}";

        // Check if seed soak is required
        $seedSoakMessage = '';
        if ($this->cropPlan->recipe && $this->cropPlan->recipe->seed_soak_hours > 0) {
            $seedSoakDate = $this->cropPlan->seed_soak_date;
            if ($seedSoakDate) {
                $seedSoakMessage = " (Seed soak starts: {$seedSoakDate->format('M j, g:i A')})";
            }
        }

        return [
            'icon' => 'heroicon-o-clock',
            'title' => "Planting Reminder: {$variety}",
            'message' => "{$message} - {$this->cropPlan->trays_needed} trays needed for {$customerName}{$seedSoakMessage}",
            'crop_plan_id' => $this->cropPlan->id,
            'order_id' => $this->cropPlan->order_id,
            'plant_by_date' => $this->cropPlan->plant_by_date->format('M j, Y'),
            'trays_needed' => $this->cropPlan->trays_needed,
            'variety' => $variety,
            'customer' => $customerName,
            'url' => route('filament.admin.resources.orders.edit', ['record' => $this->cropPlan->order_id]),
        ];
    }
}