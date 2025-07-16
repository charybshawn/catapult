<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class SchemaComparisonService
{
    /**
     * Compare current database schema with migration-defined schema
     */
    public function compareSchemas(): array
    {
        $currentSchema = $this->getCurrentDatabaseSchema();
        $migrationSchema = $this->getMigrationDefinedSchema();
        
        return $this->findSchemaDifferences($currentSchema, $migrationSchema);
    }
    
    /**
     * Get the current database schema
     */
    private function getCurrentDatabaseSchema(): array
    {
        $schema = [];
        $tables = $this->getDatabaseTables();
        
        foreach ($tables as $table) {
            $columns = Schema::getColumnListing($table);
            $columnDetails = [];
            
            foreach ($columns as $column) {
                // Get column information using raw SQL since Doctrine is no longer available in Laravel 12
                $columnInfo = DB::select("
                    SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_TYPE
                    FROM INFORMATION_SCHEMA.COLUMNS 
                    WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?
                ", [DB::getDatabaseName(), $table, $column]);
                
                if (!empty($columnInfo)) {
                    $info = $columnInfo[0];
                    $columnDetails[$column] = [
                        'type' => $info->DATA_TYPE,
                        'nullable' => $info->IS_NULLABLE === 'YES',
                    ];
                }
            }
            
            $schema[$table] = $columnDetails;
        }
        
        return $schema;
    }
    
    /**
     * Get schema as defined by migrations
     */
    public function getMigrationDefinedSchema(): array
    {
        // Create a temporary database to run migrations
        $tempDb = 'catapult_schema_test_' . time();
        
        try {
            // Create temporary database
            DB::statement("CREATE DATABASE IF NOT EXISTS `{$tempDb}`");
            
            // Save current database config
            $originalDb = config('database.connections.mysql.database');
            
            // Switch to temporary database
            config(['database.connections.mysql.database' => $tempDb]);
            DB::purge('mysql');
            DB::reconnect('mysql');
            
            // Run migrations on temporary database
            Artisan::call('migrate', ['--force' => true]);
            
            // Get schema from temporary database
            $schema = $this->getCurrentDatabaseSchema();
            
            // Switch back to original database
            config(['database.connections.mysql.database' => $originalDb]);
            DB::purge('mysql');
            DB::reconnect('mysql');
            
            // Drop temporary database
            DB::statement("DROP DATABASE IF EXISTS `{$tempDb}`");
            
            return $schema;
            
        } catch (\Exception $e) {
            // Ensure we switch back even if there's an error
            config(['database.connections.mysql.database' => $originalDb]);
            DB::purge('mysql');
            DB::reconnect('mysql');
            
            // Try to drop temp database
            try {
                DB::statement("DROP DATABASE IF EXISTS `{$tempDb}`");
            } catch (\Exception $e) {
                // Ignore cleanup errors
            }
            
            throw new \Exception("Failed to analyze migration schema: " . $e->getMessage());
        }
    }
    
    /**
     * Find differences between current and migration schemas
     */
    private function findSchemaDifferences(array $currentSchema, array $migrationSchema): array
    {
        $differences = [
            'extra_tables' => [],      // Tables in DB but not in migrations
            'missing_tables' => [],    // Tables in migrations but not in DB
            'column_differences' => [], // Column mismatches
            'summary' => '',
            'has_issues' => false,
        ];
        
        // Find extra tables (in current but not in migrations)
        $currentTables = array_keys($currentSchema);
        $migrationTables = array_keys($migrationSchema);
        
        $differences['extra_tables'] = array_diff($currentTables, $migrationTables);
        $differences['missing_tables'] = array_diff($migrationTables, $currentTables);
        
        // Check column differences for common tables
        $commonTables = array_intersect($currentTables, $migrationTables);
        
        foreach ($commonTables as $table) {
            $currentColumns = array_keys($currentSchema[$table]);
            $migrationColumns = array_keys($migrationSchema[$table]);
            
            $extraColumns = array_diff($currentColumns, $migrationColumns);
            $missingColumns = array_diff($migrationColumns, $currentColumns);
            
            if (!empty($extraColumns) || !empty($missingColumns)) {
                $differences['column_differences'][$table] = [
                    'extra_columns' => $extraColumns,  // In DB but not in migrations
                    'missing_columns' => $missingColumns, // In migrations but not in DB
                ];
            }
        }
        
        // Generate summary
        $issues = [];
        
        if (!empty($differences['extra_tables'])) {
            $issues[] = count($differences['extra_tables']) . " tables exist in database but not in migrations";
        }
        
        if (!empty($differences['missing_tables'])) {
            $issues[] = count($differences['missing_tables']) . " tables defined in migrations but missing from database";
        }
        
        if (!empty($differences['column_differences'])) {
            $tableCount = count($differences['column_differences']);
            $issues[] = "{$tableCount} tables have column mismatches";
        }
        
        if (!empty($issues)) {
            $differences['has_issues'] = true;
            $differences['summary'] = "Schema mismatches detected: " . implode(", ", $issues);
        } else {
            $differences['summary'] = "Database schema matches migrations perfectly";
        }
        
        return $differences;
    }
    
    /**
     * Get all database tables (excluding system tables)
     */
    private function getDatabaseTables(): array
    {
        $tables = [];
        $result = DB::select('SHOW TABLES');
        $key = 'Tables_in_' . DB::getDatabaseName();
        
        foreach ($result as $table) {
            $tableName = $table->$key;
            
            // Skip system tables and views
            if (!in_array($tableName, ['migrations', 'failed_jobs', 'telescope_entries', 'telescope_entries_tags', 'telescope_monitoring'])) {
                // Check if it's a table (not a view)
                $tableInfo = DB::select("SHOW CREATE TABLE `{$tableName}`");
                if (isset($tableInfo[0]->{'Create Table'})) {
                    $tables[] = $tableName;
                }
            }
        }
        
        return $tables;
    }
    
    /**
     * Format differences for display
     */
    public function formatDifferences(array $differences): string
    {
        if (!$differences['has_issues']) {
            return "✅ " . $differences['summary'];
        }
        
        $output = "⚠️ " . $differences['summary'] . "\n\n";
        
        if (!empty($differences['extra_tables'])) {
            $output .= "📋 Extra tables in database (not in migrations):\n";
            foreach ($differences['extra_tables'] as $table) {
                $output .= "   - {$table}\n";
            }
            $output .= "\n";
        }
        
        if (!empty($differences['missing_tables'])) {
            $output .= "❌ Missing tables (in migrations but not in database):\n";
            foreach ($differences['missing_tables'] as $table) {
                $output .= "   - {$table}\n";
            }
            $output .= "\n";
        }
        
        if (!empty($differences['column_differences'])) {
            $output .= "🔧 Tables with column mismatches:\n";
            foreach ($differences['column_differences'] as $table => $columnInfo) {
                $output .= "   📊 Table: {$table}\n";
                
                if (!empty($columnInfo['extra_columns'])) {
                    $output .= "      🔴 In DATABASE but NOT in migrations: " . implode(', ', $columnInfo['extra_columns']) . "\n";
                    $output .= "         → These columns exist in your database but are not defined in any migration\n";
                    $output .= "         → Action: Create a migration to add these columns OR manually drop them from database\n";
                }
                
                if (!empty($columnInfo['missing_columns'])) {
                    $output .= "      🟡 In MIGRATIONS but NOT in database: " . implode(', ', $columnInfo['missing_columns']) . "\n";
                    $output .= "         → These columns are defined in migrations but don't exist in your database\n";
                    $output .= "         → Action: Run 'php artisan migrate' OR the column was manually dropped\n";
                }
                
                $output .= "\n";
            }
        }
        
        $output .= "💡 Impact on Backups:\n";
        
        $hasExtraColumns = false;
        $hasMissingColumns = false;
        
        foreach ($differences['column_differences'] as $columnInfo) {
            if (!empty($columnInfo['extra_columns'])) $hasExtraColumns = true;
            if (!empty($columnInfo['missing_columns'])) $hasMissingColumns = true;
        }
        
        if ($hasExtraColumns) {
            $output .= "   • Full backups: Will include the extra columns (no issues)\n";
            $output .= "   • Data-only backups: Will fail to restore if schema changes\n";
        }
        
        if ($hasMissingColumns) {
            $output .= "   • Full backups: Will work fine (includes current schema)\n";
            $output .= "   • Data-only backups: May fail - backup expects columns that don't exist\n";
        }
        
        if (!$hasExtraColumns && !$hasMissingColumns && empty($differences['extra_tables']) && empty($differences['missing_tables'])) {
            $output .= "   ✅ All backup types will work correctly - schema is in sync\n";
        }
        
        return $output;
    }
}