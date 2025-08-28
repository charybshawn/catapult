<?php

namespace App\Actions\Product;

use App\Models\OrderItem;
use App\Models\Crop;
use App\Models\Product;

class ValidateProductDeletionAction
{
    /**
     * Check if a product can be safely deleted
     */
    public function execute(Product $product): array
    {
        $errors = [];
        
        // Check for active inventory
        $this->checkInventory($product, $errors);
        
        // Check for pending orders
        $this->checkPendingOrders($product, $errors);
        
        // Check for planting plans
        $this->checkPlantingPlans($product, $errors);
        
        // Check for active price variations
        $this->checkActivePriceVariations($product, $errors);
        
        return [
            'canDelete' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Check if product has inventory that would be affected
     */
    protected function checkInventory(Product $product, array &$errors): void
    {
        // Check for existing inventory records
        $inventoryCount = $product->inventory()->count();
        if ($inventoryCount > 0) {
            $errors[] = "Product has {$inventoryCount} inventory record(s). Transfer or remove inventory first.";
        }
        
        // Check for positive inventory quantities
        $positiveInventory = $product->inventory()
            ->where('quantity', '>', 0)
            ->count();
            
        if ($positiveInventory > 0) {
            $errors[] = "Product has {$positiveInventory} inventory record(s) with positive quantities. Reduce quantities to zero first.";
        }
    }

    /**
     * Check for pending or active orders
     */
    protected function checkPendingOrders(Product $product, array &$errors): void
    {
        // Check for order items that reference this product
        $orderItemsCount = OrderItem::where('product_id', $product->id)
            ->whereHas('order', function ($query) {
                $query->whereIn('status', ['pending', 'processing', 'confirmed']);
            })
            ->count();
            
        if ($orderItemsCount > 0) {
            $errors[] = "Product has {$orderItemsCount} pending order item(s). Complete or cancel orders first.";
        }
    }

    /**
     * Check for planting plans that reference this product
     */
    protected function checkPlantingPlans(Product $product, array &$errors): void
    {
        // Check for crops that reference this product
        $activeCropsCount = Crop::where('product_id', $product->id)
            ->whereIn('status', ['planned', 'planted', 'growing'])
            ->count();
            
        if ($activeCropsCount > 0) {
            $errors[] = "Product is used in {$activeCropsCount} active crop(s). Complete or remove crop plans first.";
        }
    }

    /**
     * Check for active price variations
     */
    protected function checkActivePriceVariations(Product $product, array &$errors): void
    {
        $activePriceVariations = $product->priceVariations()
            ->where('is_active', true)
            ->count();
            
        if ($activePriceVariations > 0) {
            $errors[] = "Product has {$activePriceVariations} active price variation(s). Deactivate price variations first.";
        }
    }
}