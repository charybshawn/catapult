<?php

namespace App\Console\Commands;

use App\Services\DatabaseBackupService;
use App\Services\SimpleBackupService;
use Illuminate\Console\Command;

class DatabaseRestoreCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:restore 
                            {file? : Backup file path or filename (from backup directory)}
                            {--list : List available backups to restore from}
                            {--latest : Restore from the most recent backup}
                            {--force : Skip confirmation prompt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Restore database from backup file';

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

        if ($this->option('latest')) {
            $this->restoreLatestBackup();
            return;
        }

        $file = $this->argument('file');
        
        if (!$file) {
            $file = $this->selectBackupInteractively();
        }

        if (!$file) {
            $this->error('No backup file specified.');
            return;
        }

        $this->restoreBackup($file);
    }

    protected function restoreBackup(string $file): void
    {
        // Determine full path
        $fullPath = $this->resolveBackupPath($file);
        
        if (!$fullPath || !file_exists($fullPath)) {
            $this->error("❌ Backup file not found: {$file}");
            return;
        }

        // Safety confirmation
        if (!$this->option('force')) {
            $this->warn('⚠️  WARNING: This will completely replace your current database!');
            $this->line("📁 Backup file: " . basename($fullPath));
            $this->line("📍 Full path: {$fullPath}");
            $this->line("🔄 Process: Reset DB → Run Migrations → Import Data");
            $this->newLine();
            
            if (!$this->confirm('Are you sure you want to proceed?')) {
                $this->info('Restore operation cancelled.');
                return;
            }
        }

        $this->info('🔄 Starting seamless database restore...');
        $this->line('   Step 1: Resetting database and running fresh migrations');
        $this->line('   Step 2: Importing data from backup');
        $this->line('   Step 3: Clearing caches and optimizing');
        
        try {
            $filename = basename($fullPath);
            $this->backupService->restoreBackup($filename);
            
            $this->info("✅ Seamless database restore completed successfully!");
            $this->line("🗃️  Schema: Created from current migrations");
            $this->line("📊 Data: Imported from backup file");
            $this->line("🕒 Completed at: " . now()->format('M j, Y g:i A'));
        } catch (\Exception $e) {
            $this->error("❌ Restore failed: {$e->getMessage()}");
        }
    }

    protected function restoreLatestBackup(): void
    {
        $backups = $this->backupService->listBackups();

        if ($backups->isEmpty()) {
            $this->error('❌ No backups found.');
            return;
        }

        $latestBackup = $backups->first(); // Already sorted by creation time, newest first
        $this->info("📋 Found latest backup: {$latestBackup['name']} ({$latestBackup['size']})");
        
        $this->restoreBackup($latestBackup['name']);
    }

    protected function selectBackupInteractively(): ?string
    {
        $backups = $this->backupService->listBackups();

        if ($backups->isEmpty()) {
            $this->error('❌ No backups found.');
            return null;
        }

        $this->info('📋 Available backups:');
        
        $choices = [];
        foreach ($backups as $index => $backup) {
            $choice = "{$backup['name']} ({$backup['size']}) - {$backup['created_at']->format('M j, Y g:i A')}";
            $choices[$index] = $choice;
            $this->line(($index + 1) . ". {$choice}");
        }

        $selection = $this->ask('Enter the number of the backup to restore');
        
        if (!is_numeric($selection) || $selection < 1 || $selection > $backups->count()) {
            $this->error('Invalid selection.');
            return null;
        }

        return $backups[$selection - 1]['name'];
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

        $headers = ['#', 'Filename', 'Size', 'Created At'];
        $rows = [];

        foreach ($backups as $index => $backup) {
            $rows[] = [
                $index + 1,
                $backup['name'],
                $backup['size'],
                $backup['created_at']->format('M j, Y g:i A'),
            ];
        }

        $this->table($headers, $rows);
        $this->newLine();
        $this->line('💡 Use: php artisan db:restore [filename] to restore a specific backup');
        $this->line('💡 Use: php artisan db:restore --latest to restore the most recent backup');
    }

    protected function resolveBackupPath(string $file): ?string
    {
        // If it's already a full path, use it
        if (str_starts_with($file, '/') && file_exists($file)) {
            return $file;
        }

        // Check if it's in the backup directory (use Laravel storage path)
        $backupPath = \Illuminate\Support\Facades\Storage::disk('local')->path("backups/database/{$file}");
        if (file_exists($backupPath)) {
            return $backupPath;
        }

        // Check if user provided just the filename without extension
        if (!str_ends_with($file, '.sql')) {
            $backupPath = \Illuminate\Support\Facades\Storage::disk('local')->path("backups/database/{$file}.sql");
            if (file_exists($backupPath)) {
                return $backupPath;
            }
        }

        return null;
    }
}
