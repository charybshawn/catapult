<?php

namespace App\Filament\Resources\ProductPhotoResource\Pages;

use App\Filament\Resources\ProductPhotoResource;
use Filament\Actions;
use App\Filament\Pages\Base\BaseEditRecord;

class EditProductPhoto extends BaseEditRecord
{
    protected static string $resource = ProductPhotoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
} 