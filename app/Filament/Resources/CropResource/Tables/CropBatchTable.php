<?php

namespace App\Filament\Resources\CropResource\Tables;

use App\Models\Crop;
use App\Models\CropBatch;
use App\Models\Order;
use App\Models\RecipeOptimizedView;
use App\Services\CropStageCache;
use App\Filament\Resources\CropResource\Actions\StageTransitionActions;
use App\Services\CropStageTransitionService;
use Filament\Forms;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Crop Batch Table Configuration
 * Returns Filament table components for crop batch listing
 */
class CropBatchTable
{
    /**
     * Get table columns
     */
    public static function columns(): array
    {
        return [
            Tables\Columns\TextColumn::make('recipe_name')
                ->label('Recipe')
                ->weight('bold')
                ->searchable()
                ->sortable(),
            Tables\Columns\ViewColumn::make('tray_numbers')
                ->label('Trays')
                ->view('components.tray-badges')
                ->searchable()
                ->sortable(false)
                ->toggleable(),
            Tables\Columns\TextColumn::make('planting_at')
                ->label('Planted')
                ->date()
                ->sortable()
                ->toggleable(),
            Tables\Columns\TextColumn::make('current_stage_name')
                ->label('Current Stage')
                ->badge()
                ->color(fn ($record) => $record->current_stage_color ?? 'gray')
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('stage_age_display')
                ->label('Time in Stage')
                ->sortable(false)
                ->toggleable(),
            Tables\Columns\TextColumn::make('time_to_next_stage_display')
                ->label('Time to Next Stage')
                ->sortable(false)
                ->toggleable(),
            Tables\Columns\TextColumn::make('total_age_display')
                ->label('Total Age')
                ->sortable(false)
                ->toggleable(),
            Tables\Columns\TextColumn::make('expected_harvest_at')
                ->label('Expected Harvest')
                ->date()
                ->sortable()
                ->toggleable(),
        ];
    }

    /**
     * Get table filters
     */
    public static function filters(): array
    {
        return [
            Tables\Filters\SelectFilter::make('current_stage_id')
                ->label('Stage')
                ->options(CropStageCache::all()->pluck('name', 'id')),
            Tables\Filters\TernaryFilter::make('active_crops')
                ->label('Active Crops')
                ->placeholder('All Crops')
                ->trueLabel('Active Only')
                ->falseLabel('Harvested Only')
                ->queries(
                    true: fn (Builder $query): Builder => $query->active(),
                    false: fn (Builder $query): Builder => $query->harvested(),
                    blank: fn (Builder $query): Builder => $query,
                )
                ->default(true),
        ];
    }

