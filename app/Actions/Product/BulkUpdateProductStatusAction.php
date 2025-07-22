<?php

namespace App\Actions\Product;

use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;

class BulkUpdateProductStatusAction
{
    /**
     * Update the active status for multiple products
     */
    public function updateActiveStatus(Collection $products, bool $isActive): int
    {
        $updatedCount = 0;
        
        foreach ($products as $product) {
            if ($product instanceof Product) {
                $product->update(['is_active' => $isActive]);
                $updatedCount++;
            }
        }
        
        return $updatedCount;
    }

    /**
     * Update the store visibility for multiple products
     */
    public function updateStoreVisibility(Collection $products, bool $isVisible): int
    {
        $updatedCount = 0;
        
        foreach ($products as $product) {
            if ($product instanceof Product) {
                $product->update(['is_visible_in_store' => $isVisible]);
                $updatedCount++;
            }
        }
        
        return $updatedCount;
    }

    /**
     * Activate multiple products
     */
    public function activate(Collection $products): int
    {
        return $this->updateActiveStatus($products, true);
    }

    /**
     * Deactivate multiple products
     */
    public function deactivate(Collection $products): int
    {
        return $this->updateActiveStatus($products, false);
    }

    /**
     * Show multiple products in store
     */
    public function showInStore(Collection $products): int
    {
        return $this->updateStoreVisibility($products, true);
    }

    /**
     * Hide multiple products from store
     */
    public function hideFromStore(Collection $products): int
    {
        return $this->updateStoreVisibility($products, false);
    }
}