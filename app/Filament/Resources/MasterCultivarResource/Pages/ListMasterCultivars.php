<?php

namespace App\Filament\Resources\MasterCultivarResource\Pages;

use App\Filament\Resources\MasterCultivarResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMasterCultivars extends ListRecords
{
    protected static string $resource = MasterCultivarResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
