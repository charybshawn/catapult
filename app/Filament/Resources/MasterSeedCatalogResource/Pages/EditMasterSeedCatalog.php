<?php

namespace App\Filament\Resources\MasterSeedCatalogResource\Pages;

use App\Filament\Resources\MasterSeedCatalogResource;
use Filament\Actions;
use App\Filament\Pages\Base\BaseEditRecord;

class EditMasterSeedCatalog extends BaseEditRecord
{
    protected static string $resource = MasterSeedCatalogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
    
}
