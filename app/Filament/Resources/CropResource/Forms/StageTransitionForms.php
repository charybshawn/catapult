<?php

namespace App\Filament\Resources\CropResource\Forms;

use Filament\Forms\Components\DateTimePicker;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use App\Models\Crop;
use App\Models\CropStage;
use App\Services\CropStageCache;
use Filament\Forms;
use Carbon\Carbon;
use App\Filament\Resources\CropResource\Utilities\CropQueryHelpers;

/**
 * Form builders for stage transition actions
 * Extracted from StageTransitionActions to reduce complexity
 */
class StageTransitionForms
{
    /**
     * Build form for advance stage action
     */
    public static function buildAdvanceStageForm($record): array
    {
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
            $crops = CropQueryHelpers::getCropsForRecord($record);
            
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
    }

    /**
     * Build form for rollback stage action
     */
    public static function buildRollbackStageForm(): array
    {
        return [
            Textarea::make('reason')
                ->label('Reason for rollback (optional)')
                ->placeholder('Explain why this rollback is necessary...')
                ->rows(3)
                ->maxLength(255),
        ];
    }

    /**
     * Build form for harvest action
     */
    public static function buildHarvestForm(): array
    {
        return [
            DateTimePicker::make('harvest_timestamp')
                ->label('When was this harvested?')
                ->default(now())
                ->seconds(false)
                ->required()
                ->maxDate(now())
                ->helperText('Specify the actual time when the harvest occurred'),
        ];
    }

}