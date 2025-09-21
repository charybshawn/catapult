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
    /**
     * Returns Filament form schema - NOT a custom form system
     */
    public static function schema(): array
    {
        return [
            Forms\Components\Section::make('Grow Details')
                ->schema([
                    Forms\Components\Select::make('recipe_id')
                        ->label('Recipe')
                        ->options(Recipe::pluck('name', 'id'))
                        ->required()
                        ->searchable()
                        ->preload(),

                    Forms\Components\Section::make('Soaking Information')
                        ->schema([
                            Forms\Components\Placeholder::make('soaking_required_info')
                                ->label('')
                                ->content(fn (Get $get) => static::getSoakingRequiredInfo($get))
                                ->visible(fn (Get $get) => static::checkRecipeRequiresSoaking($get)),
                            Forms\Components\TextInput::make('soaking_duration_display')
                                ->label('Soaking Duration')
                                ->disabled()
                                ->visible(fn (Get $get) => static::checkRecipeRequiresSoaking($get))
                                ->dehydrated(false),
                            Forms\Components\TextInput::make('soaking_tray_count')
                                ->label('Number of Trays to Soak')
                                ->numeric()
                                ->required(fn (Get $get) => static::checkRecipeRequiresSoaking($get))
                                ->default(1)
                                ->minValue(1)
                                ->maxValue(50)
                                ->visible(fn (Get $get) => static::checkRecipeRequiresSoaking($get))
                                ->reactive()
                                ->helperText('How many trays worth of seed will be soaked?')
                                ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                    static::updateSeedQuantityCalculation($set, $get);
                                }),
                            Forms\Components\Placeholder::make('seed_quantity_display')
                                ->label('Seed Quantity Required')
                                ->content(fn (Get $get) => static::getSeedQuantityDisplay($get))
                                ->visible(fn (Get $get) => static::checkRecipeRequiresSoaking($get)),
                        ])
                        ->visible(fn (Get $get) => static::checkRecipeRequiresSoaking($get))
                        ->compact(),

                    Forms\Components\DateTimePicker::make('soaking_at')
                        ->label('Soaking Started At')
                        ->seconds(false)
                        ->default(now())
                        ->visible(fn (Get $get) => static::checkRecipeRequiresSoaking($get))
                        ->required(fn (Get $get) => static::checkRecipeRequiresSoaking($get))
                        ->reactive()
                        ->afterStateUpdated(function ($state, Set $set, Get $get) {
                            static::updatePlantingDate($set, $get);
                        }),

                    Forms\Components\Section::make('Planting Time')
                        ->schema([
                            Forms\Components\Radio::make('planting_time_option')
                                ->label('When would you like to plant?')
                                ->options([
                                    'now' => 'Right now',
                                    'scheduled' => 'Set a specific date and time',
                                ])
                                ->default('now')
                                ->live()
                                ->afterStateUpdated(function (Set $set, $state) {
                                    if ($state === 'now') {
                                        $set('germination_at', now());
                                    } elseif ($state === 'scheduled') {
                                        // Clear the field so user can set their own time
                                        $set('germination_at', null);
                                    }
                                })
                                ->visible(fn (Get $get) => !static::checkRecipeRequiresSoaking($get))
                                ->columnSpanFull(),

                            Forms\Components\DateTimePicker::make('germination_at')
                                ->label('Planting Date')
                                ->required(fn (Get $get) => !static::checkRecipeRequiresSoaking($get))
                                ->default(now())
                                ->seconds(false)
                                ->helperText(fn (Get $get) => static::checkRecipeRequiresSoaking($get)
                                    ? 'Auto-calculated from soaking start time + duration. You can override if needed.'
                                    : 'When the crop will be planted')
                                ->columnSpanFull(),
                        ])
                        ->compact()
                        ->columnSpanFull(),
                    Forms\Components\Select::make('current_stage_id')
                        ->label('Current Stage')
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
                        ->visible(fn ($livewire) => !($livewire instanceof \App\Filament\Resources\CropBatchResource\Pages\CreateCropBatch)),
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
                        ->placeholder('Add tray numbers (e.g., 1, 2, 3)')
                        ->separator(',')
                        ->helperText(fn (Get $get) => static::checkRecipeRequiresSoaking($get)
                            ? 'Optional for soaking crops - tray numbers can be assigned later'
                            : 'Enter tray numbers or IDs for this grow batch (alphanumeric supported)')
                        ->rules(fn (Get $get) => static::checkRecipeRequiresSoaking($get)
                            ? ['array']
                            : ['array', 'min:1'])
                        ->nestedRecursiveRules(['string', 'max:20']),
                ]),
            
            Forms\Components\Section::make('Growth Stage Timestamps')
                ->description('Record of when each growth stage began')
                ->schema([
                    Forms\Components\Grid::make()
                        ->schema([
                            Forms\Components\DateTimePicker::make('soaking_at')
                                ->label('Soaking')
                                ->helperText('When soaking stage began')
                                ->seconds(false),
                            Forms\Components\DateTimePicker::make('germination_at')
                                ->label('Planting')
                                ->helperText('Changes to planting date will adjust all stage timestamps proportionally')
                                ->seconds(false),
                            
                            Forms\Components\DateTimePicker::make('blackout_at')
                                ->label('Blackout')
                                ->helperText('When blackout stage began')
                                ->seconds(false),
                            Forms\Components\DateTimePicker::make('light_at')
                                ->label('Light')
                                ->helperText('When light stage began')
                                ->seconds(false),
                            Forms\Components\DateTimePicker::make('harvested_at')
                                ->label('Harvested')
                                ->helperText('When crop was harvested')
                                ->seconds(false),
                        ])
                        ->columns(3),
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