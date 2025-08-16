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
                        
                    Components\TextEntry::make('germination_at')
                        ->label('Germination Date')
                        ->getStateUsing(function ($record) {
                            return static::formatGerminationDate($record);
                        }),
                        
                    Components\TextEntry::make('expected_harvest_at')
                        ->label('Expected Harvest')
                        ->getStateUsing(function ($record) {
                            return static::formatExpectedHarvestDate($record);
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
                ->collapsed(false)
                ->schema([
                    Components\TextEntry::make('stage_history')
                        ->label('')
                        ->html()
                        ->getStateUsing(function ($record) {
                            return static::generateStageHistory($record);
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
     * Format germination date for display
     */
    protected static function formatGerminationDate($record): string
    {
        if ($record->germination_at) {
            $date = is_string($record->germination_at) ? Carbon::parse($record->germination_at) : $record->germination_at;
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
     * Generate tray number badges HTML
     */
    protected static function generateTrayNumberBadges($record): string
    {
        // Handle both array and string formats
        $trayNumbers = $record->tray_numbers ?? $record->tray_numbers_array ?? [];
        
        // Convert to array if it's a string
        if (is_string($trayNumbers)) {
            $trayNumbers = explode(', ', $trayNumbers);
        }
        
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
            ->get()
            ->filter(function ($history) {
                // Skip cancelled stages (entered and exited at the same time)
                if ($history->exited_at && $history->entered_at->equalTo($history->exited_at)) {
                    return false;
                }
                return true;
            });
            
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
            
            // Remove "By:" section for cleaner display
            $html .= '</div>';
            
            // Duration
            $html .= '<div class="text-right">';
            $html .= '<span class="text-sm font-medium text-gray-700 dark:text-gray-300">';
            $html .= $history->duration_display ?? 'In Progress';
            $html .= '</span>';
            $html .= '</div>';
            
            $html .= '</div>';
            
            // Skip notes for backfilled entries to keep display clean
            if ($history->notes && !str_contains($history->notes, 'Batch-level stage history')) {
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