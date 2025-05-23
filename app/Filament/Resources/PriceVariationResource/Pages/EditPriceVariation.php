<?php

namespace App\Filament\Resources\PriceVariationResource\Pages;

use App\Filament\Resources\PriceVariationResource;
use App\Models\PriceVariation;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPriceVariation extends EditRecord
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
        // for the same item are also set as default
        if ($this->record->is_default) {
            PriceVariation::where('item_id', $this->record->item_id)
                ->where('id', '!=', $this->record->id)
                ->where('is_default', true)
                ->update(['is_default' => false]);
        }
    }
}
