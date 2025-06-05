<?php

namespace App\Filament\Widgets;

use App\Models\Crop;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;

class GroupedCropAlertsWidget extends Widget
{
    protected static string $view = 'filament.widgets.grouped-crop-alerts-widget';
    
    // Set the default widget positioning
    protected static ?int $sort = 1;
    
    // Will be loaded at the top of the dashboard
    protected static ?string $section = 'header';
    
    // Refresh interval in seconds (1 hour)
    protected static ?string $pollingInterval = '3600s';
    
    // Make widget span full width
    protected int | string | array $columnSpan = 'full';

    public function getCropsNeedingAction(): array
    {
        $stages = [
            'germination' => [
                'title' => 'Germination Stage',
                'icon' => 'sparkles',
                'color' => 'primary',
                'crops' => [],
            ],
            'blackout' => [
                'title' => 'Blackout Stage',
                'icon' => 'moon',
                'color' => 'warning',
                'crops' => [],
            ],
            'light' => [
                'title' => 'Light Stage',
                'icon' => 'sun',
                'color' => 'success',
                'crops' => [],
            ],
        ];

        // Get all active crops (not harvested) with their relationships
        $crops = Crop::whereNotIn('current_stage', ['harvested'])
            ->with(['recipe.seedCultivar', 'recipe'])
            ->get();

        foreach ($crops as $crop) {
            // Get the variety name, falling back to recipe name if variety is not available
            $variety = 'Unknown';
            if ($crop->recipe) {
                if ($crop->recipe->seedCultivar) {
                    $variety = $crop->recipe->seedCultivar->name;
                } else if ($crop->recipe->name) {
                    $variety = $crop->recipe->name;
                }
            }
            
            $trayName = $crop->tray_number ?? 'No Tray';
            
            // Get the timestamp for the current stage
            $stageField = "{$crop->current_stage}_at";
            $stageStartTime = $crop->$stageField;
            
            if (!$stageStartTime) {
                continue; // Skip if no timestamp
            }
            
            // Get more precise time measurements
            $now = Carbon::now();
            $diff = $now->diff($stageStartTime);
            $daysInStage = $diff->d;
            $hoursInStage = $diff->h;
            $minutesInStage = $diff->i;
            
            // Format the time display
            $timeInStage = $this->formatTimeDisplay($daysInStage, $hoursInStage, $minutesInStage);
            
            // Determine the target stage based on current stage
            $targetStage = $this->getTargetStage($crop->current_stage);
            
            // Get recommended days based on current stage
            $recommendedDays = match($crop->current_stage) {
                'germination' => $crop->recipe ? $crop->recipe->germination_days : 3,
                'blackout' => $crop->recipe ? $crop->recipe->blackout_days : 3,
                'light' => $crop->recipe ? $crop->recipe->light_days : 7,
                'planting' => 1,
                default => 0
            };
            
            // Calculate if overdue
            $overdue = $now->diffInHours($stageStartTime) > ($recommendedDays * 24);
            
            // Add crop to its current stage group
            $cropData = [
                'id' => $crop->id,
                'variety' => $variety,
                'tray' => $trayName,
                'time_in_stage' => $timeInStage,
                'recommended_days' => $recommendedDays,
                'overdue' => $overdue,
                'target_stage' => $targetStage,
            ];
            
            // Group by current stage
            if (isset($stages[$crop->current_stage])) {
                $stages[$crop->current_stage]['crops'][] = $cropData;
            }
        }

        return $stages;
    }

    private function formatTimeDisplay($days, $hours, $minutes)
    {
        // If all values are 0, show "Just started"
        if ($days === 0 && $hours === 0 && $minutes === 0) {
            return "Just started";
        }
        
        $timeInStage = '';
        if ($days > 0) {
            $timeInStage .= $days . 'd ';
        }
        if ($hours > 0 || $days > 0) {
            $timeInStage .= $hours . 'h ';
        }
        $timeInStage .= $minutes . 'm';
        
        return trim($timeInStage);
    }

    private function getTargetStage(string $currentStage): string
    {
        return match($currentStage) {
            'planting' => 'Germination',
            'germination' => 'Blackout',
            'blackout' => 'Light',
            'light' => 'Harvest',
            default => 'Unknown'
        };
    }
} 