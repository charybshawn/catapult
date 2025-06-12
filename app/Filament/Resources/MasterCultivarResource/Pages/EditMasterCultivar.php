<?php

namespace App\Filament\Resources\MasterCultivarResource\Pages;

use App\Filament\Resources\MasterCultivarResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMasterCultivar extends EditRecord
{
    protected static string $resource = MasterCultivarResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
