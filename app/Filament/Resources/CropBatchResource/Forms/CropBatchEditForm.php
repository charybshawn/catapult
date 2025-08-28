<?php

namespace App\Filament\Resources\CropBatchResource\Forms;

use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TagsInput;
use App\Models\CropStage;
use App\Services\CropStageCache;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Forms\Set;

/**
 * Crop batch editing form schema for agricultural production management.
 * 
 * Provides comprehensive batch editing interface for managing multiple crops
 * simultaneously including stage transitions, timestamp updates, and tray
 * organization. Supports efficient agricultural production workflow management.
 * 
 * @filament_form Crop batch editing with agricultural production context
 * @business_context Agricultural production management and batch operations
 * @batch_operations Multi-crop editing for efficient production management
 */
class CropBatchEditForm
{
    /**
     * Get crop batch editing form schema with production management features.
     * 
     * Returns comprehensive form sections for batch information display,
     * batch-wide updates, growth stage timestamp management, and tray
     * organization for agricultural production efficiency.
     * 
     * @return array Complete crop batch editing form schema
     * @filament_usage Form schema for CropBatchResource editing
     * @agricultural_management Batch production operations and stage tracking
     */
    public static function schema(): array
    {
        return [
            Section::make('Batch Information')
                ->schema([
                    Placeholder::make('batch_info')
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

            Section::make('Batch-wide Updates')
                ->description('These changes will be applied to ALL crops in this batch')
                ->schema([
                    Select::make('current_stage_id')
                        ->label('Current Stage')
                        ->options(CropStage::pluck('name', 'id'))
                        ->required()
                        ->reactive()
                        ->helperText('Change the growth stage for all crops in this batch')
                        ->afterStateHydrated(function ($component, $record) {
                            if ($record) {
                                // Get the current stage from the first crop
                                $firstCrop = $record->crops()->first();
                                if ($firstCrop) {
                                    $component->state($firstCrop->current_stage_id);
                                }
                            }
                        }),

                    Textarea::make('notes')
                        ->label('Batch Notes')
                        ->rows(3)
                        ->helperText('These notes will be applied to all crops in the batch')
                        ->columnSpanFull(),
                ])
                ->columns(2),

            Section::make('Growth Stage Timestamps')
                ->description('Update stage timestamps for all crops in the batch')
                ->schema([
                    Grid::make()
                        ->schema([
                            DateTimePicker::make('soaking_at')
                                ->label('Soaking Started')
                                ->helperText('When soaking stage began')
                                ->seconds(false),
                            DateTimePicker::make('planting_at')
                                ->label('Planting Date')
                                ->helperText('When crops were planted')
                                ->seconds(false),
                            DateTimePicker::make('germination_at')
                                ->label('Germination Started')
                                ->helperText('When germination stage began')
                                ->seconds(false),
                            DateTimePicker::make('blackout_at')
                                ->label('Blackout Started')
                                ->helperText('When blackout stage began')
                                ->seconds(false),
                            DateTimePicker::make('light_at')
                                ->label('Light Started')
                                ->helperText('When light stage began')
                                ->seconds(false),
                            DateTimePicker::make('harvested_at')
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

            Section::make('Tray Numbers')
                ->description('Edit the tray numbers for crops in this batch')
                ->schema([
                    TagsInput::make('tray_numbers')
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