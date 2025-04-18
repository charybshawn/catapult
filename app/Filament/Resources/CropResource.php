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
                    ->nestedRecursiveRules(['integer', 'min:1', 'max:100']),
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
                Forms\Components\Select::make('current_stage')
                    ->label('Current Stage')
                    ->options([
                        'planting' => 'Planting',
                        'germination' => 'Germination',
                        'blackout' => 'Blackout',
                        'light' => 'Light',
                        'harvested' => 'Harvested',
                    ])
                    ->required()
                    ->default('planting'),
                Forms\Components\TextInput::make('harvest_weight_grams')
                    ->label('Harvest Weight (grams)')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(10000)
                    ->helperText('Can be added at any stage, but required when harvested')
                    ->required(fn (Forms\Get $get) => $get('current_stage') === 'harvested'),
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
                        if (!$record->recipe || !$record->recipe->seedVariety) {
                            return null;
                        }
                        return $record->recipe->seedVariety->name;
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
                        'planting' => 'gray',
                        'germination' => 'info',
                        'blackout' => 'warning',
                        'light' => 'success',
                        'harvested' => 'gray',
                        default => 'gray',
                    })
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('stage_age')
                    ->label('Days in Stage')
                    ->getStateUsing(function (Crop $record) {
                        $stageField = "{$record->current_stage}_at";
                        if ($record->$stageField) {
                            return $record->$stageField->diffInDays(now());
                        }
                        return 0;
                    })
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('time_to_next_stage')
                    ->label('Time to Next Stage')
                    ->getStateUsing(function (Crop $record) {
                        // Skip if already harvested
                        if ($record->current_stage === 'harvested') {
                            return '-';
                        }
                        
                        $recipe = $record->recipe;
                        if (!$recipe) {
                            return 'No recipe';
                        }
                        
                        // Get current stage
                        $currentStage = $record->current_stage;
                        
                        // Get the timestamp for the current stage
                        $stageField = "{$currentStage}_at";
                        $stageStartTime = $record->$stageField;
                        
                        if (!$stageStartTime) {
                            return 'Unknown';
                        }
                        
                        // Determine the duration for the current stage
                        $stageDuration = match ($currentStage) {
                            'planting' => 1, // Planting is typically just 1 day
                            'germination' => $recipe->germination_days,
                            'blackout' => $recipe->blackout_days,
                            'light' => $recipe->light_days,
                            default => 0,
                        };
                        
                        // Skip the calculation for blackout if duration is 0
                        if ($currentStage === 'blackout' && $stageDuration === 0) {
                            return 'Skip blackout';
                        }
                        
                        // Calculate expected end date for this stage
                        $expectedEndDate = $stageStartTime->copy()->addDays($stageDuration);
                        
                        // Calculate time remaining
                        if ($expectedEndDate->isPast()) {
                            return 'Overdue!';
                        }
                        
                        // Calculate difference components directly instead of converting from seconds
                        $now = now();
                        $diff = $now->diff($expectedEndDate);
                        
                        $days = $diff->days;
                        $hours = $diff->h;
                        $minutes = $diff->i;
                        
                        // Format based on time remaining
                        if ($days > 0) {
                            return "{$days}d {$hours}h";
                        } elseif ($hours > 0) {
                            return "{$hours}h {$minutes}m";
                        } else {
                            return "{$minutes}m";
                        }
                    })
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('total_age')
                    ->label('Total Days')
                    ->getStateUsing(function (Crop $record) {
                        return $record->planted_at->diffInDays(now());
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
                    ->label('Growth Stage')
                    ->options([
                        'planting' => 'Planting',
                        'germination' => 'Germination',
                        'blackout' => 'Blackout',
                        'light' => 'Light',
                        'harvested' => 'Harvested',
                    ]),
            ])
            ->actions([
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
                                'planting' => 'Planting',
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
                                    'planting' => 'Planting',
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