<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class OrderCannotBeFulfilled extends Notification implements ShouldQueue
{
    use Queueable;

    public Order $order;
    public array $issues;

    /**
     * Create a new notification instance.
     */
    public function __construct(Order $order, array $issues)
    {
        $this->order = $order;
        $this->issues = $issues;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $customerName = $this->order->customer?->business_name ?? $this->order->customer?->contact_name ?? 'Unknown Customer';
        
        $mail = (new MailMessage)
            ->error()
            ->subject("Order #{$this->order->id} Cannot Be Fulfilled")
            ->greeting("Attention Required!")
            ->line("Order #{$this->order->id} for {$customerName} cannot be fulfilled by the delivery date.")
            ->line("Delivery Date: " . $this->order->delivery_date?->format('M j, Y'));
            
        // Add issue details
        $mail->line('**Issues found:**');
        foreach ($this->issues as $issue) {
            if (is_array($issue)) {
                $recipe = $issue['recipe'] ?? 'Unknown variety';
                $problem = $issue['issue'] ?? 'Unknown issue';
                $mail->line("• {$recipe}: {$problem}");
            } else {
                $mail->line("• {$issue}");
            }
        }
        
        $mail->action('View Order', route('filament.admin.resources.orders.edit', ['record' => $this->order->id]))
            ->line('Please adjust the delivery date or contact the customer.');
            
        return $mail;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $customerName = $this->order->customer?->business_name ?? $this->order->customer?->contact_name ?? 'Unknown Customer';
        
        // Format issues into readable messages
        $issueMessages = [];
        foreach ($this->issues as $issue) {
            if (is_array($issue)) {
                $recipe = $issue['recipe'] ?? 'Unknown variety';
                $problem = $issue['issue'] ?? 'Unknown issue';
                $issueMessages[] = "{$recipe}: {$problem}";
            } else {
                $issueMessages[] = $issue;
            }
        }

        return [
            'icon' => 'heroicon-o-exclamation-triangle',
            'title' => 'Order Cannot Be Fulfilled',
            'message' => "Order #{$this->order->id} for {$customerName} cannot be fulfilled by the delivery date due to timing constraints.",
            'order_id' => $this->order->id,
            'customer_name' => $customerName,
            'delivery_date' => $this->order->delivery_date?->format('M j, Y'),
            'issues' => $issueMessages,
            'url' => route('filament.admin.resources.orders.edit', ['record' => $this->order->id]),
        ];
    }
}