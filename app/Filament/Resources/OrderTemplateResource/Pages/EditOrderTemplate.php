<?php

namespace App\Filament\Resources\OrderTemplateResource\Pages;

use App\Filament\Resources\OrderTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOrderTemplate extends EditRecord
{
    protected static string $resource = OrderTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
