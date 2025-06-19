<?php

namespace App\Filament\Resources\DataExportResource\Pages;

use App\Filament\Resources\DataExportResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDataExports extends ListRecords
{
    protected static string $resource = DataExportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Export Data'),
                
            Actions\Action::make('import')
                ->label('Import Data')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->form([
                    \Filament\Forms\Components\FileUpload::make('file')
                        ->label('Import File')
                        ->helperText('Upload a ZIP file exported from this system')
                        ->acceptedFileTypes(['application/zip'])
                        ->required()
                        ->maxSize(1024 * 50) // 50MB
                        ->preserveFilenames()
                        ->directory('imports'),
                        
                    \Filament\Forms\Components\Toggle::make('validate_only')
                        ->label('Validate Only')
                        ->helperText('Check data compatibility without importing')
                        ->default(true),
                        
                    \Filament\Forms\Components\Toggle::make('truncate')
                        ->label('Replace Existing Data')
                        ->helperText('WARNING: This will delete existing data before importing')
                        ->default(false)
                        ->visible(fn ($get) => !$get('validate_only')),
                ])
                ->action(function (array $data) {
                    $filepath = storage_path('app/' . $data['file']);
                    
                    try {
                        $importService = new \App\Services\ImportExport\ResourceImportService();
                        
                        $options = [
                            'validate_only' => $data['validate_only'],
                            'truncate' => $data['truncate'] ?? false,
                        ];
                        
                        $results = $importService->importResource($filepath, $options);
                        
                        if ($data['validate_only']) {
                            if (empty($results['errors'])) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Validation Successful')
                                    ->body('Data is compatible and ready to import. Run import again without validation to proceed.')
                                    ->success()
                                    ->send();
                            } else {
                                \Filament\Notifications\Notification::make()
                                    ->title('Validation Failed')
                                    ->body(implode("\n", $results['errors']))
                                    ->danger()
                                    ->send();
                            }
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->title('Import Successful')
                                ->body("Successfully imported data for {$results['resource']} resource.")
                                ->success()
                                ->send();
                        }
                        
                        // Clean up uploaded file
                        unlink($filepath);
                        
                    } catch (\Exception $e) {
                        \Filament\Notifications\Notification::make()
                            ->title('Import Failed')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                            
                        // Clean up uploaded file
                        if (file_exists($filepath)) {
                            unlink($filepath);
                        }
                    }
                }),
        ];
    }
}
