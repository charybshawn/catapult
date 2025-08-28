<?php

namespace App\Filament\Resources\ProductInventoryResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\ProductInventoryResource;
use Filament\Actions;
use App\Filament\Pages\Base\BaseEditRecord;

/**
 * EditProductInventory Page for Agricultural Inventory Management
 * 
 * Handles editing of agricultural product inventory records with support for
 * batch tracking, expiration date management, and quantity adjustments.
 * Critical for maintaining accurate inventory levels in microgreens operations.
 * 
 * @filament_page Edit page for ProductInventoryResource
 * @business_domain Agricultural product inventory with perishable tracking
 * @extends BaseEditRecord Standard Catapult edit page for agricultural inventory
 * 
 * @agricultural_context Manages perishable inventory with time-sensitive tracking
 * @inventory_operations Standard edit with delete capability for inventory cleanup
 * @business_importance Critical for accurate stock levels in agricultural operations
 * 
 * @related_models ProductInventory with product, price variation, and status relationships
 * @crud_operations Standard edit with delete action for inventory record management
 */
class EditProductInventory extends BaseEditRecord
{
    protected static string $resource = ProductInventoryResource::class;

    /**
     * Get header actions for product inventory editing.
     * 
     * Provides delete action for removing agricultural inventory records.
     * Delete operations are important for cleaning up expired or obsolete
     * inventory while maintaining referential integrity in agricultural systems.
     * 
     * @return array Header actions including delete capability
     * @agricultural_context Allows cleanup of expired or obsolete inventory records
     * @business_safety Delete action includes confirmation for data protection
     */
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
