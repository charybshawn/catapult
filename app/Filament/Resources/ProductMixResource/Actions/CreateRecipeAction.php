<?php

namespace App\Filament\Resources\ProductMixResource\Actions;

use App\Models\Recipe;
use App\Filament\Resources\RecipeResource\Forms\RecipeForm;
use Filament\Forms;
use Filament\Forms\Components\Actions\Action;
use Filament\Notifications\Notification;

class CreateRecipeAction
{
    public static function make(): Action
    {
        return Action::make('createRecipe')
            ->label('Create New Recipe')
            ->icon('heroicon-o-plus')
            ->color('primary')
            ->size('sm')
            ->modal()
            ->modalHeading('Create New Recipe')
            ->modalDescription('Create a recipe for the selected variety and cultivar.')
            ->form(static::getFormSchema())
            ->action(function (array $data, callable $get, callable $set) {
                // Generate the proper recipe name format: "Name (Cultivar)(Lot:x) - DTM - Seed Density (g)"
                $commonName = $data['common_name'] ?? 'Unknown';
                $cultivarName = $data['cultivar_name'] ?? 'Unknown';
                $dtm = $data['days_to_maturity'] ?? 0;
                $seedDensity = $data['seed_density_grams_per_tray'] ?? 0;
                $lotNumber = $data['lot_number'] ?? null;
                
                // Build the name components
                $nameComponents = [];
                $nameComponents[] = "{$commonName} ({$cultivarName})";
                
                if ($lotNumber) {
                    $nameComponents[] = "(Lot:{$lotNumber})";
                }
                
                $nameComponents[] = "{$dtm}DTM";
                $nameComponents[] = "{$seedDensity}g";
                
                // Join with " - " separator
                $recipeName = implode(' - ', $nameComponents);
                $data['name'] = $recipeName;
                
                // Create the recipe
                $recipe = Recipe::create($data);
                
                // Show success notification
                Notification::make()
                    ->title('Recipe Created')
                    ->body("Recipe '{$recipe->name}' has been created successfully.")
                    ->success()
                    ->send();
                
                // Set the recipe in the parent form
                $set('recipe_id', $recipe->id);
                
                return $recipe;
            });
    }
    
    protected static function getFormSchema(): array
    {
        return [
            Forms\Components\Section::make('Recipe Information')
                ->schema([
                    Forms\Components\Hidden::make('master_seed_catalog_id'),
                    Forms\Components\Hidden::make('master_cultivar_id'),
                    Forms\Components\Hidden::make('common_name'),
                    Forms\Components\Hidden::make('cultivar_name'),
                    Forms\Components\Hidden::make('name'),
                    
                    Forms\Components\Placeholder::make('variety_info')
                        ->label('Creating Recipe For')
                        ->content(function (callable $get) {
                            $commonName = $get('common_name') ?? 'Unknown Variety';
                            $cultivarName = $get('cultivar_name') ?? 'Unknown Cultivar';
                            return "{$commonName} ({$cultivarName})";
                        }),
                    
                    Forms\Components\Select::make('lot_number')
                        ->label('Seed Lot (Optional)')
                        ->options(function () {
                            // Get available seed lots from consumables
                            $seedTypeId = app(\App\Services\InventoryManagementService::class)->getSeedTypeId();
                            if (!$seedTypeId) {
                                return [];
                            }
                            
                            $consumables = \App\Models\Consumable::where('consumable_type_id', $seedTypeId)
                                ->where('is_active', true)
                                ->whereNotNull('lot_no')
                                ->where('lot_no', '<>', '')
                                ->with(['consumableType', 'masterSeedCatalog', 'masterCultivar'])
                                ->get();
                            
                            $options = [];
                            foreach ($consumables as $consumable) {
                                $lotNumber = $consumable->lot_no;
                                
                                // Calculate available quantity
                                $available = max(0, $consumable->total_quantity - $consumable->consumed_quantity);
                                
                                // Skip depleted lots
                                if ($available <= 0) {
                                    continue;
                                }
                                
                                $unit = $consumable->quantity_unit ?? 'g';
                                
                                // Build seed name from item_name or fallback to stored name
                                $seedName = $consumable->item_name ?? $consumable->getAttribute('name') ?? 'Unknown Seed';
                                
                                // Format: "LOT123 (1500g available) - Broccoli Seeds"
                                $label = "{$lotNumber} ({$available}{$unit} available) - {$seedName}";
                                $options[$lotNumber] = $label;
                            }
                            
                            return $options;
                        })
                        ->searchable()
                        ->nullable()
                        ->helperText('Select from available seed lots'),
                        
                    Forms\Components\Toggle::make('is_active')
                        ->label('Active')
                        ->default(true),
                ])
                ->columns(2),

            Forms\Components\Section::make('Growing Parameters')
                ->schema([
                    RecipeForm::getDaysToMaturityField(),
                    RecipeForm::getSeedSoakHoursField(),
                    RecipeForm::getGerminationDaysField(),
                    RecipeForm::getBlackoutDaysField(),
                    RecipeForm::getLightDaysField(),
                    RecipeForm::getSeedDensityField(),
                    RecipeForm::getExpectedYieldField(),
                ])
                ->columns(2),
        ];
    }
}