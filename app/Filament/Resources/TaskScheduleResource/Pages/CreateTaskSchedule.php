<?php

namespace App\Filament\Resources\TaskScheduleResource\Pages;

use App\Filament\Resources\TaskScheduleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTaskSchedule extends CreateRecord
{
    protected static string $resource = TaskScheduleResource::class;
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
} 