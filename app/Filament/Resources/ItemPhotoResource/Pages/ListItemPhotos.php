<?php

namespace App\Filament\Resources\ItemPhotoResource\Pages;

use App\Filament\Resources\ItemPhotoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListItemPhotos extends ListRecords
{
    protected static string $resource = ItemPhotoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
