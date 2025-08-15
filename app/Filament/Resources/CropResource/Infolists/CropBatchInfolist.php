<?php

namespace App\Filament\Resources\CropResource\Infolists;

use App\Models\Recipe;
use App\Models\CropStageHistory;
use Filament\Infolists\Components;
use Carbon\Carbon;

/**
 * Crop Batch Infolist Configuration
 * Returns Filament infolist components for crop batch display
 */
class CropBatchInfolist
{
    /**
     * Get infolist schema components
     */
    public static function schema(): array
    {
        return [
            Components\Section::make('Crop Details')
                ->schema([
                    Components\Group::make([
                        Components\TextEntry::make('variety')
                            ->label('')
                            ->weight('bold')
                            ->size('xl')
                            ->getStateUsing(function ($record) {
                                return static::getVarietyName($record);
                            }),
                        Components\TextEntry::make('recipe.name')
                            ->label('')
                            ->color('gray')
                            ->getStateUsing(fn ($record) => $record->recipe_name ?? 'Unknown Recipe'),
                    ])->columns(1),
                    
                    Components\Group::make([
                        Components\TextEntry::make('current_stage_name')
                            ->label('Status')
                            ->badge()
                            ->color(fn ($record) => $record->current_stage_color ?? 'gray'),
                        Components\TextEntry::make('crop_count')
                            ->label('Tray Count'),
                    ])->columns(2),
                    
                    Components\TextEntry::make('stage_age_display')
                        ->label('Time in Stage'),
                        
                    Components\TextEntry::make('time_to_next_stage_display')
                        ->label('Time to Next Stage'),
                        
                    Components\TextEntry::make('total_age_display')
                        ->label('Total Age'),
                        
                    Components\TextEntry::make('planting_at')
                        ->label('Planted Date')
                        ->getStateUsing(function ($record) {
                            return static::formatPlantingDate($record);
                        }),
                        
                    Components\TextEntry::make('expected_harvest_at')
                        ->label('Expected Harvest')
                        ->getStateUsing(function ($record) {
                            return static::formatExpectedHarvestDate($record);
                        }),
                ]),
                
            Components\Section::make('Stage Timeline')
                ->schema([
                    Components\TextEntry::make('stage_timeline')
                        ->label('')
                        ->html()
                        ->getStateUsing(function ($record) {
                            return static::generateStageTimeline($record);
                        }),
                ]),
                
            Components\Section::make('Tray Numbers')
                ->schema([
                    Components\TextEntry::make('tray_numbers')
                        ->label('')
                        ->html()
                        ->getStateUsing(function ($record) {
                            return static::generateTrayNumberBadges($record);
                        }),
                ]),
                
            Components\Section::make('Stage History')
                ->schema([
                    Components\TextEntry::make('stage_history')
                        ->label('')
                        ->html()
                        ->getStateUsing(function ($record) {
                            return static::generateStageHistory($record);
                        }),
                ])
                ->collapsed(),
        ];
    }

    /**
     * Get variety name from recipe
     */
    protected static function getVarietyName($record): string
    {
        if ($record->recipe_id) {
            $recipe = Recipe::find($record->recipe_id);
            if ($recipe) {
                // Extract just the variety part (before the lot number)
                $parts = explode(' - ', $recipe->name);
                if (count($parts) >= 2) {
                    return $parts[0] . ' - ' . $parts[1];
                }
                return $recipe->name;
            }
        }
        return 'Unknown';
    }

    /**
     * Format planting date for display
     */
    protected static function formatPlantingDate($record): string
    {
        if ($record->planting_at) {
            $date = is_string($record->planting_at) ? Carbon::parse($record->planting_at) : $record->planting_at;
            return $date->format('M j, Y g:i A');
        }
        return 'Unknown';
    }

    /**
     * Format expected harvest date for display
     */
    protected static function formatExpectedHarvestDate($record): string
    {
        if ($record->expected_harvest_at) {
            $date = is_string($record->expected_harvest_at) ? Carbon::parse($record->expected_harvest_at) : $record->expected_harvest_at;
            return $date->format('M j, Y');
        }
        return 'Not calculated';
    }

