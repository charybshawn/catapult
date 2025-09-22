<?php

namespace App\Console\Commands;

use App\Models\Crop;
use App\Services\CropTimeCalculator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RecalculateCropTimeValues extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'crops:recalculate-time-values 
                            {--dry-run : Show what would be updated without making changes}
                            {--force : Skip confirmation prompt}
                            {--all : Recalculate all crops, not just those with missing values}';

    /**
     * The console command description.
     */
    protected $description = 'Recalculate missing time values for crops (stage_age_display, time_to_next_stage_display, total_age_display)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🌱 Recalculating crop time values...');
        $this->newLine();

        // Find crops to update based on options
        if ($this->option('all')) {
            // Recalculate all crops
            $cropsWithMissingValues = Crop::with(['recipe', 'currentStage']);
            $this->info('📊 Mode: Recalculating ALL crops');
        } else {
            // Find crops with missing or default time values
            $cropsWithMissingValues = Crop::where(function ($query) {
                $query->whereNull('stage_age_display')
                      ->orWhereNull('time_to_next_stage_display')
                      ->orWhereNull('total_age_display')
                      ->orWhere('stage_age_display', '')
                      ->orWhere('time_to_next_stage_display', '')
                      ->orWhere('total_age_display', '')
                      ->orWhere('stage_age_display', '0m')
                      ->orWhere('time_to_next_stage_display', 'Unknown')
                      ->orWhere('total_age_display', '0m');
            })->with(['recipe', 'currentStage']);
            $this->info('🔍 Mode: Recalculating crops with missing/default values');
        }

        $totalCount = $cropsWithMissingValues->count();

        if ($totalCount === 0) {
            $this->info('✅ No crops found with missing time values. All crops are up to date!');
            return 0;
        }

        $this->info("Found {$totalCount} crops with missing time values:");
        $this->newLine();

        // Show preview of affected crops
        $preview = $cropsWithMissingValues->take(5)->get();
        $headers = ['ID', 'Tray', 'Stage', 'Stage Age', 'Time to Next', 'Total Age'];
        
        $rows = $preview->map(function ($crop) {
            return [
                $crop->id,
                $crop->tray_number ?? 'N/A',
                $crop->currentStage?->name ?? 'Unknown',
                $crop->stage_age_display ?: '❌ Missing',
                $crop->time_to_next_stage_display ?: '❌ Missing',
                $crop->total_age_display ?: '❌ Missing',
            ];
        });

        $this->table($headers, $rows);
        
        if ($totalCount > 5) {
            $this->info("... and " . ($totalCount - 5) . " more crops");
        }
        $this->newLine();

        if ($this->option('dry-run')) {
            $this->info('🔍 DRY RUN: No changes will be made');
            return 0;
        }

        // Confirm before proceeding
        if (!$this->option('force')) {
            if (!$this->confirm("Proceed with recalculating time values for {$totalCount} crops?")) {
                $this->info('Operation cancelled.');
                return 0;
            }
        }

        // Process crops in batches to avoid memory issues
        $timeCalculator = app(CropTimeCalculator::class);
        $updated = 0;
        $failed = 0;
        $batchSize = 100;

        // DON'T enable bulk operation mode - we need the Observer to run to trigger calculations
        // Instead, we'll use the time calculator directly
        
        $this->info('🔄 Processing crops in batches...');
        $progressBar = $this->output->createProgressBar($totalCount);

        $cropsWithMissingValues->chunk($batchSize, function ($crops) use ($timeCalculator, &$updated, &$failed, $progressBar) {
            foreach ($crops as $crop) {
                try {
                    // Use transaction for each crop to ensure data consistency
                    DB::transaction(function () use ($crop, $timeCalculator) {
                        // Calculate time values directly
                        $timeCalculator->updateTimeCalculations($crop);
                        
                        // Force save without triggering events to avoid double calculation
                        // The time values are already set by updateTimeCalculations
                        $crop->saveQuietly();
                    });

                    $updated++;
                    $progressBar->advance();

                } catch (\Exception $e) {
                    $failed++;
                    $this->error("\nFailed to update crop {$crop->id}: " . $e->getMessage());
                    $progressBar->advance();
                }
            }
        });

        $progressBar->finish();
        $this->newLine(2);

        // Show results
        if ($updated > 0) {
            $this->info("✅ Successfully updated {$updated} crops");
        }
        if ($failed > 0) {
            $this->error("❌ Failed to update {$failed} crops");
        }

        $this->newLine();
        $this->info('🎉 Time value recalculation complete!');

        return $failed > 0 ? 1 : 0;
    }
}