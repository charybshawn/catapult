<?php

namespace App\Filament\Resources\ProductMixResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\ProductMixResource;
use App\Filament\Pages\Base\BaseEditRecord;
use Filament\Actions;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;

/**
 * EditProductMix Page for Agricultural Product Mix Management
 * 
 * Handles editing of agricultural product mixes with complex component management,
 * percentage validation, and recipe integration. Provides sophisticated data
 * transformation for managing multi-variety blends with precise ratio control
 * essential for consistent agricultural product mixes.
 * 
 * @filament_page Edit page for ProductMixResource with complex component management
 * @business_domain Agricultural product mix editing with percentage validation and recipe integration
 * @extends BaseEditRecord Enhanced edit page with agricultural business logic
 * 
 * @component_management Many-to-many relationship management with pivot data (percentage, cultivar, recipe)
 * @validation_logic Enforces 100% total percentage rule for agricultural mix integrity
 * @data_transformation Complex form data handling for variety selection and component synchronization
 * 
 * @agricultural_context Multi-variety microgreen mixes with growing parameter coordination
 * @business_rules Percentage validation, component synchronization, recipe assignment
 * @related_models ProductMix, ProductMixComponent, MasterSeedCatalog, Recipe
 */
class EditProductMix extends BaseEditRecord
{
    protected static string $resource = ProductMixResource::class;
    
    protected array $formData = [];

    /**
     * Transform database data for agricultural product mix form display.
     * 
     * Loads and transforms many-to-many relationship data with pivot information
     * into form-compatible format. Essential for displaying existing mix components
     * with percentages, cultivars, and recipe assignments in editable form.
     * 
     * @param array $data Raw database data from ProductMix record
     * @return array Transformed data compatible with agricultural mix form structure
     * @component_transformation Converts pivot data to form-compatible variety selections
     * @agricultural_data Includes percentage, cultivar, recipe data for each component
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Explicitly load the masterSeedCatalogs relationship data
        $components = $this->record->masterSeedCatalogs()
            ->withPivot('percentage', 'cultivar', 'recipe_id')
            ->get();
            
        $data['masterSeedCatalogs'] = $components->map(function ($catalog) {
            $cultivar = $catalog->pivot->cultivar ?: 'Unknown';
            return [
                'master_seed_catalog_id' => $catalog->id,
                'cultivar' => $cultivar,
                'percentage' => floatval($catalog->pivot->percentage),
                'recipe_id' => $catalog->pivot->recipe_id,
                'variety_selection' => $catalog->id . '|' . $cultivar,
            ];
        })->toArray();
        
        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->tooltip('Delete mix'),
        ];
    }
    
    /**
     * Validate and prepare agricultural product mix data before saving.
     * 
     * Performs percentage validation to ensure mix components total 100%
     * and stores form data for use in relationship synchronization.
     * Critical for maintaining agricultural mix integrity.
     * 
     * @param array $data Form data from agricultural product mix submission
     * @return array Validated data ready for database storage
     * @validation_logic Ensures 100% total percentage for agricultural mix accuracy
     * @business_rule Enforces precise percentage control for consistent product mixes
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Store the form data for use in afterSave
        $this->formData = $data;
        
        // Log the data to debug
        Log::info('ProductMix save data:', [
            'data' => $data,
            'this_data_masterSeedCatalogs' => $this->data['masterSeedCatalogs'] ?? [],
            'form_data_masterSeedCatalogs' => $data['masterSeedCatalogs'] ?? []
        ]);
        
        // Validate the percentages add up to 100
        $this->validatePercentages();
        
        return $data;
    }
    
    /**
     * Synchronize agricultural product mix components after saving main record.
     * 
     * Handles complex many-to-many relationship synchronization with pivot data
     * including percentages, cultivars, and recipe assignments. Essential for
     * maintaining accurate component relationships in agricultural mix management.
     * 
     * @return void Synchronizes component relationships with pivot data
     * @relationship_sync Updates ProductMix -> MasterSeedCatalog many-to-many with percentages
     * @agricultural_data Preserves cultivar and recipe information for each component
     * @business_logic Handles both new and existing component data transformation
     */
    protected function afterSave(): void
    {
        // Use the stored form data instead of $this->data
        $components = $this->formData['masterSeedCatalogs'] ?? [];
        $syncData = [];
        
        foreach ($components as $component) {
            // Handle both new and existing components
            $catalogId = null;
            $cultivar = null;
            $percentage = null;
            $recipeId = null;
            
            // Check if this is a new component (has variety_selection)
            if (isset($component['variety_selection']) && $component['variety_selection']) {
                [$catalogId, $cultivar] = explode('|', $component['variety_selection']);
                $percentage = $component['percentage'] ?? null;
                $recipeId = $component['recipe_id'] ?? null;
            } else {
                // This is existing data
                $catalogId = $component['master_seed_catalog_id'] ?? null;
                $cultivar = $component['cultivar'] ?? null;
                $percentage = $component['percentage'] ?? null;
                $recipeId = $component['recipe_id'] ?? null;
            }
            
            if ($catalogId && $percentage) {
                $syncData[$catalogId] = [
                    'percentage' => $percentage,
                    'cultivar' => $cultivar,
                    'recipe_id' => $recipeId,
                ];
            }
        }
        
        Log::info('Syncing components:', [
            'raw_data' => $components,
            'sync_data' => $syncData
        ]);
        
        $this->record->masterSeedCatalogs()->sync($syncData);
    }
    
    /**
     * Validate that agricultural product mix percentages total exactly 100%.
     * 
     * Enforces critical business rule that mix components must total 100%
     * for accurate agricultural product blending. Provides user feedback
     * and halts save process if validation fails.
     * 
     * @return void Validates percentage totals or halts save with notification
     * @business_rule Mix components must total exactly 100% for agricultural accuracy
     * @validation_tolerance Allows 0.01% tolerance for floating point precision
     * @user_feedback Clear error notification with current total for correction
     */
    protected function validatePercentages(): void
    {
        $components = $this->data['masterSeedCatalogs'] ?? [];
        $total = 0;
        
        foreach ($components as $component) {
            if (isset($component['percentage']) && is_numeric($component['percentage'])) {
                $total += floatval($component['percentage']);
            }
        }
        
        // Round to 2 decimal places to match database precision
        $total = round($total, 2);
        
        // Allow for very small floating point differences
        if (abs($total - 100) > 0.01) {
            Notification::make()
                ->title('Invalid Mix Percentages')
                ->body('The total percentage must equal 100%. Current total: ' . number_format($total, 2) . '%')
                ->danger()
                ->persistent()
                ->send();
                
            $this->halt();
        }
    }
} 