<?php

namespace App\Filament\Resources\SeedScrapeUploadResource\Pages;

use App\Filament\Resources\SeedScrapeUploadResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateSeedScrapeUpload extends CreateRecord
{
    protected static string $resource = SeedScrapeUploadResource::class;
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
} 