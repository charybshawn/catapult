<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\TimeCard;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Filament\Notifications\Notification;

class CheckMaxShiftExceeded implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('CheckMaxShiftExceeded job started');

        // Get all active time cards
        $activeTimeCards = TimeCard::where('status', 'active')
            ->where('max_shift_exceeded', false) // Only check cards not already flagged
            ->with('user')
            ->get();

        $flaggedCount = 0;

        foreach ($activeTimeCards as $timeCard) {
            if ($timeCard->checkAndFlagIfNeeded()) {
                $flaggedCount++;
                
                // Send notification to the user
                $this->sendUserNotification($timeCard);
                
                // Log the event
                Log::info("Time card {$timeCard->id} flagged for user {$timeCard->user->name} - exceeded 8 hours");
            }
        }

        Log::info("CheckMaxShiftExceeded job completed. Flagged {$flaggedCount} time cards.");
    }

    /**
     * Send notification to user that their shift exceeded 8 hours
     */
    private function sendUserNotification(TimeCard $timeCard): void
    {
        try {
            // Send email notification
            if ($timeCard->user->email) {
                Mail::raw(
                    "Hi {$timeCard->user->name},\n\n" .
                    "You've been clocked in for over 8 hours since " . 
                    $timeCard->clock_in->format('g:i A') . " today.\n\n" .
                    "If you forgot to clock out, please contact your manager or " .
                    "use the time tracking system to correct your time card.\n\n" .
                    "Current time worked: " . $timeCard->elapsed_time . "\n\n" .
                    "Thank you!",
                    function ($message) use ($timeCard) {
                        $message->to($timeCard->user->email)
                                ->subject('Time Clock Alert - Over 8 Hours');
                    }
                );
            }

            // Create a Filament notification for managers/admins
            $admins = User::role('admin')->get();
            foreach ($admins as $admin) {
                Notification::make()
                    ->title('Employee Exceeded 8-Hour Shift')
                    ->body("{$timeCard->user->name} has been clocked in for over 8 hours and requires review.")
                    ->warning()
                    ->persistent()
                    ->sendToDatabase($admin);
            }

        } catch (\Exception $e) {
            Log::error("Failed to send notification for time card {$timeCard->id}: " . $e->getMessage());
        }
    }
}
