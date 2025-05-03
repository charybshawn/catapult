<?php

namespace App\Console\Commands;

use App\Models\PlantingSchedule;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SyncPlantingSchedules extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'planting:sync 
                            {--days=90 : Number of days to sync in the future}
                            {--force : Run even if already synced today}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync planting schedules from recurring orders';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = $this->option('days');
        $force = $this->option('force');
        
        // Check if we already ran sync today to avoid duplication
        $lastSyncPath = storage_path('app/last_planting_sync.txt');
        
        if (file_exists($lastSyncPath) && !$force) {
            $lastSync = file_get_contents($lastSyncPath);
            $lastSyncDate = Carbon::parse($lastSync);
            
            // If we've already synced today, skip
            if ($lastSyncDate->isToday()) {
                $this->info('Already synced schedules today at ' . $lastSyncDate->format('H:i:s'));
                $this->info('Use --force to run again');
                return 0;
            }
        }
        
        $startDate = Carbon::now();
        $endDate = Carbon::now()->addDays($days);
        
        $this->info("Syncing planting schedules from now until {$endDate->format('Y-m-d')}");
        
        $count = PlantingSchedule::syncFromRecurringOrders($startDate, $endDate);
        
        $this->info("Created {$count} new planting schedules from recurring orders");
        
        // Record last sync time
        file_put_contents($lastSyncPath, now()->toDateTimeString());
        
        return 0;
    }
} 