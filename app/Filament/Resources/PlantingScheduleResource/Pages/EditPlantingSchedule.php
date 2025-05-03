<?php

namespace App\Filament\Resources\PlantingScheduleResource\Pages;

use App\Filament\Resources\PlantingScheduleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPlantingSchedule extends EditRecord
{
    protected static string $resource = PlantingScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
} 