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
            ->modifyQueryUsing(function (Builder $query): Builder {
                // We'll still need a few raw statements for MySQL-specific functions like GROUP_CONCAT
                // But we can use Laravel's aggregate methods for most operations
                return $query
                    ->select('recipe_id', 'planted_at', 'current_stage')
                    ->selectRaw('MIN(id) as id')
                    ->selectRaw('MIN(created_at) as created_at')
                    ->selectRaw('MIN(updated_at) as updated_at')
                    ->selectRaw('MIN(planting_at) as planting_at')
                    ->selectRaw('MIN(germination_at) as germination_at')
                    ->selectRaw('MIN(blackout_at) as blackout_at')
                    ->selectRaw('MIN(light_at) as light_at')
                    ->selectRaw('MIN(harvested_at) as harvested_at')
                    ->selectRaw('AVG(harvest_weight_grams) as harvest_weight_grams')
                    ->selectRaw('MIN(time_to_next_stage_minutes) as time_to_next_stage_minutes')
                    ->selectRaw('MIN(time_to_next_stage_status) as time_to_next_stage_status')
                    ->selectRaw('MIN(stage_age_minutes) as stage_age_minutes')
                    ->selectRaw('MIN(stage_age_status) as stage_age_status')
                    ->selectRaw('MIN(total_age_minutes) as total_age_minutes')
                    ->selectRaw('MIN(total_age_status) as total_age_status')
                    ->selectRaw('MIN(watering_suspended_at) as watering_suspended_at')
                    ->selectRaw('MIN(notes) as notes')
                    ->selectRaw('COUNT(id) as tray_count')
                    ->selectRaw('GROUP_CONCAT(DISTINCT tray_number ORDER BY tray_number SEPARATOR ", ") as tray_number_list')
                    ->groupBy(['recipe_id', 'planted_at', 'current_stage'])
                    ->when(request()->query('tableSortColumn'), function ($query, $column) {
                        $direction = request()->query('tableSortDirection', 'asc');
                        
                        // Handle special cases for sorting
                        if ($column === 'id') {
                            return $query->orderByRaw("MIN(id) {$direction}");
                        }
                        
                        // For other columns, use the column name directly
                        return $query->orderBy($column, $direction);
                    });
            })
            ->recordUrl(fn ($record) => static::getUrl('edit', ['record' => $record]))
            ->columns([
                Tables\Columns\TextColumn::make('recipe.seedVariety.name')
                    ->label('Variety')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->weight('medium')
                    ->description(fn (Crop $record): ?string => $record->recipe?->name)
                    ->size('md')
                    ->getStateUsing(function (Crop $record): ?string {
                        if (!$record->recipe) {
                            return 'No Recipe';
                        }
                        
                        // Get seed variety name if it exists
                        if ($record->recipe->seedVariety) {
                            return $record->recipe->seedVariety->name;
                        }
                        
                        // If no seed variety, only show this message
                        return 'Unknown Variety';
                    }),
                Tables\Columns\TextColumn::make('tray_count')
                    ->label('# of Trays')
                    ->formatStateUsing(fn ($state) => $state ? $state : 1)
                    ->alignCenter()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('tray_number_list')
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
                Tables\Columns\TextColumn::make('stage_age')
                    ->label('Time in Stage')
                    ->getStateUsing(fn (Crop $record): string => $record->stage_age_status ?? $record->getStageAgeStatus())
                    ->color(function (Crop $record) {
                        $stageField = "{$record->current_stage}_at";
                        if ($record->$stageField) {
                            $now = now();
                            $stageStart = $record->$stageField;
                            $totalHours = $stageStart->diffInHours($now);
                            
                            $stageDuration = match ($record->current_stage) {
                                'germination' => $record->recipe?->germination_days ?? 0,
                                'blackout' => $record->recipe?->blackout_days ?? 0,
                                'light' => $record->recipe?->light_days ?? 0,
                                default => 0,
                            };
                            
                            $expectedHours = $stageDuration * 24;
                            
                            if ($expectedHours > 0 && $totalHours > $expectedHours) {
                                return 'danger';
                            }
                        }
                        return null;
                    })
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy('stage_age_minutes', $direction);
                    })
                    ->toggleable(),
                Tables\Columns\TextColumn::make('time_to_next_stage')
                    ->label('Time to Next Stage')
                    ->getStateUsing(function (Crop $record): ?string {
                        $baseStatus = $record->time_to_next_stage_status ?? $record->timeToNextStage();
                        
                        // Check if the status includes overflow information (format: "Ready to advance|2d 4h")
                        if (str_contains($baseStatus, 'Ready to advance|')) {
                            $parts = explode('|', $baseStatus);
                            if (count($parts) === 2) {
                                $overflowText = $parts[1];
                                return "Ready to advance<br><span style=\"color: #ef4444; font-size: 0.875em;\">+{$overflowText} overdue</span>";
                            }
                        }
                        
                        // If status is "Ready to advance" without stored overflow time, calculate it now
                        if ($baseStatus === 'Ready to advance') {
                            // Get the timestamp for the current stage
                            $stageField = "{$record->current_stage}_at";
                            $stageStartTime = $record->$stageField;
                            
                            if ($stageStartTime && $record->recipe) {
                                // Get the duration for the current stage from the recipe
                                $stageDuration = match ($record->current_stage) {
                                    'germination' => $record->recipe->germination_days,
                                    'blackout' => $record->recipe->blackout_days,
                                    'light' => $record->recipe->light_days,
                                    default => 0,
                                };
                                
                                // Calculate the expected end date for this stage
                                $stageEndDate = $stageStartTime->copy()->addDays($stageDuration);
                                
                                // Calculate how much time has passed since stage should have ended
                                $now = now();
                                $overTime = $stageEndDate->diff($now);
                                $days = (int)$overTime->format('%a');
                                $hours = $overTime->h;
                                $minutes = $overTime->i;
                                
                                // Format the overtime
                                $overflowText = '';
                                if ($days > 0) {
                                    $overflowText = "{$days}d {$hours}h";
                                } elseif ($hours > 0) {
                                    $overflowText = "{$hours}h {$minutes}m";
                                } else {
                                    $overflowText = "{$minutes}m";
                                }
                                
                                return "Ready to advance<br><span style=\"color: #ef4444; font-size: 0.875em;\">+{$overflowText} overdue</span>";
                            }
                        }
                        
                        return $baseStatus;
                    })
                    ->html()
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy('time_to_next_stage_minutes', $direction);
                    })
                    ->toggleable(),
                Tables\Columns\TextColumn::make('total_age')
                    ->label('Total Age')
                    ->getStateUsing(fn (Crop $record): ?string => $record->total_age_status ?? $record->getTotalAgeStatus())
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy('planting_at', $direction);
                    })
                    ->toggleable(),
                Tables\Columns\TextColumn::make('expected_harvest_date')
                    ->label('Expected Harvest')
                    ->getStateUsing(function (Crop $record): ?string {
                        $date = $record->expectedHarvestDate();
                        return $date ? $date->format('M j, Y') : '-';
                    })
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        // Custom sorting for expected harvest date
                        return $query->orderBy('light_at', $direction);
                    })
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