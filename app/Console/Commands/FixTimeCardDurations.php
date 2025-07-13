<?php

namespace App\Console\Commands;

use App\Models\TimeCard;
use Illuminate\Console\Command;

class FixTimeCardDurations extends Command
{
    protected $signature = 'timecard:fix-durations 
                           {--dry-run : Show what would be fixed without making changes}';

    protected $description = 'Fix incorrect duration calculations in time cards';

    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        
        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        // Find all time cards with clock_in and clock_out times
        $timeCards = TimeCard::whereNotNull('clock_in')
            ->whereNotNull('clock_out')
            ->get();

        if ($timeCards->isEmpty()) {
            $this->info('No time cards found with both clock in and clock out times.');
            return 0;
        }

        $fixedCount = 0;
        $errorCount = 0;

        $this->info("Checking {$timeCards->count()} time cards...");
        $this->newLine();

        foreach ($timeCards as $timeCard) {
            try {
                // Calculate correct duration
                $correctDuration = $timeCard->clock_out->diffInMinutes($timeCard->clock_in);
                $currentDuration = $timeCard->duration_minutes;
                
                if ($correctDuration !== $currentDuration) {
                    $currentHours = number_format($currentDuration / 60, 2);
                    $correctHours = number_format($correctDuration / 60, 2);
                    
                    $this->line("ID {$timeCard->id} - User: {$timeCard->user->name}");
                    $this->line("  Clock In: {$timeCard->clock_in->format('Y-m-d H:i:s')}");
                    $this->line("  Clock Out: {$timeCard->clock_out->format('Y-m-d H:i:s')}");
                    $this->line("  Current Duration: {$currentDuration} min ({$currentHours} hrs)");
                    $this->line("  Correct Duration: {$correctDuration} min ({$correctHours} hrs)");
                    
                    if (!$isDryRun) {
                        // Update without triggering model events to avoid double calculation
                        TimeCard::where('id', $timeCard->id)->update([
                            'duration_minutes' => $correctDuration
                        ]);
                        $this->info("  ✓ Fixed");
                    } else {
                        $this->info("  → Would fix");
                    }
                    
                    $fixedCount++;
                    $this->newLine();
                }
            } catch (\Exception $e) {
                $this->error("Error processing time card ID {$timeCard->id}: " . $e->getMessage());
                $errorCount++;
            }
        }

        $this->newLine();
        
        if ($isDryRun) {
            $this->info("DRY RUN COMPLETE:");
            $this->info("- {$fixedCount} time cards would be fixed");
        } else {
            $this->info("OPERATION COMPLETE:");
            $this->info("- {$fixedCount} time cards fixed");
        }
        
        if ($errorCount > 0) {
            $this->warn("- {$errorCount} errors encountered");
        }

        return 0;
    }
}