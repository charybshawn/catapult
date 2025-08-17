<?php

namespace App\Filament\Resources\CropResource\Infolists;

use App\Models\Recipe;
use App\Models\CropStageHistory;
use App\Models\Setting;
use App\Models\Crop;
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
                                $varietyService = app(\App\Services\RecipeVarietyService::class);
                                $recipe = $record->recipe_id ? \App\Models\Recipe::find($record->recipe_id) : null;
                                return $varietyService->getFullVarietyName($recipe);
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
            
            // Debug section - only show when debug mode is enabled
            ...static::getDebugSection(),
        ];
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

    /**
     * Get debug section components
     */
    protected static function getDebugSection(): array
    {
        // Only show debug section if debug mode is enabled
        if (!Setting::getValue('debug_mode_enabled', false)) {
            return [];
        }

        return [
            Components\Section::make('🔧 Debug Information')
                ->collapsed(true)
                ->description('Raw database information and relationships (Debug Mode)')
                ->schema([
                    Components\TextEntry::make('debug_raw_data')
                        ->label('')
                        ->html()
                        ->getStateUsing(function ($record) {
                            return static::generateDebugData($record);
                        }),
                ]),
        ];
    }

    /**
     * Generate minimalist debug data display
     */
    protected static function generateDebugData($record): string
    {
        $html = '<div class="font-mono text-xs space-y-4">';
        
        // Raw CropBatch data
        $html .= '<div>';
        $html .= '<h4 class="font-bold text-sm mb-2 text-gray-800 dark:text-gray-200">crop_batches (ID: ' . $record->id . ')</h4>';
        $html .= '<div class="bg-gray-50 dark:bg-gray-800 p-3 rounded border">';
        $html .= static::formatTableData($record->toArray());
        $html .= '</div>';
        $html .= '</div>';

        // Related Crops data
        $crops = Crop::where('crop_batch_id', $record->id)->get();
        if ($crops->isNotEmpty()) {
            $html .= '<div>';
            $html .= '<h4 class="font-bold text-sm mb-2 text-gray-800 dark:text-gray-200">crops (' . $crops->count() . ' records)</h4>';
            foreach ($crops as $crop) {
                $html .= '<div class="bg-gray-50 dark:bg-gray-800 p-3 rounded border mb-2">';
                $html .= '<div class="text-xs text-blue-600 dark:text-blue-400 mb-1">ID: ' . $crop->id . '</div>';
                $html .= static::formatTableData($crop->toArray());
                $html .= '</div>';
            }
            $html .= '</div>';
        }

        // Recipe data
        if ($record->recipe_id) {
            $recipe = Recipe::find($record->recipe_id);
            if ($recipe) {
                $html .= '<div>';
                $html .= '<h4 class="font-bold text-sm mb-2 text-gray-800 dark:text-gray-200">recipes (ID: ' . $recipe->id . ')</h4>';
                $html .= '<div class="bg-gray-50 dark:bg-gray-800 p-3 rounded border">';
                $html .= static::formatTableData($recipe->toArray());
                $html .= '</div>';
                $html .= '</div>';
            }
        }

        // Stage History data
        $stageHistory = CropStageHistory::where('crop_batch_id', $record->id)
            ->with(['stage', 'createdBy'])
            ->orderBy('entered_at', 'asc')
            ->get();
        
        if ($stageHistory->isNotEmpty()) {
            $html .= '<div>';
            $html .= '<h4 class="font-bold text-sm mb-2 text-gray-800 dark:text-gray-200">crop_stage_history (' . $stageHistory->count() . ' records)</h4>';
            foreach ($stageHistory as $history) {
                $html .= '<div class="bg-gray-50 dark:bg-gray-800 p-3 rounded border mb-2">';
                $html .= '<div class="text-xs text-blue-600 dark:text-blue-400 mb-1">ID: ' . $history->id . '</div>';
                $html .= static::formatTableData($history->toArray());
                $html .= '</div>';
            }
            $html .= '</div>';
        }

        $html .= '</div>';
        
        return $html;
    }

    /**
     * Format table data as key-value pairs
     */
    protected static function formatTableData(array $data): string
    {
        $html = '<div class="space-y-1">';
        
        foreach ($data as $key => $value) {
            // Skip large computed fields and relations
            if (in_array($key, ['stage_age_display', 'time_to_next_stage_display', 'total_age_display', 
                                 'tray_numbers_array', 'current_stage_name', 'current_stage_color'])) {
                continue;
            }
            
            $html .= '<div class="flex">';
            $html .= '<span class="text-gray-600 dark:text-gray-400 w-40 flex-shrink-0">' . htmlspecialchars($key) . ':</span>';
            
            if (is_null($value)) {
                $html .= '<span class="text-gray-400">NULL</span>';
            } elseif (is_bool($value)) {
                $html .= '<span class="text-blue-600">' . ($value ? 'true' : 'false') . '</span>';
            } elseif (is_array($value) || is_object($value)) {
                $html .= '<span class="text-purple-600">' . htmlspecialchars(json_encode($value)) . '</span>';
            } else {
                $displayValue = strlen((string)$value) > 50 ? substr((string)$value, 0, 50) . '...' : (string)$value;
                $html .= '<span class="text-gray-800 dark:text-gray-200">' . htmlspecialchars($displayValue) . '</span>';
            }
            
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
}