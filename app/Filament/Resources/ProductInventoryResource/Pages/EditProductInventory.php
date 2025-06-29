<?php

namespace App\Filament\Resources\ProductInventoryResource\Pages;

use App\Filament\Resources\ProductInventoryResource;
use Filament\Actions;
use App\Filament\Pages\Base\BaseEditRecord;

class EditProductInventory extends BaseEditRecord
{
    protected static string $resource = ProductInventoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
