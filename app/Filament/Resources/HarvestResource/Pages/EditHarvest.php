<?php

namespace App\Filament\Resources\HarvestResource\Pages;

use App\Filament\Resources\HarvestResource;
use Filament\Actions;
use App\Filament\Pages\Base\BaseEditRecord;

class EditHarvest extends BaseEditRecord
{
    protected static string $resource = HarvestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
