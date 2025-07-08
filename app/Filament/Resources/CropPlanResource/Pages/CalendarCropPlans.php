<?php

namespace App\Filament\Resources\CropPlanResource\Pages;

use App\Filament\Resources\CropPlanResource;
use App\Filament\Widgets\CropPlanCalendarWidget;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;

class CalendarCropPlans extends Page
{
    protected static string $resource = CropPlanResource::class;

    protected static string $view = 'filament.resources.crop-plan-resource.pages.calendar-crop-plans';

    protected static ?string $title = 'Crop Planning Calendar';

    protected static ?string $navigationLabel = 'Calendar';

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('list')
                ->label('List View')
                ->icon('heroicon-o-list-bullet')
                ->url(CropPlanResource::getUrl('index'))
                ->color('gray'),
            
            Action::make('manual_planning')
                ->label('Manual Planning')
                ->icon('heroicon-o-calculator')
                ->url(CropPlanResource::getUrl('manual-planning'))
                ->color('success'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            CropPlanCalendarWidget::class,
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