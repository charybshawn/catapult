<?php

namespace App\Filament\Resources\MasterSeedCatalogResource\Pages;

use App\Filament\Resources\MasterSeedCatalogResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMasterSeedCatalogs extends ListRecords
{
    protected static string $resource = MasterSeedCatalogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Create New Entry'),
        ];
    }
}
