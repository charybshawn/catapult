<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class OrderItemsChangedWithActivePlans extends Notification
{
    use Queueable;

    protected Order $order;
    protected string $message;
    protected Collection $plans;

    /**
     * Create a new notification instance.
     */
    public function __construct(Order $order, string $message, Collection $plans)
    {
        $this->order = $order;
        $this->message = $message;
        $this->plans = $plans;
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
        return (new MailMessage)
            ->subject('Order Items Changed - Active Crop Plans Affected')
            ->line('Order items have been changed for an order with active crop plans.')
            ->line('Order #' . $this->order->id . ' - Customer: ' . $this->order->customer->contact_name)
            ->line('Change: ' . $this->message)
            ->line('Active crop plans: ' . $this->plans->count())
            ->action('View Order', route('filament.admin.resources.orders.edit', $this->order))
            ->line('Please review the crop plans and make necessary adjustments.');
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
            'message' => $this->message,
            'active_plans_count' => $this->plans->count(),
        ];
    }
}