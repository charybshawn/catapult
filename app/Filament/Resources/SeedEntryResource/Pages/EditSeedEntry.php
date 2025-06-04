<?php

namespace App\Filament\Resources\SeedEntryResource\Pages;

use App\Filament\Resources\SeedEntryResource;
use Filament\Actions;
use App\Filament\Pages\Base\BaseEditRecord;

class EditSeedEntry extends BaseEditRecord
{
    protected static string $resource = SeedEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
} 