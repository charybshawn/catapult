<?php

namespace App\Filament\Pages;

use App\Services\RecipeVarietyService;
use App\Models\Crop;
use App\Models\CropStage;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

/**
 * Active Soaking Monitoring Page
 * 
 * Agricultural operations dashboard page for monitoring active seed soaking batches
 * in microgreens production. Provides real-time tracking of soaking durations,
 * overdue alerts, and batch progress for production staff.
 * 
 * @filament_page Custom page for production monitoring
 * @agricultural_context Microgreens require precise soaking times before germination
 * @business_workflow Part of crop lifecycle management from soaking → germination → harvest
 * @ui_features Auto-refresh every 5 minutes, overdue alerts, batch grouping
 * 
 * @package App\Filament\Pages
 * @author Catapult Development Team
 * @version 1.0.0
 */
class ActiveSoaking extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-beaker';
    protected static ?string $navigationLabel = 'Active Soaking';
    protected static string | \UnitEnum | null $navigationGroup = 'Production';
    protected static ?string $title = 'Active Soaking';
    protected string $view = 'filament.pages.active-soaking';
    protected static ?string $slug = 'active-soaking';

    /**
     * Auto-refresh interval for real-time monitoring
     * 
     * @var int Refresh interval in seconds (300 = 5 minutes)
     * @agricultural_timing Balances real-time monitoring with server load
     */
    public $refreshInterval = 300;

    /**
     * Get all soaking batches (crops grouped by batch)
     * 
     * Retrieves and groups all crops currently in soaking stage for batch monitoring.
     * Groups crops by recipe, soaking start time, and current stage to create logical
     * production batches with calculated timing and progress information.
     * 
     * @agricultural_workflow Critical for monitoring seed soaking timing in production
     * @business_logic Groups trays into batches based on recipe and timing
     * @performance Uses eager loading to prevent N+1 queries on recipe relationships
     * 
     * @return Collection<object> Collection of enriched batch objects with timing data
     * @throws \Exception If crop stage lookup fails
     */
    public function getActiveSoakingBatches(): Collection
    {
        $soakingStage = CropStage::findByCode('soaking');
        
        if (!$soakingStage) {
            return collect();
        }

        $crops = Crop::with(['recipe', 'currentStage'])
            ->where('current_stage_id', $soakingStage->id)
            ->where('requires_soaking', true)
            ->whereNotNull('soaking_at')
            ->orderBy('soaking_at')
            ->get();

        // Group crops by batch (recipe_id + soaking_at date + current_stage_id)
        $batches = $crops->groupBy(function ($crop) {
            return $crop->recipe_id . '_' . $crop->soaking_at->format('Y-m-d_H:i') . '_' . $crop->current_stage_id;
        });

        return $batches->map(function ($batchCrops, $batchKey) {
            return $this->enrichBatchData($batchCrops);
        })->values();
    }

    /**
     * Get all crops currently in the soaking stage (kept for backward compatibility)
     * 
     * Maintained for backward compatibility with existing views and components.
     * Delegates to getActiveSoakingBatches() for consistent data structure.
     * 
     * @deprecated Use getActiveSoakingBatches() instead
     * @legacy_compatibility Maintained for template compatibility
     * 
     * @return Collection<object> Collection of batch objects (same as getActiveSoakingBatches)
     */
    public function getActiveSoakingCrops(): Collection
    {
        return $this->getActiveSoakingBatches();
    }

    /**
     * Enrich batch data with calculated soaking information
     * 
     * Processes a collection of crops in the same batch to calculate timing data,
     * progress percentages, and agricultural metrics for production monitoring.
     * Creates comprehensive batch summary with overdue detection and formatting.
     * 
     * @agricultural_calculations Computes soaking duration, elapsed time, remaining time
     * @business_rules Detects overdue batches based on recipe soaking requirements
     * @ui_formatting Provides formatted time displays and progress percentages
     * 
     * @param Collection $batchCrops Crops in same batch (recipe + timing)
     * @return object Enriched batch object with calculated fields
     */
    private function enrichBatchData(Collection $batchCrops): object
    {
        $firstCrop = $batchCrops->first();
        $recipe = $firstCrop->recipe;
        
        // Calculate soaking duration and timing directly to avoid filtering logic issues
        $soakingDurationMinutes = $recipe && $recipe->seed_soak_hours ? $recipe->seed_soak_hours * 60 : null;
        $elapsedMinutes = $firstCrop->soaking_at->diffInMinutes(Carbon::now());
        
        // Calculate time remaining directly
        $timeRemaining = null;
        if ($soakingDurationMinutes !== null) {
            $timeRemaining = $soakingDurationMinutes - $elapsedMinutes;
        }
        
        $isOverdue = $timeRemaining !== null && $timeRemaining < 0;
        $status = $isOverdue ? 'overdue' : 'on-time';
        
        $trayCount = $batchCrops->count();
        $trayNumbers = $batchCrops->pluck('tray_number')->sort()->values()->toArray();
        
        // Calculate total seed quantity
        $seedPerTray = $recipe->seed_density_grams_per_tray ?? 0;
        $totalSeedQuantity = $seedPerTray * $trayCount;
        
        return (object) [
            'batch_id' => $firstCrop->recipe_id . '_' . $firstCrop->soaking_at->format('Y-m-d_H:i'),
            'recipe_id' => $firstCrop->recipe_id,
            'recipe_name' => $recipe->name ?? 'Unknown Recipe',
            'variety_name' => $this->getVarietyName($recipe),
            'tray_count' => $trayCount,
            'tray_numbers' => $trayNumbers,
            'tray_numbers_formatted' => $this->formatTrayNumbers($trayNumbers),
            'seed_quantity_per_tray' => $seedPerTray,
            'total_seed_quantity' => $totalSeedQuantity,
            'soaking_started_at' => $firstCrop->soaking_at,
            'soaking_duration_minutes' => $soakingDurationMinutes,
            'time_remaining_minutes' => $timeRemaining,
            'elapsed_minutes' => $elapsedMinutes,
            'status' => $status,
            'is_overdue' => $isOverdue,
            'formatted_start_time' => $firstCrop->soaking_at->format('M j, Y g:i A'),
            'formatted_elapsed_time' => $this->formatMinutesToHoursMinutes($elapsedMinutes),
            'formatted_remaining_time' => $timeRemaining !== null ? $this->formatMinutesToHoursMinutes($timeRemaining) : 'Unknown',
            'formatted_total_duration' => $soakingDurationMinutes ? $this->formatMinutesToHoursMinutes($soakingDurationMinutes) : 'Unknown',
            'progress_percentage' => $soakingDurationMinutes > 0 ? min(100, ($elapsedMinutes / $soakingDurationMinutes) * 100) : 0,
            'crop_ids' => $batchCrops->pluck('id')->toArray(),
        ];
    }

    /**
     * Get variety name from recipe
     * 
     * Extracts or builds variety name from recipe relationships using RecipeVarietyService.
     * Handles missing recipe data gracefully and provides fallback naming.
     * 
     * @agricultural_context Variety naming critical for production identification
     * @service_integration Uses RecipeVarietyService for consistent variety names
     * 
     * @param mixed $recipe Recipe model instance or null
     * @return string Full variety name or 'Unknown Variety' fallback
     */
    private function getVarietyName($recipe): string
    {
        if (!$recipe) {
            return 'Unknown Variety';
        }
        
        // Try to build variety name from recipe relationships
        $varietyService = app(RecipeVarietyService::class);
        return $varietyService->getFullVarietyName($recipe);
    }

    /**
     * Format tray numbers for display
     * 
     * Formats tray number arrays for compact UI display. Shows individual numbers
     * for small batches, truncated with count for large batches.
     * 
     * @ui_formatting Optimized for dashboard space constraints
     * @display_logic Shows first 2 numbers + count for batches > 3 trays
     * 
     * @param array $trayNumbers Array of tray numbers (integers)
     * @return string Formatted tray numbers (e.g., "1, 2, 3" or "1, 2, ... +5 more")
     */
    private function formatTrayNumbers(array $trayNumbers): string
    {
        if (empty($trayNumbers)) {
            return 'No trays';
        }
        
        if (count($trayNumbers) <= 3) {
            return implode(', ', $trayNumbers);
        }
        
        return implode(', ', array_slice($trayNumbers, 0, 2)) . ', ... +' . (count($trayNumbers) - 2) . ' more';
    }

    /**
     * Format minutes to human-readable hours and minutes
     * 
     * Converts minute values to human-readable time formats for UI display.
     * Handles negative values (overdue) with appropriate prefixes and formatting.
     * 
     * @ui_formatting Provides intuitive time displays for production staff
     * @agricultural_timing Critical for monitoring soaking overdue conditions
     * 
     * @param int|null $minutes Time in minutes (negative for overdue)
     * @return string Formatted time (e.g., "2h 30m", "45m", "Overdue by 1h 15m")
     */
    private function formatMinutesToHoursMinutes(?int $minutes): string
    {
        if ($minutes === null) {
            return 'Unknown';
        }
        
        if ($minutes < 0) {
            $minutes = abs($minutes);
            $prefix = 'Overdue by ';
        } else {
            $prefix = '';
        }

        if ($minutes < 60) {
            return $prefix . $minutes . 'm';
        }

        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;

        if ($remainingMinutes === 0) {
            return $prefix . $hours . 'h';
        }

        return $prefix . $hours . 'h ' . $remainingMinutes . 'm';
    }

    /**
     * Get view data for the template
     * 
     * Compiles comprehensive data structure for the soaking monitoring template.
     * Separates overdue and on-time batches for UI organization and calculates
     * summary statistics for dashboard display.
     * 
     * @template_data Primary data provider for active-soaking blade template
     * @dashboard_metrics Provides summary counts and last updated timestamp
     * @agricultural_organization Separates batches by timing status for workflow
     * 
     * @return array Template data with batches, counts, and metadata
     */
    public function getViewData(): array
    {
        $batches = $this->getActiveSoakingBatches();
        
        // Separate overdue and on-time batches
        $overdueBatches = $batches->where('is_overdue', true);
        $onTimeBatches = $batches->where('is_overdue', false);
        
        // Calculate totals across all batches
        $totalTrays = $batches->sum('tray_count');
        
        return [
            'crops' => $batches, // Keep same name for template compatibility
            'batches' => $batches,
            'overdue_batches' => $overdueBatches,
            'on_time_batches' => $onTimeBatches,
            'total_batches' => $batches->count(),
            'total_crops' => $totalTrays, // Total trays across all batches
            'overdue_crops' => $overdueBatches->count(),
            'on_time_crops' => $onTimeBatches->count(),
            'last_updated' => Carbon::now()->format('M j, Y g:i A'),
        ];
    }

    /**
     * Get the polling interval for auto-refresh
     * 
     * Returns polling interval in milliseconds for JavaScript auto-refresh functionality.
     * Converts internal refresh interval from seconds to milliseconds for frontend.
     * 
     * @frontend_integration Provides polling interval for JavaScript auto-refresh
     * @realtime_monitoring Ensures dashboard stays current with production status
     * 
     * @return int Polling interval in milliseconds (300000 = 5 minutes)
     */
    public function getPollingInterval(): int
    {
        return $this->refreshInterval * 1000; // Convert to milliseconds
    }

    /**
     * Get header actions for the page
     * 
     * Defines header actions available on the soaking monitoring page.
     * Provides manual refresh capability for immediate data updates.
     * 
     * @filament_actions Standard Filament header action configuration
     * @ui_controls Manual refresh for immediate status updates
     * 
     * @return array Array of Filament Action objects for page header
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Refresh')
                ->icon('heroicon-o-arrow-path')
                ->action(function () {
                    // This will trigger a page refresh
                    $this->redirect(request()->header('Referer'));
                }),
        ];
    }

    /**
     * Determine if this page should be registered in navigation
     * 
     * Controls whether this page appears in Filament navigation menu.
     * Currently disabled to prevent unnecessary database queries on navigation load.
     * 
     * @filament_navigation Navigation visibility control
     * @performance Disabled to avoid soaking queries on every page load
     * 
     * @return bool False to exclude from navigation menu
     */
    public static function shouldRegisterNavigation(): bool
    {
        return false; // Disable navigation to prevent unnecessary queries
    }
}