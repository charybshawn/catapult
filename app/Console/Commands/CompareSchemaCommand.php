<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CompareSchemaCommand extends Command
{
    protected $signature = 'db:compare-schema {backup_file?}';
    protected $description = 'Compare current database schema with backup file schema';

    public function handle()
    {
        $this->info('Analyzing database schema differences...');
        
        // Get all tables in current database
        $currentTables = DB::select('SHOW TABLES');
        $tableKey = 'Tables_in_' . DB::getDatabaseName();
        $tables = array_column($currentTables, $tableKey);
        
        $this->info("\nCurrent database tables: " . count($tables));
        
        // Check each table's structure
        $mismatches = [];
        
        foreach ($tables as $table) {
            $columns = Schema::getColumnListing($table);
            $columnDetails = [];
            
            foreach ($columns as $column) {
                $type = DB::getSchemaBuilder()->getColumnType($table, $column);
                $columnDetails[$column] = $type;
            }
            
            // Store for comparison
            $mismatches[$table] = $columnDetails;
        }
        
        // If backup file provided, analyze it
        $backupFile = $this->argument('backup_file');
        if ($backupFile) {
            $this->analyzeBackupFile($backupFile, $mismatches);
        } else {
            // Just show current schema
            foreach ($mismatches as $table => $columns) {
                $this->line("\nTable: $table");
                foreach ($columns as $column => $type) {
                    $this->line("  - $column: $type");
                }
            }
        }
        
        return 0;
    }
    
    private function analyzeBackupFile($backupFile, $currentSchema)
    {
        $fullPath = base_path('database/backups/' . $backupFile);
        if (!file_exists($fullPath)) {
            $this->error("Backup file not found: $backupFile");
            return;
        }
        
        $content = file_get_contents($fullPath);
        
        // Extract CREATE TABLE statements
        preg_match_all('/CREATE TABLE `([^`]+)`[^;]+;/s', $content, $matches);
        
        $this->info("\nBackup file contains " . count($matches[1]) . " tables");
        
        // Parse backup schema
        $backupSchema = [];
        foreach ($matches[0] as $index => $createStatement) {
            $tableName = $matches[1][$index];
            preg_match_all('/`([^`]+)`\s+([A-Z]+[^,\n)]+)/i', $createStatement, $columnMatches);
            
            $columns = [];
            for ($i = 0; $i < count($columnMatches[1]); $i++) {
                $columnName = $columnMatches[1][$i];
                if ($columnName != $tableName) { // Skip table name matches
                    $columns[$columnName] = trim($columnMatches[2][$i]);
                }
            }
            $backupSchema[$tableName] = $columns;
        }
        
        // Compare schemas
        $this->info("\n=== SCHEMA DIFFERENCES ===\n");
        
        // Tables in backup but not in current
        $backupOnly = array_diff(array_keys($backupSchema), array_keys($currentSchema));
        if (!empty($backupOnly)) {
            $this->warn("Tables only in backup:");
            foreach ($backupOnly as $table) {
                $this->line("  - $table");
            }
        }
        
        // Tables in current but not in backup
        $currentOnly = array_diff(array_keys($currentSchema), array_keys($backupSchema));
        if (!empty($currentOnly)) {
            $this->warn("\nTables only in current database:");
            foreach ($currentOnly as $table) {
                $this->line("  - $table");
            }
        }
        
        // Check column differences for common tables
        $commonTables = array_intersect(array_keys($currentSchema), array_keys($backupSchema));
        foreach ($commonTables as $table) {
            $currentCols = array_keys($currentSchema[$table]);
            $backupCols = array_keys($backupSchema[$table]);
            
            $colsOnlyInBackup = array_diff($backupCols, $currentCols);
            $colsOnlyInCurrent = array_diff($currentCols, $backupCols);
            
            if (!empty($colsOnlyInBackup) || !empty($colsOnlyInCurrent)) {
                $this->warn("\nTable '$table' has column differences:");
                
                if (!empty($colsOnlyInBackup)) {
                    $this->line("  Columns only in backup:");
                    foreach ($colsOnlyInBackup as $col) {
                        $this->line("    - $col");
                    }
                }
                
                if (!empty($colsOnlyInCurrent)) {
                    $this->line("  Columns only in current:");
                    foreach ($colsOnlyInCurrent as $col) {
                        $this->line("    - $col");
                    }
                }
            }
        }
        
        // Analyze INSERT statement column counts
        $this->info("\n=== INSERT STATEMENT ANALYSIS ===\n");
        
        foreach ($commonTables as $table) {
            if (preg_match("/INSERT INTO `$table` VALUES \(([^;]+)\);/s", $content, $insertMatch)) {
                // Count columns in first row
                $firstRow = explode('),', $insertMatch[1])[0];
                $valueCount = substr_count($firstRow, ',') + 1;
                $currentColCount = count($currentSchema[$table]);
                
                if ($valueCount != $currentColCount) {
                    $this->error("Table '$table': INSERT has $valueCount values but current schema has $currentColCount columns");
                }
            }
        }
    }
}