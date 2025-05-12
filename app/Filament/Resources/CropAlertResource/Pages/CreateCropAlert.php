<?php

namespace App\Filament\Resources\CropAlertResource\Pages;

use App\Filament\Resources\CropAlertResource;
use App\Filament\Pages\BaseCreateRecord;

class CreateCropAlert extends BaseCreateRecord
{
    protected static string $resource = CropAlertResource::class;
    
    protected function getHeaderTitle(): string 
    {
        return 'Create Crop Alert';
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
} 