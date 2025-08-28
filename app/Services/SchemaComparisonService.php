<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

/**
 * Agricultural database schema integrity and migration validation service.
 * 
 * Ensures database schema consistency for agricultural operations by comparing
 * actual database structure with migration-defined schema. Critical for maintaining
 * data integrity across development, staging, and production environments,
 * preventing data loss during backups and ensuring reliable agricultural system operation.
 *
 * @business_domain Agricultural database integrity and schema management
 * @related_services SimpleBackupService, migration system
 * @used_by Database console, backup validation, deployment verification
 * @agricultural_context Protects critical agricultural data during schema changes and deployments
 */
class SchemaComparisonService
{
    /**
     * Compare current agricultural database schema with migration-defined schema.
     * 
     * Performs comprehensive schema validation to ensure database integrity
     * for agricultural operations. Identifies drift between actual database
     * structure and migration definitions that could compromise data integrity
     * during critical agricultural processes.
     *
     * @return array Schema comparison results including:
     *   - extra_tables: Tables in database but not in migrations
     *   - missing_tables: Tables in migrations but not in database
     *   - column_differences: Column mismatches per table
     *   - summary: Human-readable summary of issues
     *   - has_issues: Boolean flag indicating schema problems
     * @agricultural_context Ensures reliable operation of crop, order, and inventory systems
     */
    public function compareSchemas(): array
    {
        $currentSchema = $this->getCurrentDatabaseSchema();
        $migrationSchema = $this->getMigrationDefinedSchema();
        
        return $this->findSchemaDifferences($currentSchema, $migrationSchema);
    }
    
    /**
     * Extract current agricultural database schema from live database.
     * 
     * Queries actual database structure to build complete schema map
     * including all tables, columns, and their properties. Essential
     * for validating that agricultural data structures are intact.
     *
     * @return array Current database schema structure
     * @agricultural_context Maps actual structure of crop, product, and order tables
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
     * Generate reference schema from Laravel migrations for agricultural database.
     * 
     * Creates temporary database and runs all migrations to determine what
     * the schema SHOULD look like according to migration definitions.
     * This provides the authoritative reference for agricultural database structure.
     *
     * @return array Migration-defined schema structure
     * @throws Exception If temporary database creation or migration execution fails
     * @agricultural_context Establishes correct structure for agricultural data tables
     * @safety Creates isolated temporary database to avoid affecting production
     */
    public function getMigrationDefinedSchema(): array
    {
        // Create a temporary database to run migrations
        $tempDb = 'catapult_schema_test_' . time();
        
        // Save current database config before try block
        $originalDb = config('database.connections.mysql.database');
        
        try {
            // Create temporary database
            DB::statement("CREATE DATABASE IF NOT EXISTS `{$tempDb}`");
            
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
            
        } catch (Exception $e) {
            // Ensure we switch back even if there's an error
            config(['database.connections.mysql.database' => $originalDb]);
            DB::purge('mysql');
            DB::reconnect('mysql');
            
            // Try to drop temp database
            try {
                DB::statement("DROP DATABASE IF EXISTS `{$tempDb}`");
            } catch (Exception $e) {
                // Ignore cleanup errors
            }
            
            throw new Exception("Failed to analyze migration schema: " . $e->getMessage());
        }
    }
    
    /**
     * Identify schema inconsistencies that could impact agricultural operations.
     * 
     * Compares actual database structure with migration-defined structure
     * to identify potential issues that could cause agricultural system
     * failures, backup problems, or data integrity issues.
     *
     * @param array $currentSchema Actual database schema structure
     * @param array $migrationSchema Migration-defined schema structure
     * @return array Detailed differences analysis with remediation guidance
     * @agricultural_context Identifies risks to crop, order, and inventory data integrity
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
     * Retrieve all agricultural business tables from database.
     * 
     * Filters out system tables and views to focus on agricultural
     * business tables like crops, products, orders, and related entities.
     * Excludes migration tracking and monitoring tables from analysis.
     *
     * @return array List of agricultural business table names
     * @agricultural_context Focuses analysis on tables containing crop, product, and order data
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
     * Format schema differences into actionable agricultural system report.
     * 
     * Converts schema analysis into human-readable report with specific
     * remediation steps for maintaining agricultural database integrity.
     * Includes impact analysis on backup strategies and system reliability.
     *
     * @param array $differences Schema comparison results
     * @return string Formatted report with remediation guidance
     * @agricultural_context Provides actionable insights for maintaining agricultural data integrity
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
        
        $columnDifferences = $differences['column_differences'] ?? [];
        foreach ($columnDifferences as $columnInfo) {
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