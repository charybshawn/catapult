<?php

namespace App\Collections;

use Illuminate\Database\Eloquent\Collection;

/**
 * @deprecated No longer needed with database view approach
 */
class CropBatchCollection extends Collection
{
    /**
     * Transform all batches with computed attributes to avoid N+1 queries.
     */
    public function withComputedAttributes(): self
    {
        // Preload all stages to avoid individual queries
        $stages = \App\Models\CropStage::all()->keyBy('id');
        
        // Transform each batch
        $this->each(function ($batch) use ($stages) {
            $attributes = $batch->getComputedAttributes();
            
            // Set attributes directly on the model to make them accessible
            foreach ($attributes as $key => $value) {
                $batch->setAttribute($key, $value);
            }
            
            // Store stages for later use
            $batch->setRelation('stagesCache', $stages);
        });
        
        return $this;
    }
}