    /**
     * Get table actions
     */
    public static function actions(): array
    {
        return [
            Tables\Actions\ActionGroup::make([
                Tables\Actions\ViewAction::make()
                ->tooltip('View crop details')
                ->modalHeading('Crop Details')
                ->modalWidth('sm')
                ->slideOver()
                ->modalIcon('heroicon-o-eye')
                ->extraModalFooterActions([
                    Tables\Actions\Action::make('advance_stage')
                        ->label('Advance Stage')
                        ->icon('heroicon-o-chevron-double-right')
                        ->color('success')
                        ->visible(function ($record) {
                            $stage = CropStageCache::find($record->current_stage_id);
                            return $stage?->code !== 'harvested';
                        })
                        ->action(function ($record, $livewire) {
                            // Close the view modal and trigger the main advance stage action
                            $livewire->mountTableAction('advanceStage', $record->id);
                        }),
                    Tables\Actions\Action::make('edit_crop')
                        ->label('Edit Crop')
                        ->icon('heroicon-o-pencil-square')
                        ->color('primary')
                        ->url(fn ($record) => \App\Filament\Resources\CropResource::getUrl('edit', ['record' => $record])),
                    Tables\Actions\Action::make('view_all_crops')
                        ->label('View All Crops')
                        ->icon('heroicon-o-list-bullet')
                        ->color('gray')
                        ->url(fn ($record) => \App\Filament\Resources\CropResource::getUrl('index')),
                ]),
            static::getDebugAction(),
            static::getFixTimestampsAction(),
            
            StageTransitionActions::advanceStage(),
            StageTransitionActions::harvest(),
            StageTransitionActions::rollbackStage(),
            static::getSuspendWateringAction(),
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make()
                ->requiresConfirmation()
                ->modalHeading('Delete Entire Grow Batch?')
                ->modalDescription(fn ($record) => "This will delete all {$record->crop_count} trays in this batch.")
                ->modalSubmitActionLabel('Yes, Delete All Trays')
                ->action(function ($record) {
                    // Begin transaction for safety
                    DB::beginTransaction();
                    
                    try {
                        // Get all tray numbers and delete crops in this batch
                        $trayNumbers = $record->tray_numbers_array;
                        $count = \App\Models\Crop::where('crop_batch_id', $record->id)->delete();
                        
                        // Also delete the batch itself
                        \App\Models\CropBatch::destroy($record->id);
                        
                        DB::commit();
                        
                        // Show a detailed notification
                        \Filament\Notifications\Notification::make()
                            ->title('Grow Batch Deleted')
                            ->body("Successfully deleted {$count} tray(s): " . implode(', ', $trayNumbers))
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        DB::rollBack();
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Error')
                            ->body('Failed to delete grow batch: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            ])
            ->label('Actions')
            ->icon('heroicon-m-ellipsis-vertical')
            ->size('sm')
            ->color('gray')
            ->button(),
        ];
    }

    /**
     * Get table bulk actions
     */
    public static function bulkActions(): array
    {
        return [
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make(),
                static::getAdvanceStagesBulkAction(),
                static::getRollbackStagesBulkAction(),
            ]),
        ];
    }

    /**
     * Get table groups/groupings
     */
    public static function groups(): array
    {
        return [
            Tables\Grouping\Group::make('recipe.name')
                ->label('Recipe'),
            Tables\Grouping\Group::make('planting_at')
                ->label('Plant Date')
                ->date(),
            Tables\Grouping\Group::make('current_stage_name')
                ->label('Growth Stage'),
        ];
    }

    /**
     * Get debug action for troubleshooting
     */
    protected static function getDebugAction(): Action
    {
        return Tables\Actions\Action::make('debug')
            ->label('')
            ->icon('heroicon-o-code-bracket')
            ->tooltip('Debug Info')
            ->action(function ($record) {
                // Get current time for debugging
                $now = now();
                
                // Get first crop for detailed information
                $firstCrop = $record->crops()->first();
                
                // Prepare batch data with modern features
                $batchData = [
                    'Batch ID' => $record->id,
                    'Crop Count' => $record->crop_count,
                    'Tray Numbers' => implode(', ', $record->tray_numbers_array ?? []),
                    'Recipe ID' => $record->recipe_id,
                    'Recipe Name' => $record->recipe_name ?? 'Unknown',
                    'Current Stage' => $record->current_stage_name . ' (ID: ' . ($record->current_stage_id ?? 'N/A') . ')',
                    'Stage Color' => $record->current_stage_color ?? 'N/A',
                    'Created At' => $record->created_at ? $record->created_at->format('Y-m-d H:i:s') : 'N/A',
                    'Current Time' => $now->format('Y-m-d H:i:s'),
                ];
                
                // Stage timestamps - more detailed
                $stageData = [
                    'Planted At' => $record->planting_at ? $record->planting_at->format('Y-m-d H:i:s') : 'N/A',
                    'Soaking At' => $record->soaking_at ? $record->soaking_at->format('Y-m-d H:i:s') : 'N/A',
                    'Germination At' => $record->germination_at ? $record->germination_at->format('Y-m-d H:i:s') : 'N/A',
                    'Blackout At' => $record->blackout_at ? $record->blackout_at->format('Y-m-d H:i:s') : 'N/A',
                    'Light At' => $record->light_at ? $record->light_at->format('Y-m-d H:i:s') : 'N/A',
                    'Harvested At' => $record->harvested_at ? $record->harvested_at->format('Y-m-d H:i:s') : 'N/A',
                    'Expected Harvest' => $record->expected_harvest_at ? $record->expected_harvest_at->format('Y-m-d H:i:s') : 'N/A',
                ];
                
                // Get recipe data using optimized view
                $recipe = \App\Models\RecipeOptimizedView::find($record->recipe_id);
                
                // TEMP DEBUG: Also get the regular recipe to compare
                $regularRecipe = \App\Models\Recipe::find($record->recipe_id);
                
                $recipeData = [];
                if ($recipe) {
                    $cultivarName = 'N/A';
                    if ($recipe->common_name && $recipe->cultivar_name) {
                        $cultivarName = $recipe->common_name . ' - ' . $recipe->cultivar_name;
                    } elseif ($recipe->common_name) {
                        $cultivarName = $recipe->common_name;
                    }
                    
                    $recipeData = [
                        'Recipe ID' => $recipe->id,
                        'Recipe Name' => $recipe->name,
                        'Variety' => $cultivarName,
                        'Lot Number' => $recipe->lot_number ?? 'N/A',
                        'Common Name' => $recipe->common_name ?? 'N/A',
                        'Cultivar' => $recipe->cultivar_name ?? 'N/A',
                        'Category' => $recipe->category ?? 'N/A',
                        'Master Seed Cat ID' => $recipe->master_seed_catalog_id ?? 'N/A',
                        'Master Cultivar ID' => $recipe->master_cultivar_id ?? 'N/A',
                        'Germination Days' => $recipe->germination_days ?? 'N/A',
                        'Blackout Days' => $recipe->blackout_days ?? 'N/A',
                        'Light Days' => $recipe->light_days ?? 'N/A',
                        'Days to Maturity' => $recipe->days_to_maturity ?? 'N/A',
                        'Seed Soak Hours' => $recipe->seed_soak_hours ?? 'N/A',
                        'Requires Soaking' => $recipe->requires_soaking ? 'Yes' : 'No',
                        'Seed Density (g/tray)' => $recipe->seed_density_grams_per_tray ?? 'N/A',
                        'Expected Yield (g)' => $recipe->expected_yield_grams ?? 'N/A',
                        'Buffer %' => $recipe->buffer_percentage ?? 'N/A',
                        'Is Active' => $recipe->is_active ? 'Yes' : 'No',
                    ];
                    
                    // TEMP DEBUG: Add comparison with regular recipe
                    if ($regularRecipe) {
                        $recipeData['--- REGULAR RECIPE ---'] = '---';
                        $recipeData['Regular Name'] = $regularRecipe->name ?? 'N/A';
                        $recipeData['Regular Master Seed Cat ID'] = $regularRecipe->master_seed_catalog_id ?? 'N/A';
                        $recipeData['Regular Master Cultivar ID'] = $regularRecipe->master_cultivar_id ?? 'N/A';
                        $recipeData['Regular Lot Number'] = $regularRecipe->lot_number ?? 'N/A';
                    }
                }
                
                // Add modern time calculations and stage timeline
                $timeCalculations = [];
                
                // Current stage age
                $timeCalculations['Current Stage Age'] = [
                    'Display Value' => $record->stage_age_display ?? 'Unknown',
                    'Minutes' => $record->stage_age_minutes ?? 'N/A',
                ];
                
                // Time to next stage
                $timeCalculations['Time to Next Stage'] = [
                    'Display Value' => $record->time_to_next_stage_display ?? 'Unknown',
                    'Minutes' => $record->time_to_next_stage_minutes ?? 'N/A',
                ];
                
                // Total crop age
                $timeCalculations['Total Crop Age'] = [
                    'Display Value' => $record->total_age_display ?? 'Unknown', 
                    'Minutes' => $record->total_age_minutes ?? 'N/A',
                ];
                
                // Add stage timeline using our new service
                if ($firstCrop) {
                    $timelineService = app(\App\Services\CropStageTimelineService::class);
                    $timeline = $timelineService->generateTimeline($firstCrop);
                    
                    $timeCalculations['Stage Timeline'] = [];
                    foreach ($timeline as $stageCode => $stage) {
                        $status = $stage['status'] ?? 'unknown';
                        $duration = $stage['duration'] ?? 'N/A';
                        $timeCalculations['Stage Timeline'][$stage['name']] = ucfirst($status) . 
                            ($duration !== 'N/A' && $duration ? " ({$duration})" : '');
                    }
                    
                    // TEMP DEBUG: Add crop details for timeline debugging
                    $timeCalculations['--- CROP DEBUG ---'] = [];
                    $timeCalculations['--- CROP DEBUG ---']['Crop ID'] = $firstCrop->id;
                    $timeCalculations['--- CROP DEBUG ---']['Current Stage ID'] = $firstCrop->current_stage_id;
                    $timeCalculations['--- CROP DEBUG ---']['Current Stage Code'] = $firstCrop->currentStage?->code ?? 'NULL';
                    $timeCalculations['--- CROP DEBUG ---']['Planting At'] = $firstCrop->planting_at?->format('Y-m-d H:i:s') ?? 'NULL';
                    $timeCalculations['--- CROP DEBUG ---']['Germination At'] = $firstCrop->germination_at?->format('Y-m-d H:i:s') ?? 'NULL';
                    $timeCalculations['--- CROP DEBUG ---']['Stage Age Minutes'] = $firstCrop->stage_age_minutes ?? 'NULL';
                    $timeCalculations['--- CROP DEBUG ---']['Stage Age Display'] = $firstCrop->stage_age_display ?? 'NULL';
                }
                
                // Next stage info
                $currentStageCode = $record->currentStage?->code;
                if ($recipe && $currentStageCode !== 'harvested') {
                    $nextStage = match($currentStageCode) {
                        'soaking' => 'germination',
                        'germination' => 'blackout',
                        'blackout' => 'light',
                        'light' => 'harvested',
                        default => null
                    };
                    
                    if ($nextStage) {
                        $timeCalculations['Next Stage Info'] = [
                            'Current Stage' => $record->current_stage_name,
                            'Next Stage' => $nextStage,
                        ];
                    }
                }
                
                // Format the debug data for display in a notification
                $batchDataHtml = static::formatDebugSection('Batch Information', $batchData);
                $stageDataHtml = static::formatDebugSection('Stage Timestamps', $stageData);
                $recipeDataHtml = !empty($recipeData) ? 
                    static::formatDebugSection('Recipe Data', $recipeData) : 
                    '<div class="text-gray-500 dark:text-gray-400 mb-4">Recipe not found</div>';
                $timeCalcHtml = static::formatTimeCalculationsSection($timeCalculations);
                
                Notification::make()
                    ->title('Crop Batch Debug Information')
                    ->body($batchDataHtml . $stageDataHtml . $recipeDataHtml . $timeCalcHtml)
                    ->persistent()
                    ->actions([
                        \Filament\Notifications\Actions\Action::make('close')
                            ->label('Close')
                            ->color('gray')
                    ])
                    ->send();
            });
    }

    /**
     * Get fix timestamps action
     */
    protected static function getFixTimestampsAction(): Action
    {
        return Tables\Actions\Action::make('fix_timestamps')
            ->label('')
            ->icon('heroicon-o-wrench-screwdriver')
            ->tooltip('Fix Missing Timestamps')
            ->action(function ($record) {
                $fixedCount = 0;
                $crops = $record->crops;
                
                foreach ($crops as $crop) {
                    $fixed = app(\App\Services\CropStageTransitionService::class)->fixMissingStageTimestamps($crop);
                    if ($fixed) {
                        $fixedCount++;
                    }
                }
                
                Notification::make()
                    ->title('Timestamp Fix Complete')
                    ->body("Fixed timestamps for {$fixedCount} crops in this batch.")
                    ->success()
                    ->send();
            });
    }

    /**
     * Get suspend watering action
     */
    protected static function getSuspendWateringAction(): Action
    {
        return Action::make('suspendWatering')
            ->label('Suspend Watering')
            ->icon('heroicon-o-no-symbol')
            ->color('warning')
            ->visible(function ($record): bool {
                return $record->current_stage_code === 'light' && !$record->watering_suspended_at;
            })
            ->requiresConfirmation()
            ->modalHeading('Suspend Watering?')
            ->modalDescription('This will mark watering as suspended for all crops in this batch.')
            ->form([
                Forms\Components\DateTimePicker::make('suspension_timestamp')
                    ->label('When was watering suspended?')
                    ->default(now())
                    ->seconds(false)
                    ->required()
                    ->maxDate(now())
                    ->helperText('Specify the actual time when watering was suspended'),
            ])
            ->action(function ($record, array $data) {
                // Begin transaction for safety
                DB::beginTransaction();
                
                try {
                    // Get all crops in this batch
                    $crops = \App\Models\Crop::where('crop_batch_id', $record->id)->get();
                    
                    $count = $crops->count();
                    $trayNumbers = $crops->pluck('tray_number')->toArray();
                    
                    // Update all crops in this batch
                    $suspensionTime = $data['suspension_timestamp'];
                    foreach ($crops as $crop) {
                        // Suspend watering on the Crop model with custom timestamp
                        $crop->suspendWatering($suspensionTime);
                        
                        // Deactivate the corresponding TaskSchedule
                        $task = \App\Models\TaskSchedule::where('resource_type', 'crops')
                            ->where('conditions->crop_id', $crop->id)
                            ->where('task_name', 'suspend_watering') // Match the task name
                            ->where('is_active', true)
                            ->first();
                            
                        if ($task) {
                            $task->update([
                                'is_active' => false,
                                'last_run_at' => now(),
                            ]);
                        }
                    }
                    
                    DB::commit();
                    
                    \Filament\Notifications\Notification::make()
                        ->title('Watering Suspended for Batch')
                        ->body("Successfully suspended watering for {$count} tray(s).")
                        ->success()
                        ->send();
                } catch (\Exception $e) {
                    DB::rollBack();
                    
                    \Filament\Notifications\Notification::make()
                        ->title('Error')
                        ->body('Failed to suspend watering: ' . $e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    /**
     * Get advance stages bulk action
     */
    protected static function getAdvanceStagesBulkAction(): Tables\Actions\BulkAction
    {
        return Tables\Actions\BulkAction::make('advance_stage_bulk')
            ->label('Advance Stage')
            ->icon('heroicon-o-arrow-right')
            ->before(function ($records, $action) {
                // Check if any of the selected batches are in soaking stage
                foreach ($records as $record) {
                    $stage = CropStageCache::find($record->current_stage_id);
                    if ($stage?->code === 'soaking') {
                        \Filament\Notifications\Notification::make()
                            ->title('Cannot Bulk Advance Soaking Crops')
                            ->body('Crops in the soaking stage require individual tray number assignment. Please use the individual "Advance Stage" action for each soaking batch.')
                            ->warning()
                            ->send();
                        $action->cancel();
                        return;
                    }
                }
            })
            ->form([
                Forms\Components\DateTimePicker::make('advancement_timestamp')
                    ->label('When did this advancement occur?')
                    ->default(now())
                    ->seconds(false)
                    ->required()
                    ->maxDate(now())
                    ->helperText('Specify the actual time when the stage advancement happened'),
            ])
            ->action(function ($records, array $data) {
                $transitionService = app(\App\Services\CropStageTransitionService::class);
                $totalCount = 0;
                $batchCount = 0;
                $successfulBatches = 0;
                $failedBatches = 0;
                $warnings = [];
                
                $transitionTime = \Carbon\Carbon::parse($data['advancement_timestamp']);
                
                foreach ($records as $record) {
                    try {
                        // Use the first crop from the batch as the transition target
                        // The service will automatically find all crops in the batch
                        $firstCrop = $record->crops()->first();
                        if (!$firstCrop) {
                            throw new \Exception('No crops found in batch');
                        }
                        $result = $transitionService->advanceStage($firstCrop, $transitionTime);
                        
                        $totalCount += $result['affected_count'];
                        $batchCount++;
                        $successfulBatches++;
                        
                        if (!empty($result['warnings'])) {
                            $warnings = array_merge($warnings, $result['warnings']);
                        }
                    } catch (\Illuminate\Validation\ValidationException $e) {
                        $failedBatches++;
                        $warnings[] = "Batch {$record->batch_number}: " . implode(', ', $e->errors()['stage'] ?? $e->errors()['target'] ?? ['Unknown error']);
                    } catch (\Exception $e) {
                        $failedBatches++;
                        $warnings[] = "Batch {$record->batch_number}: " . $e->getMessage();
                    }
                }
                
                // Build notification message
                $message = "Successfully advanced {$successfulBatches} batch(es) containing {$totalCount} tray(s).";
                if ($failedBatches > 0) {
                    $message .= " Failed to advance {$failedBatches} batch(es).";
                }
                
                if ($successfulBatches > 0) {
                    \Filament\Notifications\Notification::make()
                        ->title('Batches Advanced')
                        ->body($message)
                        ->success()
                        ->send();
                } else {
                    \Filament\Notifications\Notification::make()
                        ->title('No Batches Advanced')
                        ->body($message)
                        ->danger()
                        ->send();
                }
                
                // Show warnings if any
                if (!empty($warnings)) {
                    \Filament\Notifications\Notification::make()
                        ->title('Warnings')
                        ->body(implode("\n", array_slice($warnings, 0, 5)) . (count($warnings) > 5 ? "\n...and " . (count($warnings) - 5) . " more" : ''))
                        ->warning()
                        ->persistent()
                        ->send();
                }
            })
            ->requiresConfirmation()
            ->modalHeading('Advance Selected Batches?')
            ->modalDescription('This will advance all trays in the selected batches to their next stage.');
    }

    /**
     * Get rollback stages bulk action
     */
    protected static function getRollbackStagesBulkAction(): Tables\Actions\BulkAction
    {
        return Tables\Actions\BulkAction::make('rollback_stage_bulk')
            ->label('Rollback Stage')
            ->icon('heroicon-o-arrow-uturn-left')
            ->color('warning')
            ->form([
                Forms\Components\Textarea::make('reason')
                    ->label('Reason for rollback (optional)')
                    ->rows(3)
                    ->helperText('Provide a reason for rolling back these batches'),
            ])
            ->action(function ($records, array $data) {
                $transitionService = app(\App\Services\CropStageTransitionService::class);
                $totalCount = 0;
                $batchCount = 0;
                $successfulBatches = 0;
                $failedBatches = 0;
                $skippedCount = 0;
                $warnings = [];
                
                $reason = $data['reason'] ?? null;
                
                foreach ($records as $record) {
                    try {
                        // Use the first crop from the batch as the transition target
                        // The service will automatically find all crops in the batch
                        $firstCrop = $record->crops()->first();
                        if (!$firstCrop) {
                            throw new \Exception('No crops found in batch');
                        }
                        $result = $transitionService->revertStage($firstCrop, $reason);
                        
                        $totalCount += $result['affected_count'];
                        $batchCount++;
                        $successfulBatches++;
                        
                        if (!empty($result['warnings'])) {
                            $warnings = array_merge($warnings, $result['warnings']);
                        }
                    } catch (\Illuminate\Validation\ValidationException $e) {
                        $errors = $e->errors();
                        if (isset($errors['stage']) && str_contains($errors['stage'][0], 'already at first stage')) {
                            $skippedCount++;
                        } else {
                            $failedBatches++;
                            $warnings[] = "Batch {$record->batch_number}: " . implode(', ', $errors['stage'] ?? $errors['target'] ?? ['Unknown error']);
                        }
                    } catch (\Exception $e) {
                        $failedBatches++;
                        $warnings[] = "Batch {$record->batch_number}: " . $e->getMessage();
                    }
                }
                
                // Build notification message
                $message = "Successfully rolled back {$successfulBatches} batch(es) containing {$totalCount} tray(s).";
                if ($skippedCount > 0) {
                    $message .= " Skipped {$skippedCount} batch(es) already at first stage.";
                }
                if ($failedBatches > 0) {
                    $message .= " Failed to rollback {$failedBatches} batch(es).";
                }
                
                if ($successfulBatches > 0) {
                    \Filament\Notifications\Notification::make()
                        ->title('Batches Rolled Back')
                        ->body($message)
                        ->success()
                        ->send();
                } else if ($skippedCount > 0) {
                    \Filament\Notifications\Notification::make()
                        ->title('No Changes Made')
                        ->body($message)
                        ->warning()
                        ->send();
                } else {
                    \Filament\Notifications\Notification::make()
                        ->title('Rollback Failed')
                        ->body($message)
                        ->danger()
                        ->send();
                }
                
                // Show warnings if any
                if (!empty($warnings)) {
                    \Filament\Notifications\Notification::make()
                        ->title('Warnings')
                        ->body(implode("\n", array_slice($warnings, 0, 5)) . (count($warnings) > 5 ? "\n...and " . (count($warnings) - 5) . " more" : ''))
                        ->warning()
                        ->persistent()
                        ->send();
                }
            })
            ->requiresConfirmation()
            ->modalHeading('Rollback Selected Batches?')
            ->modalDescription('This will revert all trays in the selected batches to their previous stage by removing the current stage timestamp.');
    }

    /**
     * Format debug section HTML
     */
    protected static function formatDebugSection(string $title, array $data): string
    {
        $html = '<div class="mb-4">';
        $html .= '<h3 class="text-lg font-medium mb-2">' . $title . '</h3>';
        $html .= '<div class="overflow-auto max-h-48 space-y-1">';
        
        foreach ($data as $key => $value) {
            $html .= '<div class="flex">';
            $html .= '<span class="font-medium w-32">' . $key . ':</span>';
            $html .= '<span class="text-gray-600 dark:text-gray-400">' . $value . '</span>';
            $html .= '</div>';
        }
        
        $html .= '</div></div>';
        return $html;
    }

    /**
     * Format time calculations section HTML
     */
    protected static function formatTimeCalculationsSection(array $timeCalculations): string
    {
        $html = '<div class="mb-4">';
        $html .= '<h3 class="text-lg font-medium mb-2">Time Calculations</h3>';
        $html .= '<div class="overflow-auto max-h-80 space-y-4">';
        
        foreach ($timeCalculations as $section => $data) {
            $html .= '<div class="border-t pt-2">';
            $html .= '<h4 class="font-medium text-blue-600 dark:text-blue-400 mb-1">' . $section . '</h4>';
            
            foreach ($data as $key => $value) {
                $html .= '<div class="flex">';
                $html .= '<span class="font-medium w-40 text-sm">' . $key . ':</span>';
                $html .= '<span class="text-gray-600 dark:text-gray-400 text-sm">' . $value . '</span>';
                $html .= '</div>';
            }
            
            $html .= '</div>';
        }
        
        $html .= '</div></div>';
        return $html;
    }
}