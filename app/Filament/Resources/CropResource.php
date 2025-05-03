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
                        
                        Forms\Components\TagsInput::make('existing_tray_numbers')
                            ->label('Current Tray Numbers')
                            ->placeholder('Current trays')
                            ->separator(',')
                            ->helperText('These are the current trays in this grow batch')
                            ->disabled()
                            ->visible(fn ($livewire, $record) => !($livewire instanceof Pages\CreateCrop) && $record),
                            
                        Forms\Components\TagsInput::make('add_tray_numbers')
                            ->label('Add New Trays')
                            ->placeholder('Add more tray numbers')
                            ->separator(',')
                            ->helperText('Add additional trays to this grow batch')
                            ->rules(['array'])
                            ->nestedRecursiveRules(['integer'])
                            ->visible(fn ($livewire) => !($livewire instanceof Pages\CreateCrop)),
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
                    ->groupBy(['recipe_id', 'planted_at', 'current_stage']);
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
                    ->sortable()
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
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('total_age')
                    ->label('Total Age')
                    ->getStateUsing(fn (Crop $record): ?string => $record->total_age_status ?? $record->getTotalAgeStatus())
                    ->sortable()
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
                Tables\Actions\Action::make('advance_stage')
                    ->icon('heroicon-o-arrow-right')
                    ->tooltip('Advance all trays to next stage')
                    ->action(function (Crop $record) {
                        // Get all crops in this grow batch (same recipe_id and planted_at)
                        $crops = Crop::where('recipe_id', $record->recipe_id)
                            ->where('planted_at', $record->planted_at)
                            ->where('current_stage', $record->current_stage)
                            ->get();
                            
                        foreach ($crops as $crop) {
                            $crop->advanceStage();
                        }
                    })
                    ->visible(fn (Crop $record) => $record->current_stage !== 'harvested'),
                Tables\Actions\Action::make('harvest')
                    ->icon('heroicon-o-scissors')
                    ->tooltip('Harvest all trays')
                    ->action(function (Crop $record) {
                        // Get all crops in this grow batch (same recipe_id and planted_at)
                        $crops = Crop::where('recipe_id', $record->recipe_id)
                            ->where('planted_at', $record->planted_at)
                            ->where('current_stage', $record->current_stage)
                            ->get();
                            
                        foreach ($crops as $crop) {
                            // Check if harvest method exists, if not use advanceStage instead
                            if (method_exists($crop, 'harvest')) {
                                $crop->harvest();
                            } else {
                                $crop->advanceStage(); // Just advance to harvested stage
                            }
                        }
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
                            ->helperText('Warning: Setting to an earlier stage will clear timestamps for all later stages for all trays in this grow batch.')
                    ])
                    ->action(function (Crop $record, array $data): void {
                        // Get all crops in this grow batch (same recipe_id and planted_at)
                        $crops = Crop::where('recipe_id', $record->recipe_id)
                            ->where('planted_at', $record->planted_at)
                            ->get();
                            
                        foreach ($crops as $crop) {
                            $crop->resetToStage($data['new_stage']);
                        }
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Set Growth Stage')
                    ->modalDescription('Set all trays in this grow batch to a specific growth stage. This will clear timestamps for any later stages.'),
                Tables\Actions\EditAction::make()
                    ->tooltip('Edit grow batch'),
                Tables\Actions\DeleteAction::make()
                    ->tooltip('Delete grow batch')
                    ->requiresConfirmation()
                    ->modalDescription('Are you sure you want to delete this entire grow batch? This will delete all trays in this batch.')
                    ->action(function (Crop $record): void {
                        // Delete all crops in this grow batch (same recipe_id and planted_at)
                        Crop::where('recipe_id', $record->recipe_id)
                            ->where('planted_at', $record->planted_at)
                            ->delete();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->action(function ($records): void {
                            foreach ($records as $record) {
                                // Delete all crops in this grow batch (same recipe_id and planted_at)
                                Crop::where('recipe_id', $record->recipe_id)
                                    ->where('planted_at', $record->planted_at)
                                    ->delete();
                            }
                        }),
                    Tables\Actions\BulkAction::make('advance_stage_bulk')
                        ->label('Advance Stage')
                        ->icon('heroicon-o-arrow-right')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                if ($record->current_stage !== 'harvested') {
                                    // Get all crops in this grow batch (same recipe_id and planted_at)
                                    $crops = Crop::where('recipe_id', $record->recipe_id)
                                        ->where('planted_at', $record->planted_at)
                                        ->where('current_stage', $record->current_stage)
                                        ->get();
                                        
                                    foreach ($crops as $crop) {
                                        $crop->advanceStage();
                                    }
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
                                ->helperText('Warning: Setting to an earlier stage will clear timestamps for all later stages for all trays in the selected grow batches.')
                        ])
                        ->action(function ($records, array $data) {
                            foreach ($records as $record) {
                                // Get all crops in this grow batch (same recipe_id and planted_at)
                                $crops = Crop::where('recipe_id', $record->recipe_id)
                                    ->where('planted_at', $record->planted_at)
                                    ->get();
                                    
                                foreach ($crops as $crop) {
                                    $crop->resetToStage($data['new_stage']);
                                }
                            }
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Set Growth Stage for Selected Grows')
                        ->modalDescription('Set all trays in the selected grow batches to a specific growth stage. This will clear timestamps for any later stages.'),
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