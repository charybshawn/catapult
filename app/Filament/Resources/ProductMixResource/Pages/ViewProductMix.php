<?php

namespace App\Filament\Resources\ProductMixResource\Pages;

use App\Filament\Resources\ProductMixResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewProductMix extends ViewRecord
{
    protected static string $resource = ProductMixResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make()
                ->before(function () {
                    if ($this->record->products()->count() > 0) {
                        throw new \Exception('Cannot delete mix that is used by products.');
                    }
                }),
        ];
    }
}
