<?php

namespace App\Filament\Resources\TimeCardResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\TimeCardResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTimeCards extends ListRecords
{
    protected static string $resource = TimeCardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
