<?php

namespace App\Console\Commands;

use App\Services\DatabaseBackupService;
use App\Services\SimpleBackupService;
use Illuminate\Console\Command;

class DatabaseBackupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:backup 
                            {--output= : Custom output path for backup file}
                            {--list : List all existing backups}
                            {--delete= : Delete a specific backup file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create, list, or delete data-only database backups (schema restored via migrations)';

    protected SimpleBackupService $backupService;

    public function __construct(SimpleBackupService $backupService)
    {
        parent::__construct();
        $this->backupService = $backupService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->option('list')) {
            $this->listBackups();
            return;
        }

        if ($deleteFile = $this->option('delete')) {
            $this->deleteBackup($deleteFile);
            return;
        }

        $this->createBackup();
    }

    protected function createBackup(): void
    {
        $this->info('Creating data-only database backup...');
        
        try {
            $filename = $this->backupService->createBackup();
            
            $this->info("✅ Data-only backup created successfully!");
            $this->line("📁 File: {$filename}");
            $this->line("💡 Contains data only - schema will be created from migrations during restore");
            
            // Handle custom output path
            if ($customPath = $this->option('output')) {
                $backupPath = storage_path('app/backups/database/' . $filename);
                $this->copyToCustomPath($backupPath, $customPath);
            }
        } catch (\Exception $e) {
            $this->error("❌ Backup failed: {$e->getMessage()}");
        }
    }

    protected function listBackups(): void
    {
        $backups = $this->backupService->listBackups();

        if ($backups->isEmpty()) {
            $this->info('No backups found.');
            return;
        }

        $this->info('📋 Available Database Backups:');
        $this->newLine();

        $headers = ['Filename', 'Size', 'Created At'];
        $rows = [];

        foreach ($backups as $backup) {
            $rows[] = [
                $backup['name'],
                $backup['size'],
                $backup['created_at']->format('M j, Y g:i A'),
            ];
        }

        $this->table($headers, $rows);
    }

    protected function deleteBackup(string $filename): void
    {
        if (!$this->confirm("Are you sure you want to delete backup '{$filename}'?")) {
            $this->info('Backup deletion cancelled.');
            return;
        }

        try {
            $this->backupService->deleteBackup($filename);
            $this->info("✅ Backup '{$filename}' deleted successfully.");
        } catch (\Exception $e) {
            $this->error("❌ Failed to delete backup '{$filename}': {$e->getMessage()}");
        }
    }

    protected function copyToCustomPath(string $sourcePath, string $customPath): void
    {
        try {
            if (is_dir($customPath)) {
                $customPath = rtrim($customPath, '/') . '/' . basename($sourcePath);
            }

            if (copy($sourcePath, $customPath)) {
                $this->info("📤 Backup also saved to: {$customPath}");
            } else {
                $this->warn("⚠️  Could not copy backup to custom path: {$customPath}");
            }
        } catch (\Exception $e) {
            $this->warn("⚠️  Error copying to custom path: {$e->getMessage()}");
        }
    }
}
