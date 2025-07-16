<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SimpleBackupService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateColumnMappedBackup extends Command
{
    protected $signature = 'db:backup-with-columns 
                            {--name= : Custom backup name}
                            {--data-only : Create data-only backup with column names}';
    
    protected $description = 'Create a backup with explicit column names for better compatibility';

    public function handle()
    {
        $this->info('Creating column-mapped backup...');
        
        $backupService = new SimpleBackupService();
        $isDataOnly = $this->option('data-only');
        
        if ($isDataOnly) {
            $this->info('Creating data-only backup with column mappings...');
            $filename = $this->createColumnMappedDataBackup();
        } else {
            // Regular backup
            $name = $this->option('name');
            $filename = $backupService->createBackup($name);
        }
        
        $this->info("Backup created: {$filename}");
        
        return 0;
    }
    
    private function createColumnMappedDataBackup(): string
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        $filename = "column_mapped_data_{$timestamp}.sql";
        $filepath = base_path('database/backups/' . $filename);
        
        // Ensure backup directory exists
        if (!is_dir(dirname($filepath))) {
            mkdir(dirname($filepath), 0755, true);
        }
        
        $content = "-- Column-mapped data-only backup\n";
        $content .= "-- Created: " . now()->format('Y-m-d H:i:s') . "\n";
        $content .= "-- This backup includes column names for better compatibility\n\n";
        
        // Get all tables
        $tables = DB::select('SHOW TABLES');
        $tableKey = 'Tables_in_' . DB::getDatabaseName();
        
        foreach ($tables as $table) {
            $tableName = $table->$tableKey;
            
            // Skip system tables
            if (in_array($tableName, ['migrations', 'failed_jobs', 'password_resets', 'personal_access_tokens'])) {
                continue;
            }
            
            // Get table data
            $data = DB::table($tableName)->get();
            
            if ($data->isEmpty()) {
                continue;
            }
            
            $this->info("Backing up {$tableName}... ({$data->count()} rows)");
            
            // Get column names
            $columns = Schema::getColumnListing($tableName);
            $columnNames = implode('`, `', $columns);
            
            $content .= "\n-- Table: {$tableName}\n";
            $content .= "DELETE FROM `{$tableName}`;\n";
            
            // Build INSERT statements with column names
            $values = [];
            foreach ($data as $row) {
                $rowValues = [];
                foreach ($columns as $column) {
                    $value = $row->$column;
                    
                    if (is_null($value)) {
                        $rowValues[] = 'NULL';
                    } elseif (is_numeric($value)) {
                        $rowValues[] = $value;
                    } elseif (is_bool($value)) {
                        $rowValues[] = $value ? '1' : '0';
                    } else {
                        // Escape string values
                        $escaped = str_replace(['\\', "'"], ['\\\\', "\\'"], $value);
                        $rowValues[] = "'{$escaped}'";
                    }
                }
                $values[] = '(' . implode(', ', $rowValues) . ')';
            }
            
            // Write INSERT statements in batches
            $chunks = array_chunk($values, 100);
            foreach ($chunks as $chunk) {
                $content .= "INSERT INTO `{$tableName}` (`{$columnNames}`) VALUES\n";
                $content .= implode(",\n", $chunk) . ";\n";
            }
        }
        
        // Save to file
        file_put_contents($filepath, $content);
        
        return $filename;
    }
}