<?php

namespace App\Filament\Resources\CropAlertResource\Tables;

use App\Models\Crop;
use App\Models\CropAlert;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Illuminate\Database\Eloquent\Builder;

class CropAlertTable
{
    /**
     * Configure the table query
     */
    public static function modifyQuery(Builder $query): Builder
    {
        // Since CropAlert uses JSON conditions to store crop_id, 
        // we can't use traditional eager loading
        return $query;
    }

    /**
     * Get table columns for CropAlertResource
     */
    public static function columns(): array
    {
        return [
            static::getAlertTypeColumn(),
            static::getTrayNumberColumn(),
            static::getVarietyColumn(),
            static::getSeedVarietyColumn(),
            static::getTargetStageColumn(),
            static::getNextRunAtColumn(),
            static::getTimeUntilColumn(),
            static::getCropIdColumn(),
        ];
    }

    /**
     * Get table filters for CropAlertResource
     */
    public static function filters(): array
    {
        return [
            SelectFilter::make('target_stage')
                ->label('Target Stage')
                ->options([
                    'germination' => 'Germination',
                    'blackout' => 'Blackout',
                    'light' => 'Light',
                    'harvested' => 'Harvested',
                ])
                ->query(function ($query, array $data) {
                    if (isset($data['value'])) {
                        return $query->whereRaw("json_extract(conditions, '$.target_stage') = ?", [$data['value']]);
                    }
                    return $query;
                }),
        ];
    }

    /**
     * Get table groups for CropAlertResource
     */
    public static function groups(): array
    {
        return [
            Group::make('tray_number')
                ->label('Tray Number')
                ->getTitleFromRecordUsing(function ($record) {
                    return $record->conditions['tray_number'] ?? 'Unknown';
                })
                ->orderQueryUsing(fn (Builder $query, string $direction): Builder => 
                    $query->orderByRaw("json_extract(conditions, '$.tray_number') {$direction}")
                ),
            Group::make('target_stage')
                ->label('Target Stage')
                ->getTitleFromRecordUsing(function ($record) {
                    return ucfirst($record->conditions['target_stage'] ?? 'Unknown');
                })
                ->orderQueryUsing(fn (Builder $query, string $direction): Builder => 
                    $query->orderByRaw("json_extract(conditions, '$.target_stage') {$direction}")
                ),
            Group::make('variety')
                ->label('Recipe')
                ->getTitleFromRecordUsing(function ($record) {
                    // First try to get from conditions (for backward compatibility)
                    if (isset($record->conditions['variety'])) {
                        return $record->conditions['variety'];
                    }
                    
                    // If not in conditions, get from the crop's recipe relationship
                    $cropId = $record->conditions['crop_id'] ?? null;
                    if (!$cropId) return 'Unknown';
                    
                    $crop = Crop::with(['recipe.seedEntry'])->find($cropId);
                    if (!$crop || !$crop->recipe) return 'Unknown';
                    
                    return $crop->recipe->name ?? 'Unknown';
                })
                ->orderQueryUsing(fn (Builder $query, string $direction): Builder => 
                    $query->orderByRaw("json_extract(conditions, '$.variety') {$direction}")
                )
        ];
    }

    /**
     * Alert type column with formatted display
     */
    protected static function getAlertTypeColumn(): TextColumn
    {
        return TextColumn::make('alert_type')
            ->label('Action')
            ->getStateUsing(fn (CropAlert $record) => $record->alert_type)
            ->sortable(query: function (Builder $query, string $direction): Builder {
                return $query->orderBy('task_name', $direction);
            })
            ->searchable(query: function (Builder $query, string $search): Builder {
                return $query->where('task_name', 'like', "%{$search}%");
            })
            ->toggleable();
    }

    /**
     * Tray number column with batch information
     */
    protected static function getTrayNumberColumn(): TextColumn
    {
        return TextColumn::make('tray_number')
            ->label('Batch Trays')
            ->getStateUsing(function (CropAlert $record) {
                // If we have tray_numbers array, it's already a batch operation
                if (isset($record->conditions['tray_numbers']) && is_array($record->conditions['tray_numbers'])) {
                    $count = count($record->conditions['tray_numbers']);
                    return "{$count} trays";
                }
                
                // For operations targeting single trays, find the batch info
                if (isset($record->conditions['crop_id'])) {
                    $cropId = $record->conditions['crop_id'];
                    $crop = Crop::find($cropId);
                    
                    if ($crop) {
                        // Get all crops with the same batch identifier
                        $batchCount = Crop::where('recipe_id', $crop->recipe_id)
                            ->where('planting_at', $crop->planting_at)
                            ->where('current_stage_id', $crop->current_stage_id)
                            ->count();
                            
                        return "{$batchCount} trays";
                    }
                }
                
                // Fallback to original behavior
                return $record->conditions['tray_number'] ? 'Single tray' : 'Unknown';
            })
            ->sortable(query: function ($query, $direction) {
                return $query->orderByRaw("json_extract(conditions, '$.tray_number') {$direction}");
            })
            ->searchable(query: function ($query, $search) {
                return $query->whereRaw("json_extract(conditions, '$.tray_number') LIKE ?", ["%{$search}%"]);
            })
            ->toggleable();
    }

