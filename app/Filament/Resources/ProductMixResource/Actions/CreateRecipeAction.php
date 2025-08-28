<?php

namespace App\Filament\Resources\ProductMixResource\Actions;

use Filament\Actions\Action;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use App\Services\InventoryManagementService;
use App\Models\Consumable;
use Filament\Forms\Components\Toggle;
use App\Models\Recipe;
use App\Filament\Resources\RecipeResource\Forms\RecipeForm;
use Filament\Forms;
use Filament\Notifications\Notification;

/**
 * CreateRecipeAction for Agricultural Growing Recipe Creation
 * 
 * Provides specialized action for creating new growing recipes directly from
 * ProductMix context with intelligent name generation based on variety, cultivar,
 * and growing parameters. Essential for rapid recipe development in microgreens
 * operations where recipes are variety-specific with detailed growing parameters.
 * 
 * @filament_action Recipe creation action for ProductMixResource
 * @business_domain Agricultural recipe creation with automated naming and parameter management
 * @recipe_creation Variety-specific growing recipes with cultivar and lot tracking
 * 
 * @naming_automation "Name (Cultivar)(Lot:x) - DTM - Seed Density (g)" format
 * @agricultural_parameters Days to maturity, seed density, germination timing
 * @inventory_integration Seed lot selection with availability checking
 * 
 * @business_workflow Create recipe -> set in parent form -> return to product mix configuration
 * @related_models Recipe, MasterSeedCatalog, MasterCultivar, Consumable for complete context
 * @form_integration Delegates to RecipeForm for consistent parameter fields
 */
class CreateRecipeAction
{
    /**
     * Create recipe creation action with agricultural business logic.
     * 
     * Builds comprehensive action for creating growing recipes with intelligent
     * name generation, seed lot integration, and parameter validation.
     * Essential for streamlined recipe creation in agricultural operations.
     * 
     * @return Action Configured recipe creation action with agricultural context
     * @agricultural_naming Automated recipe naming based on variety and growing parameters
     * @inventory_aware Seed lot selection with availability calculations
     * @business_integration Sets created recipe in parent form for immediate use
     */
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
            ->schema(static::getFormSchema())
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
    
    /**
     * Get form schema for agricultural recipe creation.
     * 
     * Provides comprehensive form sections including recipe information with
     * variety context, seed lot selection, and growing parameters. Integrates
     * with existing RecipeForm fields for consistency and validation.
     * 
     * @return array Form schema for agricultural recipe creation
     * @form_sections Recipe info with variety display, growing parameters with agricultural fields
     * @agricultural_context Variety/cultivar display, seed lot selection, growing parameters
     * @field_delegation Uses RecipeForm for consistent parameter field definitions
     */
    protected static function getFormSchema(): array
    {
        return [
            Section::make('Recipe Information')
                ->schema([
                    Hidden::make('master_seed_catalog_id'),
                    Hidden::make('master_cultivar_id'),
                    Hidden::make('common_name'),
                    Hidden::make('cultivar_name'),
                    Hidden::make('name'),
                    
                    Placeholder::make('variety_info')
                        ->label('Creating Recipe For')
                        ->content(function (callable $get) {
                            $commonName = $get('common_name') ?? 'Unknown Variety';
                            $cultivarName = $get('cultivar_name') ?? 'Unknown Cultivar';
                            return "{$commonName} ({$cultivarName})";
                        }),
                    
                    Select::make('lot_number')
                        ->label('Seed Lot (Optional)')
                        ->options(function () {
                            // Get available seed lots from consumables
                            $seedTypeId = app(InventoryManagementService::class)->getSeedTypeId();
                            if (!$seedTypeId) {
                                return [];
                            }
                            
                            $consumables = Consumable::where('consumable_type_id', $seedTypeId)
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
                        
                    Toggle::make('is_active')
                        ->label('Active')
                        ->default(true),
                ])
                ->columns(2),

            Section::make('Growing Parameters')
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