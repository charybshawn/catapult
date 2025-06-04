<?php

namespace App\Filament\Resources\SeedCultivarResource\Pages;

use App\Filament\Resources\SeedCultivarResource;
use Filament\Actions;
use App\Filament\Pages\Base\BaseEditRecord;

class EditSeedCultivar extends BaseEditRecord
{
    protected static string $resource = SeedCultivarResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
