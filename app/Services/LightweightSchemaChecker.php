<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class LightweightSchemaChecker
{
    /**
     * Perform a quick schema compatibility check for a backup
     * This is much faster than full schema comparison
     */
    public function checkBackupCompatibility(string $backupPath): array
    {
        $startTime = microtime(true);
        $issues = [];
        $warnings = [];
        
        // Get current database tables and columns
        $currentSchema = $this->getCurrentSchemaQuick();
        
        // Parse backup file to extract expected schema
        $backupInfo = $this->extractSchemaFromBackup($backupPath);
        $backupSchema = $backupInfo['schema'];
        $isDataOnly = $backupInfo['is_data_only'];
        
        // For data-only backups where we couldn't extract schema, compare with static schema check
        if ($isDataOnly && empty($backupSchema)) {
            // Try to load the schema check file if it exists
            $schemaCheckPath = str_replace('.sql', '_schema_check.json', $backupPath);
            if (file_exists($schemaCheckPath)) {
                $schemaCheck = json_decode(file_get_contents($schemaCheckPath), true);
                if (isset($schemaCheck['has_issues']) && $schemaCheck['has_issues']) {
                    $issues[] = "Previous schema check found issues - restore may fail";
                    // Check for column differences in the details array
                    $columnDifferences = $schemaCheck['details']['column_differences'] ?? [];
                    if (!empty($columnDifferences)) {
                        foreach ($columnDifferences as $table => $diffs) {
                            if (!empty($diffs['missing_columns'])) {
                                $issues[] = "Table '{$table}' missing columns: " . implode(', ', $diffs['missing_columns']);
                            }
                        }
                    }
                }
                
                $executionTime = round((microtime(true) - $startTime) * 1000, 2);
                return [
                    'compatible' => empty($issues),
                    'issues' => $issues,
                    'warnings' => ['Using historical schema check data'],
                    'execution_time_ms' => $executionTime,
                    'checked_at' => now()->toIso8601String(),
                    'table_count' => 0,
                ];
            }
            
            // Fallback: warn that we can't verify data-only backup compatibility
            $warnings[] = "⚠️ CRITICAL: Cannot verify data-only backup compatibility - no column information in backup file";
            $warnings[] = "This backup uses INSERT VALUES format without column names";
            $warnings[] = "Schema mismatches (like missing columns) WILL cause restore failures but cannot be detected";
            $warnings[] = "Consider creating a FULL backup to enable proper schema verification";
            
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);
            return [
                'compatible' => null, // Unknown compatibility
                'issues' => ["Cannot determine compatibility - backup contains no schema information"],
                'warnings' => $warnings,
                'execution_time_ms' => $executionTime,
                'checked_at' => now()->toIso8601String(),
                'table_count' => count($currentSchema),
            ];
        }
        
        // Quick compatibility check
        foreach ($backupSchema as $table => $expectedColumns) {
            if (!isset($currentSchema[$table])) {
                $issues[] = "Table '{$table}' exists in backup but not in current database";
                continue;
            }
            
            $currentColumns = $currentSchema[$table];
            
            // Check for missing columns (in backup but not in current DB)
            $missingColumns = array_diff($expectedColumns, $currentColumns);
            if (!empty($missingColumns)) {
                $issues[] = "Table '{$table}' is missing columns that backup expects: " . implode(', ', $missingColumns);
            }
            
            // For data-only backups, extra columns in DB are OK
            // For full backups, warn about extra columns
            $extraColumns = array_diff($currentColumns, $expectedColumns);
            if (!empty($extraColumns) && !$isDataOnly) {
                $warnings[] = "Table '{$table}' has extra columns not in backup: " . implode(', ', $extraColumns);
            }
        }
        
        $executionTime = round((microtime(true) - $startTime) * 1000, 2); // milliseconds
        
        return [
            'compatible' => empty($issues),
            'issues' => $issues,
            'warnings' => $warnings,
            'execution_time_ms' => $executionTime,
            'checked_at' => now()->toIso8601String(),
            'table_count' => count($backupSchema),
        ];
    }
    
    /**
     * Get current database schema quickly using cached results
     */
    private function getCurrentSchemaQuick(): array
    {
        return Cache::remember('lightweight_current_schema', 300, function() {
            $schema = [];
            
            // Get all tables with columns in one query
            $tables = DB::select("
                SELECT TABLE_NAME, COLUMN_NAME
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = ?
                ORDER BY TABLE_NAME, ORDINAL_POSITION
            ", [DB::getDatabaseName()]);
            
            foreach ($tables as $row) {
                if (!isset($schema[$row->TABLE_NAME])) {
                    $schema[$row->TABLE_NAME] = [];
                }
                $schema[$row->TABLE_NAME][] = $row->COLUMN_NAME;
            }
            
            return $schema;
        });
    }
    
    /**
     * Extract schema information from backup file
     * This is fast - just parses CREATE TABLE and INSERT statements
     */
    private function extractSchemaFromBackup(string $backupPath): array
    {
        $schema = [];
        $content = File::get($backupPath);
        $hasCreateTable = false;
        $hasInsertWithColumns = false;
        
        // For full backups, extract from CREATE TABLE statements
        if (preg_match_all('/CREATE TABLE `([^`]+)`[^(]*\(([^;]+)\);/s', $content, $matches)) {
            $hasCreateTable = true;
            foreach ($matches[0] as $index => $createStatement) {
                $tableName = $matches[1][$index];
                $columns = $this->parseColumnsFromCreate($createStatement);
                $schema[$tableName] = $columns;
            }
        }
        
        // For data-only backups, extract from INSERT column lists
        if (preg_match_all('/INSERT INTO `([^`]+)` \(([^)]+)\)/', $content, $matches)) {
            $hasInsertWithColumns = true;
            foreach ($matches[0] as $index => $insertStatement) {
                $tableName = $matches[1][$index];
                if (!isset($schema[$tableName])) {
                    $columnList = $matches[2][$index];
                    $columns = array_map(function($col) {
                        return trim($col, '` ');
                    }, explode(',', $columnList));
                    $schema[$tableName] = $columns;
                }
            }
        }
        
        // Check if this is a data-only backup (no CREATE TABLE statements)
        $isDataOnly = !$hasCreateTable && str_contains($content, 'INSERT INTO');
        
        // For INSERT without column lists, we can't extract schema
        // This is common for mysqldump data-only backups
        
        return [
            'schema' => $schema,
            'is_data_only' => $isDataOnly,
            'has_column_info' => $hasCreateTable || $hasInsertWithColumns,
        ];
    }
    
    /**
     * Parse column names from CREATE TABLE statement
     */
    private function parseColumnsFromCreate(string $createStatement): array
    {
        $columns = [];
        
        // Extract the column definitions part
        if (preg_match('/CREATE TABLE `[^`]+`\s*\((.+)\)\s*ENGINE/s', $createStatement, $match)) {
            $definitions = $match[1];
            
            // Split by commas but not within parentheses
            $lines = preg_split('/,(?![^(]*\))/', $definitions);
            
            foreach ($lines as $line) {
                $line = trim($line);
                
                // Skip constraints, keys, indexes
                if (preg_match('/^(PRIMARY KEY|UNIQUE KEY|KEY|CONSTRAINT|INDEX)/i', $line)) {
                    continue;
                }
                
                // Extract column name
                if (preg_match('/^`([^`]+)`/', $line, $columnMatch)) {
                    $columns[] = $columnMatch[1];
                }
            }
        }
        
        return $columns;
    }
    
    
    /**
     * Get migration schema from cached results
     * This should be updated after each migration/deployment
     */
    public function getCachedMigrationSchema(): ?array
    {
        return Cache::get('migration_defined_schema');
    }
    
    /**
     * Update the cached migration schema
     * This should be run after deployments or migrations
     */
    public function updateMigrationSchemaCache(): void
    {
        $comparisonService = new SchemaComparisonService();
        $schema = $comparisonService->getMigrationDefinedSchema();
        
        Cache::put('migration_defined_schema', $schema, now()->addDays(7));
        Cache::put('migration_schema_updated_at', now()->toIso8601String(), now()->addDays(7));
    }
}