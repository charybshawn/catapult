<?php

namespace App\Filament\Pages;

use App\Services\SimpleBackupService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
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
            return $backupService->listBackups()->toArray();
        } catch (\Exception $e) {
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
                        
                        // Create a temporary file in the backup directory
                        $tempBackupName = 'uploaded_' . now()->format('Y-m-d_H-i-s') . '.sql';
                        $backupDir = storage_path('app/backups/database');
                        
                        if (!is_dir($backupDir)) {
                            mkdir($backupDir, 0755, true);
                        }
                        
                        $tempBackupPath = $backupDir . '/' . $tempBackupName;
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
                        $result = $backupService->restoreBackup($tempBackupName);
                        
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
}