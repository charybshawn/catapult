<?php

namespace App\Filament\Resources\ProductInventoryResource\Pages;

use App\Filament\Resources\ProductInventoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewProductInventory extends ViewRecord
{
    protected static string $resource = ProductInventoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}