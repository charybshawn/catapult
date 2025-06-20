<?php

namespace App\Console\Commands;

use App\Services\DatabaseBackupService;
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
    protected $description = 'Create, list, or delete database backups';

    protected DatabaseBackupService $backupService;

    public function __construct(DatabaseBackupService $backupService)
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
        $this->info('Creating database backup...');
        
        $result = $this->backupService->createBackup();

        if ($result['success']) {
            $this->info("✅ Backup created successfully!");
            $this->line("📁 File: {$result['filename']}");
            $this->line("💾 Size: {$result['size']}");
            $this->line("📍 Path: {$result['path']}");
            
            // Handle custom output path
            if ($customPath = $this->option('output')) {
                $this->copyToCustomPath($result['path'], $customPath);
            }
        } else {
            $this->error("❌ Backup failed: {$result['error']}");
        }
    }

    protected function listBackups(): void
    {
        $backups = $this->backupService->listBackups();

        if (empty($backups)) {
            $this->info('No backups found.');
            return;
        }

        $this->info('📋 Available Database Backups:');
        $this->newLine();

        $headers = ['Filename', 'Size', 'Created At'];
        $rows = [];

        foreach ($backups as $backup) {
            $rows[] = [
                $backup['filename'],
                $backup['size'],
                $backup['created_at'],
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

        $deleted = $this->backupService->deleteBackup($filename);

        if ($deleted) {
            $this->info("✅ Backup '{$filename}' deleted successfully.");
        } else {
            $this->error("❌ Failed to delete backup '{$filename}'. File may not exist.");
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
