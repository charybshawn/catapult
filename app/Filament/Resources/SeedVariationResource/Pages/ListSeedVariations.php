<?php

namespace App\Filament\Resources\SeedVariationResource\Pages;

use App\Filament\Resources\SeedVariationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSeedVariations extends ListRecords
{
    protected static string $resource = SeedVariationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
