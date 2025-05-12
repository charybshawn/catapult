<?php

namespace App\Filament\Resources\PriceVariationResource\Pages;

use App\Filament\Resources\PriceVariationResource;
use App\Filament\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPriceVariations extends ListRecords
{
    protected static string $resource = PriceVariationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('view_products')
                ->label('View Products')
                ->icon('heroicon-o-shopping-bag')
                ->url(ProductResource::getUrl('index')),
        ];
    }
}
