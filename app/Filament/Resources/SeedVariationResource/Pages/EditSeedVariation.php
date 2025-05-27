<?php

namespace App\Filament\Resources\SeedVariationResource\Pages;

use App\Filament\Resources\SeedVariationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSeedVariation extends EditRecord
{
    protected static string $resource = SeedVariationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
