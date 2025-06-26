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
                    TextInput::make('output_path')
                        ->label('Custom Output Path (Optional)')
                        ->placeholder('Leave empty to use default location')
                        ->helperText('Specify a custom path to save the backup file'),
                ])
                ->action(function (array $data) {
                    $this->createBackup($data['output_path'] ?? null);
                }),

            Action::make('safeBackup')
                ->label('Safe Backup')
                ->icon('heroicon-o-shield-check')
                ->color('warning')
                ->form([
                    TextInput::make('commit_message')
                        ->label('Git Commit Message (Optional)')
                        ->placeholder('Safe backup: ' . now()->format('Y-m-d H:i:s'))
                        ->helperText('Custom commit message for git'),
                    Toggle::make('no_push')
                        ->label('Skip Git Push')
                        ->helperText('Skip pushing changes to remote repository'),
                ])
                ->action(function (array $data) {
                    $this->safeBackup($data);
                })
                ->requiresConfirmation()
                ->modalHeading('Create Safe Backup')
                ->modalDescription('This will create a backup, commit changes, and push to git (unless disabled).'),

            Action::make('restoreBackup')
                ->label('Restore Backup')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('danger')
                ->form([
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
                        ->required()
                        ->searchable()
                        ->helperText('Select a backup file to restore from'),
                    Toggle::make('use_latest')
                        ->label('Use Latest Backup')
                        ->helperText('Ignore selection above and use the most recent backup')
                        ->reactive(),
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

    protected function createBackup(?string $outputPath = null): void
    {
        try {
            $command = 'db:backup';
            $parameters = [];
            
            if ($outputPath) {
                $parameters['--output'] = $outputPath;
            }

            $exitCode = Artisan::call($command, $parameters);
            $output = Artisan::output();

            if ($exitCode === 0) {
                Notification::make()
                    ->success()
                    ->title('✅ Backup Created Successfully')
                    ->body($this->formatCommandOutput($output))
                    ->duration(5000)
                    ->send();
                
                $this->dispatch('refresh-backups');
            } else {
                Notification::make()
                    ->danger()
                    ->title('❌ Backup Failed')
                    ->body($this->formatCommandOutput($output))
                    ->send();
            }
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('❌ Backup Command Failed')
                ->body($e->getMessage())
                ->send();
        }
    }

    protected function safeBackup(array $data): void
    {
        try {
            $parameters = [];
            
            if (!empty($data['commit_message'])) {
                $parameters['--commit-message'] = $data['commit_message'];
            }
            
            if ($data['no_push'] ?? false) {
                $parameters['--no-push'] = true;
            }

            $exitCode = Artisan::call('safe:backup', $parameters);
            $output = Artisan::output();

            if ($exitCode === 0) {
                Notification::make()
                    ->success()
                    ->title('🎉 Safe Backup Completed')
                    ->body($this->formatCommandOutput($output))
                    ->duration(8000)
                    ->send();
                
                $this->dispatch('refresh-backups');
            } else {
                Notification::make()
                    ->danger()
                    ->title('❌ Safe Backup Failed')
                    ->body($this->formatCommandOutput($output))
                    ->send();
            }
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('❌ Safe Backup Command Failed')
                ->body($e->getMessage())
                ->send();
        }
    }

    protected function restoreBackup(array $data): void
    {
        try {
            $parameters = ['--force' => true];
            
            if ($data['use_latest'] ?? false) {
                $parameters['--latest'] = true;
            } else {
                $parameters['file'] = $data['backup_file'];
            }

            $exitCode = Artisan::call('db:restore', $parameters);
            $output = Artisan::output();

            if ($exitCode === 0) {
                Notification::make()
                    ->success()
                    ->title('✅ Database Restored Successfully')
                    ->body($this->formatCommandOutput($output))
                    ->duration(5000)
                    ->send();
            } else {
                Notification::make()
                    ->danger()
                    ->title('❌ Database Restore Failed')
                    ->body($this->formatCommandOutput($output))
                    ->send();
            }
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('❌ Restore Command Failed')
                ->body($e->getMessage())
                ->send();
        }
    }

    public function deleteBackup(string $filename): void
    {
        try {
            $exitCode = Artisan::call('db:backup', ['--delete' => $filename]);
            $output = Artisan::output();

            if ($exitCode === 0) {
                Notification::make()
                    ->success()
                    ->title('🗑️ Backup Deleted')
                    ->body("Backup '{$filename}' has been deleted successfully.")
                    ->send();
                
                $this->dispatch('refresh-backups');
            } else {
                Notification::make()
                    ->danger()
                    ->title('❌ Delete Failed')
                    ->body($this->formatCommandOutput($output))
                    ->send();
            }
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('❌ Delete Command Failed')
                ->body($e->getMessage())
                ->send();
        }
    }

    public function downloadBackup(string $filename): mixed
    {
        try {
            $backupService = new SimpleBackupService();
            return $backupService->downloadBackup($filename);
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('❌ Download Failed')
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