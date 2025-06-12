<?php

namespace App\Filament\Resources\MasterSeedCatalogResource\Pages;

use App\Filament\Resources\MasterSeedCatalogResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMasterSeedCatalog extends EditRecord
{
    protected static string $resource = MasterSeedCatalogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
