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
                ->requiresConfirmation(function (array $data) {
                    return !($data['validate_only'] ?? false) && ($data['import_mode'] ?? 'insert') === 'replace';
                })
                ->modalHeading('Confirm Data Replacement')
                ->modalDescription('This will permanently DELETE ALL existing data before importing. This action cannot be undone.')
                ->modalSubmitActionLabel('Yes, replace all data')
                ->form([
                    \Filament\Forms\Components\FileUpload::make('file')
                        ->label('Import File')
                        ->helperText('Upload a ZIP file exported from this system')
                        ->acceptedFileTypes(['application/zip', 'application/x-zip-compressed', 'application/x-zip', 'application/octet-stream'])
                        ->required()
                        ->maxSize(1024 * 50) // 50MB
                        ->preserveFilenames()
                        ->directory('imports')
                        ->disk('local')
                        ->visibility('private')
                        ->storeFileNamesIn('original_file_name'),
                        
                    \Filament\Forms\Components\Toggle::make('validate_only')
                        ->label('Validate Only')
                        ->helperText('Check data compatibility without importing')
                        ->default(true),
                        
                    \Filament\Forms\Components\Select::make('import_mode')
                        ->label('Import Mode')
                        ->options([
                            'insert' => 'Add New Records Only',
                            'replace' => 'Replace All Data (Delete existing first)',
                            'upsert' => 'Update Existing & Add New Records',
                        ])
                        ->default('insert')
                        ->helperText('Choose how to handle existing data')
                        ->visible(fn ($get) => !$get('validate_only'))
                        ->reactive(),
                        
                    \Filament\Forms\Components\Placeholder::make('replace_warning')
                        ->content('⚠️ WARNING: This will permanently DELETE ALL existing data before importing!')
                        ->visible(fn ($get) => !$get('validate_only') && $get('import_mode') === 'replace'),
                        
                    \Filament\Forms\Components\TextInput::make('unique_columns')
                        ->label('Unique Columns')
                        ->helperText('Comma-separated column names to identify unique records (e.g., id,email)')
                        ->placeholder('Leave empty to auto-detect')
                        ->visible(fn ($get) => !$get('validate_only') && in_array($get('import_mode'), ['insert', 'upsert'])),
                ])
                ->action(function (array $data) {
                    // Ensure imports directory exists
                    if (!file_exists(storage_path('app/imports'))) {
                        mkdir(storage_path('app/imports'), 0755, true);
                    }
                    
                    // Debug: Log what we receive from FileUpload
                    \Illuminate\Support\Facades\Log::info('Import file data received:', ['file' => $data['file'], 'all_data' => $data]);
                    
                    // FileUpload might return the full path or just filename
                    $fileInput = is_array($data['file']) ? $data['file'][0] : $data['file'];
                    
                    // Extract just the filename if it includes a path
                    $filename = basename($fileInput);
                    
                    // Try multiple possible paths
                    $possiblePaths = [
                        storage_path('app/' . $fileInput), // Full path as returned by FileUpload
                        storage_path('app/public/' . $fileInput), // Public disk path
                        storage_path('app/private/' . $fileInput), // Private disk path
                        storage_path('app/imports/' . $filename), // Just filename in imports dir
                        storage_path('app/' . $filename), // Just filename in app dir
                    ];
                    
                    // Also check if the file might be in the livewire-tmp directory
                    if (str_contains($fileInput, 'livewire-tmp')) {
                        $possiblePaths[] = storage_path('app/' . $fileInput);
                    }
                    
                    $filepath = null;
                    foreach ($possiblePaths as $path) {
                        if (file_exists($path)) {
                            $filepath = $path;
                            break;
                        }
                    }
                    
                    // Debug: Check if file exists
                    if (!$filepath) {
                        \Illuminate\Support\Facades\Log::error('File not found after upload', [
                            'file_input' => $fileInput,
                            'filename' => $filename,
                            'checked_paths' => $possiblePaths,
                            'raw_file_data' => $data['file']
                        ]);
                        
                        \Filament\Notifications\Notification::make()
                            ->title('File Not Found')
                            ->body("The uploaded file could not be found. Please try uploading again.")
                            ->danger()
                            ->send();
                        return;
                    }
                    
                    \Log::info('Using file path:', ['path' => $filepath]);
                    
                    try {
                        $importService = new \App\Services\ImportExport\ResourceImportService();
                        
                        $options = [
                            'validate_only' => $data['validate_only'],
                            'mode' => $data['import_mode'] ?? 'insert',
                            'unique_columns' => !empty($data['unique_columns']) 
                                ? array_map('trim', explode(',', $data['unique_columns'])) 
                                : [],
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
                            // Parse the import results to show statistics
                            $stats = [];
                            foreach ($results['tables'] as $tableResult) {
                                if (isset($tableResult['message'])) {
                                    // Extract numbers from the output message
                                    if (preg_match('/imported: (\d+)/', $tableResult['message'], $matches)) {
                                        $stats['imported'] = ($stats['imported'] ?? 0) + (int)$matches[1];
                                    }
                                    if (preg_match('/Skipped existing: (\d+)/', $tableResult['message'], $matches)) {
                                        $stats['skipped'] = ($stats['skipped'] ?? 0) + (int)$matches[1];
                                    }
                                    if (preg_match('/Updated existing: (\d+)/', $tableResult['message'], $matches)) {
                                        $stats['updated'] = ($stats['updated'] ?? 0) + (int)$matches[1];
                                    }
                                }
                            }
                            
                            $message = "Successfully processed {$results['resource']} import.";
                            if (!empty($stats)) {
                                $parts = [];
                                if (isset($stats['imported'])) $parts[] = "Imported: {$stats['imported']}";
                                if (isset($stats['updated'])) $parts[] = "Updated: {$stats['updated']}";
                                if (isset($stats['skipped'])) $parts[] = "Skipped: {$stats['skipped']}";
                                $message .= "\n" . implode(', ', $parts);
                            }
                            
                            \Filament\Notifications\Notification::make()
                                ->title('Import Successful')
                                ->body($message)
                                ->success()
                                ->send();
                        }
                        
                        // Clean up uploaded file
                        if (file_exists($filepath)) {
                            unlink($filepath);
                        }
                        
                    } catch (\Exception $e) {
                        \Filament\Notifications\Notification::make()
                            ->title('Import Failed')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                            
                        // Clean up uploaded file
                        if (isset($filepath) && file_exists($filepath)) {
                            unlink($filepath);
                        }
                    }
                }),
        ];
    }
}
