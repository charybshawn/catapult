<?php

namespace App\Filament\Resources\CropBatchResource\Pages;

use App\Filament\Resources\CropBatchResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewCropBatch extends ViewRecord
{
    protected static string $resource = CropBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Edit Batch')
                ->icon('heroicon-o-pencil-square'),
        ];
    }
}