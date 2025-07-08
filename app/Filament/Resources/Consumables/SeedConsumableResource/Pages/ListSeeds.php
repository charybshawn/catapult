<?php

namespace App\Filament\Resources\Consumables\SeedConsumableResource\Pages;

use App\Filament\Resources\Consumables\SeedConsumableResource;
use App\Filament\Resources\ConsumableResource\Pages\ListConsumables;

class ListSeeds extends ListConsumables
{
    protected static string $resource = SeedConsumableResource::class;
    
    protected function getHeaderWidgets(): array
    {
        return [
            // Could add seed-specific widgets here in the future
        ];
    }
    
    public function getTitle(): string
    {
        return 'Seeds';
    }
}