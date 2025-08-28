<?php

namespace App\Filament\Resources\OrderResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use App\Filament\Resources\OrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('calendar')
                ->label('Calendar View')
                ->icon('heroicon-o-calendar-days')
                ->url(OrderResource::getUrl('calendar'))
                ->color('primary'),
            CreateAction::make(),
        ];
    }
} 