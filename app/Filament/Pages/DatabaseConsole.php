<?php

namespace App\Filament\Pages;

use App\Services\SimpleBackupService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Artisan;

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
                    Toggle::make('use_latest')
                        ->label('Use Latest Backup')
                        ->helperText('Use the most recent backup instead of selecting specific file')
                        ->reactive(),
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
                        ->required(fn ($get) => !$get('use_latest'))
                        ->searchable()
                        ->helperText('Select a backup file to restore from')
                        ->hidden(fn ($get) => $get('use_latest')),
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
            $parameters = [];
            
            // Always use force mode in web interface (no STDIN available)
            $parameters['--force'] = true;
            
            if ($data['use_latest'] ?? false) {
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
            $this->restoreRunning = false;
            $this->restoreSuccess = ($exitCode === 0);

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