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