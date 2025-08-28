<?php

namespace App\Filament\Resources\SeedScrapeUploadResource\Pages;

use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\SeedScrapeUploadResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewSeedScrapeUpload extends ViewRecord
{
    protected static string $resource = SeedScrapeUploadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make(),
        ];
    }
} 