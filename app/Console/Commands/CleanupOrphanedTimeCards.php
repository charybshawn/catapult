<?php

namespace App\Console\Commands;

use App\Models\TimeCard;
use Illuminate\Console\Command;

class CleanupOrphanedTimeCards extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'timecard:cleanup-orphaned {--dry-run : Show what would be deleted without actually deleting}';

    /**
     * The console command description.
     */
    protected $description = 'Clean up time cards that reference deleted users';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $orphanedCards = TimeCard::orphaned()->get();

        if ($orphanedCards->isEmpty()) {
            $this->info('No orphaned time cards found.');
            return Command::SUCCESS;
        }

        $this->info("Found {$orphanedCards->count()} orphaned time cards:");

        $this->table(
            ['ID', 'User ID', 'Work Date', 'Clock In', 'Clock Out', 'Status'],
            $orphanedCards->map(function ($card) {
                return [
                    $card->id,
                    $card->user_id,
                    $card->work_date->format('Y-m-d'),
                    $card->clock_in?->format('H:i:s') ?? 'None',
                    $card->clock_out?->format('H:i:s') ?? 'Active',
                    $card->requires_review ? 'Flagged' : 'Normal'
                ];
            })
        );

        if ($this->option('dry-run')) {
            $this->warn('This was a dry run. Use without --dry-run to actually delete these records.');
            return Command::SUCCESS;
        }

        if ($this->confirm('Do you want to delete these orphaned time cards?')) {
            $deleted = $orphanedCards->count();
            TimeCard::orphaned()->delete();
            $this->info("Deleted {$deleted} orphaned time cards.");
        } else {
            $this->info('Operation cancelled.');
        }

        return Command::SUCCESS;
    }
}