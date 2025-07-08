<?php

namespace App\Filament\Resources\Consumables\OtherConsumableResource\Pages;

use App\Filament\Resources\Consumables\OtherConsumableResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewOtherConsumable extends ViewRecord
{
    protected static string $resource = OtherConsumableResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}