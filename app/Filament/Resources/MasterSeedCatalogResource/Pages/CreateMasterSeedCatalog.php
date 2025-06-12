<?php

namespace App\Filament\Resources\MasterSeedCatalogResource\Pages;

use App\Filament\Resources\MasterSeedCatalogResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateMasterSeedCatalog extends CreateRecord
{
    protected static string $resource = MasterSeedCatalogResource::class;
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
