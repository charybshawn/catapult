<?php

namespace App\Filament\Resources\Consumables\PackagingConsumableResource\Pages;

use App\Filament\Resources\Consumables\PackagingConsumableResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPackagingConsumables extends ListRecords
{
    protected static string $resource = PackagingConsumableResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}