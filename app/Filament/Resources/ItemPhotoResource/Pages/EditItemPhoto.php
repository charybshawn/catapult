<?php

namespace App\Filament\Resources\ItemPhotoResource\Pages;

use App\Filament\Resources\ItemPhotoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditItemPhoto extends EditRecord
{
    protected static string $resource = ItemPhotoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
