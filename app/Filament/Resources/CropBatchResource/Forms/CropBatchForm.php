<?php

namespace App\Filament\Resources\CropBatchResource\Forms;

use App\Filament\Resources\RecipeResource;
use App\Models\Recipe;
use App\Services\CropStageCache;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Forms\Set;

/**
 * Crop Batch Form Schema
 * Returns Filament form components for crop batch creation/editing
 */
class CropBatchForm
{
    public static function schema(): array
    {
        return [
            // Step 1: Recipe Selection
            Forms\Components\Section::make('Recipe Selection')
                ->description('Choose the recipe for this grow batch')
                ->schema([
                    Forms\Components\Select::make('recipe_id')
                        ->label('Recipe')
                        ->options(Recipe::pluck('name', 'id'))
                        ->required()
                        ->searchable()
                        ->preload()
                        ->live()
                        ->columnSpanFull(),
                ])
                ->columns(1),

            // Step 2: Soaking (if required)
            Forms\Components\Section::make('Seed Soaking')
                ->description('Pre-planting seed preparation')
                ->schema([
                    Forms\Components\Placeholder::make('soaking_info')
                        ->label('')
                        ->content(fn (Get $get) => static::getSoakingRequiredInfo($get)),
                        
                    Forms\Components\Grid::make(2)->schema([
                        Forms\Components\TextInput::make('soaking_tray_count')
                            ->label('Trays to Soak')
                            ->numeric()
                            ->required()
                            ->default(1)
                            ->minValue(1)
                            ->maxValue(50)
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                static::updateSeedQuantityCalculation($set, $get);
                            }),
                            
                        Forms\Components\Placeholder::make('seed_quantity_display')
                            ->label('Seed Required')
                            ->content(fn (Get $get) => static::getSeedQuantityDisplay($get)),
                    ]),
                    
                    Forms\Components\DateTimePicker::make('soaking_at')
                        ->label('Soaking Start Time')
                        ->seconds(false)
                        ->default(now())
                        ->required()
                        ->live()
                        ->afterStateUpdated(function ($state, Set $set, Get $get) {
                            static::updatePlantingDate($set, $get);
                        })
                        ->columnSpan(1),
                ])
                ->visible(fn (Get $get) => static::checkRecipeRequiresSoaking($get))
                ->columns(2),

            // Step 3: Planting
            Forms\Components\Section::make('Planting Schedule')
                ->description('When to plant the seeds')
                ->schema([
                    Forms\Components\Radio::make('planting_time_option')
                        ->label('Planting Time')
                        ->options([
                            'now' => 'Plant right now',
                            'scheduled' => 'Schedule for later',
                        ])
                        ->default('now')
                        ->live()
                        ->afterStateUpdated(function (Set $set, $state) {
                            if ($state === 'now') {
                                $set('germination_at', now());
                            } else {
                                $set('germination_at', null);
                            }
                        })
                        ->visible(fn (Get $get) => !static::checkRecipeRequiresSoaking($get))
                        ->columnSpanFull(),

                    Forms\Components\DateTimePicker::make('germination_at')
                        ->label('Planting Date & Time')
                        ->required()
                        ->seconds(false)
                        ->default(now())
                        ->helperText(fn (Get $get) => static::checkRecipeRequiresSoaking($get)
                            ? 'Auto-calculated from soaking completion. You can adjust if needed.'
                            : 'When the seeds will be planted')
                        ->columnSpan(1),
                ])
                ->columns(2),

            // Step 4: Tray Assignment
            Forms\Components\Section::make('Tray Assignment')
                ->description('Assign tray numbers for this batch')
                ->schema([
                    Forms\Components\TagsInput::make('tray_numbers')
                        ->label('Tray Numbers')
                        ->placeholder('Enter tray numbers (e.g., 1, 2, 3)')
                        ->separator(',')
                        ->helperText(fn (Get $get) => static::checkRecipeRequiresSoaking($get)
                            ? 'Optional for soaking batches - can be assigned when planting'
                            : 'Required - enter the tray numbers for this batch')
                        ->rules(fn (Get $get) => static::checkRecipeRequiresSoaking($get)
                            ? ['array']
                            : ['array', 'min:1'])
                        ->nestedRecursiveRules(['string', 'max:20'])
                        ->columnSpanFull(),
                ])
                ->columns(1),

