<?php

namespace App\Filament\Resources\DataExportResource\Pages;

use App\Filament\Resources\DataExportResource;
use App\Services\ImportExport\ResourceExportService;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateDataExport extends CreateRecord
{
    protected static string $resource = DataExportResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        try {
            $exportService = new ResourceExportService();
            
            // Perform the export
            $options = [
                'format' => $data['format'],
                'include_timestamps' => $data['include_timestamps'] ?? false,
            ];
            
            $zipPath = $exportService->exportResource($data['resource'], $options);
            
            // Read the manifest from the ZIP
            $zip = new \ZipArchive();
            $zip->open($zipPath);
            $manifestContent = $zip->getFromName('manifest.json');
            $manifest = json_decode($manifestContent, true);
            $zip->close();
            
            // Update data with export details
            $data['filename'] = basename($zipPath);
            $data['filepath'] = $zipPath;
            $data['manifest'] = $manifest;
            $data['options'] = $options;
            $data['file_size'] = filesize($zipPath);
            $data['record_count'] = array_sum($manifest['statistics'] ?? []);
            $data['user_id'] = auth()->id();
            
        } catch (\Exception $e) {
            Notification::make()
                ->title('Export Failed')
                ->body('Failed to export data: ' . $e->getMessage())
                ->danger()
                ->send();
                
            $this->halt();
        }
        
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Export completed successfully';
    }
}
