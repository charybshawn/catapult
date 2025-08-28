<?php

namespace App\Actions\Product;

use App\Models\PriceVariation;
use App\Models\ProductPhoto;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class CloneProductAction
{
    /**
     * Clone a product with all its price variations and photos
     */
    public function execute(Product $originalProduct): Product
    {
        return DB::transaction(function () use ($originalProduct) {
            // Create the cloned product
            $clonedProduct = $this->cloneProductRecord($originalProduct);
            
            // Clone price variations
            $this->clonePriceVariations($originalProduct, $clonedProduct);
            
            // Clone photos
            $this->clonePhotos($originalProduct, $clonedProduct);
            
            return $clonedProduct->fresh();
        });
    }

    /**
     * Clone the main product record
     */
    protected function cloneProductRecord(Product $originalProduct): Product
    {
        $data = $originalProduct->toArray();
        
        // Remove fields that shouldn't be cloned
        unset($data['id'], $data['created_at'], $data['updated_at'], $data['deleted_at']);
        
        // Modify the name to indicate it's a clone
        $data['name'] = $data['name'] . ' (Copy)';
        
        // Reset inventory-related fields
        $data['is_active'] = false; // Start as inactive for review
        
        return Product::create($data);
    }

    /**
     * Clone price variations from original to cloned product
     */
    protected function clonePriceVariations(Product $originalProduct, Product $clonedProduct): void
    {
        $originalVariations = $originalProduct->priceVariations;
        
        foreach ($originalVariations as $variation) {
            $variationData = $variation->toArray();
            
            // Remove fields that shouldn't be cloned
            unset($variationData['id'], $variationData['created_at'], $variationData['updated_at']);
            
            // Update the product_id
            $variationData['product_id'] = $clonedProduct->id;
            
            // Don't clone global templates as product-specific variations
            if ($variation->is_global) {
                $variationData['is_global'] = false;
                $variationData['template_id'] = $variation->id; // Reference the original template
            }
            
            PriceVariation::create($variationData);
        }
    }

    /**
     * Clone photos from original to cloned product
     */
    protected function clonePhotos(Product $originalProduct, Product $clonedProduct): void
    {
        $originalPhotos = $originalProduct->photos;
        
        foreach ($originalPhotos as $photo) {
            $photoData = $photo->toArray();
            
            // Remove fields that shouldn't be cloned
            unset($photoData['id'], $photoData['created_at'], $photoData['updated_at']);
            
            // Update the product_id
            $photoData['product_id'] = $clonedProduct->id;
            
            // Copy the actual file if needed (for now, we'll reference the same file)
            // In a production environment, you might want to copy the actual file
            
            ProductPhoto::create($photoData);
        }
    }
}