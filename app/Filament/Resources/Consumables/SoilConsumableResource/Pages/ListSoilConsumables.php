<?php

namespace App\Filament\Resources\Consumables\SoilConsumableResource\Pages;

use App\Filament\Resources\Consumables\SoilConsumableResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSoilConsumables extends ListRecords
{
    protected static string $resource = SoilConsumableResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}