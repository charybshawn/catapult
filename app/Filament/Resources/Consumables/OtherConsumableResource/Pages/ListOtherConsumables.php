<?php

namespace App\Filament\Resources\Consumables\OtherConsumableResource\Pages;

use App\Filament\Resources\Consumables\OtherConsumableResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOtherConsumables extends ListRecords
{
    protected static string $resource = OtherConsumableResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}