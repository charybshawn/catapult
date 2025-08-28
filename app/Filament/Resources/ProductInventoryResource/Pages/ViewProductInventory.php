<?php

namespace App\Filament\Resources\ProductInventoryResource\Pages;

use Filament\Actions\EditAction;
use App\Filament\Resources\ProductInventoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

/**
 * ViewProductInventory Page for Agricultural Inventory Detail Display
 * 
 * Provides detailed view of agricultural product inventory records with
 * comprehensive display of batch information, expiration tracking, and
 * quantity details. Essential for reviewing inventory status and history
 * in microgreens operations.
 * 
 * @filament_page View page for ProductInventoryResource
 * @business_domain Agricultural product inventory with detailed batch tracking
 * @extends ViewRecord Standard Filament view page for agricultural inventory
 * 
 * @agricultural_context Detailed inventory information including perishable tracking
 * @inventory_operations Standard view with edit capability for inventory updates
 * @business_importance Critical for inventory verification and batch history tracking
 * 
 * @related_models ProductInventory with product, price variation, and status relationships
 * @crud_operations Standard view with edit action for inventory record updates
 */
class ViewProductInventory extends ViewRecord
{
    protected static string $resource = ProductInventoryResource::class;

    /**
     * Get header actions for product inventory viewing.
     * 
     * Provides edit action for modifying agricultural inventory records.
     * Edit access is important for updating quantity levels, adjusting
     * expiration dates, and maintaining accurate inventory information.
     * 
     * @return array Header actions including edit capability
     * @agricultural_context Allows updates to inventory levels and batch information
     * @business_workflow Standard view-to-edit transition for inventory management
     */
    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}