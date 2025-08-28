<?php

namespace App\Filament\Resources\CropResource\Actions;

use Filament\Forms\Components\DateTimePicker;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Illuminate\Validation\ValidationException;
use Exception;
use Filament\Forms\Components\Textarea;
use App\Models\CropBatchListView;
use App\Models\Crop;
use App\Models\CropStage;
use App\Services\CropTaskManagementService;
use App\Services\CropStageValidationService;
use App\Services\CropStageCache;
use Filament\Forms;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

class StageTransitionActions
{
    /**
     * Create the advance stage action
     */
    public static function advanceStage(): Action
    {
        return Action::make('advanceStage')
            ->label(function ($record): string {
                $currentStage = CropStageCache::find($record->current_stage_id);
                $nextStage = $currentStage ? CropStageCache::getNextStage($currentStage) : null;
                return $nextStage ? 'Advance to ' . ucfirst($nextStage->name) : 'Harvested';
            })
            ->icon('heroicon-o-chevron-double-right')
            ->color('success')
            ->visible(function ($record): bool {
                $stage = CropStageCache::find($record->current_stage_id);
                return $stage?->code !== 'harvested';
            })
            ->requiresConfirmation()
            ->modalHeading(function ($record): string {
                $currentStage = CropStageCache::find($record->current_stage_id);
                $nextStage = $currentStage ? CropStageCache::getNextStage($currentStage) : null;
                return 'Advance to ' . ucfirst($nextStage?->name ?? 'Unknown') . '?';
            })
            ->modalDescription(function ($record): string {
                $currentStage = CropStageCache::find($record->current_stage_id);
                if ($currentStage?->code === 'soaking') {
                    // Find all crops in this batch using new service
                    $crops = self::getCropsForRecord($record);
                    $count = $crops->count();
                    return "This will advance {$count} soaking tray(s) to germination. You'll need to assign real tray numbers to replace the temporary SOAKING-X identifiers.";
                }
                return 'This will update the current stage of all crops in this batch.';
            })
            ->schema(function ($record): array {
                $currentStage = CropStageCache::find($record->current_stage_id);
                $isSoaking = $currentStage?->code === 'soaking';
                
                $formElements = [
                    DateTimePicker::make('advancement_timestamp')
                        ->label('When did this advancement occur?')
                        ->default(now())
                        ->seconds(false)
                        ->required()
                        ->maxDate(now())
                        ->helperText('Specify the actual time when the stage advancement happened'),
                ];
                
                // If advancing from soaking to germination, add tray number fields
                if ($isSoaking) {
                    $crops = self::getCropsForRecord($record);
                    
                    if ($crops->count() > 0) {
                        $formElements[] = Section::make('Assign Real Tray Numbers')
                            ->description('Replace the temporary SOAKING-X identifiers with actual tray numbers. Each tray in the batch needs a unique identifier.')
                            ->schema(function() use ($crops) {
                                $fields = [];
                                foreach ($crops as $index => $crop) {
                                    $fields[] = TextInput::make("tray_numbers.{$crop->id}")
                                        ->label("Tray currently labeled as: {$crop->tray_number}")
                                        ->placeholder('Enter real tray number')
                                        ->required()
                                        ->maxLength(20)
                                        ->helperText('Enter the actual tray number/identifier')
                                        ->rules([
                                            'required', 
                                            'string', 
                                            'max:20',
                                            function () {
                                                return function (string $attribute, $value, $fail) {
                                                    // Check if this tray number already exists in active crops
                                                    $exists = Crop::whereHas('currentStage', function($query) {
                                                        $query->where('code', '!=', 'harvested');
                                                    })
                                                    ->where('tray_number', $value)
                                                    ->exists();
                                                    
                                                    if ($exists) {
                                                        $fail("Tray number {$value} is already in use by another active crop.");
                                                    }
                                                };
                                            }
                                        ]);
                                }
                                return $fields;
                            })
                            ->columns(1);
                    }
                }
                
                return $formElements;
            })
            ->action(function ($record, array $data) {
                try {
                    $transitionService = app(CropTaskManagementService::class);
                    $validationService = app(CropStageValidationService::class);
                    
                    // Get the first real crop to use as target
                    $targetCrop = self::getFirstCropForRecord($record);
                    
                    // Perform pre-validation
                    $crops = self::getCropsForRecord($record);
                    $batchValidation = $validationService->validateBatchConsistency($crops);
                    
                    if (!$batchValidation['valid']) {
                        Notification::make()
                            ->title('Batch Validation Failed')
                            ->body('Issues found: ' . implode(', ', $batchValidation['issues']))
                            ->danger()
                            ->send();
                        return;
                    }
                    
                    // Prepare options
                    $options = [];
                    if (isset($data['tray_numbers'])) {
                        $options['tray_numbers'] = $data['tray_numbers'];
                    }
                    
                    // Perform the transition
                    $results = $transitionService->advanceStage(
                        $targetCrop,
                        $data['advancement_timestamp'],
                        $options
                    );
                    
                    // Show results
                    if ($results['failed'] === 0) {
                        Notification::make()
                            ->title('Stage Advanced Successfully')
                            ->body("Advanced {$results['advanced']} crop(s) to the next stage.")
                            ->success()
                            ->send();
                    } else {
                        $body = "Advanced {$results['advanced']} crop(s), failed {$results['failed']} crop(s).";
                        if (!empty($results['warnings'])) {
                            $body .= "\n\nWarnings:\n" . implode("\n", $results['warnings']);
                        }
                        
                        Notification::make()
                            ->title('Stage Advanced with Issues')
                            ->body($body)
                            ->warning()
                            ->send();
                    }
                    
                } catch (ValidationException $e) {
                    Notification::make()
                        ->title('Validation Failed')
                        ->body($e->validator->errors()->first())
                        ->danger()
                        ->send();
                } catch (Exception $e) {
                    Notification::make()
                        ->title('Error')
                        ->body('Failed to advance stage: ' . $e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    /**
     * Create the rollback stage action
     */
    public static function rollbackStage(): Action
    {
        return Action::make('rollbackStage')
            ->label(function ($record): string {
                $currentStage = CropStageCache::find($record->current_stage_id);
                $previousStage = $currentStage ? CropStageCache::getPreviousStage($currentStage) : null;
                return $previousStage ? 'Rollback to ' . ucfirst($previousStage->name) : 'Cannot Rollback';
            })
            ->icon('heroicon-o-arrow-uturn-left')
            ->color('warning')
            ->visible(function ($record): bool {
                $stage = CropStageCache::find($record->current_stage_id);
                // Don't allow rollback from first stage
                return $stage && $stage->sort_order > 1;
            })
            ->requiresConfirmation()
            ->modalHeading(function ($record): string {
                $currentStage = CropStageCache::find($record->current_stage_id);
                $previousStage = $currentStage ? CropStageCache::getPreviousStage($currentStage) : null;
                return 'Rollback to ' . ucfirst($previousStage?->name ?? 'Unknown') . '?';
            })
            ->modalDescription('This will revert all crops in this batch to the previous stage. Current stage timestamps will be cleared.')
            ->schema([
                Textarea::make('reason')
                    ->label('Reason for rollback (optional)')
                    ->placeholder('Explain why this rollback is necessary...')
                    ->rows(3)
                    ->maxLength(255),
            ])
            ->action(function ($record, array $data) {
                try {
                    $transitionService = app(CropTaskManagementService::class);
                    $validationService = app(CropStageValidationService::class);
                    
                    // Get the first real crop to use as target
                    $targetCrop = self::getFirstCropForRecord($record);
                    
                    // Perform pre-validation
                    $currentStage = CropStageCache::find($targetCrop->current_stage_id);
                    $previousStage = CropStageCache::getPreviousStage($currentStage);
                    
                    if (!$previousStage) {
                        Notification::make()
                            ->title('Cannot Rollback')
                            ->body('This crop is already at the first stage.')
                            ->warning()
                            ->send();
                        return;
                    }
                    
                    $validation = $validationService->canRevertToStage($targetCrop, $previousStage);
                    
                    if (!$validation['valid']) {
                        Notification::make()
                            ->title('Rollback Not Allowed')
                            ->body('Issues found: ' . implode(', ', $validation['errors']))
                            ->danger()
                            ->send();
                        return;
                    }
                    
                    // Show warnings if any
                    if (!empty($validation['warnings'])) {
                        // In a real app, might want to show these as a confirmation
                        // For now, we'll proceed but log them
                    }
                    
                    // Perform the rollback
                    $results = $transitionService->revertStage(
                        $targetCrop,
                        $data['reason'] ?? null
                    );
                    
                    // Show results
                    if ($results['failed'] === 0) {
                        Notification::make()
                            ->title('Stage Rolled Back Successfully')
                            ->body("Reverted {$results['reverted']} crop(s) to {$previousStage->name}.")
                            ->success()
                            ->send();
                    } else {
                        $body = "Reverted {$results['reverted']} crop(s), failed {$results['failed']} crop(s).";
                        if (!empty($results['warnings'])) {
                            $body .= "\n\nWarnings:\n" . implode("\n", $results['warnings']);
                        }
                        
                        Notification::make()
                            ->title('Rollback Completed with Issues')
                            ->body($body)
                            ->warning()
                            ->send();
                    }
                    
                } catch (ValidationException $e) {
                    Notification::make()
                        ->title('Validation Failed')
                        ->body($e->validator->errors()->first())
                        ->danger()
                        ->send();
                } catch (Exception $e) {
                    Notification::make()
                        ->title('Error')
                        ->body('Failed to rollback stage: ' . $e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    /**
     * Create the harvest action (special case of advance to harvested)
     */
    public static function harvest(): Action
    {
        return Action::make('harvest')
            ->label('Harvest')
            ->icon('heroicon-o-scissors')
            ->color('success')
            ->visible(function ($record): bool {
                $stage = CropStageCache::find($record->current_stage_id);
                return $stage?->code === 'light';
            })
            ->requiresConfirmation()
            ->modalHeading('Harvest Crop?')
            ->modalDescription('This will mark all crops in this batch as harvested.')
            ->schema([
                DateTimePicker::make('harvest_timestamp')
                    ->label('When was this harvested?')
                    ->default(now())
                    ->seconds(false)
                    ->required()
                    ->maxDate(now())
                    ->helperText('Specify the actual time when the harvest occurred'),
            ])
            ->action(function ($record, array $data) {
                try {
                    $transitionService = app(CropTaskManagementService::class);
                    
                    // Get the first real crop to use as target
                    $targetCrop = self::getFirstCropForRecord($record);
                    
                    // Perform the transition to harvested
                    $results = $transitionService->advanceStage(
                        $targetCrop,
                        $data['harvest_timestamp']
                    );
                    
                    // Show results
                    if ($results['failed'] === 0) {
                        Notification::make()
                            ->title('Batch Harvested')
                            ->body("Successfully harvested {$results['advanced']} tray(s).")
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Harvest Completed with Issues')
                            ->body("Harvested {$results['advanced']} tray(s), failed {$results['failed']} tray(s).")
                            ->warning()
                            ->send();
                    }
                    
                } catch (Exception $e) {
                    Notification::make()
                        ->title('Error')
                        ->body('Failed to harvest batch: ' . $e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    /**
     * Get crops for a record (handles batch grouping)
     */
    private static function getCropsForRecord($record)
    {
        // If this is a CropBatchListView, use its ID as the crop_batch_id
        if ($record instanceof CropBatchListView) {
            return Crop::where('crop_batch_id', $record->id)
                ->with(['recipe', 'currentStage'])
                ->get();
        }
        
        // If has crop_batch_id, use that
        if ($record->crop_batch_id) {
            return Crop::where('crop_batch_id', $record->crop_batch_id)
                ->with(['recipe', 'currentStage'])
                ->get();
        }
        
        // Fall back to implicit batching
        return Crop::where('recipe_id', $record->recipe_id)
            ->where('planting_at', $record->planting_at)
            ->where('current_stage_id', $record->current_stage_id)
            ->with(['recipe', 'currentStage'])
            ->get();
    }

    /**
     * Get first real crop for a grouped record
     */
    private static function getFirstCropForRecord($record): Crop
    {
        // If this is already a real crop, return it
        if ($record instanceof Crop && $record->exists && $record->tray_number) {
            return $record;
        }
        
        // Otherwise find the first real crop in the batch
        return self::getCropsForRecord($record)->first();
    }
}