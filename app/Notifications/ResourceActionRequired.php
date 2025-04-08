<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResourceActionRequired extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        protected string $subject,
        protected string $body,
        protected ?string $actionUrl = null,
        protected ?string $actionText = null
    ) {
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->subject)
            ->markdown('mail.resource-action-required', [
                'body' => $this->body,
                'actionUrl' => $this->actionUrl,
                'actionText' => $this->actionText,
            ]);

        if ($this->actionUrl && $this->actionText) {
            $mail->action($this->actionText, $this->actionUrl);
        }

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
            'subject' => $this->subject,
            'body' => $this->body,
            'action_url' => $this->actionUrl,
            'action_text' => $this->actionText,
        ];
    }
}
