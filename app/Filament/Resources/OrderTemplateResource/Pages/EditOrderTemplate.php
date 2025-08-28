<?php

namespace App\Filament\Resources\OrderTemplateResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\OrderTemplateResource;
use Filament\Actions;
use App\Filament\Pages\Base\BaseEditRecord;

class EditOrderTemplate extends BaseEditRecord
{
    protected static string $resource = OrderTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
