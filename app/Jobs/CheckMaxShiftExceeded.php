<?php

namespace App\Jobs;

use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\TimeCard;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Filament\Notifications\Notification;

/**
 * Background job for monitoring agricultural workforce shift duration compliance.
 * 
 * Automatically monitors agricultural worker time cards to identify shifts exceeding
 * 8-hour limits during crop cultivation, order processing, inventory management, and
 * seed handling operations. Provides automated notifications to workers and management
 * for labor compliance and worker safety in agricultural microgreens production.
 *
 * @package App\Jobs
 * @author Catapult Development Team
 * @since 1.0.0
 * 
 * @agricultural_workforce Crop cultivation staff, order fulfillment team, inventory managers
 * @compliance_monitoring 8-hour shift limit enforcement, labor law compliance
 * @safety_features Worker fatigue prevention, shift duration alerts
 * 
 * @notification_system Email alerts to workers, admin dashboard notifications
 * @queue_processing Background execution for continuous agricultural workforce monitoring
 * 
 * @related_models TimeCard, User For workforce time tracking and notification delivery
 */
class CheckMaxShiftExceeded implements ShouldQueue
{
    use Queueable;

    /**
     * Initialize agricultural workforce shift monitoring job.
     * 
     * Creates background job instance for monitoring agricultural worker shift durations
     * during crop cultivation, order processing, inventory management, and seed handling
     * activities. Operates autonomously to ensure continuous compliance monitoring
     * for agricultural workforce safety and labor regulations.
     *
     * @agricultural_monitoring Continuous shift duration tracking for farm operations
     * @workforce_safety Proactive detection of excessive work hours for agricultural staff
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute agricultural workforce shift monitoring and compliance checking.
     * 
     * Processes all active agricultural worker time cards to identify shifts exceeding
     * 8-hour limits during crop cultivation, order processing, inventory management,
     * and seed handling operations. Automatically flags violations, sends notifications
     * to affected workers, and alerts management for compliance review and intervention.
     *
     * @return void Processes workforce compliance monitoring and notification delivery
     * 
     * @throws Exception Logged exceptions for workforce monitoring system stability
     * 
     * @agricultural_operations Crop work shifts, order fulfillment periods, inventory tasks
     * @compliance_checking 8-hour limit validation, automatic flagging system
     * @notification_delivery Worker alerts, management notifications, audit logging
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
     * Send comprehensive notifications for agricultural workforce shift violations.
     * 
     * Delivers multi-channel notifications when agricultural workers exceed 8-hour shift
     * limits during crop cultivation, order processing, inventory management, or seed
     * handling activities. Includes email alerts to workers and Filament notifications
     * to management for prompt compliance review and corrective action.
     *
     * @param TimeCard $timeCard Agricultural worker time card exceeding shift duration limits
     * @return void Sends notifications through multiple channels for workforce compliance
     * 
     * @throws Exception Logged exceptions for notification delivery failures
     * 
     * @notification_channels Email alerts to agricultural workers, admin dashboard notifications
     * @compliance_context Shift duration details, clock-in time, elapsed work duration
     * @management_alerts Persistent notifications to administrators for compliance review
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

        } catch (Exception $e) {
            Log::error("Failed to send notification for time card {$timeCard->id}: " . $e->getMessage());
        }
    }
}
