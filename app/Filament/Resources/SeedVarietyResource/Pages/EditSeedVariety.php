<?php

namespace App\Filament\Resources\SeedVarietyResource\Pages;

use App\Filament\Resources\SeedVarietyResource;
use Filament\Actions;
use App\Filament\Pages\Base\BaseEditRecord;

class EditSeedVariety extends BaseEditRecord
{
    protected static string $resource = SeedVarietyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
} 