    /**
     * Generate stage timeline HTML
     */
    protected static function generateStageTimeline($record): string
    {
        // Get the first crop from the batch to get stage timings
        $firstCrop = $record->crops()->first();
            
        if (!$firstCrop) {
            return '<div class="text-gray-500 dark:text-gray-400">No crop data available</div>';
        }
        
        // Load relationships if not loaded
        if (!$firstCrop->relationLoaded('recipe')) {
            $firstCrop->load('recipe');
        }
        if (!$firstCrop->relationLoaded('currentStage')) {
            $firstCrop->load('currentStage');
        }
        
        // Get current stage info from the crop/batch 
        $currentStageCode = $firstCrop->currentStage?->code ?? 'unknown';
        
        // Build timeline based on actual crop data
        $timeline = [];
        
        // Soaking stage (if recipe requires it)
        if ($firstCrop->requires_soaking || ($firstCrop->recipe && $firstCrop->recipe->seed_soak_hours > 0)) {
            $timeline['soaking'] = [
                'name' => 'Soaking',
                'status' => $firstCrop->soaking_at ? 
                    ($currentStageCode === 'soaking' ? 'current' : 'completed') : 
                    'pending'
            ];
        } else {
            $timeline['soaking'] = ['name' => 'Soaking', 'status' => 'n/a'];
        }
        
        // Germination stage
        if ($firstCrop->germination_at) {
            $germStart = \Carbon\Carbon::parse($firstCrop->germination_at);
            if ($currentStageCode === 'germination') {
                $duration = $germStart->diffForHumans(now(), ['parts' => 2, 'syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE]);
                $timeline['germination'] = ['name' => 'Germination', 'status' => 'current (' . $duration . ')'];
            } else {
                $timeline['germination'] = ['name' => 'Germination', 'status' => 'completed'];
            }
        } else {
            $timeline['germination'] = ['name' => 'Germination', 'status' => 'pending'];
        }
        
        // Blackout stage (check if recipe uses blackout)
        $hasBlackout = $firstCrop->recipe && $firstCrop->recipe->blackout_days > 0;
        if ($hasBlackout) {
            if ($firstCrop->blackout_at) {
                $blackoutStart = \Carbon\Carbon::parse($firstCrop->blackout_at);
                if ($currentStageCode === 'blackout') {
                    $duration = $blackoutStart->diffForHumans(now(), ['parts' => 2, 'syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE]);
                    $timeline['blackout'] = ['name' => 'Blackout', 'status' => 'current (' . $duration . ')'];
                } else if (in_array($currentStageCode, ['light', 'harvested'])) {
                    $timeline['blackout'] = ['name' => 'Blackout', 'status' => 'completed'];
                } else {
                    $timeline['blackout'] = ['name' => 'Blackout', 'status' => 'pending'];
                }
            } else {
                $timeline['blackout'] = ['name' => 'Blackout', 'status' => $currentStageCode === 'blackout' ? 'current' : 'pending'];
            }
        } else {
            $timeline['blackout'] = ['name' => 'Blackout', 'status' => 'n/a'];
        }
        
        // Light stage
        if ($firstCrop->light_at || $currentStageCode === 'light') {
            if ($currentStageCode === 'light') {
                // Calculate duration from when light stage started
                $lightStart = $firstCrop->blackout_at ? 
                    \Carbon\Carbon::parse($firstCrop->blackout_at) : 
                    \Carbon\Carbon::parse($firstCrop->germination_at);
                $duration = $lightStart->diffForHumans(now(), ['parts' => 2, 'syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE]);
                $timeline['light'] = ['name' => 'Light', 'status' => 'current (' . $duration . ')'];
            } else if ($currentStageCode === 'harvested') {
                $timeline['light'] = ['name' => 'Light', 'status' => 'completed'];
            } else {
                $timeline['light'] = ['name' => 'Light', 'status' => 'pending'];
            }
        } else {
            $timeline['light'] = ['name' => 'Light', 'status' => 'pending'];
        }
        
        // Harvested stage
        if ($firstCrop->harvested_at) {
            $harvestTime = \Carbon\Carbon::parse($firstCrop->harvested_at);
            $timeline['harvested'] = ['name' => 'Harvested', 'status' => 'completed (' . $harvestTime->format('M j') . ')'];
        } else {
            $timeline['harvested'] = ['name' => 'Harvested', 'status' => 'pending'];
        }
        
        return static::buildTimelineHtml($timeline);
    }

    /**
     * Build the timeline HTML structure
     */
    protected static function buildTimelineHtml(array $timeline): string
    {
        $html = '<div class="space-y-1 text-sm">';
        
        foreach ($timeline as $stageCode => $stage) {
            $html .= '<div class="flex items-center gap-2">';
            
            // Stage name
            $html .= '<span class="font-medium text-gray-900 dark:text-gray-100 w-20">' . htmlspecialchars($stage['name']) . ':</span>';
            
            // Status with proper coloring
            $statusClass = static::getStatusClass($stage['status']);
            $html .= '<span class="' . $statusClass . '">' . htmlspecialchars($stage['status']) . '</span>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Get CSS class for stage status
     */
    protected static function getStatusClass(string $status): string
    {
        if (strpos($status, 'current') !== false) {
            return 'text-blue-600 dark:text-blue-400 font-medium';
        } elseif (strpos($status, 'completed') !== false) {
            return 'text-green-600 dark:text-green-400';
        } elseif ($status === 'pending') {
            return 'text-amber-600 dark:text-amber-400';
        }
        
        // Default for n/a
        return 'text-gray-500 dark:text-gray-400';
    }

    /**
     * Generate tray number badges HTML
     */
    protected static function generateTrayNumberBadges($record): string
    {
        $trayNumbers = $record->tray_numbers_array;
        
        $html = '<div class="flex flex-wrap gap-1">';
        foreach ($trayNumbers as $tray) {
            $html .= '<span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-1 text-xs font-medium text-gray-800 dark:bg-gray-800 dark:text-gray-200">' . htmlspecialchars($tray) . '</span>';
        }
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Generate stage history HTML
     */
    protected static function generateStageHistory($record): string
    {
        $stageHistory = CropStageHistory::where('crop_batch_id', $record->id)
            ->with(['stage', 'createdBy'])
            ->orderBy('entered_at', 'asc')
            ->get();
            
        if ($stageHistory->isEmpty()) {
            return '<div class="text-gray-500 dark:text-gray-400">No stage history available</div>';
        }
        
        $html = '<div class="space-y-3">';
        
        foreach ($stageHistory as $index => $history) {
            $isActive = $history->is_active;
            $bgColor = $isActive ? 'bg-blue-50 dark:bg-blue-900/20' : 'bg-gray-50 dark:bg-gray-800/50';
            
            $html .= '<div class="p-3 rounded-lg ' . $bgColor . '">';
            $html .= '<div class="flex items-start justify-between">';
            
            // Stage name and timing
            $html .= '<div>';
            $html .= '<div class="font-medium text-gray-900 dark:text-gray-100">';
            $html .= htmlspecialchars($history->stage->name);
            if ($isActive) {
                $html .= ' <span class="text-sm text-blue-600 dark:text-blue-400">(Current)</span>';
            }
            $html .= '</div>';
            
            $html .= '<div class="text-sm text-gray-600 dark:text-gray-400 mt-1">';
            $html .= 'Entered: ' . $history->entered_at->format('M j, Y g:i A');
            if ($history->exited_at) {
                $html .= '<br>Exited: ' . $history->exited_at->format('M j, Y g:i A');
            }
            $html .= '</div>';
            
            if ($history->createdBy) {
                $html .= '<div class="text-xs text-gray-500 dark:text-gray-500 mt-1">';
                $html .= 'By: ' . htmlspecialchars($history->createdBy->name);
                $html .= '</div>';
            }
            $html .= '</div>';
            
            // Duration
            $html .= '<div class="text-right">';
            $html .= '<span class="text-sm font-medium text-gray-700 dark:text-gray-300">';
            $html .= $history->duration_display ?? 'In Progress';
            $html .= '</span>';
            $html .= '</div>';
            
            $html .= '</div>';
            
            if ($history->notes) {
                $html .= '<div class="text-sm text-gray-500 dark:text-gray-400 mt-2">';
                $html .= htmlspecialchars($history->notes);
                $html .= '</div>';
            }
            
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
}