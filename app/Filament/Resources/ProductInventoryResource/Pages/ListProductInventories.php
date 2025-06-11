<?php

namespace App\Filament\Resources\ProductInventoryResource\Pages;

use App\Filament\Resources\ProductInventoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProductInventories extends ListRecords
{
    protected static string $resource = ProductInventoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
