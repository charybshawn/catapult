<?php

namespace App\Filament\Resources\PackagingTypeResource\Pages;

use App\Filament\Resources\PackagingTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPackagingTypes extends ListRecords
{
    protected static string $resource = PackagingTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
