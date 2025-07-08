<?php

namespace App\Filament\Resources\Consumables\SoilConsumableResource\Pages;

use App\Filament\Resources\Consumables\SoilConsumableResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewSoilConsumable extends ViewRecord
{
    protected static string $resource = SoilConsumableResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}