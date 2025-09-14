<?php

namespace App\Filament\Resources\MasterSeedCatalogResource\Pages;

use App\Filament\Resources\MasterSeedCatalogResource;
use Filament\Actions;
use App\Filament\Pages\Base\BaseCreateRecord;

class CreateMasterSeedCatalog extends BaseCreateRecord
{
    protected static string $resource = MasterSeedCatalogResource::class;

    public function getTitle(): string
    {
        return 'Create New Catalog Entry';
    }
}
