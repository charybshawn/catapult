<?php

namespace App\Filament\Resources\ProductMixResource\Pages;

use App\Filament\Resources\ProductMixResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProductMixes extends ListRecords
{
    protected static string $resource = ProductMixResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New Mix'),
        ];
    }
} 