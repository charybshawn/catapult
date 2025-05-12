<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CropResource\Pages;
use App\Models\Crop;
use App\Models\Recipe;
use App\Models\Order;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Filament\Resources\RecipeResource;
use Filament\Forms\Components\Actions\Action as FilamentAction;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;

class CropResource extends Resource
{
    protected static ?string $model = Crop::class;

    protected static ?string $navigationIcon = 'heroicon-o-fire';
    protected static ?string $navigationLabel = 'Grows';
    protected static ?string $navigationGroup = 'Farm Operations';
    protected static ?int $navigationSort = 2;
    
    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Grow Details')
                    ->schema([
                        Forms\Components\Select::make('recipe_id')
                            ->label('Recipe')
                            ->relationship('recipe', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->createOptionForm(RecipeResource::getFormSchema()),

                        Forms\Components\DateTimePicker::make('planted_at')
                            ->label('Planted At')
                            ->required()
                            ->default(now()),
                        Forms\Components\Select::make('current_stage')
                            ->label('Current Stage')
                            ->options([
                                'germination' => 'Germination',
                                'blackout' => 'Blackout',
                                'light' => 'Light',
                                'harvested' => 'Harvested',
                            ])
                            ->required()
                            ->default('germination')
                            ->visible(fn ($livewire) => !($livewire instanceof Pages\CreateCrop)),
                        Forms\Components\TextInput::make('harvest_weight_grams')
                            ->label('Harvest Weight Per Tray (grams)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(10000)
                            ->helperText('Can be added at any stage, but required when harvested')
                            ->required(fn (Forms\Get $get) => $get('current_stage') === 'harvested')
                            ->visible(fn ($livewire) => !($livewire instanceof Pages\CreateCrop)),
                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Tray Management')
                    ->schema([
                        Forms\Components\TagsInput::make('tray_numbers')
                            ->label('Tray Numbers')
                            ->placeholder('Add tray numbers')
                            ->separator(',')
                            ->helperText('Enter tray numbers for this grow batch')
                            ->rules(['array', 'min:1'])
                            ->nestedRecursiveRules(['integer'])
                            ->visible(fn ($livewire) => $livewire instanceof Pages\CreateCrop),
                        
                        Forms\Components\TagsInput::make('tray_numbers')
                            ->label('Tray Numbers')
                            ->placeholder('Edit tray numbers')
                            ->separator(',')
                            ->helperText('Edit the tray numbers for this grow batch')
                            ->rules(['array', 'min:1'])
                            ->nestedRecursiveRules(['integer'])
                            ->visible(fn ($livewire) => !($livewire instanceof Pages\CreateCrop))
                            ->afterStateHydrated(function ($component, $state) {
                                if (is_array($state)) {
                                    $component->state(array_values($state));
                                }
                            }),
                    ]),
                
                Forms\Components\Section::make('Growth Stage Timestamps')
                    ->description('Record of when each growth stage began')
                    ->schema([
                        Forms\Components\Grid::make()
                            ->schema([
                                Forms\Components\DateTimePicker::make('planting_at')
                                    ->label('Planting')
                                    ->disabled(),
                                Forms\Components\DateTimePicker::make('germination_at')
                                    ->label('Germination')
                                    ->disabled(),
                                Forms\Components\DateTimePicker::make('blackout_at')
                                    ->label('Blackout')
                                    ->disabled(),
                                Forms\Components\DateTimePicker::make('light_at')
                                    ->label('Light')
                                    ->disabled(),
                                Forms\Components\DateTimePicker::make('harvested_at')
                                    ->label('Harvested')
                                    ->disabled(),
                            ])
                            ->columns(3),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->visible(fn ($record) => $record !== null),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->persistSortInSession()
            ->columns([
                Tables\Columns\TextColumn::make('recipe.seedVariety.name')
                    ->label('Variety')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->weight('medium')
                    ->description(fn (Crop $record): ?string => $record->recipe?->name)
                    ->size('md'),
                Tables\Columns\TextColumn::make('tray_count')
                    ->label('# of Trays')
                    ->sortable()
                    ->alignCenter()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('tray_numbers')
                    ->label('Tray Numbers')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->wrap(),
                Tables\Columns\TextColumn::make('planted_at')
                    ->label('Planted')
                    ->date()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('current_stage')
                    ->label('Current Stage')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->color(fn (string $state): string => match ($state) {
                        'germination' => 'info',
                        'blackout' => 'warning',
                        'light' => 'success',
                        'harvested' => 'gray',
                        default => 'gray',
                    })
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('stage_age_display')
                    ->label('Time in Stage')
                    ->sortable('stage_age_minutes')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('time_to_next_stage_display')
                    ->label('Time to Next Stage')
                    ->sortable('time_to_next_stage_minutes')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('total_age_display')
                    ->label('Total Age')
                    ->sortable('total_age_minutes')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('expected_harvest_at')
                    ->label('Expected Harvest')
                    ->date()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('harvest_weight_grams')
                    ->label('Harvest Weight')
                    ->formatStateUsing(fn ($state, $record) => 
                        $state && isset($record->tray_count) 
                            ? ($state * $record->tray_count) . "g total / " . $state . "g per tray" 
                            : ($state ? $state . "g" : '-')
                    )
                    ->sortable()
                    ->toggleable(),
            ])
            ->groups([
                Tables\Grouping\Group::make('recipe.seedVariety.name')
                    ->label('Variety')
                    ->collapsible(),
                Tables\Grouping\Group::make('planted_at')
                    ->label('Plant Date')
                    ->date(),
                Tables\Grouping\Group::make('current_stage')
                    ->label('Growth Stage'),
            ])
            ->defaultGroup('recipe.seedVariety.name')
            ->filters([
                Tables\Filters\SelectFilter::make('current_stage')
                    ->label('Stage')
                    ->options([
                        'germination' => 'Germination',
                        'blackout' => 'Blackout',
                        'light' => 'Light',
                        'harvested' => 'Harvested',
                    ]),
            ])
            ->actions([
                Action::make('advanceStage')
                    ->label(function (Crop $record): string {
                        $nextStage = $record->getNextStage();
                        return $nextStage ? 'Advance to ' . ucfirst($nextStage) : 'Harvested';
                    })
                    ->icon('heroicon-o-chevron-double-right')
                    ->color('success')
                    ->visible(fn (Crop $record): bool => $record->current_stage !== 'harvested')
                    ->requiresConfirmation()
                    ->modalHeading(function (Crop $record): string {
                        $nextStage = $record->getNextStage();
                        return 'Advance to ' . ucfirst($nextStage) . '?';
                    })
                    ->modalDescription('This will update the current stage of the crop and mark the corresponding task as completed.')
                    ->action(function (Crop $record) {
                        $nextStage = $record->getNextStage();
                        
                        if (!$nextStage) {
                            \Filament\Notifications\Notification::make()
                                ->title('Already Harvested')
                                ->body('This crop has already reached its final stage.')
                                ->warning()
                                ->send();
                            return;
                        }

                        // Update Crop stage and timestamp
                        $timestampField = "{$nextStage}_at";
                        $record->current_stage = $nextStage;
                        $record->$timestampField = now();
                        $record->save();
                        
                        // Deactivate the corresponding TaskSchedule
                        $task = \App\Models\TaskSchedule::where('resource_type', 'crops')
                            ->where('conditions->crop_id', $record->id)
                            ->where('conditions->target_stage', $nextStage)
                            ->where('is_active', true)
                            ->first();
                            
                        if ($task) {
                            $task->update([
                                'is_active' => false,
                                'last_run_at' => now(),
                            ]);
                        }
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Stage Advanced')
                            ->body("Crop successfully advanced to {$nextStage}.")
                            ->success()
                            ->send();
                    }),
                Action::make('harvest')
                    ->label('Harvest')
                    ->icon('heroicon-o-scissors')
                    ->color('success')
                    ->visible(fn (Crop $record): bool => $record->current_stage === 'light')
                    ->requiresConfirmation()
                    ->modalHeading('Harvest Crop?')
                    ->modalDescription('This will mark the crop as harvested and record the harvest weight.')
                    ->form([
                        Forms\Components\TextInput::make('harvest_weight_grams')
                            ->label('Harvest Weight Per Tray (grams)')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->maxValue(10000),
                    ])
                    ->action(function (Crop $record, array $data) {
                        $record->current_stage = 'harvested';
                        $record->harvested_at = now();
                        $record->harvest_weight_grams = $data['harvest_weight_grams'];
                        $record->save();
                        
                        // Deactivate any active task schedules for this crop
                        \App\Models\TaskSchedule::where('resource_type', 'crops')
                            ->where('conditions->crop_id', $record->id)
                            ->where('is_active', true)
                            ->update([
                                'is_active' => false,
                                'last_run_at' => now(),
                            ]);
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Crop Harvested')
                            ->body('The crop has been successfully harvested.')
                            ->success()
                            ->send();
                    }),
                Action::make('suspendWatering')
                    ->label('Suspend Watering')
                    ->icon('heroicon-o-no-symbol')
                    ->color('warning')
                    ->visible(fn (Crop $record): bool => $record->current_stage === 'light' && !$record->isWateringSuspended())
                    ->requiresConfirmation()
                    ->modalHeading('Suspend Watering?')
                    ->modalDescription('This will mark watering as suspended for this crop and complete the corresponding task.')
                    ->action(function (Crop $record) {
                        // Suspend watering on the Crop model
                        $record->suspendWatering(); // This method should set the timestamp and save

                        // Deactivate the corresponding TaskSchedule
                        $task = \App\Models\TaskSchedule::where('resource_type', 'crops')
                            ->where('conditions->crop_id', $record->id)
                            ->where('task_name', 'suspend_watering') // Match the task name
                            ->where('is_active', true)
                            ->first();
                            
                        if ($task) {
                            $task->update([
                                'is_active' => false,
                                'last_run_at' => now(),
                            ]);
                        }
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Watering Suspended')
                            ->body('Watering has been successfully suspended for this crop.')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCrops::route('/'),
            'create' => Pages\CreateCrop::route('/create'),
            'edit' => Pages\EditCrop::route('/{record}/edit'),
        ];
    }
} 