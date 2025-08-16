<?php

namespace App\Filament\Resources\CropResource\Forms;

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
                        ->preload()
                        ->reactive()
                        ->createOptionForm(\App\Filament\Resources\RecipeResource\Forms\RecipeForm::schema())
                        ->afterStateUpdated(function ($state, Set $set, Get $get) {
                            // Update soaking information when recipe changes
                            if ($state) {
                                $recipe = Recipe::find($state);
                                if ($recipe && $recipe->requiresSoaking()) {
                                    $set('soaking_duration_display', $recipe->seed_soak_hours . ' hours');
                                    
                                    // Only set soaking_at if it's not already set by the user
                                    if (!$get('soaking_at')) {
                                        $set('soaking_at', now());
                                    }
                                    
                                    static::updatePlantingDate($set, $get);
                                    static::updateSeedQuantityCalculation($set, $get);
                                }
                            }
                        }),

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

                    Forms\Components\DateTimePicker::make('germination_at')
                        ->label('Germination Date')
                        ->required()
                        ->default(now())
                        ->seconds(false)
                        ->helperText(fn (Get $get) => static::checkRecipeRequiresSoaking($get)
                            ? 'Auto-calculated from soaking start time + duration. You can override if needed.'
                            : 'When the crop will be planted'),
                    
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
                        ->helperText(fn (Get $get) => static::checkRecipeRequiresSoaking($get) 
                            ? 'Optional for soaking crops - tray numbers can be assigned later'
                            : 'Enter tray numbers or IDs for this grow batch (alphanumeric supported)')
                        ->rules(fn (Get $get) => static::checkRecipeRequiresSoaking($get) 
                            ? ['array'] 
                            : ['array', 'min:1'])
                        ->nestedRecursiveRules(['string', 'max:20'])
                        ->visible(fn ($livewire) => $livewire instanceof \App\Filament\Resources\CropResource\Pages\CreateCrop),
                    
                    Forms\Components\TagsInput::make('tray_numbers')
                        ->label('Tray Numbers')
                        ->placeholder('Edit tray numbers')
                        ->separator(',')
                        ->helperText('Edit the tray numbers or IDs for this grow batch (alphanumeric supported)')
                        ->rules(['array', 'min:1'])
                        ->nestedRecursiveRules(['string', 'max:20'])
                        ->visible(fn ($livewire) => !($livewire instanceof \App\Filament\Resources\CropResource\Pages\CreateCrop))
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
                            Forms\Components\DateTimePicker::make('soaking_at')
                                ->label('Soaking')
                                ->helperText('When soaking stage began')
                                ->seconds(false),
                            Forms\Components\DateTimePicker::make('germination_at')
                                ->label('Germination')
                                ->helperText('When germination stage began')
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