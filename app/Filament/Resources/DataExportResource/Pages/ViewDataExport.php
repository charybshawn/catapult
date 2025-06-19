<?php

namespace App\Filament\Resources\DataExportResource\Pages;

use App\Filament\Resources\DataExportResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewDataExport extends ViewRecord
{
    protected static string $resource = DataExportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('download')
                ->label('Download')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->visible(fn () => $this->record->fileExists())
                ->action(function () {
                    return response()->download($this->record->filepath);
                }),
        ];
    }
}