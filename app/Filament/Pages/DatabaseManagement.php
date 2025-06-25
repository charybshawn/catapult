<?php

namespace App\Filament\Pages;

use App\Services\SimpleBackupService;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

class DatabaseManagement extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-circle-stack';
    protected static ?string $navigationLabel = 'Database Management';
    protected static ?string $navigationGroup = 'System';
    protected static string $view = 'filament.pages.database-management';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createBackup')
                ->label('Create Data Backup')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function () {
                    try {
                        $backupService = new SimpleBackupService();
                        $filename = $backupService->createBackup();
                        
                        Notification::make()
                            ->success()
                            ->title('Data Backup Created Successfully')
                            ->body("Data-only backup file: {$filename}. Schema will be created from migrations during restore.")
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title('Backup Failed')
                            ->body($e->getMessage())
                            ->send();
                    }
                }),
                
            Action::make('uploadRestore')
                ->label('Restore from Upload')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('warning')
                ->form([
                    FileUpload::make('backup_file')
                        ->label('Data Backup File (.sql)')
                        ->acceptedFileTypes([
                            'application/sql',          // generic SQL mime
                            'application/x-sql',        // common on some browsers
                            'application/octet-stream', // fallback for unknown binary/text files
                            'text/plain',               // plain-text fallback
                            'text/x-sql',               // text-based SQL
                            '.sql',                     // extension for browser file filter
                        ])
                        ->required()
                        ->maxSize(102400) // 100MB
                        ->disk('local')
                        ->directory('temp/restore')
                        ->visibility('private'),
                ])
                ->action(function (array $data) {
                    $this->restoreFromUpload($data['backup_file']);
                })
                ->requiresConfirmation()
                ->modalHeading('Seamless Database Restore')
                ->modalDescription('This will reset the database, run fresh migrations, and import your data. Process: Reset DB → Run Migrations → Import Data → Clear Caches.')
                ->modalSubmitActionLabel('Start Seamless Restore'),
        ];
    }

    public function getBackups(): array
    {
        $backupService = new SimpleBackupService();
        return $backupService->listBackups()->toArray();
    }

    public function restoreBackup(string $filename): void
    {
        try {
            $backupService = new SimpleBackupService();
            $backupService->restoreBackup($filename);
            
            Notification::make()
                ->success()
                ->title('Seamless Restore Completed!')
                ->body('Database reset → Migrations run → Data imported → Caches cleared. All done!')
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('Seamless Restore Failed')
                ->body($e->getMessage())
                ->send();
        }
    }

    public function restoreFromFilePath(string $filePath): void
    {
        try {
            // Copy uploaded file to backup directory so service can find it
            $backupService = new SimpleBackupService();
            $filename = 'uploaded_' . time() . '.sql';
            $backupPath = storage_path('app/backups/database/' . $filename);
            
            // Ensure backup directory exists
            if (!is_dir(dirname($backupPath))) {
                mkdir(dirname($backupPath), 0755, true);
            }
            
            copy($filePath, $backupPath);
            
            // Use the seamless restore process
            $backupService->restoreBackup($filename);
            
            // Clean up temporary file
            unlink($backupPath);
            
            Notification::make()
                ->success()
                ->title('Seamless Restore Completed!')
                ->body('Database reset → Migrations run → Data imported → Caches cleared. All done!')
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('Seamless Restore Failed')
                ->body($e->getMessage())
                ->send();
        }
    }


    /**
     * Handle a backup file uploaded via the FileUpload component and initiate the restore.
     *
     * Filament\Livewire returns either:
     * 1. A \Livewire\Features\SupportFileUploads\TemporaryUploadedFile instance (when the file hasn't yet been persisted), or
     * 2. A relative storage path (string) when the component has already stored the file to the chosen disk.
     *
     * We need to support both to avoid "Uploaded file not found" validation failures.
     */
    public function restoreFromUpload($uploadedFile): void
    {
        // Resolve the absolute path to the uploaded SQL file.
        if ($uploadedFile instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
            // The file is still in Livewire's temporary directory; use its real path.
            $filePath = $uploadedFile->getRealPath();
        } else {
            // Treat it as a path relative to the configured storage disk.
            $filePath = \Illuminate\Support\Facades\Storage::disk('local')->path($uploadedFile);
        }
        
        // Security validation
        if (!$this->validateBackupFile($filePath)) {
            // Clean up uploaded file
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            return;
        }
        
        $this->restoreFromFilePath($filePath);
        
        // Clean up uploaded file (only if we have a relative path, not a tmp file already managed by Livewire)
        if (isset($uploadedFile) && !($uploadedFile instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) && file_exists($filePath)) {
            unlink($filePath);
        }
    }

    protected function validateBackupFile(string $filePath): bool
    {
        // Check file exists
        if (!file_exists($filePath)) {
            Notification::make()
                ->danger()
                ->title('Validation Failed')
                ->body('Uploaded file not found.')
                ->send();
            return false;
        }

        // Check file size (prevent extremely large files)
        $maxSize = 100 * 1024 * 1024; // 100MB
        if (filesize($filePath) > $maxSize) {
            Notification::make()
                ->danger()
                ->title('File Too Large')
                ->body('Backup file exceeds 100MB limit.')
                ->send();
            return false;
        }

        // Check file extension
        if (!str_ends_with(strtolower($filePath), '.sql')) {
            Notification::make()
                ->danger()
                ->title('Invalid File Type')
                ->body('Only .sql files are allowed.')
                ->send();
            return false;
        }

        // Basic content validation
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            Notification::make()
                ->danger()
                ->title('File Read Error')
                ->body('Cannot read the uploaded file.')
                ->send();
            return false;
        }

        // Read first few lines to check if it looks like an SQL dump
        $isValidSql = false;
        $linesChecked = 0;
        $maxLinesToCheck = 50;

        while (($line = fgets($handle)) !== false && $linesChecked < $maxLinesToCheck) {
            $line = trim($line);
            
            // Skip empty lines and comments
            if (empty($line) || str_starts_with($line, '--') || str_starts_with($line, '/*')) {
                $linesChecked++;
                continue;
            }

            // Look for SQL keywords that indicate a database dump
            if (preg_match('/^(CREATE|DROP|INSERT|USE|SET|LOCK|UNLOCK)/i', $line)) {
                $isValidSql = true;
                break;
            }

            $linesChecked++;
        }

        fclose($handle);

        if (!$isValidSql) {
            Notification::make()
                ->danger()
                ->title('Invalid SQL File')
                ->body('The uploaded file does not appear to be a valid SQL database dump.')
                ->send();
            return false;
        }

        return true;
    }

    public function deleteBackup(string $filename): void
    {
        try {
            $backupService = new SimpleBackupService();
            $backupService->deleteBackup($filename);
            
            Notification::make()
                ->success()
                ->title('Backup Deleted')
                ->body('Backup file has been deleted successfully.')
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('Delete Failed')
                ->body('Could not delete the backup file.')
                ->send();
        }
    }
}