<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class OrderWithActivePlansModified extends Notification implements ShouldQueue
{
    use Queueable;

    protected Order $order;
    protected Collection $cropPlans;
    protected array $changes;

    /**
     * Create a new notification instance.
     */
    public function __construct(Order $order, Collection $cropPlans)
    {
        $this->order = $order;
        $this->cropPlans = $cropPlans;
        
        // Track what changed
        $this->changes = [];
        if ($order->isDirty('delivery_date')) {
            $this->changes['delivery_date'] = [
                'old' => $order->getOriginal('delivery_date'),
                'new' => $order->delivery_date
            ];
        }
        if ($order->isDirty('harvest_date')) {
            $this->changes['harvest_date'] = [
                'old' => $order->getOriginal('harvest_date'),
                'new' => $order->harvest_date
            ];
        }
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $activePlans = $this->cropPlans->filter(fn($p) => $p->status->code === 'active')->count();
        $completedPlans = $this->cropPlans->filter(fn($p) => $p->status->code === 'completed')->count();
        
        $mail = (new MailMessage)
            ->subject("Order #{$this->order->id} Modified - Crop Plans Need Review")
            ->greeting("Hello {$notifiable->name},")
            ->line("Order #{$this->order->id} for {$this->order->customer->contact_name} has been modified.")
            ->line("This order has {$activePlans} active and {$completedPlans} completed crop plans that may need adjustment.");
        
        // Show what changed
        if (!empty($this->changes)) {
            $mail->line('**Changes made:**');
            foreach ($this->changes as $field => $change) {
                $oldDate = \Carbon\Carbon::parse($change['old'])->format('M j, Y');
                $newDate = \Carbon\Carbon::parse($change['new'])->format('M j, Y');
                $fieldName = str_replace('_', ' ', ucfirst($field));
                $mail->line("- {$fieldName}: {$oldDate} → {$newDate}");
            }
        }
        
        // List affected plans
        $mail->line('**Affected crop plans:**');
        foreach ($this->cropPlans as $plan) {
            $mail->line("- {$plan->recipe->name}: {$plan->trays_needed} trays, Status: {$plan->status->name}");
        }
        
        $mail->line('Please review these crop plans and make any necessary adjustments.')
            ->action('View Order', route('filament.admin.resources.orders.edit', $this->order->id))
            ->line('Active crop plans may need to be rescheduled or cancelled based on the new dates.');
        
        return $mail;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'customer_name' => $this->order->customer->contact_name,
            'changes' => $this->changes,
            'active_plans' => $this->cropPlans->filter(fn($p) => $p->status->code === 'active')->count(),
            'completed_plans' => $this->cropPlans->filter(fn($p) => $p->status->code === 'completed')->count(),
            'total_plans' => $this->cropPlans->count(),
        ];
    }
}