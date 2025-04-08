<?php

namespace App\Filament\Resources\CropResource\Pages;

use App\Filament\Resources\CropResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCrop extends CreateRecord
{
    protected static string $resource = CropResource::class;
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
} 