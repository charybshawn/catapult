<?php

namespace App\Filament\Resources\SeedVariationResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\SeedVariationResource;
use Filament\Actions;
use App\Filament\Pages\Base\BaseEditRecord;

class EditSeedVariation extends BaseEditRecord
{
    protected static string $resource = SeedVariationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
