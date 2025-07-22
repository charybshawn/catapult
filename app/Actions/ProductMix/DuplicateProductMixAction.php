<?php

namespace App\Actions\ProductMix;

use App\Models\ProductMix;
use Illuminate\Support\Facades\DB;

/**
 * Business logic for duplicating a ProductMix with all its components
 */
class DuplicateProductMixAction
{
    /**
     * Execute the duplication of a ProductMix
     */
    public function execute(ProductMix $productMix): ProductMix
    {
        return DB::transaction(function () use ($productMix) {
            // Create the new mix
            $newMix = $this->duplicateProductMix($productMix);
            
            // Copy all mix components with their relationships
            $this->duplicateMixComponents($productMix, $newMix);
            
            return $newMix->fresh();
        });
    }

    /**
     * Duplicate the main ProductMix record
     */
    protected function duplicateProductMix(ProductMix $originalMix): ProductMix
    {
        $newMix = $originalMix->replicate();
        $newMix->name = $this->generateCopyName($originalMix->name);
        $newMix->save();
        
        return $newMix;
    }

    /**
     * Generate a unique name for the copy
     */
    protected function generateCopyName(string $originalName): string
    {
        $baseName = $originalName . ' (Copy)';
        $counter = 1;
        $newName = $baseName;
        
        // Ensure the name is unique
        while (ProductMix::where('name', $newName)->exists()) {
            $newName = $baseName . ' ' . $counter;
            $counter++;
        }
        
        return $newName;
    }

    /**
     * Copy all mix components (master seed catalog relationships) to the new mix
     */
    protected function duplicateMixComponents(ProductMix $originalMix, ProductMix $newMix): void
    {
        foreach ($originalMix->masterSeedCatalogs as $catalog) {
            $newMix->masterSeedCatalogs()->attach($catalog->id, [
                'percentage' => $catalog->pivot->percentage,
                'cultivar' => $catalog->pivot->cultivar,
                'recipe_id' => $catalog->pivot->recipe_id,
            ]);
        }
    }
}