<?php

namespace App\Filament\Resources\PackagingTypeResource\Pages;

use App\Filament\Resources\PackagingTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPackagingType extends EditRecord
{
    protected static string $resource = PackagingTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
