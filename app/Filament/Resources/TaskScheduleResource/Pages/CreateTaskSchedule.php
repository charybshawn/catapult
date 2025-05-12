<?php

namespace App\Filament\Resources\TaskScheduleResource\Pages;

use App\Filament\Resources\TaskScheduleResource;
use App\Filament\Pages\BaseCreateRecord;

class CreateTaskSchedule extends BaseCreateRecord
{
    protected static string $resource = TaskScheduleResource::class;
    
    protected function getHeaderTitle(): string 
    {
        return 'Create Crop Alert';
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
} 