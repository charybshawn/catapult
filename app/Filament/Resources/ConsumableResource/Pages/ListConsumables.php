<?php

namespace App\Filament\Resources\ConsumableResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\ConsumableResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListConsumables extends ListRecords
{
    protected static string $resource = ConsumableResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
} 