<?php

namespace App\Filament\Resources\PackagingTypeResource\Pages;

use App\Filament\Resources\PackagingTypeResource;
use Filament\Actions;
use App\Filament\Pages\Base\BaseEditRecord;

class EditPackagingType extends BaseEditRecord
{
    protected static string $resource = PackagingTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
