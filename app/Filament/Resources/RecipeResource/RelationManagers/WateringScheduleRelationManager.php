<?php

namespace App\Filament\Resources\RecipeResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\Recipe;
use Illuminate\Support\Facades\DB;

class WateringScheduleRelationManager extends RelationManager
{
    protected static string $relationship = 'wateringSchedule';

    protected static ?string $recordTitleAttribute = 'day_number';
    
    protected static ?string $title = 'Watering Schedule';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('growth_stage')
                    ->label('Growth Stage')
                    ->options([
                        'planting' => 'Planting (Day 1)',
                        'germination' => 'Germination',
                        'blackout' => 'Blackout',
                        'light' => 'Light Phase',
                        'pre-harvest' => 'Pre-Harvest',
                    ])
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set, Recipe $ownerRecord) {
                        // Get the day range for the selected stage
                        $dayRange = $this->getDayRangeForStage($state, $ownerRecord);
                        
                        // Set default values based on the stage
                        if ($state === 'planting') {
                            $set('day_number', 1);
                            $set('water_amount_ml', 500); // Default water for planting day
                            $set('watering_method', 'top');
                        } elseif ($state === 'germination' && $dayRange['start'] !== null) {
                            $set('day_number', $dayRange['start']);
                            $set('water_amount_ml', 0); // No water during germination after planting
                            $set('watering_method', 'mist');
                        } elseif ($state === 'blackout' && $dayRange['start'] !== null) {
                            $set('day_number', $dayRange['start']);
                            $set('water_amount_ml', 500);
                            $set('watering_method', 'bottom');
                        } elseif ($state === 'light' && $dayRange['start'] !== null) {
                            $set('day_number', $dayRange['start']);
                            $set('water_amount_ml', 500);
                            $set('watering_method', 'bottom');
                        } elseif ($state === 'pre-harvest' && $dayRange['start'] !== null) {
                            $set('day_number', $dayRange['start']);
                            $set('water_amount_ml', 0); // No water during pre-harvest
                            $set('watering_method', 'mist');
                        }
                    }),
                
                Forms\Components\TextInput::make('day_number')
                    ->label('Day')
                    ->numeric()
                    ->minValue(1)
                    ->required(),
                    
                Forms\Components\TextInput::make('water_amount_ml')
                    ->label('Water Amount (ml)')
                    ->numeric()
                    ->minValue(0)
                    ->required(),
                    
                Forms\Components\Select::make('watering_method')
                    ->options([
                        'top' => 'Top Watering',
                        'bottom' => 'Bottom Watering',
                        'mist' => 'Misting',
                    ])
                    ->required(),
                    
                Forms\Components\Toggle::make('needs_liquid_fertilizer')
                    ->label('Add Liquid Fertilizer')
                    ->default(false),
                    
                Forms\Components\Textarea::make('notes')
                    ->maxLength(65535),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('day_number')
                    ->label('Day')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('stage')
                    ->label('Stage')
                    ->getStateUsing(function ($record) {
                        $recipe = $this->getOwnerRecord();
                        return $this->getStageForDay($record->day_number, $recipe);
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Planting' => 'gray',
                        'Germination' => 'info',
                        'Blackout' => 'warning',
                        'Light Phase' => 'success',
                        'Pre-Harvest' => 'danger',
                        default => 'gray',
                    }),
                    
                Tables\Columns\TextColumn::make('water_amount_ml')
                    ->label('Water Amount')
                    ->suffix(' ml')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('watering_method')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'top' => 'Top',
                        'bottom' => 'Bottom',
                        'mist' => 'Mist',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'top' => 'info',
                        'bottom' => 'success',
                        'mist' => 'warning',
                        default => 'gray',
                    }),
                    
                Tables\Columns\IconColumn::make('needs_liquid_fertilizer')
                    ->label('Fertilizer')
                    ->boolean(),
                    
                Tables\Columns\TextColumn::make('notes')
                    ->limit(30)
                    ->wrap(),
            ])
            ->defaultSort('day_number')
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Add Watering Day')
                    ->before(function (array $data) {
                        $recipe = $this->getOwnerRecord();
                        
                        // Add stage information to the record if not present
                        if (!isset($data['stage'])) {
                            $data['stage'] = $this->getStageForDay($data['day_number'], $recipe);
                        }
                        
                        return $data;
                    }),
                    
                Tables\Actions\Action::make('generate_schedule')
                    ->label('Generate Default Schedule')
                    ->action(function () {
                        $recipe = $this->getOwnerRecord();
                        $this->generateDefaultSchedule($recipe);
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
    
    /**
     * Get the day range for a specific growth stage
     */
    protected function getDayRangeForStage(string $stage, Recipe $recipe): array
    {
        $germDays = $recipe->germination_days;
        $blackoutDays = $recipe->blackout_days;
        $lightDays = $recipe->light_days;
        $totalDays = $recipe->totalDays();
        
        // Calculate start and end days for each stage
        switch ($stage) {
            case 'planting':
                return ['start' => 1, 'end' => 1];
            case 'germination':
                return ['start' => 2, 'end' => $germDays]; // Skip day 1 which is planting
            case 'blackout':
                return ['start' => $germDays + 1, 'end' => $germDays + $blackoutDays];
            case 'light':
                return ['start' => $germDays + $blackoutDays + 1, 'end' => $germDays + $blackoutDays + $lightDays];
            case 'pre-harvest':
                return ['start' => $totalDays - 1, 'end' => $totalDays];
            default:
                return ['start' => null, 'end' => null];
        }
    }
    
    /**
     * Get the stage for a specific day
     */
    protected function getStageForDay(int $day, Recipe $recipe): string
    {
        $germDays = $recipe->germination_days;
        $blackoutDays = $recipe->blackout_days;
        $lightDays = $recipe->light_days;
        $totalDays = $recipe->totalDays();
        
        if ($day === 1) {
            return 'Planting';
        } elseif ($day >= 2 && $day <= $germDays) {
            return 'Germination';
        } elseif ($day > $germDays && $day <= $germDays + $blackoutDays) {
            return 'Blackout';
        } elseif ($day > $germDays + $blackoutDays && $day <= $germDays + $blackoutDays + $lightDays - 2) {
            return 'Light Phase';
        } elseif ($day > $germDays + $blackoutDays + $lightDays - 2 && $day <= $totalDays) {
            return 'Pre-Harvest';
        } else {
            return 'Unknown';
        }
    }
    
    /**
     * Generate a default watering schedule for a recipe
     */
    protected function generateDefaultSchedule(Recipe $recipe): void
    {
        // Clear existing schedule
        $recipe->wateringSchedule()->delete();
        
        $germDays = $recipe->germination_days;
        $blackoutDays = $recipe->blackout_days;
        $lightDays = $recipe->light_days;
        $totalDays = $recipe->totalDays();
        
        // Create schedule for each day
        for ($day = 1; $day <= $totalDays; $day++) {
            $stage = $this->getStageForDay($day, $recipe);
            $waterAmount = 0;
            $wateringMethod = 'mist';
            $needsFertilizer = false;
            
            // Set default values based on stage
            switch ($stage) {
                case 'Planting':
                    $waterAmount = 500;
                    $wateringMethod = 'top';
                    break;
                case 'Germination':
                    $waterAmount = 0; // No water during germination after planting day
                    $wateringMethod = 'mist';
                    break;
                case 'Blackout':
                    $waterAmount = 500;
                    $wateringMethod = 'bottom';
                    break;
                case 'Light Phase':
                    $waterAmount = 500;
                    $wateringMethod = 'bottom';
                    $needsFertilizer = ($day - $germDays - $blackoutDays) % 3 === 0; // Fertilize every 3rd day
                    break;
                case 'Pre-Harvest':
                    $waterAmount = 0; // No water during pre-harvest
                    $wateringMethod = 'mist';
                    break;
            }
            
            // Create the watering schedule entry
            $recipe->wateringSchedule()->create([
                'day_number' => $day,
                'water_amount_ml' => $waterAmount,
                'watering_method' => $wateringMethod,
                'needs_liquid_fertilizer' => $needsFertilizer,
                'notes' => null,
            ]);
        }
    }
} 