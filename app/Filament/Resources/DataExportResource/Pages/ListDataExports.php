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
                    return ($data['action_type'] ?? 'validate') === 'import' && ($data['import_mode'] ?? 'insert') === 'replace';
                })
                ->modalHeading('⚠️ Confirm Destructive Import')
                ->modalDescription('You have selected REPLACE mode. This will permanently DELETE ALL existing data in the affected tables before importing new data. This action cannot be undone. Are you absolutely sure?')
                ->modalSubmitActionLabel('Yes, DELETE all data and import')
                ->modalCancelActionLabel('Cancel')
                ->modalWidth('2xl')
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
                        
                    \Filament\Forms\Components\Radio::make('action_type')
                        ->label('What would you like to do?')
                        ->options([
                            'validate' => 'Validate Only - Check if data is compatible',
                            'import' => 'Import Data - Process the import',
                        ])
                        ->default('validate')
                        ->reactive()
                        ->columnSpanFull(),
                        
                    \Filament\Forms\Components\Section::make('Import Options')
                        ->description('Choose how to handle existing data')
                        ->schema([
                            \Filament\Forms\Components\Radio::make('import_mode')
                                ->label('Import Mode')
                                ->options([
                                    'insert' => 'Add New Records Only - Skip records that already exist',
                                    'upsert' => 'Update & Add - Update existing records and add new ones',
                                    'replace' => 'Replace All - DELETE all existing data and import fresh',
                                ])
                                ->default('insert')
                                ->reactive()
                                ->columnSpanFull()
                                ->descriptions([
                                    'insert' => 'Safe option: Only adds records that don\'t exist based on unique columns',
                                    'upsert' => 'Updates matching records and adds new ones',
                                    'replace' => '⚠️ DANGER: Permanently deletes ALL existing data first!',
                                ]),
                                
                            \Filament\Forms\Components\Placeholder::make('replace_warning')
                                ->label('')
                                ->content(new \Illuminate\Support\HtmlString('
                                    <div class="p-4 bg-warning-50 dark:bg-warning-900/50 rounded-lg border border-warning-200 dark:border-warning-800">
                                        <div class="flex">
                                            <div class="flex-shrink-0">
                                                <svg class="h-5 w-5 text-warning-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                                </svg>
                                            </div>
                                            <div class="ml-3">
                                                <h3 class="text-sm font-medium text-warning-800 dark:text-warning-200">
                                                    Destructive Operation Warning
                                                </h3>
                                                <div class="mt-2 text-sm text-warning-700 dark:text-warning-300">
                                                    This will PERMANENTLY DELETE ALL existing data in the related tables before importing. This action cannot be undone!
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                '))
                                ->visible(fn ($get) => $get('import_mode') === 'replace'),
                                
                            \Filament\Forms\Components\TextInput::make('unique_columns')
                                ->label('Unique Columns (Optional)')
                                ->helperText('Comma-separated column names to identify unique records (e.g., id,email)')
                                ->placeholder('Leave empty to auto-detect (id, email, username, etc.)')
                                ->visible(fn ($get) => in_array($get('import_mode'), ['insert', 'upsert'])),
                        ])
                        ->visible(fn ($get) => $get('action_type') === 'import')
                        ->columnSpanFull(),
                        
                    \Filament\Forms\Components\Section::make('Summary')
                        ->description(function ($get) {
                            $action = $get('action_type');
                            $mode = $get('import_mode') ?? 'insert';
                            
                            if ($action === 'validate') {
                                return 'The import file will be checked for compatibility without making any changes to your database.';
                            }
                            
                            return match($mode) {
                                'insert' => 'New records will be added. Existing records will be skipped.',
                                'upsert' => 'Existing records will be updated and new records will be added.',
                                'replace' => '⚠️ ALL existing data will be DELETED and replaced with the imported data.',
                                default => ''
                            };
                        })
                        ->schema([
                            \Filament\Forms\Components\Placeholder::make('action_summary')
                                ->label('')
                                ->content(function ($get) {
                                    $action = $get('action_type');
                                    $mode = $get('import_mode') ?? 'insert';
                                    
                                    if ($action === 'validate') {
                                        return '✓ No data will be modified - validation only';
                                    }
                                    
                                    return match($mode) {
                                        'insert' => '✓ Safe import - only new records will be added',
                                        'upsert' => '✓ Update and add - existing records updated, new ones added',
                                        'replace' => '⚠️ DESTRUCTIVE - all data will be deleted first',
                                        default => ''
                                    };
                                }),
                        ])
                        ->columnSpanFull(),
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
                    
                    \Illuminate\Support\Facades\Log::info('Using file path:', ['path' => $filepath]);
                    
                    try {
                        $importService = new \App\Services\ImportExport\ResourceImportService();
                        
                        $validateOnly = $data['action_type'] === 'validate';
                        
                        $options = [
                            'validate_only' => $validateOnly,
                            'mode' => $data['import_mode'] ?? 'insert',
                            'unique_columns' => !empty($data['unique_columns']) 
                                ? array_map('trim', explode(',', $data['unique_columns'])) 
                                : [],
                        ];
                        
                        $results = $importService->importResource($filepath, $options);
                        
                        if ($validateOnly) {
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
