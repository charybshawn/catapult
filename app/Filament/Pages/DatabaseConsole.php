<?php

namespace App\Filament\Pages;

use App\Services\SimpleBackupService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DatabaseConsole extends Page
{

    protected static ?string $navigationIcon = 'heroicon-o-command-line';
    protected static ?string $navigationLabel = 'Database Console';
    protected static ?string $navigationGroup = 'System';
    protected static string $view = 'filament.pages.database-console';

    public function getBackups(): array
    {
        try {
            $backupService = new SimpleBackupService();
            $backups = $backupService->listBackups()->toArray();
            
            // Debug: Log backup directory and files found
            Log::info('Backup list debug', [
                'backup_count' => count($backups),
                'backup_files' => array_column($backups, 'name'),
            ]);
            
            return $backups;
        } catch (\Exception $e) {
            Log::error('Failed to get backups', ['error' => $e->getMessage()]);
            return [];
        }
    }


    protected function getHeaderActions(): array
    {
        return [
            Action::make('createBackup')
                ->label('Create Backup')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->form([
                    Toggle::make('safe_backup')
                        ->label('Safe Backup (Git Integration)')
                        ->helperText('Create backup, commit changes, and push to git')
                        ->reactive(),
                    TextInput::make('commit_message')
                        ->label('Git Commit Message (Optional)')
                        ->placeholder('Safe backup: ' . now()->format('Y-m-d H:i:s'))
                        ->helperText('Custom commit message for git')
                        ->hidden(fn ($get) => !$get('safe_backup')),
                    Toggle::make('no_push')
                        ->label('Skip Git Push')
                        ->helperText('Skip pushing changes to remote repository')
                        ->hidden(fn ($get) => !$get('safe_backup')),
                    Select::make('backup_type')
                        ->label('Backup Type')
                        ->options([
                            'full' => 'Full Backup (Schema + Data)',
                            'schema_only' => 'Schema Only',
                            'data_only' => 'Data Only', 
                            'separate' => 'Separate Files (Schema + Data)'
                        ])
                        ->default('full')
                        ->helperText('Choose what to include in the backup')
                        ->hidden(fn ($get) => !$get('safe_backup')),
                ])
                ->action(function (array $data) {
                    $this->createBackup($data);
                }),


            Action::make('restoreBackup')
                ->label('Restore Backup')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('danger')
                ->form([
                    Select::make('restore_source')
                        ->label('Restore Source')
                        ->options([
                            'latest' => 'Use Latest Backup',
                            'existing' => 'Select Existing Backup',
                            'upload' => 'Upload Backup File'
                        ])
                        ->default('existing')
                        ->reactive()
                        ->helperText('Choose the source for your backup file'),
                    Select::make('backup_file')
                        ->label('Select Backup File')
                        ->options(function () {
                            $backups = $this->getBackups();
                            $options = [];
                            foreach ($backups as $backup) {
                                $label = $backup['name'] . ' (' . $backup['size'] . ') - ' . $backup['created_at']->format('M j, Y g:i A');
                                $options[$backup['name']] = $label;
                            }
                            return $options;
                        })
                        ->required(fn ($get) => $get('restore_source') === 'existing')
                        ->searchable()
                        ->helperText('Select a backup file to restore from')
                        ->visible(fn ($get) => $get('restore_source') === 'existing'),
                    FileUpload::make('upload_file')
                        ->label('Upload Backup File')
                        ->maxSize(1024 * 1024) // 1GB max
                        ->directory('temp-backups')
                        ->required(fn ($get) => $get('restore_source') === 'upload')
                        ->helperText('Upload a .sql backup file (max 1GB) - accepts any file type')
                        ->visible(fn ($get) => $get('restore_source') === 'upload'),
                ])
                ->action(function (array $data) {
                    $this->restoreBackup($data);
                })
                ->requiresConfirmation()
                ->modalHeading('Restore Database')
                ->modalDescription('⚠️ WARNING: This will completely replace your current database! This action cannot be undone.')
                ->modalSubmitActionLabel('Restore Database'),

            Action::make('listBackups')
                ->label('Refresh List')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(function () {
                    $this->dispatch('refresh-backups');
                    Notification::make()
                        ->info()
                        ->title('Backup List Refreshed')
                        ->body('The backup list has been updated.')
                        ->send();
                }),

            Action::make('mergeSchema')
                ->label('Merge Schema File')
                ->icon('heroicon-o-document-plus')
                ->color('info')
                ->form([
                    FileUpload::make('schema_file')
                        ->label('Upload Schema File')
                        ->maxSize(50 * 1024) // 50MB max for schema files
                        ->directory('temp-schema')
                        ->required()
                        ->helperText('Upload a .sql schema file to merge with the current database (data-only recommended) - accepts any file type'),
                    Toggle::make('create_backup_first')
                        ->label('Create backup before merge')
                        ->helperText('Recommended: Create a safety backup before applying schema changes')
                        ->default(true),
                    Textarea::make('merge_notes')
                        ->label('Merge Notes')
                        ->placeholder('Optional: Describe what this schema merge contains...')
                        ->rows(3),
                ])
                ->action(function (array $data) {
                    $this->mergeSchemaFile($data);
                })
                ->requiresConfirmation()
                ->modalHeading('Merge Schema File')
                ->modalDescription('This will append/merge the uploaded schema with your current database. Existing data will be preserved.')
                ->modalSubmitActionLabel('Merge Schema'),
        ];
    }

    protected function createBackup(array $data): void
    {
        $this->safeBackupOutput = '';
        $this->safeBackupRunning = true;
        $this->safeBackupSuccess = false;
        $this->showSafeBackupModal = true;
        
        $this->dispatch('open-safe-backup-modal');
        
        try {
            if ($data['safe_backup'] ?? false) {
                // Use safe backup command via Artisan::call (works fine)
                $command = 'safe:backup';
                $parameters = [];
                
                if (!empty($data['commit_message'])) {
                    $parameters['--commit-message'] = $data['commit_message'];
                }
                
                if ($data['no_push'] ?? false) {
                    $parameters['--no-push'] = true;
                }
                
                $backupType = $data['backup_type'] ?? 'full';
                switch ($backupType) {
                    case 'schema_only':
                        $parameters['--schema-only'] = true;
                        break;
                    case 'data_only':
                        $parameters['--data-only'] = true;
                        break;
                    case 'separate':
                        $parameters['--separate'] = true;
                        break;
                    default:
                        // full backup - no additional flags needed
                        break;
                }

                $exitCode = Artisan::call($command, $parameters);
                $output = Artisan::output();
                
                $this->safeBackupOutput = $output;
                $this->safeBackupRunning = false;
                $this->safeBackupSuccess = ($exitCode === 0);
            } else {
                // Use backup service directly to avoid Artisan::call environment issues
                $backupService = new \App\Services\SimpleBackupService();
                $this->safeBackupOutput = "Creating database backup...\n";
                
                $filename = $backupService->createBackup();
                
                $this->safeBackupOutput .= "Backup created successfully!\n";
                $this->safeBackupOutput .= "File: {$filename}\n";
                $this->safeBackupRunning = false;
                $this->safeBackupSuccess = true;
            }

            if ($this->safeBackupSuccess) {
                $this->dispatch('refresh-backups');
            }
        } catch (\Exception $e) {
            $this->safeBackupOutput .= "\n\nError: " . $e->getMessage();
            $this->safeBackupRunning = false;
            $this->safeBackupSuccess = false;
        }
    }

    public $safeBackupOutput = '';
    public $safeBackupRunning = false;
    public $safeBackupSuccess = false;
    public $showSafeBackupModal = false;

    public $restoreOutput = '';
    public $restoreRunning = false;
    public $restoreSuccess = false;
    public $showRestoreModal = false;


    public function closeSafeBackupModal(): void
    {
        $this->showSafeBackupModal = false;
        $this->safeBackupOutput = '';
        $this->safeBackupRunning = false;
        $this->safeBackupSuccess = false;
    }

    public function closeRestoreModal(): void
    {
        $this->showRestoreModal = false;
        $this->restoreOutput = '';
        $this->restoreRunning = false;
        $this->restoreSuccess = false;
    }


    protected function restoreBackup(array $data): void
    {
        $this->restoreOutput = '';
        $this->restoreRunning = true;
        $this->restoreSuccess = false;
        $this->showRestoreModal = true;
        
        $this->dispatch('open-restore-modal');
        
        try {
            $restoreSource = $data['restore_source'] ?? 'existing';
            
            if ($restoreSource === 'upload') {
                // Handle uploaded file
                $uploadedFile = $data['upload_file'] ?? null;
                if ($uploadedFile) {
                    $this->restoreOutput = "Processing uploaded file...\n";
                    
                    // Get the actual file path - Filament stores files in temp-backups directory
                    $filePath = null;
                    if (is_string($uploadedFile)) {
                        $filePath = $uploadedFile;
                    } elseif (is_array($uploadedFile) && !empty($uploadedFile)) {
                        // Get the first uploaded file
                        $filePath = reset($uploadedFile);
                    }
                    
                    if (!$filePath) {
                        throw new \Exception("No valid file path found in upload data: " . json_encode($uploadedFile));
                    }
                    
                    $this->restoreOutput .= "File identifier: {$filePath}\n";
                    
                    // Debug: Check what's actually in storage - check all directories
                    $this->restoreOutput .= "Debug: Checking storage directories:\n";
                    try {
                        // Check multiple possible storage locations
                        $checkPaths = ['', 'temp-backups', 'public', 'private', 'livewire-tmp'];
                        foreach ($checkPaths as $checkPath) {
                            try {
                                $files = Storage::files($checkPath);
                                if (!empty($files)) {
                                    $this->restoreOutput .= "Directory '{$checkPath}': " . count($files) . " files\n";
                                    foreach ($files as $file) {
                                        if (str_contains($file, $filePath) || str_contains($file, '.sql')) {
                                            $this->restoreOutput .= "  - Relevant: {$file}\n";
                                        }
                                    }
                                }
                            } catch (\Exception $e) {
                                // Skip directories that don't exist
                            }
                        }
                        
                        // Also check if it's a temporary file with livewire naming
                        $this->restoreOutput .= "Checking for livewire-tmp files...\n";
                        $livewireTmpFiles = Storage::files('livewire-tmp');
                        foreach ($livewireTmpFiles as $tmpFile) {
                            $this->restoreOutput .= "  - Livewire tmp: {$tmpFile}\n";
                        }
                    } catch (\Exception $e) {
                        $this->restoreOutput .= "- Error checking storage: " . $e->getMessage() . "\n";
                    }
                    
                    // Try to get file contents using multiple approaches
                    $fileContents = null;
                    try {
                        // Approach 1: Try all possible storage paths based on actual filesystem
                        $possiblePaths = [
                            $filePath,                                    // Direct path as provided
                            'public/' . $filePath,                       // Public disk (most likely)
                            'private/' . $filePath,                      // Private disk
                            'public/temp-backups/' . basename($filePath), // Public temp-backups with just filename
                            'private/livewire-tmp/' . basename($filePath), // Livewire temp location
                        ];
                        
                        foreach ($possiblePaths as $testPath) {
                            $this->restoreOutput .= "Trying storage path: {$testPath}\n";
                            if (Storage::exists($testPath)) {
                                $fileContents = Storage::get($testPath);
                                $this->restoreOutput .= "✓ Found file using Storage at: {$testPath}\n";
                                $filePath = $testPath;
                                break;
                            }
                        }
                        
                        // Approach 2: If Storage doesn't work, try direct filesystem access
                        if (!$fileContents) {
                            $this->restoreOutput .= "Storage approach failed, trying direct filesystem...\n";
                            $directPaths = [
                                storage_path('app/' . $filePath),
                                storage_path('app/public/' . $filePath),
                                storage_path('app/public/temp-backups/' . basename($filePath)),
                                storage_path('app/private/livewire-tmp/' . basename($filePath)),
                            ];
                            
                            foreach ($directPaths as $directPath) {
                                $this->restoreOutput .= "Trying filesystem path: {$directPath}\n";
                                if (file_exists($directPath)) {
                                    $fileContents = file_get_contents($directPath);
                                    $this->restoreOutput .= "✓ Found file using filesystem at: {$directPath}\n";
                                    break;
                                }
                            }
                        }
                        
                        if (!$fileContents) {
                            throw new \Exception("Could not locate uploaded file using Storage or direct filesystem access");
                        }
                        $this->restoreOutput .= "File size: " . strlen($fileContents) . " bytes\n";
                        
                        if (strlen($fileContents) == 0) {
                            throw new \Exception("File is empty - upload may have failed");
                        }
                        
                        // Show first 200 characters for debugging
                        $preview = substr($fileContents, 0, 200);
                        $this->restoreOutput .= "File preview: " . $preview . "...\n";
                        
                        // Create a temporary file in the temp directory (will be cleaned up after restore)
                        $tempBackupName = 'uploaded_' . now()->format('Y-m-d_H-i-s') . '.sql';
                        $tempDir = storage_path('app/temp');
                        
                        if (!is_dir($tempDir)) {
                            mkdir($tempDir, 0755, true);
                        }
                        
                        $tempBackupPath = $tempDir . '/' . $tempBackupName;
                        file_put_contents($tempBackupPath, $fileContents);
                        
                        $uploadedFilePath = $tempBackupPath;
                        
                    } catch (\Exception $e) {
                        throw new \Exception("Could not read uploaded file: " . $e->getMessage());
                    }
                    
                    $this->restoreOutput .= "Analyzing backup file...\n";
                    
                    // Check file contents to understand what type of backup this is
                    if (str_contains($fileContents, 'CREATE TABLE')) {
                        $this->restoreOutput .= "Contains schema (CREATE TABLE statements)\n";
                    } else {
                        $this->restoreOutput .= "Data-only backup (no CREATE TABLE statements)\n";
                    }
                    
                    if (str_contains($fileContents, 'INSERT INTO')) {
                        $this->restoreOutput .= "Contains data (INSERT statements)\n";
                    } else {
                        $this->restoreOutput .= "No INSERT statements found\n";
                    }
                    
                    $this->restoreOutput .= "Starting restore process...\n";
                    
                    // Before restore - check what's currently in the database
                    $this->restoreOutput .= "Pre-restore counts:\n";
                    try {
                        $preCropCount = DB::table('crops')->count();
                        $preUserCount = DB::table('users')->count();
                        $preRecipeCount = DB::table('recipes')->count();
                        
                        $this->restoreOutput .= "- Crops: {$preCropCount} records\n";
                        $this->restoreOutput .= "- Users: {$preUserCount} records\n";
                        $this->restoreOutput .= "- Recipes: {$preRecipeCount} records\n";
                    } catch (\Exception $e) {
                        $this->restoreOutput .= "Could not get pre-restore counts: " . $e->getMessage() . "\n";
                    }
                    
                    // Use backup service to restore from uploaded file
                    $backupService = new SimpleBackupService();
                    
                    try {
                        $this->restoreOutput .= "Calling backup service restore...\n";
                        $result = $backupService->restoreFromFile($tempBackupPath);
                        
                        if ($result) {
                            $this->restoreOutput .= "Backup service returned success - verifying data...\n";
                            
                            
                            // Quick verification - check a few table row counts
                            try {
                                $cropCount = DB::table('crops')->count();
                                $userCount = DB::table('users')->count();
                                $recipeCount = DB::table('recipes')->count();
                                
                                $this->restoreOutput .= "Post-restore verification:\n";
                                $this->restoreOutput .= "- Crops: {$cropCount} records\n";
                                $this->restoreOutput .= "- Users: {$userCount} records\n";
                                $this->restoreOutput .= "- Recipes: {$recipeCount} records\n";
                                
                                // Check if any data was actually restored
                                $totalRestored = ($cropCount - $preCropCount) + ($userCount - $preUserCount) + ($recipeCount - $preRecipeCount);
                                if ($totalRestored > 0) {
                                    $this->restoreSuccess = true;
                                    $this->restoreOutput .= "Successfully restored {$totalRestored} new records!\n";
                                } else {
                                    $this->restoreSuccess = false;
                                    $this->restoreOutput .= "⚠️ WARNING: No new records were added. The SQL statements may have failed silently.\n";
                                    $this->restoreOutput .= "Check the backup file format and database schema compatibility.\n";
                                }
                            } catch (\Exception $verifyException) {
                                $this->restoreOutput .= "Verification failed: " . $verifyException->getMessage() . "\n";
                                $this->restoreSuccess = false;
                            }
                        } else {
                            $this->restoreOutput .= "Restore failed - backup service returned false\n";
                            $this->restoreSuccess = false;
                        }
                    } catch (\Exception $restoreException) {
                        $this->restoreOutput .= "Restore exception: " . $restoreException->getMessage() . "\n";
                        $this->restoreSuccess = false;
                    }
                    
                    // Clean up temp file
                    if (file_exists($uploadedFilePath)) {
                        unlink($uploadedFilePath);
                        $this->restoreOutput .= "Cleaned up temp file: {$tempBackupName}\n";
                    }
                    
                    // Clean up the uploaded file from storage
                    try {
                        Storage::delete($filePath);
                    } catch (\Exception $e) {
                        // Ignore cleanup errors
                    }
                } else {
                    throw new \Exception('No file uploaded');
                }
            } else {
                // Handle existing backup files or latest
                $parameters = [];
                
                // Always use force mode in web interface (no STDIN available)
                $parameters['--force'] = true;
                
                if ($restoreSource === 'latest') {
                    $parameters['--latest'] = true;
                } else {
                    $file = $data['backup_file'] ?? null;
                    if ($file) {
                        $parameters['file'] = $file;
                    }
                }

                $exitCode = Artisan::call('db:restore', $parameters);
                $output = Artisan::output();
                
                $this->restoreOutput = $output;
                $this->restoreSuccess = ($exitCode === 0);
            }
            
            $this->restoreRunning = false;

        } catch (\Exception $e) {
            $this->restoreOutput .= "\n\nError: " . $e->getMessage();
            $this->restoreRunning = false;
            $this->restoreSuccess = false;
        }
    }

    public function deleteBackup(string $filename): void
    {
        try {
            $backupService = new SimpleBackupService();
            $backupService->deleteBackup($filename);
            
            Notification::make()
                ->success()
                ->title('Backup Deleted')
                ->body("Backup '{$filename}' has been deleted successfully.")
                ->send();
            
            $this->dispatch('refresh-backups');
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('Delete Failed')
                ->body($e->getMessage())
                ->send();
        }
    }

    public function massDeleteBackups(array $filenames): void
    {
        if (empty($filenames)) {
            Notification::make()
                ->warning()
                ->title('No Backups Selected')
                ->body('Please select at least one backup to delete.')
                ->send();
            return;
        }

        $successCount = 0;
        $failCount = 0;
        $errors = [];

        foreach ($filenames as $filename) {
            try {
                $backupService = new SimpleBackupService();
                $backupService->deleteBackup($filename);
                $successCount++;
            } catch (\Exception $e) {
                $failCount++;
                $errors[] = $filename . ': ' . $e->getMessage();
            }
        }

        // Send appropriate notification based on results
        if ($successCount > 0 && $failCount === 0) {
            Notification::make()
                ->success()
                ->title('Mass Delete Completed')
                ->body("{$successCount} backup(s) deleted successfully.")
                ->duration(5000)
                ->send();
        } elseif ($successCount > 0 && $failCount > 0) {
            Notification::make()
                ->warning()
                ->title('Partial Success')
                ->body("{$successCount} deleted, {$failCount} failed. First error: " . ($errors[0] ?? 'Unknown error'))
                ->duration(8000)
                ->send();
        } else {
            Notification::make()
                ->danger()
                ->title('Mass Delete Failed')
                ->body("Failed to delete all {$failCount} backup(s). First error: " . ($errors[0] ?? 'Unknown error'))
                ->duration(8000)
                ->send();
        }

        $this->dispatch('refresh-backups');
    }

    public function downloadBackup(string $filename): mixed
    {
        try {
            $backupService = new SimpleBackupService();
            return $backupService->downloadBackup($filename);
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('Download Failed')
                ->body($e->getMessage())
                ->send();
            
            return null;
        }
    }

    protected function formatCommandOutput(string $output): string
    {
        // Clean up the output for better display in notifications
        $output = trim($output);
        
        // Remove ANSI color codes
        $output = preg_replace('/\x1b\[[0-9;]*m/', '', $output);
        
        // Limit length for notification display
        if (strlen($output) > 300) {
            $output = substr($output, 0, 300) . '...';
        }
        
        return $output ?: 'Command executed successfully.';
    }

    protected function mergeSchemaFile(array $data): void
    {
        try {
            // Create backup first if requested
            if ($data['create_backup_first']) {
                $this->dispatch('show-notification', [
                    'type' => 'info',
                    'title' => 'Creating Backup',
                    'body' => 'Creating safety backup before schema merge...'
                ]);
                
                $backupService = new SimpleBackupService();
                $backupFilename = $backupService->createBackup('full');
            }

            // Handle uploaded file (exact same logic as restore backup)
            $uploadedFile = $data['schema_file'] ?? null;
            if ($uploadedFile) {
                // Get the actual file path - Filament stores files in temp-schema directory
                $filePath = null;
                if (is_string($uploadedFile)) {
                    $filePath = $uploadedFile;
                } elseif (is_array($uploadedFile) && !empty($uploadedFile)) {
                    // Get the first uploaded file
                    $filePath = reset($uploadedFile);
                }
                
                if (!$filePath) {
                    throw new \Exception("No valid file path found in upload data: " . json_encode($uploadedFile));
                }
                
                // Debug: Check what's actually in storage - check all directories
                $debugOutput = "Debug: File identifier: {$filePath}\n";
                $debugOutput .= "Checking storage directories:\n";
                try {
                    // Check multiple possible storage locations
                    $checkPaths = ['', 'temp-schema', 'public', 'private', 'livewire-tmp'];
                    foreach ($checkPaths as $checkPath) {
                        try {
                            $files = Storage::files($checkPath);
                            if (!empty($files)) {
                                $debugOutput .= "Directory '{$checkPath}': " . count($files) . " files\n";
                                foreach ($files as $file) {
                                    if (str_contains($file, $filePath) || str_contains($file, '.sql')) {
                                        $debugOutput .= "  - Relevant: {$file}\n";
                                    }
                                }
                            }
                        } catch (\Exception $e) {
                            // Skip directories that don't exist
                        }
                    }
                    
                    // Also check if it's a temporary file with livewire naming
                    $debugOutput .= "Checking for livewire-tmp files...\n";
                    try {
                        $livewireTmpFiles = Storage::files('livewire-tmp');
                        foreach ($livewireTmpFiles as $tmpFile) {
                            $debugOutput .= "  - Livewire tmp: {$tmpFile}\n";
                        }
                    } catch (\Exception $e) {
                        $debugOutput .= "  - No livewire-tmp directory\n";
                    }
                } catch (\Exception $e) {
                    $debugOutput .= "- Error checking storage: " . $e->getMessage() . "\n";
                }
                
                // Try to get file contents using multiple approaches
                $schemaContent = null;
                try {
                    // Approach 1: Try all possible storage paths based on actual filesystem
                    $possiblePaths = [
                        $filePath,                                    // Direct path as provided
                        'public/' . $filePath,                       // Public disk (most likely)
                        'private/' . $filePath,                      // Private disk
                        'public/temp-schema/' . basename($filePath), // Public temp-schema with just filename
                        'private/livewire-tmp/' . basename($filePath), // Livewire temp location
                    ];
                    
                    foreach ($possiblePaths as $testPath) {
                        $debugOutput .= "Trying storage path: {$testPath}\n";
                        if (Storage::exists($testPath)) {
                            $schemaContent = Storage::get($testPath);
                            $debugOutput .= "✓ Found file using Storage at: {$testPath}\n";
                            $filePath = $testPath;
                            break;
                        }
                    }
                    
                    // Approach 2: If Storage doesn't work, try direct filesystem access
                    if (!$schemaContent) {
                        $debugOutput .= "Storage approach failed, trying direct filesystem...\n";
                        $directPaths = [
                            storage_path('app/' . $filePath),
                            storage_path('app/public/' . $filePath),
                            storage_path('app/public/temp-schema/' . basename($filePath)),
                            storage_path('app/private/livewire-tmp/' . basename($filePath)),
                        ];
                        
                        foreach ($directPaths as $directPath) {
                            $debugOutput .= "Trying filesystem path: {$directPath}\n";
                            if (file_exists($directPath)) {
                                $schemaContent = file_get_contents($directPath);
                                $debugOutput .= "✓ Found file using filesystem at: {$directPath}\n";
                                break;
                            }
                        }
                    }
                    
                    if (!$schemaContent) {
                        throw new \Exception("Could not locate uploaded file using Storage or direct filesystem access\n\n" . $debugOutput);
                    }
                    
                    if (strlen($schemaContent) == 0) {
                        throw new \Exception("File is empty - upload may have failed");
                    }
                    
                } catch (\Exception $e) {
                    throw new \Exception("Could not read uploaded file: " . $e->getMessage() . "\n\n" . $debugOutput);
                }
            } else {
                throw new \Exception('No schema file was uploaded');
            }

            // Validate it's a SQL file
            if (!$this->isValidSqlContent($schemaContent)) {
                throw new \Exception('Invalid SQL content detected');
            }

            // Execute schema merge using PDO (same approach as backup service restore)
            try {
                // Get database connection
                $config = config('database.connections.mysql');
                $pdo = new \PDO(
                    "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}",
                    $config['username'],
                    $config['password'],
                    [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
                );

                // Configure for schema merge
                $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
                $pdo->exec('SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO"');
                $pdo->exec('SET AUTOCOMMIT=0');
                $pdo->exec('SET UNIQUE_CHECKS=0');
                $pdo->exec('START TRANSACTION');
                
                // Split SQL into individual statements (same method as backup service)
                $statements = $this->splitSqlStatements($schemaContent);
                
                $successCount = 0;
                $failCount = 0;
                $errors = [];
                
                foreach ($statements as $statement) {
                    $statement = trim($statement);
                    if (!empty($statement) && $statement !== ';') {
                        try {
                            // Convert INSERT statements to INSERT IGNORE to handle duplicates gracefully
                            if (str_starts_with(strtoupper($statement), 'INSERT INTO')) {
                                $statement = preg_replace('/^INSERT INTO/i', 'INSERT IGNORE INTO', $statement);
                            }
                            
                            $pdo->exec($statement);
                            $successCount++;
                        } catch (\Exception $e) {
                            $failCount++;
                            $errorMsg = $e->getMessage();
                            $stmtPreview = substr($statement, 0, 200) . "...";
                            $errors[] = "SQL Error: {$errorMsg} | Statement: {$stmtPreview}";
                        }
                    }
                }
                
                // Commit transaction and re-enable checks
                $pdo->exec('COMMIT');
                $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
                $pdo->exec('SET UNIQUE_CHECKS=1');
                
                // Check for significant failures (be more lenient for data merges)
                if ($successCount === 0) {
                    throw new \Exception("No SQL statements succeeded. First error: " . ($errors[0] ?? 'Unknown error'));
                } elseif ($failCount > 0 && $successCount < ($failCount * 0.2)) {
                    // Only fail if less than 20% succeeded (most likely a real issue)
                    throw new \Exception("Too many statements failed ({$failCount}) compared to succeeded ({$successCount}). First error: " . ($errors[0] ?? 'Unknown error'));
                }
                
                $returnCode = 0; // Success
                $output = ["Schema merge completed: {$successCount} successful, {$failCount} failed statements"];
                
            } catch (\Exception $e) {
                $returnCode = 1; // Failure
                $output = ["Schema merge failed: " . $e->getMessage()];
            }

            // Clean up uploaded file
            if ($filePath) {
                try {
                    Storage::delete($filePath);
                } catch (\Exception $e) {
                    // Ignore cleanup errors
                }
            }

            if ($returnCode !== 0) {
                throw new \Exception('Schema merge failed: ' . implode("\n", $output));
            }

            // Log the merge activity
            $notes = $data['merge_notes'] ?? 'Schema file merged via database console';
            Log::info('Schema file merged successfully', [
                'filename' => basename($filePath ?? 'unknown'),
                'notes' => $notes,
                'backup_created' => $data['create_backup_first'] ?? false,
                'backup_file' => $backupFilename ?? null,
            ]);

            Notification::make()
                ->success()
                ->title('Schema Merged Successfully')
                ->body('The schema file has been merged with your database. ' . 
                       ($data['create_backup_first'] ? "Backup created: {$backupFilename}" : ''))
                ->duration(8000)
                ->send();

        } catch (\Exception $e) {
            Log::error('Schema merge failed', [
                'error' => $e->getMessage(),
                'file' => $data['schema_file'] ?? null,
            ]);

            Notification::make()
                ->danger()
                ->title('Schema Merge Failed')
                ->body($e->getMessage())
                ->duration(10000)
                ->send();
        }
    }

    private function isValidSqlContent(string $content): bool
    {
        // Basic validation for SQL content
        $content = trim(strtolower($content));
        
        // Check for common SQL keywords that indicate valid schema content
        $validKeywords = [
            'create table',
            'insert into',
            'alter table',
            'create index',
            'create view',
            'create procedure',
            'create function'
        ];

        foreach ($validKeywords as $keyword) {
            if (str_contains($content, $keyword)) {
                return true;
            }
        }

        // Also allow if it starts with SQL comments
        return str_starts_with($content, '--') || str_starts_with($content, '/*');
    }

    /**
     * Split SQL content into individual statements (same method as backup service)
     */
    private function splitSqlStatements(string $sql): array
    {
        // Remove comments and split by semicolons
        $sql = preg_replace('/--.*$/m', '', $sql); // Remove single-line comments
        $sql = preg_replace('/\/\*.*?\*\//s', '', $sql); // Remove multi-line comments
        
        // Split by semicolons, but be careful with quoted strings
        $statements = [];
        $current = '';
        $inQuotes = false;
        $quoteChar = '';
        
        for ($i = 0; $i < strlen($sql); $i++) {
            $char = $sql[$i];
            
            if (!$inQuotes && ($char === '"' || $char === "'")) {
                $inQuotes = true;
                $quoteChar = $char;
            } elseif ($inQuotes && $char === $quoteChar) {
                // Check if it's escaped
                if ($i > 0 && $sql[$i-1] !== '\\') {
                    $inQuotes = false;
                    $quoteChar = '';
                }
            } elseif (!$inQuotes && $char === ';') {
                $statements[] = trim($current);
                $current = '';
                continue;
            }
            
            $current .= $char;
        }
        
        // Add the last statement if it exists
        if (!empty(trim($current))) {
            $statements[] = trim($current);
        }
        
        return array_filter($statements, function($stmt) {
            return !empty(trim($stmt));
        });
    }
}