            // Step 5: Additional Information
            Forms\Components\Section::make('Additional Information')
                ->schema([
                    Forms\Components\Select::make('current_stage_id')
                        ->label('Starting Stage')
                        ->options(\App\Services\CropStageCache::all()->pluck('name', 'id'))
                        ->required()
                        ->default(function (Get $get) {
                            $recipeId = $get('recipe_id');
                            if ($recipeId) {
                                $recipe = Recipe::find($recipeId);
                                if ($recipe && $recipe->requiresSoaking()) {
                                    $soakingStage = CropStageCache::findByCode('soaking');
                                    if ($soakingStage) {
                                        return $soakingStage->id;
                                    }
                                }
                            }
                            $germination = CropStageCache::findByCode('germination');
                            return $germination ? $germination->id : null;
                        })
                        ->visible(fn ($livewire) => !($livewire instanceof \App\Filament\Resources\CropBatchResource\Pages\CreateCropBatch))
                        ->columnSpan(1),
                        
                    Forms\Components\Textarea::make('notes')
                        ->label('Notes')
                        ->placeholder('Any special instructions or observations...')
                        ->rows(3)
                        ->columnSpan(1),
                ])
                ->columns(2)
                ->collapsible()
                ->collapsed(),

            // Advanced: Timestamp Management (for editing only)
            Forms\Components\Section::make('Growth Stage Timeline')
                ->description('Historical record of growth stage transitions')
                ->schema([
                    Forms\Components\Grid::make(3)->schema([
                        Forms\Components\DateTimePicker::make('soaking_at')
                            ->label('Soaking Started')
                            ->seconds(false)
                            ->disabled(fn ($record) => $record === null),
                            
                        Forms\Components\DateTimePicker::make('germination_at')
                            ->label('Planted')
                            ->seconds(false)
                            ->helperText('Changing this will adjust all subsequent stage times'),
                            
                        Forms\Components\DateTimePicker::make('blackout_at')
                            ->label('Blackout Started')
                            ->seconds(false),
                    ]),
                    
                    Forms\Components\Grid::make(2)->schema([
                        Forms\Components\DateTimePicker::make('light_at')
                            ->label('Light Started')
                            ->seconds(false),
                            
                        Forms\Components\DateTimePicker::make('harvested_at')
                            ->label('Harvested')
                            ->seconds(false),
                    ]),
                ])
                ->collapsible()
                ->collapsed()
                ->visible(fn ($record) => $record !== null),
        ];
    }
    
    /**
     * Update planting date based on soaking start time and duration
     */
    protected static function updatePlantingDate(Set $set, Get $get): void
    {
        $soakingAt = $get('soaking_at');
        $recipeId = $get('recipe_id');
        
        if ($soakingAt && $recipeId) {
            $recipe = Recipe::find($recipeId);
            if ($recipe && $recipe->seed_soak_hours > 0) {
                $soakingStart = \Carbon\Carbon::parse($soakingAt);
                $plantingDate = $soakingStart->copy()->addHours($recipe->seed_soak_hours);
                $set('germination_at', $plantingDate);
            }
        }
    }

    /**
     * Check if recipe requires soaking
     */
    public static function checkRecipeRequiresSoaking(Get $get): bool
    {
        $recipeId = $get('recipe_id');
        if (!$recipeId) {
            return false;
        }
        
        $recipe = Recipe::find($recipeId);
        return $recipe?->requiresSoaking() ?? false;
    }

    /**
     * Get soaking required information text
     */
    public static function getSoakingRequiredInfo(Get $get): string
    {
        $recipeId = $get('recipe_id');
        if (!$recipeId) {
            return '';
        }
        
        $recipe = Recipe::find($recipeId);
        if (!$recipe || !$recipe->requiresSoaking()) {
            return '';
        }
        
        return "⚠️ This recipe requires soaking for {$recipe->seed_soak_hours} hours before planting.";
    }

    /**
     * Calculate and update seed quantity based on recipe and tray count
     */
    protected static function updateSeedQuantityCalculation(Set $set, Get $get): void
    {
        $recipeId = $get('recipe_id');
        $trayCount = $get('soaking_tray_count');
        
        if ($recipeId && $trayCount) {
            $recipe = Recipe::find($recipeId);
            if ($recipe && $recipe->seed_density_grams_per_tray) {
                $totalSeed = $recipe->seed_density_grams_per_tray * $trayCount;
                $set('calculated_seed_quantity', $totalSeed);
            }
        }
    }

    /**
     * Get formatted seed quantity display
     */
    public static function getSeedQuantityDisplay(Get $get): string
    {
        $recipeId = $get('recipe_id');
        $trayCount = $get('soaking_tray_count');
        
        if (!$recipeId || !$trayCount) {
            return 'Select recipe and enter tray count to calculate seed quantity';
        }
        
        $recipe = Recipe::find($recipeId);
        if (!$recipe) {
            return 'Recipe not found';
        }
        
        if (!$recipe->seed_density_grams_per_tray) {
            return 'Recipe does not specify seed density per tray';
        }
        
        $totalSeed = $recipe->seed_density_grams_per_tray * $trayCount;
        $perTray = $recipe->seed_density_grams_per_tray;
        
        return "**{$totalSeed}g total** ({$perTray}g per tray × {$trayCount} trays)";
    }
}