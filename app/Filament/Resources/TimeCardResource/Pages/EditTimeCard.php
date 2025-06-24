<?php

namespace App\Filament\Resources\TimeCardResource\Pages;

use App\Filament\Resources\TimeCardResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTimeCard extends EditRecord
{
    protected static string $resource = TimeCardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
