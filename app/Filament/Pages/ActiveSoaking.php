<?php

namespace App\Filament\Pages;

use App\Models\Crop;
use App\Models\CropStage;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class ActiveSoaking extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-beaker';
    protected static ?string $navigationLabel = 'Active Soaking';
    protected static ?string $navigationGroup = 'Production';
    protected static ?string $title = 'Active Soaking';
    protected static string $view = 'filament.pages.active-soaking';
    protected static ?string $slug = 'active-soaking';

    // Auto-refresh every 5 minutes (in seconds)
    public $refreshInterval = 300;

    /**
     * Get all soaking batches (crops grouped by batch)
     */
    public function getActiveSoakingBatches(): Collection
    {
        $soakingStage = CropStage::findByCode('soaking');
        
        if (!$soakingStage) {
            return collect();
        }

        $crops = Crop::with(['recipe.masterCultivar.masterSeedCatalog', 'recipe.masterSeedCatalog', 'currentStage'])
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
     */
    public function getActiveSoakingCrops(): Collection
    {
        return $this->getActiveSoakingBatches();
    }

    /**
     * Enrich batch data with calculated soaking information
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
     */
    private function getVarietyName($recipe): string
    {
        if (!$recipe) {
            return 'Unknown Variety';
        }
        
        // Try to build variety name from recipe relationships
        $varietyService = app(\App\Services\RecipeVarietyService::class);
        return $varietyService->getFullVarietyName($recipe);
    }

    /**
     * Format tray numbers for display
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
     */
    public function getPollingInterval(): int
    {
        return $this->refreshInterval * 1000; // Convert to milliseconds
    }

    /**
     * Get header actions for the page
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
     * Get the navigation badge for the soaking page
     */
    public static function getNavigationBadge(): ?string
    {
        $batches = (new static())->getActiveSoakingBatches();
        $count = $batches->count();
        
        return $count > 0 ? (string) $count : null;
    }

    /**
     * Get the navigation badge color
     */
    public static function getNavigationBadgeColor(): ?string
    {
        $batches = (new static())->getActiveSoakingBatches();
        $overdueBatches = $batches->where('is_overdue', true)->count();
        
        if ($overdueBatches > 0) {
            return 'danger'; // Red for overdue batches
        }
        
        if ($batches->count() > 0) {
            return 'primary'; // Blue for active batches
        }
        
        return null;
    }

    /**
     * Determine if this page should be registered in navigation
     */
    public static function shouldRegisterNavigation(): bool
    {
        return true; // Always show in navigation
    }
}