<?php

namespace App\Filament\Resources\Consumables\LabelConsumableResource\Pages;

use App\Filament\Resources\Consumables\LabelConsumableResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewLabelConsumable extends ViewRecord
{
    protected static string $resource = LabelConsumableResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}