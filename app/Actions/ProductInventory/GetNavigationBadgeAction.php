<?php

namespace App\Actions\ProductInventory;

use App\Models\ProductInventory;
use Illuminate\Support\Facades\Log;

class GetNavigationBadgeAction
{
    /**
     * Get the navigation badge count for active product inventory
     */
    public function getBadgeCount(): ?string
    {
        try {
            return ProductInventory::active()->count();
        } catch (\Exception $e) {
            Log::error('Navigation badge error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get the navigation badge color based on low stock count
     */
    public function getBadgeColor(): ?string
    {
        try {
            $lowStock = ProductInventory::active()
                ->where('available_quantity', '>', 0)
                ->where('available_quantity', '<=', 10)
                ->count();

            return $lowStock > 0 ? 'warning' : 'success';
        } catch (\Exception $e) {
            Log::error('Navigation badge color error: ' . $e->getMessage());
            return null;
        }
    }
}