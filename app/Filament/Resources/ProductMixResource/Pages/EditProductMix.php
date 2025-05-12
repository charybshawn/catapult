<?php

namespace App\Filament\Resources\ProductMixResource\Pages;

use App\Filament\Resources\ProductMixResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProductMix extends EditRecord
{
    protected static string $resource = ProductMixResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->tooltip('Delete mix'),
        ];
    }
} 