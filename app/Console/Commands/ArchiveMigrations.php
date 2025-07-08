<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ArchiveMigrations extends Command
{
    protected $signature = 'migrations:archive 
                           {--before= : Archive migrations before this date (Y-m-d format)}
                           {--pattern= : Archive migrations matching this pattern}
                           {--dry-run : Show what would be archived without actually moving files}
                           {--restore : Restore archived migrations back to active directory}';
    
    protected $description = 'Archive old migrations to prevent them from running on migrate:fresh';

    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        $isRestore = $this->option('restore');
        
        if ($isRestore) {
            return $this->restoreArchived($isDryRun);
        }
        
        $beforeDate = $this->option('before');
        $pattern = $this->option('pattern');
        
        if (!$beforeDate && !$pattern) {
            $this->error('You must specify either --before or --pattern option');
            return 1;
        }
        
        return $this->archiveMigrations($beforeDate, $pattern, $isDryRun);
    }
    
    private function archiveMigrations($beforeDate, $pattern, $isDryRun)
    {
        $migrationPath = database_path('migrations');
        $archivePath = database_path('migrations/archive');
        
        // Ensure archive directory exists
        if (!File::exists($archivePath)) {
            File::makeDirectory($archivePath, 0755, true);
        }
        
        $migrations = File::files($migrationPath);
        $toArchive = [];
        
        foreach ($migrations as $migration) {
            $filename = $migration->getFilename();
            
            // Skip if it's already in archive or consolidated directory
            if (Str::contains($migration->getPath(), ['archive', 'consolidated'])) {
                continue;
            }
            
            $shouldArchive = false;
            
            // Check date-based archiving
            if ($beforeDate && $this->isBeforeDate($filename, $beforeDate)) {
                $shouldArchive = true;
            }
            
            // Check pattern-based archiving
            if ($pattern && Str::contains($filename, $pattern)) {
                $shouldArchive = true;
            }
            
            if ($shouldArchive) {
                $toArchive[] = $migration;
            }
        }
        
        if (empty($toArchive)) {
            $this->info('No migrations found matching the criteria.');
            return 0;
        }
        
        $this->info(count($toArchive) . ' migrations found to archive:');
        
        foreach ($toArchive as $migration) {
            $filename = $migration->getFilename();
            $this->line("  → {$filename}");
        }
        
        if ($isDryRun) {
            $this->warn('🔍 DRY RUN MODE - No files will be moved');
            return 0;
        }
        
        if (!$this->confirm('Do you want to proceed with archiving these migrations?')) {
            $this->info('Archiving cancelled.');
            return 0;
        }
        
        $archived = 0;
        foreach ($toArchive as $migration) {
            $filename = $migration->getFilename();
            $source = $migration->getPathname();
            $destination = $archivePath . '/' . $filename;
            
            if (File::move($source, $destination)) {
                $this->info("✅ Archived: {$filename}");
                $archived++;
            } else {
                $this->error("❌ Failed to archive: {$filename}");
            }
        }
        
        $this->info("Successfully archived {$archived} migrations.");
        $this->warn('⚠️  Remember to test migrate:fresh on a copy of your database first!');
        
        return 0;
    }
    
    private function restoreArchived($isDryRun)
    {
        $archivePath = database_path('migrations/archive');
        $migrationPath = database_path('migrations');
        
        if (!File::exists($archivePath)) {
            $this->error('Archive directory does not exist.');
            return 1;
        }
        
        $archivedMigrations = File::files($archivePath);
        
        if (empty($archivedMigrations)) {
            $this->info('No archived migrations found.');
            return 0;
        }
        
        $this->info(count($archivedMigrations) . ' archived migrations found:');
        
        foreach ($archivedMigrations as $migration) {
            $filename = $migration->getFilename();
            $this->line("  → {$filename}");
        }
        
        if ($isDryRun) {
            $this->warn('🔍 DRY RUN MODE - No files will be moved');
            return 0;
        }
        
        if (!$this->confirm('Do you want to restore these migrations?')) {
            $this->info('Restore cancelled.');
            return 0;
        }
        
        $restored = 0;
        foreach ($archivedMigrations as $migration) {
            $filename = $migration->getFilename();
            $source = $migration->getPathname();
            $destination = $migrationPath . '/' . $filename;
            
            if (File::move($source, $destination)) {
                $this->info("✅ Restored: {$filename}");
                $restored++;
            } else {
                $this->error("❌ Failed to restore: {$filename}");
            }
        }
        
        $this->info("Successfully restored {$restored} migrations.");
        
        return 0;
    }
    
    private function isBeforeDate($filename, $beforeDate)
    {
        // Extract date from migration filename (YYYY_MM_DD format)
        if (preg_match('/^(\d{4})_(\d{2})_(\d{2})_/', $filename, $matches)) {
            $migrationDate = $matches[1] . '-' . $matches[2] . '-' . $matches[3];
            return $migrationDate < $beforeDate;
        }
        
        return false;
    }
}