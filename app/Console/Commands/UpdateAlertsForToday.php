<?php

namespace App\Console\Commands;

use App\Models\TaskSchedule;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class UpdateAlertsForToday extends Command
{
    protected $signature = 'alerts:update-for-today {count=5 : Number of alerts to update}';
    protected $description = 'Update a specified number of task schedule alerts to occur today';

    protected $sampleTaskNames = [
        'Water Crops', 
        'Move to Blackout', 
        'Move to Light', 
        'Harvest Ready', 
        'Add Nutrients', 
        'Inspect Germination',
        'Check Humidity',
        'Rotate Trays'
    ];
    
    protected $sampleVarieties = [
        'Sunflower',
        'Broccoli',
        'Radish',
        'Pea Shoots',
        'Mustard',
        'Kale',
        'Arugula',
        'Amaranth'
    ];

    public function handle()
    {
        $count = (int) $this->argument('count');
        $today = Carbon::now();
        
        // Get some active alerts
        $alerts = TaskSchedule::where('resource_type', 'crops')
                              ->where('is_active', true)
                              ->take($count)
                              ->get();

        if ($alerts->isEmpty()) {
            $this->error('No active alerts found to update.');
            return 1;
        }

        $this->info("Found {$alerts->count()} alerts to update.");
        $updatedCount = 0;

        // Spread alerts throughout today
        foreach ($alerts as $index => $alert) {
            // Calculate a time today (spread between now and end of day)
            $hoursToAdd = (int)(($index + 1) * (24 - $today->hour) / ($alerts->count() + 1));
            $newTime = $today->copy()->addHours($hoursToAdd)->minutes(0);
            
            // Update alert with sample task names
            $taskNameIndex = $index % count($this->sampleTaskNames);
            $alert->task_name = $this->sampleTaskNames[$taskNameIndex];
            $alert->next_run_at = $newTime;
            
            // Add grow batch information to demonstrate batch functionality
            $varietyIndex = $index % count($this->sampleVarieties);
            $variety = $this->sampleVarieties[$varietyIndex];
            $trayCount = rand(2, 5); // Random number of trays between 2 and 5
            
            // Create tray numbers
            $startTray = rand(1, 20);
            $trayNumbers = range($startTray, $startTray + $trayCount - 1);
            $trayList = implode(', ', $trayNumbers);
            
            // Sample recipe ID and planted date
            $recipeId = rand(1, 8);
            $plantedDate = today()->subDays(rand(1, 14))->format('Y-m-d');
            $batchIdentifier = "{$recipeId}_{$plantedDate}_light";
            
            // Set the conditions
            $alert->conditions = [
                'crop_id' => rand(1, 100),
                'batch_identifier' => $batchIdentifier,
                'target_stage' => 'harvested',
                'tray_numbers' => $trayNumbers,
                'tray_count' => $trayCount,
                'tray_list' => $trayList,
                'variety' => $variety,
            ];
            
            $alert->save();
            
            $this->info("Updated alert '{$alert->task_name}' to run at {$newTime->format('g:i A')} with {$trayCount} trays of {$variety}");
            $updatedCount++;
        }

        $this->info("Successfully updated {$updatedCount} alerts to occur today.");
        return 0;
    }
} 