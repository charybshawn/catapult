<?php

namespace App\Filament\Resources\Consumables\OtherConsumableResource\Pages;

use App\Filament\Resources\Consumables\OtherConsumableResource;
use Filament\Resources\Pages\CreateRecord;

class CreateOtherConsumable extends CreateRecord
{
    protected static string $resource = OtherConsumableResource::class;
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}