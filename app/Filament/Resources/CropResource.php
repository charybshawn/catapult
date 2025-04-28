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
                    ->nestedRecursiveRules(['integer', 'min:1', 'max:100'])
                    ->visible(fn ($livewire) => $livewire instanceof Pages\CreateCrop),
                Forms\Components\Select::make('recipe_id')
                    ->label('Recipe')
                    ->relationship('recipe', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
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
            ->recordUrl(fn ($record) => static::getUrl('edit', ['record' => $record]))
            ->columns([
                Tables\Columns\TextColumn::make('tray_number')
                    ->label('Tray #')
                    ->sortable()
                    ->toggleable(),
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
                    ->getStateUsing(function (Crop $record) {
                        $stageField = "{$record->current_stage}_at";
                        if ($record->$stageField) {
                            $now = now();
                            $stageStart = $record->$stageField;
                            
                            // Calculate total time difference
                            $totalHours = $stageStart->diffInHours($now);
                            $totalMinutes = $stageStart->diffInMinutes($now) % 60;
                            $totalDays = floor($totalHours / 24);
                            $remainingHours = $totalHours % 24;
                            
                            // Get the expected duration for this stage
                            $stageDuration = match ($record->current_stage) {
                                'germination' => $record->recipe?->germination_days ?? 0,
                                'blackout' => $record->recipe?->blackout_days ?? 0,
                                'light' => $record->recipe?->light_days ?? 0,
                                default => 0,
                            };
                            
                            $expectedHours = $stageDuration * 24;
                            
                            // Format based on total time
                            $timeDisplay = '';
                            if ($totalDays > 0) {
                                $timeDisplay = "{$totalDays}d {$remainingHours}h";
                            } elseif ($remainingHours > 0) {
                                $timeDisplay = "{$remainingHours}h {$totalMinutes}m";
                            } else {
                                $timeDisplay = "{$totalMinutes}m";
                            }
                            
                            // Add overdue indicator if applicable
                            if ($expectedHours > 0 && $totalHours > $expectedHours) {
                                $overdueHours = $totalHours - $expectedHours;
                                $overdueDays = floor($overdueHours / 24);
                                $overdueHours = $overdueHours % 24;
                                
                                if ($overdueDays > 0) {
                                    $timeDisplay .= " (Overdue by {$overdueDays}d {$overdueHours}h)";
                                } else {
                                    $timeDisplay .= " (Overdue by {$overdueHours}h)";
                                }
                            }
                            
                            return $timeDisplay;
                        }
                        return '0m';
                    })
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
                    ->getStateUsing(fn (Crop $record): ?string => $record->timeToNextStage())
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('total_age')
                    ->label('Total Age')
                    ->getStateUsing(function (Crop $record) {
                        if ($record->planted_at) {
                            $now = now();
                            $plantedAt = $record->planted_at;
                            
                            // Calculate total time difference
                            $totalHours = $plantedAt->diffInHours($now);
                            $totalMinutes = $plantedAt->diffInMinutes($now) % 60;
                            $totalDays = floor($totalHours / 24);
                            $remainingHours = $totalHours % 24;
                            
                            // Format based on total time
                            if ($totalDays > 0) {
                                return "{$totalDays}d {$remainingHours}h";
                            } elseif ($remainingHours > 0) {
                                return "{$remainingHours}h {$totalMinutes}m";
                            } else {
                                return "{$totalMinutes}m";
                            }
                        }
                        return '0m';
                    })
                    ->sortable()
                    ->toggleable(),
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
                    ->toggleable(),
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
                Action::make('debug_data')
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
                    ->label('Advance Stage')
                    ->icon('heroicon-o-arrow-right')
                    ->action(function (Crop $record): void {
                        $record->advanceStage();
                    })
                    ->visible(fn (Crop $record): bool => $record->current_stage !== 'harvested'),
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
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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