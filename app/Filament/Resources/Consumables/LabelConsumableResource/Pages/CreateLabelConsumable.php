<?php

namespace App\Filament\Resources\Consumables\LabelConsumableResource\Pages;

use App\Filament\Resources\Consumables\LabelConsumableResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLabelConsumable extends CreateRecord
{
    protected static string $resource = LabelConsumableResource::class;
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}