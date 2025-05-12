<?php

namespace App\Notifications;

use App\Models\Crop;
use App\Models\TaskSchedule;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CropTaskActionDue extends Notification implements ShouldQueue // Implement ShouldQueue for background sending
{
    use Queueable;

    public TaskSchedule $task;
    public Crop $crop;

    /**
     * Create a new notification instance.
     */
    public function __construct(TaskSchedule $task, Crop $crop)
    {
        $this->task = $task;
        $this->crop = $crop;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        // Only use the database channel for now
        return ['database']; 
    }

    /**
     * Get the mail representation of the notification.
     */
    // public function toMail(object $notifiable): MailMessage
    // {
    //     return (new MailMessage)
    //                 ->line('The introduction to the notification.')
    //                 ->action('Notification Action', url('/'))
    //                 ->line('Thank you for using our application!');
    // }

    /**
     * Get the array representation of the notification for the database.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $variety = $this->crop->recipe?->seedVariety?->name ?? ($this->crop->recipe?->name ?? 'Unknown Variety');
        $tray = $this->crop->tray_number;
        $icon = 'heroicon-o-bell-alert'; // Default icon
        $title = "Action Due: {$variety} (Tray {$tray})"; // Default title
        $message = 'Crop requires attention.'; // Default message

        // Customize content based on task name
        switch ($this->task->task_name) {
            case 'advance_to_blackout':
            case 'advance_to_light':
            case 'advance_to_harvested':
                $targetStage = ucfirst($this->task->conditions['target_stage'] ?? 'Unknown Stage');
                $message = "Crop requires action: Advance to {$targetStage}";
                $icon = 'heroicon-o-chevron-double-right'; 
                break;
            
            case 'suspend_watering':
                $message = "Crop requires action: Suspend Watering";
                $title = "Suspend Watering Due: {$variety} (Tray {$tray})";
                $icon = 'heroicon-o-no-symbol'; // Example: water drop with slash
                break;
            
            // Add cases for other potential crop task types if needed
                
            default:
                // Use defaults or log unexpected task type
                \Illuminate\Support\Facades\Log::warning("Generating notification for unexpected task type: {$this->task->task_name}");
                break;
        }
        
        return [
            'icon' => $icon,
            'title' => $title,
            'message' => $message,
            'crop_id' => $this->crop->id,
            'task_schedule_id' => $this->task->id,
            // Generate URL to the specific crop edit page
            'url' => route('filament.admin.resources.crops.edit', ['record' => $this->crop->id]),
        ];
    }
}
