<?php

namespace App\Filament\Resources\SeedScrapeUploadResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\SeedScrapeUploadResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSeedScrapeUploads extends ListRecords
{
    protected static string $resource = SeedScrapeUploadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
} 