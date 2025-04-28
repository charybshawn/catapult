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

    public function getCropsNeedingAction(): array
    {
        $stages = [
            'seeded' => [
                'title' => 'Seeded Crops',
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
            'growing' => [
                'title' => 'Growing Crops',
                'icon' => 'sun',
                'color' => 'success',
                'crops' => [],
            ],
        ];

        // Get all active crops (not harvested)
        $crops = Crop::whereNotIn('current_stage', ['harvested'])
            ->with(['recipe.seedVariety'])
            ->get();

        foreach ($crops as $crop) {
            $variety = $crop->recipe && $crop->recipe->seedVariety ? $crop->recipe->seedVariety->name : 'Unknown Variety';
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
            
            if ($crop->current_stage === 'germination') {
                // Crop is in germination stage
                $recommendedDays = $crop->recipe ? $crop->recipe->germination_days : 3;
                $overdue = $now->diffInHours($stageStartTime) > ($recommendedDays * 24);
                
                if ($now->diffInHours($stageStartTime) >= ($recommendedDays * 24)) {
                    $stages['seeded']['crops'][] = [
                        'id' => $crop->id,
                        'variety' => $variety,
                        'tray' => $trayName,
                        'time_in_stage' => $timeInStage,
                        'recommended_days' => $recommendedDays,
                        'overdue' => $overdue,
                    ];
                }
            } elseif ($crop->current_stage === 'light') {
                // Crop is in light/growing stage
                $recommendedDays = $crop->recipe ? $crop->recipe->light_days : 7;
                $overdue = $now->diffInHours($stageStartTime) > ($recommendedDays * 24);
                
                if ($now->diffInHours($stageStartTime) >= ($recommendedDays * 24)) {
                    $stages['growing']['crops'][] = [
                        'id' => $crop->id,
                        'variety' => $variety,
                        'tray' => $trayName,
                        'time_in_stage' => $timeInStage,
                        'recommended_days' => $recommendedDays,
                        'overdue' => $overdue,
                    ];
                }
            } elseif ($crop->current_stage === 'blackout') {
                // Crop is in blackout stage
                $recommendedDays = $crop->recipe ? $crop->recipe->blackout_days : 3;
                
                // Calculate hours difference for more precision
                $recommendedHours = $recommendedDays * 24;
                $hoursInStage = $now->diffInHours($stageStartTime);
                $hoursDifference = $recommendedHours - $hoursInStage;
                
                // Include in alerts if within 24 hours of recommended transition
                if ($hoursDifference <= 24 && $hoursDifference >= 0) {
                    $stages['blackout']['crops'][] = [
                        'id' => $crop->id,
                        'variety' => $variety,
                        'tray' => $trayName,
                        'time_in_stage' => $timeInStage,
                        'recommended_days' => $recommendedDays,
                        'overdue' => false,
                    ];
                }
            } elseif ($crop->current_stage === 'planting') {
                // Crop is in planting stage - typically this is a short stage (1 day)
                $recommendedDays = 1;
                $overdue = $now->diffInHours($stageStartTime) > ($recommendedDays * 24);
                
                // Always include planting stage crops
                $stages['seeded']['crops'][] = [
                    'id' => $crop->id,
                    'variety' => $variety,
                    'tray' => $trayName,
                    'time_in_stage' => $timeInStage,
                    'recommended_days' => $recommendedDays,
                    'overdue' => $overdue,
                ];
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
} 