<?php

namespace App\Filament\Resources\CropPlanResource\Pages;

use Filament\Actions\Action;
use App\Filament\Resources\CropPlanResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCropPlans extends ListRecords
{
    protected static string $resource = CropPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('calendar')
                ->label('Calendar View')
                ->icon('heroicon-o-calendar-days')
                ->url(CropPlanResource::getUrl('index'))
                ->color('primary'),
        ];
    }
}
