<?php

namespace App\Filament\Resources\SupplierResource\Pages;

use App\Filament\Resources\SupplierResource;
use Filament\Actions;
use App\Filament\Pages\Base\BaseEditRecord;

class EditSupplier extends BaseEditRecord
{
    protected static string $resource = SupplierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->tooltip('Delete supplier'),
        ];
    }
} 