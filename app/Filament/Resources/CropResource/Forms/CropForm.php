<?php

namespace App\Filament\Resources\CropResource\Forms;

use App\Filament\Resources\RecipeResource;
use App\Models\Recipe;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Carbon\Carbon;

class CropForm
{
    public static function schema(): array
    {
        return [
            // Main recipe selection
            static::getRecipeField()
                ->columnSpanFull(),
            
            // Conditional tray fields based on soaking requirement
            static::getTrayCountField()
                ->visible(fn (Get $get) => static::checkRecipeRequiresSoaking($get)),
                
            static::getTrayNumbersField()
                ->visible(fn (Get $get) => !static::checkRecipeRequiresSoaking($get))
                ->dehydrated(fn (Get $get) => !static::checkRecipeRequiresSoaking($get)),
            
            // Soaking section - only appears if recipe requires soaking
            Forms\Components\Section::make('Soaking Setup')
                ->schema([
                    Forms\Components\Placeholder::make('temp_tray_info')
                        ->label('')
                        ->content('Temporary tray numbers will be assigned during soaking and can be updated after germination.')
                        ->columnSpanFull(),
                        
                    static::getSeedWeightCalculation(),
                    static::getSoakingTimeFields(),
                ])
                ->visible(fn (Get $get) => static::checkRecipeRequiresSoaking($get))
                ->columns(2)
                ->compact(),
            
            // Optional advanced fields
            Forms\Components\Section::make('Advanced Options')
                ->schema([
                    ...static::getTimelineFields(),
                    Forms\Components\Textarea::make('notes')
                        ->label('Notes')
                        ->rows(3)
                        ->columnSpanFull(),
                ])
                ->collapsed()
                ->compact(),
        ];
    }
    
    protected static function getRecipeField(): Forms\Components\Select
    {
        return Forms\Components\Select::make('recipe_id')
            ->label('Recipe')
            ->relationship('recipe', 'name')
            ->required()
            ->searchable()
            ->preload()
            ->reactive()
            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                if ($state) {
                    $recipe = Recipe::find($state);
                    if ($recipe && $recipe->requiresSoaking()) {
                        $set('soaking_duration_display', $recipe->seed_soak_hours . ' hours');
                        if (!$get('soaking_at')) {
                            $set('soaking_at', now());
                        }
                    }
                }
            });
    }
    
    
    
    protected static function getTrayCountField(): Forms\Components\TextInput
    {
        return Forms\Components\TextInput::make('tray_count')
            ->label('Number of Trays')
            ->helperText('How many trays are you planting?')
            ->numeric()
            ->default(1)
            ->required()
            ->minValue(1)
            ->reactive()
            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                // Recalculate seed weight when tray count changes
                static::calculateSeedWeight($get, $set);
            });
    }
    
    protected static function getTrayNumbersField(): Forms\Components\TagsInput
    {
        return Forms\Components\TagsInput::make('tray_numbers')
            ->label('Tray Numbers')
            ->helperText('Enter tray numbers (e.g., A1, A2, B1) - press Enter after each')
            ->placeholder('A1')
            ->columnSpanFull()
            ->reactive()
            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                // Auto-update tray count based on number of tray numbers entered
                if (is_array($state) && count($state) > 0) {
                    $set('tray_count', count($state));
                    static::calculateSeedWeight($get, $set);
                }
            });
    }
    
    protected static function getSeedWeightCalculation(): Forms\Components\Placeholder
    {
        return Forms\Components\Placeholder::make('seed_weight_calculation')
            ->label('Total Seed Required')
            ->content(function (Get $get) {
                $recipeId = $get('recipe_id');
                $trayCount = (int) $get('tray_count') ?: 1;
                
                if (!$recipeId) {
                    return 'Select a recipe first';
                }
                
                $recipe = Recipe::find($recipeId);
                if (!$recipe || !$recipe->seed_density_grams_per_tray) {
                    return 'Recipe seed density not set';
                }
                
                $totalGrams = $recipe->seed_density_grams_per_tray * $trayCount;
                return number_format($totalGrams, 1) . 'g (' . $trayCount . ' trays × ' . $recipe->seed_density_grams_per_tray . 'g per tray)';
            })
            ->columnSpanFull();
    }
    
    protected static function getSoakingTimeFields(): Forms\Components\Group
    {
        return Forms\Components\Group::make([
            Forms\Components\DateTimePicker::make('soaking_at')
                ->label('Soaking Start Time')
                ->default(now())
                ->required(fn (Get $get) => static::checkRecipeRequiresSoaking($get))
                ->visible(fn (Get $get) => static::checkRecipeRequiresSoaking($get))
                ->reactive(),
        ])
        ->columnSpanFull();
    }
    
    protected static function calculateSeedWeight(Get $get, Set $set): void
    {
        $recipeId = $get('recipe_id');
        $trayCount = (int) $get('tray_count') ?: 1;
        
        if ($recipeId) {
            $recipe = Recipe::find($recipeId);
            if ($recipe && $recipe->seed_density_grams_per_tray) {
                $totalGrams = $recipe->seed_density_grams_per_tray * $trayCount;
                // This will trigger the placeholder to update
            }
        }
    }
    
    protected static function getTimelineFields(): array
    {
        return [
            Forms\Components\DateTimePicker::make('soaking_at')
                ->label('Soaking Date')
                ->visible(fn (Get $get) => static::checkRecipeRequiresSoaking($get)),
            
            Forms\Components\DateTimePicker::make('germination_at')
                ->label('Germination Date'),
            
            Forms\Components\DateTimePicker::make('blackout_at')
                ->label('Blackout Date'),
            
            Forms\Components\DateTimePicker::make('light_at')
                ->label('Light Date'),
            
            Forms\Components\DateTimePicker::make('harvested_at')
                ->label('Harvested Date'),
        ];
    }
    
    protected static function checkRecipeRequiresSoaking(Get $get): bool
    {
        $recipeId = $get('recipe_id');
        if (!$recipeId) {
            return false;
        }
        
        $recipe = Recipe::find($recipeId);
        return $recipe && $recipe->requiresSoaking();
    }
    
    protected static function getSoakingRequiredInfo(Get $get): string
    {
        $recipe = Recipe::find($get('recipe_id'));
        if (!$recipe) {
            return '';
        }
        
        return "This recipe requires {$recipe->seed_soak_hours} hours of soaking before planting.";
    }
}