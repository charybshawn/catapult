<?php

namespace App\Filament\Resources\SeedScrapeUploadResource\Pages;

use App\Filament\Resources\SeedScrapeUploadResource;
use App\Filament\Pages\Base\BaseEditRecord;
use Filament\Actions;

class EditSeedScrapeUpload extends BaseEditRecord
{
    protected static string $resource = SeedScrapeUploadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
} 