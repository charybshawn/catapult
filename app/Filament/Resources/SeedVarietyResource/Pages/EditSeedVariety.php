<?php

namespace App\Filament\Resources\SeedVarietyResource\Pages;

use App\Filament\Resources\SeedVarietyResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSeedVariety extends EditRecord
{
    protected static string $resource = SeedVarietyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
} 