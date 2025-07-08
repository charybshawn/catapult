<?php

namespace App\Filament\Resources\Consumables\PackagingConsumableResource\Pages;

use App\Filament\Resources\Consumables\PackagingConsumableResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPackagingConsumable extends EditRecord
{
    protected static string $resource = PackagingConsumableResource::class;

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