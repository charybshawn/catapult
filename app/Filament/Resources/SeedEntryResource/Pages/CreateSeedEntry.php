<?php

namespace App\Filament\Resources\SeedEntryResource\Pages;

use App\Filament\Resources\SeedEntryResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateSeedEntry extends CreateRecord
{
    protected static string $resource = SeedEntryResource::class;
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
} 