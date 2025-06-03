<?php

namespace App\Filament\Resources\SeedEntryResource\Pages;

use App\Filament\Resources\SeedEntryResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewSeedEntry extends ViewRecord
{
    protected static string $resource = SeedEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
            Actions\Action::make('visit_url')
                ->label('Visit Product URL')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->url(fn () => $this->record->supplier_product_url)
                ->openUrlInNewTab(),
        ];
    }
} 