<?php

namespace App\Filament\Resources\Consumables\SoilConsumableResource\Pages;

use App\Filament\Resources\Consumables\SoilConsumableResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSoilConsumable extends CreateRecord
{
    protected static string $resource = SoilConsumableResource::class;
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}