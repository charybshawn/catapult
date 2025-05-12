<?php

namespace App\Filament\Resources\SeedVarietyResource\Pages;

use App\Filament\Resources\SeedVarietyResource;
use App\Filament\Pages\BaseCreateRecord;

class CreateSeedVariety extends BaseCreateRecord
{
    protected static string $resource = SeedVarietyResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
} 