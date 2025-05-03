<?php

namespace App\Filament\Resources\RecurringOrderResource\Pages;

use App\Filament\Resources\RecurringOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRecurringOrders extends ListRecords
{
    protected static string $resource = RecurringOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
} 