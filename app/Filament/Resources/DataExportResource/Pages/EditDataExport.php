<?php

namespace App\Filament\Resources\DataExportResource\Pages;

use App\Filament\Resources\DataExportResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDataExport extends EditRecord
{
    protected static string $resource = DataExportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
