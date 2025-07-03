<?php

namespace App\Filament\Resources\PriceVariationResource\Pages;

use App\Filament\Resources\PriceVariationResource;
use App\Models\PriceVariation;
use Filament\Actions;
use App\Filament\Pages\BaseCreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreatePriceVariation extends BaseCreateRecord
{
    protected static string $resource = PriceVariationResource::class;
    
    protected function handleRecordCreation(array $data): Model
    {
        // Ensure product_id is null for global price variations
        if (isset($data['is_global']) && $data['is_global']) {
            $data['product_id'] = null;
            $data['is_default'] = false; // Global variations can't be default for a specific product
        }
        
        return static::getModel()::create($data);
    }
    
    protected function afterCreate(): void
    {
        // If this price variation is set as default, make sure no other variations 
        // for the same product are also set as default
        if ($this->record->is_default && $this->record->product_id) {
            PriceVariation::where('product_id', $this->record->product_id)
                ->where('id', '!=', $this->record->id)
                ->where('is_default', true)
                ->update(['is_default' => false]);
        }
    }
}
