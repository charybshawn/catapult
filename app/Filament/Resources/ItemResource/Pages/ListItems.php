<?php

namespace App\Filament\Resources\ItemResource\Pages;

use App\Filament\Resources\ItemResource;
use App\Filament\Resources\PriceVariationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListItems extends ListRecords
{
    protected static string $resource = ItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('view_price_variations')
                ->label('View Price Variations')
                ->icon('heroicon-o-currency-dollar')
                ->url(PriceVariationResource::getUrl('index')),
        ];
    }
} 