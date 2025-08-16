<?php

namespace App\Filament\Resources\CropResource\Tables;

use App\Models\Crop;
use App\Models\CropBatch;
use App\Models\Order;
use App\Models\Recipe;
use App\Services\CropStageCache;
use App\Filament\Resources\CropResource\Actions\StageTransitionActions;
use App\Filament\Resources\CropResource\Actions\CropBatchDebugAction;
use App\Services\CropTaskManagementService;
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
                ->label('Variety')
                ->weight('bold')
                ->searchable()
                ->sortable(),
            Tables\Columns\ViewColumn::make('tray_numbers')
                ->label('Trays')
                ->view('components.tray-badges')
                ->searchable()
                ->sortable(false)
                ->toggleable(),
            Tables\Columns\TextColumn::make('germination_date_formatted')
                ->label('Started')
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
                    true: fn (Builder $query): Builder => $query->activeOnly(),
                    false: fn (Builder $query): Builder => $query->byStage('harvested'),
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
                ->mutateRecordDataUsing(function (array $data, $record): array {
                    // Ensure proper relationships are loaded
                    if (!$record->relationLoaded('crops')) {
                        $record->load(['crops', 'recipe.masterSeedCatalog', 'recipe.masterCultivar']);
                    }
                    
                    // Get transformed data and set as attributes
                    $displayService = app(\App\Services\CropBatchDisplayService::class);
                    $transformedData = $displayService->getCachedForBatch($record->id);
                    
                    if ($transformedData) {
                        $record->setAttribute('tray_numbers_array', $transformedData->tray_numbers);
                        $record->setAttribute('current_stage_color', 'gray');
                        $record->setAttribute('recipe_name', $transformedData->recipe_name);
                        $record->setAttribute('current_stage_name', $transformedData->current_stage_name);
                        $record->setAttribute('stage_age_display', $transformedData->stage_age_display);
                        $record->setAttribute('time_to_next_stage_display', $transformedData->time_to_next_stage_display);
                        $record->setAttribute('total_age_display', $transformedData->total_age_display);
                    }
                    
                    return $data;
                }),
            CropBatchDebugAction::make(),
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
            Tables\Grouping\Group::make('germination_date_formatted')
                ->label('Start Date'),
            Tables\Grouping\Group::make('current_stage_name')
                ->label('Growth Stage'),
        ];
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
                // Query crops directly for this batch
                $crops = \App\Models\Crop::where('crop_batch_id', $record->id)->get();
                
                foreach ($crops as $crop) {
                    $fixed = app(\App\Services\CropTaskManagementService::class)->fixMissingStageTimestamps($crop);
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
                $transitionService = app(\App\Services\CropTaskManagementService::class);
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
                        $firstCrop = \App\Models\Crop::where('crop_batch_id', $record->id)->first();
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
                $transitionService = app(\App\Services\CropTaskManagementService::class);
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
                        $firstCrop = \App\Models\Crop::where('crop_batch_id', $record->id)->first();
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

}