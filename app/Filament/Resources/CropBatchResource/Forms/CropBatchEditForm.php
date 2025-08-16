<?php

namespace App\Filament\Resources\CropBatchResource\Forms;

use Filament\Forms;
use Filament\Forms\Get;
use Filament\Forms\Set;

/**
 * Crop Batch Edit Form Schema
 * Returns Filament form components for batch editing of crops
 * This form allows editing fields that apply to all crops in the batch
 */
class CropBatchEditForm
{
    public static function schema(): array
    {
        return [
            Forms\Components\Section::make('Batch Information')
                ->schema([
                    Forms\Components\Placeholder::make('batch_info')
                        ->label('')
                        ->content(function ($record) {
                            if (!$record) {
                                return '';
                            }
                            
                            $cropCount = $record->crops()->count();
                            $recipe = $record->recipe;
                            
                            return "**Batch ID:** {$record->id} | **Recipe:** {$recipe->name} | **Crops in batch:** {$cropCount}";
                        }),
                ])
                ->columnSpanFull(),

            Forms\Components\Section::make('Batch-wide Updates')
                ->description('These changes will be applied to ALL crops in this batch')
                ->schema([
                    Forms\Components\Textarea::make('notes')
                        ->label('Batch Notes')
                        ->rows(3)
                        ->helperText('These notes will be applied to all crops in the batch')
                        ->columnSpanFull(),
                ])
                ->columns(2),

            Forms\Components\Section::make('Growth Stage Timestamps')
                ->description('Update stage timestamps for all crops in the batch. Current stage will be automatically determined based on completed timestamps.')
                ->schema([
                    Forms\Components\Grid::make()
                        ->schema([
                            Forms\Components\DateTimePicker::make('soaking_at')
                                ->label('Soaking Started')
                                ->helperText('When soaking stage began')
                                ->seconds(false),
                            Forms\Components\DateTimePicker::make('planting_at')
                                ->label('Planting Date')
                                ->helperText('When crops were planted')
                                ->seconds(false),
                            Forms\Components\DateTimePicker::make('germination_at')
                                ->label('Germination Started')
                                ->helperText('When germination stage began')
                                ->seconds(false),
                            Forms\Components\DateTimePicker::make('blackout_at')
                                ->label('Blackout Started')
                                ->helperText('When blackout stage began')
                                ->seconds(false),
                            Forms\Components\DateTimePicker::make('light_at')
                                ->label('Light Started')
                                ->helperText('When light stage began')
                                ->seconds(false),
                            Forms\Components\DateTimePicker::make('harvested_at')
                                ->label('Harvested')
                                ->helperText('When crops were harvested')
                                ->seconds(false),
                        ])
                        ->columns(3)
                        ->afterStateHydrated(function ($component, $record) {
                            if ($record) {
                                // Get timestamps from the first crop
                                $firstCrop = $record->crops()->first();
                                if ($firstCrop) {
                                    $component->fill([
                                        'soaking_at' => $firstCrop->soaking_at,
                                        'planting_at' => $firstCrop->planting_at,
                                        'germination_at' => $firstCrop->germination_at,
                                        'blackout_at' => $firstCrop->blackout_at,
                                        'light_at' => $firstCrop->light_at,
                                        'harvested_at' => $firstCrop->harvested_at,
                                    ]);
                                }
                            }
                        }),
                ])
                ->collapsible(),

            Forms\Components\Section::make('Tray Numbers')
                ->description('Edit the tray numbers for crops in this batch')
                ->schema([
                    Forms\Components\TagsInput::make('tray_numbers')
                        ->label('Tray Numbers')
                        ->placeholder('Enter tray numbers...')
                        ->separator(',')
                        ->helperText('Edit tray numbers - each tag represents one tray in this batch')
                        ->required()
                        ->afterStateHydrated(function ($component, $state, $record) {
                            if ($record && !$state) {
                                // Load tray numbers from crops
                                $trayNumbers = $record->crops()
                                    ->orderBy('tray_number')
                                    ->pluck('tray_number')
                                    ->toArray();
                                $component->state($trayNumbers);
                            }
                        }),
                ])
                ->collapsible(),
        ];
    }
}