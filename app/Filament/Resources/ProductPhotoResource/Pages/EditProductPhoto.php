<?php

namespace App\Filament\Resources\ProductPhotoResource\Pages;

use App\Filament\Resources\ProductPhotoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProductPhoto extends EditRecord
{
    protected static string $resource = ProductPhotoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
} 