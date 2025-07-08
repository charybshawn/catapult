<?php

namespace App\Filament\Resources\Consumables\LabelConsumableResource\Pages;

use App\Filament\Resources\Consumables\LabelConsumableResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLabelConsumable extends EditRecord
{
    protected static string $resource = LabelConsumableResource::class;

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