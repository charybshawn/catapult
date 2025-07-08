<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MigrationStatus extends Command
{
    protected $signature = 'migrations:status-extended';
    protected $description = 'Show extended migration status including archived and consolidated migrations';

    public function handle()
    {
        $this->info('📋 Extended Migration Status Report');
        $this->line('');
        
        // Get counts for each directory
        $activeMigrations = $this->getMigrationFiles('migrations');
        $archivedMigrations = $this->getMigrationFiles('migrations/archive');
        $consolidatedMigrations = $this->getMigrationFiles('migrations/consolidated');
        
        // Get migration status from database
        $ranMigrations = collect();
        if (Schema::hasTable('migrations')) {
            $ranMigrations = DB::table('migrations')->pluck('migration');
        }
        
        $this->displaySummary($activeMigrations, $archivedMigrations, $consolidatedMigrations, $ranMigrations);
        $this->line('');
        
        $this->displayActiveStatus($activeMigrations, $ranMigrations);
        $this->line('');
        
        $this->displayArchivedStatus($archivedMigrations);
        $this->line('');
        
        $this->displayConsolidatedStatus($consolidatedMigrations);
        
        return 0;
    }
    
    private function getMigrationFiles($directory)
    {
        $path = database_path($directory);
        
        if (!File::exists($path)) {
            return collect();
        }
        
        return collect(File::files($path))
            ->filter(function ($file) {
                return $file->getExtension() === 'php';
            })
            ->map(function ($file) {
                return [
                    'filename' => $file->getFilename(),
                    'name' => pathinfo($file->getFilename(), PATHINFO_FILENAME),
                    'path' => $file->getPathname(),
                    'size' => $file->getSize(),
                    'modified' => $file->getMTime(),
                ];
            });
    }
    
    private function displaySummary($active, $archived, $consolidated, $ran)
    {
        $this->info('📊 Migration Summary');
        $this->table(['Category', 'Count', 'Status'], [
            ['Active Migrations', $active->count(), '✅ Will run on migrate:fresh'],
            ['Archived Migrations', $archived->count(), '📁 Ignored by Laravel'],
            ['Consolidated Migrations', $consolidated->count(), '🔄 Ready for transition'],
            ['Database Migrations', $ran->count(), '💾 Currently in database'],
        ]);
    }
    
    private function displayActiveStatus($active, $ran)
    {
        $this->info('🚀 Active Migrations (will run on migrate:fresh)');
        
        if ($active->isEmpty()) {
            $this->warn('No active migrations found.');
            return;
        }
        
        $activeData = $active->map(function ($migration) use ($ran) {
            $migrationName = str_replace('.php', '', $migration['filename']);
            $status = $ran->contains($migrationName) ? '✅ Ran' : '⏳ Pending';
            
            return [
                'filename' => $migration['filename'],
                'status' => $status,
                'size' => $this->formatBytes($migration['size']),
            ];
        });
        
        $this->table(['Migration', 'Status', 'Size'], $activeData->toArray());
    }
    
    private function displayArchivedStatus($archived)
    {
        $this->info('📁 Archived Migrations (ignored by Laravel)');
        
        if ($archived->isEmpty()) {
            $this->line('No archived migrations found.');
            return;
        }
        
        $archivedData = $archived->map(function ($migration) {
            return [
                'filename' => $migration['filename'],
                'size' => $this->formatBytes($migration['size']),
                'archived' => date('Y-m-d H:i', $migration['modified']),
            ];
        });
        
        $this->table(['Migration', 'Size', 'Archived'], $archivedData->toArray());
    }
    
    private function displayConsolidatedStatus($consolidated)
    {
        $this->info('🔄 Consolidated Migrations (ready for transition)');
        
        if ($consolidated->isEmpty()) {
            $this->line('No consolidated migrations found.');
            return;
        }
        
        $consolidatedData = $consolidated->map(function ($migration) {
            return [
                'filename' => $migration['filename'],
                'size' => $this->formatBytes($migration['size']),
                'table' => $this->extractTableName($migration['filename']),
            ];
        });
        
        $this->table(['Migration', 'Size', 'Table'], $consolidatedData->toArray());
    }
    
    private function extractTableName($filename)
    {
        if (preg_match('/create_(.+)_table\.php$/', $filename, $matches)) {
            return $matches[1];
        }
        return 'Unknown';
    }
    
    private function formatBytes($size)
    {
        if ($size < 1024) {
            return $size . ' B';
        } elseif ($size < 1048576) {
            return round($size / 1024, 1) . ' KB';
        } else {
            return round($size / 1048576, 1) . ' MB';
        }
    }
}