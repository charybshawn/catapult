<?php

namespace App\Filament\Resources\PriceVariationResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\PriceVariationResource;
use App\Models\PriceVariation;
use Filament\Actions;
use App\Filament\Pages\Base\BaseEditRecord;

/**
 * EditPriceVariation Page for Agricultural Product Pricing Management
 * 
 * Handles editing of agricultural product price variations with business rule
 * enforcement for default pricing uniqueness. Ensures only one variation per
 * product can be marked as default, critical for agricultural pricing integrity.
 * 
 * @filament_page Edit page for PriceVariationResource
 * @business_domain Agricultural product pricing with default variation enforcement
 * @extends BaseEditRecord Standard Catapult edit page with agricultural business logic
 * 
 * @business_rule Enforces single default variation per product after save
 * @agricultural_context Manages retail, wholesale, bulk pricing for microgreens
 * @data_integrity Prevents multiple default prices that would break order calculations
 * 
 * @related_models PriceVariation with product relationship and pricing constraints
 * @crud_operations Standard edit with delete action and post-save business rule enforcement
 */
class EditPriceVariation extends BaseEditRecord
{
    protected static string $resource = PriceVariationResource::class;

    /**
     * Get header actions for price variation editing.
     * 
     * Provides standard delete action for removing agricultural price variations.
     * Delete operations are important for cleaning up unused pricing structures
     * while maintaining referential integrity in agricultural business operations.
     * 
     * @return array Header actions including delete capability
     * @agricultural_context Allows cleanup of unused pricing variations for microgreens
     * @business_safety Delete action includes confirmation for data protection
     */
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
    
    /**
     * Post-save business rule enforcement for agricultural pricing integrity.
     * 
     * Ensures only one price variation per agricultural product can be marked as default.
     * Critical for maintaining pricing consistency in microgreens business operations
     * where default pricing is used for order calculations and customer displays.
     * 
     * @return void Enforces default pricing uniqueness constraint
     * @business_rule Single default variation per product for pricing integrity
     * @agricultural_context Prevents confusion in microgreen product pricing
     * @data_consistency Updates other variations to non-default when new default is set
     */
    protected function afterSave(): void
    {
        // If this price variation is set as default, make sure no other variations 
        // for the same product are also set as default
        if ($this->record->is_default) {
            PriceVariation::where('product_id', $this->record->product_id)
                ->where('id', '!=', $this->record->id)
                ->where('is_default', true)
                ->update(['is_default' => false]);
        }
    }
}
