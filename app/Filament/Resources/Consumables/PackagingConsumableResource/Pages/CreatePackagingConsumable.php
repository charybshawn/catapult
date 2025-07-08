<?php

namespace App\Filament\Resources\Consumables\PackagingConsumableResource\Pages;

use App\Filament\Resources\Consumables\PackagingConsumableResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePackagingConsumable extends CreateRecord
{
    protected static string $resource = PackagingConsumableResource::class;
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}