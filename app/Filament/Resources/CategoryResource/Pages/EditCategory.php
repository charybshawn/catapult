<?php

namespace App\Filament\Resources\CategoryResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\CategoryResource;
use Filament\Actions;
use App\Filament\Pages\Base\BaseEditRecord;

class EditCategory extends BaseEditRecord
{
    protected static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
