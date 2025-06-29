<?php

namespace App\Filament\Resources\MasterCultivarResource\Pages;

use App\Filament\Resources\MasterCultivarResource;
use Filament\Actions;
use App\Filament\Pages\Base\BaseEditRecord;

class EditMasterCultivar extends BaseEditRecord
{
    protected static string $resource = MasterCultivarResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
