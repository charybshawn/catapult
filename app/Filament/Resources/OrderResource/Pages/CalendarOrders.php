<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Filament\Widgets\OrderCalendarWidget;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;

class CalendarOrders extends Page
{
    protected static string $resource = OrderResource::class;

    protected static string $view = 'filament.resources.order-resource.pages.calendar-orders';

    protected static ?string $title = 'Orders Calendar';

    protected static ?string $navigationLabel = 'Calendar';

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('list')
                ->label('List View')
                ->icon('heroicon-o-list-bullet')
                ->url(OrderResource::getUrl('index'))
                ->color('gray'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            OrderCalendarWidget::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [];
    }

    /**
     * Override to use full width for calendar
     */
    public function getMaxContentWidth(): ?string
    {
        return 'full';
    }
}