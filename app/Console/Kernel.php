<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Check resource levels every hour
        $schedule->command('app:check-resource-levels')
                 ->hourly();
                 
        // Update crop time fields every 15 minutes
        $schedule->command('app:update-crop-time-fields')
            ->everyFifteenMinutes()
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/crop-time-updates.log'));
                 
        // You can also schedule specific resource checks
        // $schedule->command('app:check-resource-levels --resource=inventory')
        //         ->dailyAt('08:00');

        // Add command to process crop tasks
        $schedule->command('app:process-crop-tasks')->everyFifteenMinutes(); // Check every 15 minutes

        // Check lot depletion daily at 7 AM and automatically mark depleted lots
        $schedule->command('app:check-lot-depletion --notify --auto-mark')
            ->dailyAt('07:00')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/lot-depletion.log'));

        // Additional lot depletion check every 4 hours without notifications (monitoring only)
        $schedule->command('app:check-lot-depletion')
            ->cron('0 */4 * * *')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/lot-depletion.log'));

        // Send crop plan reminders daily at 8 AM for plans due in next 2 days
        $schedule->command('crop-plans:send-reminders --days=2')
            ->dailyAt('08:00')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/crop-plan-reminders.log'));

        // Check crop plan status daily at 7:30 AM
        $schedule->command('crop-plans:check-status')
            ->dailyAt('07:30')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/crop-plan-status.log'));

        // TEMPORARILY DISABLED: Process recurring orders daily at 6 AM (before typical business hours)
        // Use manual action buttons in OrderResource instead
        // $schedule->command('orders:process-recurring')
        //     ->dailyAt('06:00')
        //     ->withoutOverlapping()
        //     ->appendOutputTo(storage_path('logs/recurring-orders.log'));

        // Run database optimization weekly during low-traffic period
        $schedule->command('db:optimize --analyze --optimize')
            ->weekly()
            ->sundays()
            ->at('03:00')
            ->appendOutputTo(storage_path('logs/db-optimize.log'));
        
        // Prune old database records and optimize file storage weekly
        $schedule->command('model:prune')
            ->weekly()
            ->sundays()
            ->at('04:00');
        
        // Update migration schema cache daily at 2 AM (after typical deployment times)
        $schedule->command('schema:update-cache')
            ->dailyAt('02:00')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/schema-cache.log'));
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
        
        // Define a simple command to update a recipe's germination days
        $this->command('recipe:set-germination {recipe_id} {days}', function (int $recipeId, float $days) {
            $recipe = \App\Models\Recipe::find($recipeId);
            
            if (!$recipe) {
                $this->error("Recipe with ID {$recipeId} not found");
                return 1;
            }
            
            $this->info("Current germination days: {$recipe->germination_days}");
            
            $recipe->germination_days = $days;
            $recipe->save();
            
            $this->info("Updated germination days to: {$recipe->germination_days}");
            
            return 0;
        })->purpose('Set the germination days for a recipe');

        require base_path('routes/console.php');
    }
} 