<?php

namespace App\Filament\Resources\Consumables\PackagingConsumableResource\Pages;

use App\Filament\Resources\Consumables\PackagingConsumableResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPackagingConsumable extends ViewRecord
{
    protected static string $resource = PackagingConsumableResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}