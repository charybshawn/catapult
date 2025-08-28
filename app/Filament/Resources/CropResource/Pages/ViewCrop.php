<?php

namespace App\Filament\Resources\CropResource\Pages;

use Filament\Actions\EditAction;
use App\Filament\Resources\CropResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewCrop extends ViewRecord
{
    protected static string $resource = CropResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}