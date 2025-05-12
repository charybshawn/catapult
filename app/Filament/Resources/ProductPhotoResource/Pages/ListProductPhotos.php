<?php

namespace App\Filament\Resources\ProductPhotoResource\Pages;

use App\Filament\Resources\ProductPhotoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProductPhotos extends ListRecords
{
    protected static string $resource = ProductPhotoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
} 