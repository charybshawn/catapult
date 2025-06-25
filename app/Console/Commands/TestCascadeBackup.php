<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TestCascadeBackup extends Command
{
    protected $signature = 'test:cascade-backup';
    protected $description = 'Test the automatic backup system before cascading deletes';

    public function handle()
    {
        $this->info('Testing Automatic Backup System for Cascading Deletes');
        $this->newLine();

        // Check if setting exists and is enabled
        $isEnabled = Setting::getValue('auto_backup_before_cascade_delete', true);
        $this->info("Auto Backup Setting: " . ($isEnabled ? 'ENABLED' : 'DISABLED'));

        // Show current backup count
        $backupPath = storage_path('app/backups/database');
        $backupCount = 0;
        if (is_dir($backupPath)) {
            $backupCount = count(glob($backupPath . '/*.sql'));
        }
        $this->info("Current Backup Count: {$backupCount}");
        $this->newLine();

        // Create a test user to delete (if safe to do so)
        if ($this->confirm('Create and delete a test user to verify backup system?')) {
            $this->info('Creating test user...');
            
            $testUser = User::create([
                'name' => 'Test Backup User',
                'email' => 'test-backup-' . time() . '@example.com',
                'customer_type' => 'retail',
            ]);

            $this->info("Test user created with ID: {$testUser->id}");

            // Check backup count before deletion
            $backupCountBefore = count(glob($backupPath . '/*.sql'));
            $this->info("Backups before deletion: {$backupCountBefore}");

            // Delete the test user (this should trigger backup)
            $this->info('Deleting test user (this should trigger automatic backup)...');
            $testUser->delete();

            // Check backup count after deletion
            sleep(1); // Brief delay to ensure backup is created
            $backupCountAfter = count(glob($backupPath . '/*.sql'));
            $this->info("Backups after deletion: {$backupCountAfter}");

            if ($backupCountAfter > $backupCountBefore) {
                $this->info('✅ SUCCESS: Automatic backup was created!');
                
                // Show the latest backup file
                $backupFiles = glob($backupPath . '/*.sql');
                if (!empty($backupFiles)) {
                    $latestBackup = array_pop($backupFiles);
                    $backupName = basename($latestBackup);
                    $this->info("Latest backup: {$backupName}");
                }
            } else {
                $this->error('❌ FAILED: No backup was created automatically');
            }
        }

        $this->newLine();
        $this->info('Test completed. Check the logs for more details.');
        $this->info('Logs location: storage/logs/laravel.log');
    }
}