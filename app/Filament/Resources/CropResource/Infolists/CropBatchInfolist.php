<?php

namespace App\Filament\Resources\CropResource\Infolists;

use App\Models\RecipeOptimizedView;
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
        ];
    }

    /**
     * Get variety name from recipe
     */
    protected static function getVarietyName($record): string
    {
        if ($record->recipe_id) {
            $recipe = RecipeOptimizedView::find($record->recipe_id);
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
        
        // Get current stage info from the crop/batch 
        $currentStageName = $firstCrop->currentStage?->name ?? 'Unknown';
        $currentStageCode = $firstCrop->currentStage?->code ?? 'unknown';
        
        // Use existing stage age calculation from the model
        $stageAge = $record->stage_age_display ?? '';
        
        // Build simple timeline based on current stage
        $timeline = [
            'soaking' => ['name' => 'Soaking', 'status' => 'n/a'],
            'germination' => ['name' => 'Germination', 'status' => 'TBD'],
            'blackout' => ['name' => 'Blackout', 'status' => 'n/a'],
            'light' => ['name' => 'Light', 'status' => 'TBD'],
            'harvested' => ['name' => 'Harvested', 'status' => 'TBD']
        ];
        
        // Update based on current stage
        switch($currentStageCode) {
            case 'germination':
                $timeline['germination']['status'] = 'current (' . $stageAge . ' elapsed)';
                break;
            case 'blackout':
                $timeline['germination']['status'] = 'completed';
                $timeline['blackout']['status'] = 'current (' . $stageAge . ' elapsed)';
                break;
            case 'light':
                $timeline['germination']['status'] = 'completed';
                $timeline['blackout']['status'] = 'completed';
                $timeline['light']['status'] = 'current (' . $stageAge . ' elapsed)';
                break;
            case 'harvested':
                $timeline['germination']['status'] = 'completed';
                $timeline['blackout']['status'] = 'completed';
                $timeline['light']['status'] = 'completed';
                $timeline['harvested']['status'] = 'current (' . $stageAge . ' elapsed)';
                break;
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
        } elseif ($status === 'completed') {
            return 'text-green-600 dark:text-green-400';
        }
        
        // Default for n/a and TBD
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
}