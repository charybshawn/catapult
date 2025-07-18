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
     * Get all crops currently in the soaking stage
     */
    public function getActiveSoakingCrops(): Collection
    {
        $soakingStage = CropStage::findByCode('soaking');
        
        if (!$soakingStage) {
            return collect();
        }

        return Crop::with(['recipe', 'currentStage'])
            ->where('current_stage_id', $soakingStage->id)
            ->where('requires_soaking', true)
            ->whereNotNull('soaking_at')
            ->whereNull('planting_at') // Still soaking, not planted yet
            ->orderBy('soaking_at')
            ->get()
            ->map(function ($crop) {
                return $this->enrichCropData($crop);
            });
    }

    /**
     * Enrich crop data with calculated soaking information
     */
    private function enrichCropData(Crop $crop): object
    {
        $soakingDuration = $crop->getSoakingDuration(); // in minutes
        $timeRemaining = $crop->getSoakingTimeRemaining(); // in minutes
        $elapsedMinutes = $crop->soaking_at->diffInMinutes(Carbon::now());
        
        $isOverdue = $timeRemaining !== null && $timeRemaining <= 0;
        $status = $isOverdue ? 'overdue' : 'on-time';
        
        return (object) [
            'id' => $crop->id,
            'recipe_name' => $crop->recipe->name ?? 'Unknown Recipe',
            'variety_name' => $crop->variety_name ?? 'Unknown Variety',
            'tray_number' => $crop->tray_number,
            'tray_count' => $crop->tray_count ?? 1,
            'soaking_started_at' => $crop->soaking_at,
            'soaking_duration_minutes' => $soakingDuration,
            'time_remaining_minutes' => $timeRemaining,
            'elapsed_minutes' => $elapsedMinutes,
            'status' => $status,
            'is_overdue' => $isOverdue,
            'formatted_start_time' => $crop->soaking_at->format('M j, Y g:i A'),
            'formatted_elapsed_time' => $this->formatMinutesToHoursMinutes($elapsedMinutes),
            'formatted_remaining_time' => $timeRemaining !== null ? $this->formatMinutesToHoursMinutes($timeRemaining) : 'Unknown',
            'formatted_total_duration' => $soakingDuration ? $this->formatMinutesToHoursMinutes($soakingDuration) : 'Unknown',
            'progress_percentage' => $soakingDuration > 0 ? min(100, ($elapsedMinutes / $soakingDuration) * 100) : 0,
        ];
    }

    /**
     * Format minutes to human-readable hours and minutes
     */
    private function formatMinutesToHoursMinutes(int $minutes): string
    {
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
        $crops = $this->getActiveSoakingCrops();
        
        return [
            'crops' => $crops,
            'total_crops' => $crops->count(),
            'overdue_crops' => $crops->where('is_overdue', true)->count(),
            'on_time_crops' => $crops->where('is_overdue', false)->count(),
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
}