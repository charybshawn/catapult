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

class CropResource extends Resource
{
    protected static ?string $model = Crop::class;

    protected static ?string $navigationIcon = 'heroicon-o-fire';
    protected static ?string $navigationLabel = 'Grow Trays';
    protected static ?string $navigationGroup = 'Farm Operations';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TagsInput::make('tray_numbers')
                    ->label('Tray Numbers')
                    ->placeholder('Add tray numbers')
                    ->separator(',')
                    ->helperText('Enter multiple tray numbers to create separate records for each tray')
                    ->rules(['array', 'min:1'])
                    ->nestedRecursiveRules(['integer'])
                    ->visible(fn ($livewire) => $livewire instanceof Pages\CreateCrop),
                Forms\Components\Select::make('recipe_id')
                    ->label('Recipe')
                    ->relationship('recipe', 'name')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->createOptionForm([
                        Forms\Components\TextInput::make('name')
                            ->label('Recipe Name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('seed_variety_id')
                            ->label('Seed Variety')
                            ->relationship('seedVariety', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->label('Variety Name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('crop_type')
                                    ->label('Crop Type')
                                    ->default('microgreens')
                                    ->maxLength(255),
                                Forms\Components\Toggle::make('is_active')
                                    ->label('Active')
                                    ->default(true),
                            ])
                            ->createOptionUsing(function (array $data) {
                                return \App\Models\SeedVariety::create($data)->id;
                            }),
                        Forms\Components\Grid::make([
                            Forms\Components\TextInput::make('germination_days')
                                ->label('Germination Days')
                                ->numeric()
                                ->default(3)
                                ->required(),
                            Forms\Components\TextInput::make('blackout_days')
                                ->label('Blackout Days')
                                ->numeric()
                                ->default(0)
                                ->required(),
                            Forms\Components\TextInput::make('light_days')
                                ->label('Light Days')
                                ->numeric()
                                ->default(7)
                                ->required(),
                        ])->columns(3),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ])
                    ->createOptionUsing(function (array $data) {
                        return \App\Models\Recipe::create($data)->id;
                    }),
                Forms\Components\DateTimePicker::make('planted_at')
                    ->label('Planted At')
                    ->required()
                    ->default(now()),
                Forms\Components\TextInput::make('harvest_weight_grams')
                    ->label('Harvest Weight (grams)')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(10000)
                    ->helperText('Can be added at any stage, but required when harvested')
                    ->required(fn (Forms\Get $get) => $get('current_stage') === 'harvested')
                    ->visible(fn ($livewire) => !($livewire instanceof Pages\CreateCrop)),
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
                Forms\Components\Textarea::make('notes')
                    ->label('Notes')
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->persistSortInSession()
            ->modifyQueryUsing(function (Builder $query): Builder {
                // Get the current sort column and direction
                $sortColumn = request()->query('tableFilters.0.sorts.0.column');
                $sortDirection = request()->query('tableFilters.0.sorts.0.direction') ?? 'asc';
                
                // If no sort column is explicitly set, check if there's a default
                if (empty($sortColumn)) {
                    $sortColumn = session()->get('tables.crop_resource.sorts.0.column');
                    $sortDirection = session()->get('tables.crop_resource.sorts.0.direction', 'asc');
                }
                
                // Map virtual column names to actual database columns
                $columnMapping = [
                    'time_to_next_stage' => 'time_to_next_stage_minutes',
                    'stage_age' => 'stage_age_minutes',
                    'total_age' => 'total_age_minutes',
                ];
                
                if (!empty($sortColumn) && array_key_exists($sortColumn, $columnMapping)) {
                    // Log what we're doing
                    \Illuminate\Support\Facades\Log::info('CropResource applying custom sort:', [
                        'virtual_column' => $sortColumn,
                        'database_column' => $columnMapping[$sortColumn],
                        'direction' => $sortDirection,
                    ]);
                    
                    return $query->orderBy($columnMapping[$sortColumn], $sortDirection);
                }
                
                return $query;
            })
            ->recordUrl(fn ($record) => static::getUrl('edit', ['record' => $record]))
            ->columns([
                Tables\Columns\TextColumn::make('tray_number')
                    ->label('Tray #')
                    ->sortable()
                    ->toggleable()
                    ->summarize(Tables\Columns\Summarizers\Count::make()
                        ->label('Total Trays')),
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
                Tables\Columns\TextColumn::make('planted_at')
                    ->label('Planted At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable()
                    ->summarize(Tables\Columns\Summarizers\Range::make()
                        ->label('Plant Date Range')
                        ->minimalDateTimeDifference()),
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
                    ->toggleable()
                    ->summarize(
                        Tables\Columns\Summarizers\Count::make()
                            ->query(fn ($query) => $query instanceof \Illuminate\Database\Eloquent\Builder
                                ? $query->where('current_stage', 'germination')
                                : \Illuminate\Database\Eloquent\Builder::query()->fromQuery($query)->where('current_stage', 'germination'))
                            ->label('Germination'),
                        Tables\Columns\Summarizers\Count::make()
                            ->query(fn ($query) => $query instanceof \Illuminate\Database\Eloquent\Builder
                                ? $query->where('current_stage', 'blackout')
                                : \Illuminate\Database\Eloquent\Builder::query()->fromQuery($query)->where('current_stage', 'blackout'))
                            ->label('Blackout'),
                        Tables\Columns\Summarizers\Count::make()
                            ->query(fn ($query) => $query instanceof \Illuminate\Database\Eloquent\Builder
                                ? $query->where('current_stage', 'light')
                                : \Illuminate\Database\Eloquent\Builder::query()->fromQuery($query)->where('current_stage', 'light'))
                            ->label('Light'),
                        Tables\Columns\Summarizers\Count::make()
                            ->query(fn ($query) => $query instanceof \Illuminate\Database\Eloquent\Builder
                                ? $query->where('current_stage', 'harvested')
                                : \Illuminate\Database\Eloquent\Builder::query()->fromQuery($query)->where('current_stage', 'harvested'))
                            ->label('Harvested')
                    ),
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
                    ->sortable()
                    ->toggleable()
                    ->summarize(
                        Tables\Columns\Summarizers\Summarizer::make()
                            ->label('Stage Age Stats')
                            ->using(function ($query): string {
                                // Get the records in this group
                                $crops = ($query instanceof \Illuminate\Database\Eloquent\Builder)
                                    ? $query->get()
                                    : \Illuminate\Database\Eloquent\Builder::query()->fromQuery($query)->get();
                                
                                // Count overdue crops (in "danger" state)
                                $overdueCount = $crops->filter(function ($crop) {
                                    $stageField = "{$crop->current_stage}_at";
                                    if ($crop->$stageField) {
                                        $now = now();
                                        $stageStart = $crop->$stageField;
                                        $totalHours = $stageStart->diffInHours($now);
                                        
                                        $stageDuration = match ($crop->current_stage) {
                                            'germination' => $crop->recipe?->germination_days ?? 0,
                                            'blackout' => $crop->recipe?->blackout_days ?? 0,
                                            'light' => $crop->recipe?->light_days ?? 0,
                                            default => 0,
                                        };
                                        
                                        $expectedHours = $stageDuration * 24;
                                        
                                        return $expectedHours > 0 && $totalHours > $expectedHours;
                                    }
                                    return false;
                                })->count();
                                
                                if ($overdueCount > 0) {
                                    return "{$overdueCount} overdue trays";
                                }
                                
                                return "All trays on schedule";
                            })
                    ),
                Tables\Columns\TextColumn::make('time_to_next_stage')
                    ->label('Time to Next Stage')
                    ->getStateUsing(fn (Crop $record): ?string => $record->time_to_next_stage_status)
                    ->sortable()
                    ->toggleable()
                    ->summarize(
                        Tables\Columns\Summarizers\Summarizer::make()
                            ->label('Ready Trays')
                            ->using(function ($query): string {
                                // Get count of crops that are ready to advance
                                $readyCount = ($query instanceof \Illuminate\Database\Eloquent\Builder)
                                    ? $query->where('time_to_next_stage_status', 'Ready to advance')->count()
                                    : \Illuminate\Database\Eloquent\Builder::query()->fromQuery($query)->where('time_to_next_stage_status', 'Ready to advance')->count();
                                
                                if ($readyCount > 0) {
                                    return "{$readyCount} trays ready";
                                }
                                
                                return "No trays ready";
                            })
                    ),
                Tables\Columns\TextColumn::make('total_age')
                    ->label('Total Age')
                    ->getStateUsing(fn (Crop $record): ?string => $record->total_age_status ?? $record->getTotalAgeStatus())
                    ->sortable()
                    ->toggleable()
                    ->summarize(
                        Tables\Columns\Summarizers\Summarizer::make()
                            ->label('Age Range')
                            ->using(function ($query): string {
                                $minAge = ($query instanceof \Illuminate\Database\Eloquent\Builder)
                                    ? $query->min('total_age_minutes')
                                    : \Illuminate\Database\Eloquent\Builder::query()->fromQuery($query)->min('total_age_minutes');
                                $maxAge = ($query instanceof \Illuminate\Database\Eloquent\Builder)
                                    ? $query->max('total_age_minutes')
                                    : \Illuminate\Database\Eloquent\Builder::query()->fromQuery($query)->max('total_age_minutes');
                                
                                // Convert minutes to days for display
                                $minDays = floor($minAge / (24 * 60));
                                $maxDays = floor($maxAge / (24 * 60));
                                
                                if ($minDays == $maxDays) {
                                    return "{$minDays} days";
                                }
                                
                                return "{$minDays}-{$maxDays} days";
                            })
                    ),
                Tables\Columns\TextColumn::make('planting_at')
                    ->label('Planting Started')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('germination_at')
                    ->label('Germination Started')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('blackout_at')
                    ->label('Blackout Started')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('light_at')
                    ->label('Light Started')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('harvested_at')
                    ->label('Harvested At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('harvest_weight_grams')
                    ->label('Harvest Weight')
                    ->formatStateUsing(fn ($state) => $state ? "{$state}g" : '-')
                    ->sortable()
                    ->toggleable()
                    ->summarize([
                        Tables\Columns\Summarizers\Sum::make()
                            ->numeric(0)
                            ->label('Total Harvested'),
                        Tables\Columns\Summarizers\Average::make()
                            ->numeric(0)
                            ->label('Avg. Weight/Tray'),
                    ]),
                Tables\Columns\TextColumn::make('order_id')
                    ->label('Order ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('watering_suspended')
                    ->label('Watering Suspended')
                    ->boolean()
                    ->getStateUsing(fn (Crop $record): bool => $record->isWateringSuspended())
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('watering_suspended_at')
                    ->label('Watering Suspended At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->groups([
                Tables\Grouping\Group::make('recipe.seedVariety.name')
                    ->label('Variety')
                    ->getDescriptionFromRecordUsing(function ($record) {
                        if (!$record->recipe) {
                            return null;
                        }
                        
                        return $record->recipe->name . ' - ' . 
                            $record->recipe->germination_days . 'd germ / ' . 
                            $record->recipe->blackout_days . 'd blackout / ' . 
                            $record->recipe->light_days . 'd light';
                    })
                    ->collapsible(),
                Tables\Grouping\Group::make('planted_at')
                    ->label('Plant Date')
                    ->date()
                    ->getDescriptionFromRecordUsing(function ($record) {
                        if (!$record->expectedHarvestDate()) {
                            return null;
                        }
                        
                        return 'Expected Harvest: ' . $record->expectedHarvestDate()->format('Y-m-d');
                    }),
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
                Tables\Actions\Action::make('debug_data')
                    ->label('Debug Data')
                    ->icon('heroicon-o-bug-ant')
                    ->modalHeading('Debug Data for Crop')
                    ->modalContent(function (Model $record) {
                        $stageStartTime = $record->{$record->current_stage . '_at'};
                        $stageDuration = match ($record->current_stage) {
                            'germination' => $record->recipe->germination_days,
                            'blackout' => $record->recipe->blackout_days,
                            'light' => $record->recipe->light_days,
                            default => 0,
                        };
                        $hourDuration = $stageDuration * 24;
                        $expectedEndDate = $stageStartTime ? $stageStartTime->copy()->addHours($hourDuration) : null;
                        $totalStageDiff = $stageStartTime && $expectedEndDate ? $stageStartTime->diff($expectedEndDate) : null;
                        $totalHours = $hourDuration;
                        $elapsedHours = $stageStartTime ? $stageStartTime->diffInHours(now()) : 0;
                        $elapsedPercent = 0;
                        
                        if ($stageStartTime && $expectedEndDate) {
                            $totalDuration = $stageStartTime->diffInSeconds($expectedEndDate);
                            $elapsed = $stageStartTime->diffInSeconds(now());
                            $elapsedPercent = min(100, round(($elapsed / max(1, $totalDuration)) * 100));
                        }
                        
                        // Add stage calculation data for table display
                        $stage_data = [
                            'current_stage' => $record->current_stage,
                            'stage_start_time' => $stageStartTime ? $stageStartTime->format('Y-m-d H:i:s') : 'N/A',
                            'stage_duration_days' => $stageDuration,
                            'stage_duration_hours' => $hourDuration,
                            'expected_end_date' => $expectedEndDate ? $expectedEndDate->format('Y-m-d H:i:s') : 'N/A',
                            'elapsed_hours' => $elapsedHours,
                            'elapsed_percent' => $elapsedPercent . '%',
                            'time_remaining' => $expectedEndDate ? now()->diffForHumans($expectedEndDate, ['parts' => 2]) : 'N/A',
                        ];
                        
                        // Add recipe data
                        $recipe_data = [];
                        if ($record->recipe) {
                            $recipe_data = [
                                'name' => $record->recipe->name,
                                'germination_days' => $record->recipe->germination_days,
                                'blackout_days' => $record->recipe->blackout_days,
                                'light_days' => $record->recipe->light_days,
                                'total_days' => $record->recipe->total_days,
                            ];
                        }
                        
                        // Add timestamps data
                        $timestamps = [
                            'created_at' => $record->created_at ? $record->created_at->format('Y-m-d H:i:s') : 'N/A',
                            'planted_at' => $record->planted_at ? $record->planted_at->format('Y-m-d H:i:s') : 'N/A',
                            'germination_at' => $record->germination_at ? $record->germination_at->format('Y-m-d H:i:s') : 'N/A',
                            'blackout_at' => $record->blackout_at ? $record->blackout_at->format('Y-m-d H:i:s') : 'N/A',
                            'light_at' => $record->light_at ? $record->light_at->format('Y-m-d H:i:s') : 'N/A',
                            'harvested_at' => $record->harvested_at ? $record->harvested_at->format('Y-m-d H:i:s') : 'N/A',
                            'expected_harvest_date' => $record->expectedHarvestDate() ? $record->expectedHarvestDate()->format('Y-m-d H:i:s') : 'N/A',
                        ];
                        
                        return view('filament.resources.crop-resource.debug', [
                            'crop' => $record,
                            'recipe' => $record->recipe,
                            'stageStartTime' => $stageStartTime,
                            'stageDuration' => $stageDuration,
                            'hourDuration' => $hourDuration,
                            'expectedEndDate' => $expectedEndDate,
                            'totalStageDiff' => $totalStageDiff,
                            'totalHours' => $totalHours,
                            'elapsedHours' => $elapsedHours,
                            'elapsedPercent' => $elapsedPercent,
                            'stage_data' => $stage_data,
                            'recipe_data' => $recipe_data,
                            'timestamps' => $timestamps,
                        ]);
                    })
                    ->visible(true),
                Tables\Actions\Action::make('advance_stage')
                    ->icon('heroicon-o-arrow-right')
                    ->tooltip('Advance to next stage')
                    ->action(function (Crop $record) {
                        $record->advanceStage();
                    })
                    ->visible(fn (Crop $record) => $record->current_stage !== 'harvested'),
                Tables\Actions\Action::make('harvest')
                    ->icon('heroicon-o-scissors')
                    ->tooltip('Harvest crop')
                    ->action(function (Crop $record) {
                        $record->harvest();
                    })
                    ->visible(fn (Crop $record) => $record->current_stage === 'light'),
                Tables\Actions\Action::make('set_stage')
                    ->label('Set Stage')
                    ->icon('heroicon-o-arrow-path')
                    ->form([
                        Forms\Components\Select::make('new_stage')
                            ->label('Growth Stage')
                            ->options([
                                'germination' => 'Germination',
                                'blackout' => 'Blackout',
                                'light' => 'Light',
                                'harvested' => 'Harvested',
                            ])
                            ->required()
                            ->helperText('Warning: Setting to an earlier stage will clear timestamps for all later stages.')
                    ])
                    ->action(function (Crop $record, array $data): void {
                        $record->resetToStage($data['new_stage']);
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Set Growth Stage')
                    ->modalDescription('Set the crop to a specific growth stage. This will clear timestamps for any later stages.'),
                Tables\Actions\EditAction::make()
                    ->tooltip('Edit crop'),
                Tables\Actions\DeleteAction::make()
                    ->tooltip('Delete crop')
                    ->requiresConfirmation(),
                Tables\Actions\Action::make('debug_sort')
                    ->label('Debug Sort')
                    ->icon('heroicon-o-bug-ant')
                    ->modalHeading('Debug Sort Data')
                    ->modalContent(function (Model $record) {
                        // Build debug info array
                        $sortData = [
                            'id' => $record->id,
                            'tray_number' => $record->tray_number,
                            'current_stage' => $record->current_stage,
                            'time_to_next_stage_status' => $record->time_to_next_stage_status,
                            'time_to_next_stage_minutes' => $record->time_to_next_stage_minutes,
                            'stage_age_status' => $record->stage_age_status,
                            'stage_age_minutes' => $record->stage_age_minutes,
                            'total_age_status' => $record->total_age_status,
                            'total_age_minutes' => $record->total_age_minutes,
                        ];
                        
                        // Return debug view with data
                        return view('filament.resources.crop-resource.debug-sort', [
                            'sortData' => $sortData,
                        ]);
                    })
                    ->visible(true),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('advance_stage_bulk')
                        ->label('Advance Stage')
                        ->icon('heroicon-o-arrow-right')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                if ($record->current_stage !== 'harvested') {
                                    $record->advanceStage();
                                }
                            }
                        }),
                    Tables\Actions\BulkAction::make('set_stage_bulk')
                        ->label('Set Stage')
                        ->icon('heroicon-o-arrow-path')
                        ->form([
                            Forms\Components\Select::make('new_stage')
                                ->label('Growth Stage')
                                ->options([
                                    'germination' => 'Germination',
                                    'blackout' => 'Blackout',
                                    'light' => 'Light',
                                    'harvested' => 'Harvested',
                                ])
                                ->required()
                                ->helperText('Warning: Setting to an earlier stage will clear timestamps for all later stages.')
                        ])
                        ->action(function ($records, array $data) {
                            foreach ($records as $record) {
                                $record->resetToStage($data['new_stage']);
                            }
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Set Growth Stage for Selected Crops')
                        ->modalDescription('Set all selected crops to a specific growth stage. This will clear timestamps for any later stages.'),
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