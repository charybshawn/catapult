<?php

namespace App\Filament\Resources\Consumables\OtherConsumableResource\Pages;

use App\Filament\Resources\Consumables\OtherConsumableResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOtherConsumable extends EditRecord
{
    protected static string $resource = OtherConsumableResource::class;

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