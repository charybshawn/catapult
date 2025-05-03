<?php

namespace App\Filament\Resources\RecurringOrderResource\Pages;

use App\Filament\Resources\RecurringOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRecurringOrder extends EditRecord
{
    protected static string $resource = RecurringOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
} 