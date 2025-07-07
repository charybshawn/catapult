<?php

namespace App\Filament\Resources\PriceVariationResource\Pages;

use App\Filament\Resources\PriceVariationResource;
use App\Models\PriceVariation;
use Filament\Actions;
use App\Filament\Pages\Base\BaseEditRecord;

class EditPriceVariation extends BaseEditRecord
{
    protected static string $resource = PriceVariationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
    
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
