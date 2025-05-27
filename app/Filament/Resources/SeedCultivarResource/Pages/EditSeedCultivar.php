<?php

namespace App\Filament\Resources\SeedCultivarResource\Pages;

use App\Filament\Resources\SeedCultivarResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSeedCultivar extends EditRecord
{
    protected static string $resource = SeedCultivarResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
