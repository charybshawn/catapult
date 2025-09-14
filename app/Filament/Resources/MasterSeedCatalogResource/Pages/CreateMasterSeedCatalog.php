<?php

namespace App\Filament\Resources\MasterSeedCatalogResource\Pages;

use App\Filament\Pages\Base\BaseCreateRecord;
use App\Filament\Resources\MasterSeedCatalogResource;

class CreateMasterSeedCatalog extends BaseCreateRecord
{
    protected static string $resource = MasterSeedCatalogResource::class;

    public function getTitle(): string
    {
        return 'Create Entry';
    }
}