    /**
     * Variety/recipe column
     */
    protected static function getVarietyColumn(): TextColumn
    {
        return TextColumn::make('variety')
            ->label('Recipe')
            ->getStateUsing(function (CropAlert $record) {
                // First try to get from conditions (for backward compatibility)
                if (isset($record->conditions['variety'])) {
                    return $record->conditions['variety'];
                }
                
                // If not in conditions, get from the crop's recipe relationship
                $cropId = $record->conditions['crop_id'] ?? null;
                if (!$cropId) return 'Unknown';
                
                $crop = Crop::with(['recipe'])->find($cropId);
                if (!$crop) return 'Unknown - Crop Not Found';
                
                if (!$crop->recipe) return 'Unknown - No Recipe';
                
                return $crop->recipe->name ?? 'Unknown - No Recipe Name';
            })
            ->sortable(query: function ($query, $direction) {
                return $query->orderByRaw("json_extract(conditions, '$.variety') {$direction}");
            })
            ->searchable(query: function ($query, $search) {
                return $query->whereRaw("json_extract(conditions, '$.variety') LIKE ?", ["%{$search}%"]);
            })
            ->toggleable();
    }

    /**
     * Seed variety column with common name and cultivar
     */
    protected static function getSeedVarietyColumn(): TextColumn
    {
        return TextColumn::make('seed_variety')
            ->label('Seed Variety')
            ->getStateUsing(function (CropAlert $record) {
                $cropId = $record->conditions['crop_id'] ?? null;
                if (!$cropId) return 'Unknown';
                
                $crop = Crop::with(['recipe.masterSeedCatalog', 'recipe.masterCultivar'])->find($cropId);
                if (!$crop) return 'Unknown - Crop Not Found';
                
                if (!$crop->recipe) return 'Unknown - No Recipe';
                
                // Get common name from masterSeedCatalog and cultivar name from masterCultivar
                $commonName = $crop->recipe->masterSeedCatalog?->common_name ?? 'Unknown';
                $cultivarName = $crop->recipe->masterCultivar?->cultivar_name ?? '';
                
                if ($cultivarName) {
                    return $commonName . ' (' . $cultivarName . ')';
                } else {
                    return $commonName;
                }
            })
            ->toggleable();
    }

    /**
     * Target stage column with badges
     */
    protected static function getTargetStageColumn(): TextColumn
    {
        return TextColumn::make('target_stage')
            ->label('Target Stage')
            ->getStateUsing(function (CropAlert $record) {
                // For suspend_watering and similar non-stage tasks, show N/A
                if (in_array($record->task_name, ['suspend_watering', 'resume_watering'])) {
                    return 'N/A';
                }
                
                return ucfirst($record->conditions['target_stage'] ?? 'unknown');
            })
            ->sortable(query: function ($query, $direction) {
                return $query->orderByRaw("json_extract(conditions, '$.target_stage') {$direction}");
            })
            ->badge()
            ->color(function (CropAlert $record) {
                // For non-stage tasks, use a neutral color
                if (in_array($record->task_name, ['suspend_watering', 'resume_watering'])) {
                    return 'gray';
                }
                
                return match ($record->conditions['target_stage'] ?? '') {
                    'germination' => 'info',
                    'blackout' => 'warning',
                    'light' => 'success',
                    'harvested' => 'danger',
                    default => 'gray',
                };
            })
            ->toggleable();
    }

    /**
     * Next run at column
     */
    protected static function getNextRunAtColumn(): TextColumn
    {
        return TextColumn::make('next_run_at')
            ->label('Scheduled For')
            ->dateTime()
            ->sortable()
            ->toggleable();
    }

    /**
     * Time until column with dynamic coloring
     */
    protected static function getTimeUntilColumn(): TextColumn
    {
        return TextColumn::make('time_until')
            ->label('Time Until')
            ->getStateUsing(fn (CropAlert $record) => $record->time_until)
            ->badge()
            ->color(fn (CropAlert $record) => $record->next_run_at->isPast() ? 'danger' : 'success')
            ->toggleable();
    }

    /**
     * Crop ID column with link to crop edit page
     */
    protected static function getCropIdColumn(): TextColumn
    {
        return TextColumn::make('crop_id')
            ->label('Crop')
            ->getStateUsing(function (CropAlert $record): ?int {
                return $record->conditions['crop_id'] ?? null;
            })
            ->formatStateUsing(fn (?int $state): string => $state ? "#{$state}" : 'Unknown')
            ->url(function (CropAlert $record): ?string {
                $cropId = $record->conditions['crop_id'] ?? null;
                if ($cropId) {
                    return route('filament.admin.resources.crops.edit', ['record' => $cropId]);
                }
                return null;
            })
            ->openUrlInNewTab()
            ->toggleable();
    }
}