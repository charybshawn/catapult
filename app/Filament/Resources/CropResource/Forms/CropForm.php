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
            Forms\Components\Section::make('Grow Details')
                ->schema([
                    static::getRecipeField(),
                    static::getSoakingSection(),
                    static::getOrderField(),
                    static::getTrayFields(),
                ])
                ->columns(2),
            
            Forms\Components\Section::make('Growth Timeline')
                ->schema(static::getTimelineFields())
                ->collapsed(),
            
            Forms\Components\Section::make('Additional Information')
                ->schema([
                    Forms\Components\Textarea::make('notes')
                        ->label('Notes')
                        ->rows(3)
                        ->columnSpanFull(),
                ])
                ->collapsed(),
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
            ->createOptionForm(RecipeResource::getFormSchema())
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
    
    protected static function getSoakingSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('Soaking Information')
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
                
                Forms\Components\DateTimePicker::make('soaking_at')
                    ->label('Soaking Start Time')
                    ->required(fn (Get $get) => static::checkRecipeRequiresSoaking($get))
                    ->visible(fn (Get $get) => static::checkRecipeRequiresSoaking($get))
                    ->reactive(),
            ])
            ->visible(fn (Get $get) => static::checkRecipeRequiresSoaking($get))
            ->columnSpanFull();
    }
    
    protected static function getOrderField(): Forms\Components\Select
    {
        return Forms\Components\Select::make('order_id')
            ->label('Order')
            ->relationship('order', 'id')
            ->searchable()
            ->preload()
            ->getOptionLabelFromRecordUsing(fn ($record) => "Order #{$record->id} - {$record->customer?->name}");
    }
    
    protected static function getTrayFields(): array
    {
        return [
            Forms\Components\TextInput::make('tray_number')
                ->label('Tray Number')
                ->maxLength(255),
            
            Forms\Components\TextInput::make('tray_count')
                ->label('Number of Trays')
                ->numeric()
                ->default(1)
                ->required()
                ->minValue(1),
        ];
    }
    
    protected static function getTimelineFields(): array
    {
        return [
            Forms\Components\DateTimePicker::make('soaking_at')
                ->label('Soaking Date'),
            
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