<?php

namespace App\Filament\Resources\SeedCultivarResource\Pages;

use App\Filament\Resources\SeedCultivarResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSeedCultivars extends ListRecords
{
    protected static string $resource = SeedCultivarResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
