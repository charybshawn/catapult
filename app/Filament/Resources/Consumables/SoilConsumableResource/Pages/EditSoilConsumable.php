<?php

namespace App\Filament\Resources\Consumables\SoilConsumableResource\Pages;

use App\Filament\Resources\Consumables\SoilConsumableResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSoilConsumable extends EditRecord
{
    protected static string $resource = SoilConsumableResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}