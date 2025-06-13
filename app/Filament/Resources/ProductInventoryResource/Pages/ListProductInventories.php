<?php

namespace App\Filament\Resources\ProductInventoryResource\Pages;

use App\Filament\Resources\ProductInventoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProductInventories extends ListRecords
{
    protected static string $resource = ProductInventoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Create Single Entry'),
            Actions\Action::make('bulk_create')
                ->label('Bulk Create')
                ->icon('heroicon-o-squares-plus')
                ->color('success')
                ->url(fn (): string => ProductInventoryResource::getUrl('bulk-create'))
                ->tooltip('Create inventory for all price variations of a product'),
        ];
    }
}
