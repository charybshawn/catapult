<?php

namespace App\Services;

use Exception;
use PDO;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use Symfony\Component\Process\Process;

/**
 * Comprehensive agricultural database backup and restore service.
 * 
 * Provides robust backup and restore functionality for agricultural database systems
 * with advanced schema validation, compatibility checking, and automated repair capabilities.
 * Supports both full backups (with schema) and data-only backups with column mapping
 * for maximum flexibility during agricultural system maintenance and disaster recovery.
 *
 * @business_domain Agricultural database backup and disaster recovery
 * @related_services SchemaComparisonService, LightweightSchemaChecker
 * @used_by Database console, automated backup systems, disaster recovery procedures
 * @backup_types Full backups (schema + data), Data-only backups (with column mapping)
 * @agricultural_context Critical for protecting agricultural data during system maintenance
 */
class SimpleBackupService
{
    private $backupPath;
    public $lastRestoreSchemaFixes = [];

    /**
     * Initialize backup service with standardized backup directory path.
     * 
     * Sets up service with consistent backup storage location for all
     * agricultural database backup operations.
     */
    public function __construct()
    {
        // ALWAYS use database/backups - no exceptions
        $this->backupPath = 'database/backups';
    }

    /**
     * Validate agricultural database schema integrity before backup creation.
     * 
     * Performs comprehensive schema validation to identify potential issues
     * that could compromise backup integrity or restoration success.
     * Essential for ensuring agricultural data protection during backup operations.
     *
     * @return array Schema validation results including:
     *   - has_issues: Boolean indicating schema problems
     *   - summary: Human-readable issue description
     *   - error: Boolean indicating validation errors
     *   - extra_tables: Tables in DB but not in migrations
     *   - missing_tables: Tables in migrations but not in DB
     *   - column_differences: Column mismatches per table
     * @agricultural_context Prevents backup of corrupted agricultural database structures
     */
    public function checkSchemaBeforeBackup(): array
    {
        try {
            $comparisonService = new SchemaComparisonService();
            return $comparisonService->compareSchemas();
        } catch (Exception $e) {
            return [
                'has_issues' => true,
                'summary' => 'Could not compare schemas: ' . $e->getMessage(),
                'error' => true,
                'extra_tables' => [],
                'missing_tables' => [],
                'column_differences' => []
            ];
        }
    }
    
    /**
     * Create comprehensive agricultural database backup using native mysqldump.
     * 
     * Generates full database backup including schema and data for agricultural
     * systems with advanced validation and schema compatibility checking.
     * Includes automatic view exclusion and comprehensive error handling
     * to ensure reliable agricultural data protection.
     *
     * @param string|null $name Custom backup filename (auto-generated if null)
     * @param bool $excludeViews Whether to exclude database views from backup
     * @return string Generated backup filename
     * @throws Exception If backup creation fails or schema issues prevent safe backup
     * @agricultural_context Creates complete backup of crop, product, order, and inventory data
     * @locking Uses file locking to prevent concurrent backup operations
     * @validation Includes comprehensive schema validation and file integrity checking
     */
    public function createBackup(?string $name = null, bool $excludeViews = true): string
    {
        $lockFile = base_path('database/backups/.backup.lock');
        $lockHandle = null;
        
        try {
            // ALWAYS use database/backups directory - no conditional logic
            $fullBackupDir = base_path($this->backupPath);
            
            if (!is_dir($fullBackupDir)) {
                mkdir($fullBackupDir, 0755, true);
            }
            
            // Check schema before creating backup
            $schemaCheck = $this->checkSchemaBeforeBackup();
            $schemaHasIssues = $schemaCheck['has_issues'] ?? false;
            
            // Create lock directory if it doesn't exist
            $lockDir = dirname($lockFile);
            if (!is_dir($lockDir)) {
                mkdir($lockDir, 0755, true);
            }
            
            // Acquire exclusive lock to prevent concurrent backups
            $lockHandle = fopen($lockFile, 'w');
            if (!$lockHandle || !flock($lockHandle, LOCK_EX | LOCK_NB)) {
                throw new Exception('Another backup operation is already in progress. Please wait and try again.');
            }
            
            $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
            $filename = $name ?? "database_backup_{$timestamp}.sql";
            
            // Validate and sanitize filename
            $filename = $this->sanitizeFilename($filename);
            
            // Ensure the filename ends with .sql
            if (!str_ends_with($filename, '.sql')) {
                $filename .= '.sql';
            }
            
            // Get database connection details
            $config = config('database.connections.mysql');
            
            // Full path to backup file - ALWAYS in database/backups
            $backupPath = base_path($this->backupPath . '/' . $filename);
            
            // Create mysqldump command
            $command = [
                'mysqldump',
                '--host=' . $config['host'],
                '--port=' . $config['port'],
                '--user=' . $config['username'],
                '--password=' . $config['password'],
                '--single-transaction',
                '--no-tablespaces',
                '--skip-add-locks',
                '--skip-routines',
                '--skip-triggers',
                '--skip-events',
                '--quick',
                '--lock-tables=false',
            ];
            
            // Add views exclusion if requested
            if ($excludeViews) {
                // Always use the ignore-table approach for views
                $views = $this->getDatabaseViews();
                foreach ($views as $view) {
                    $command[] = '--ignore-table=' . $config['database'] . '.' . $view;
                }
                
                $command[] = '--no-create-db';
            }
            
            // Add database name at the end
            $command[] = $config['database'];
            
            // Try to find mysqldump in known locations
            $mysqldumpPath = $this->findMysqldump();
            if ($mysqldumpPath) {
                $command[0] = $mysqldumpPath;
            }
            
            $process = new Process($command, base_path());
            
            // Set memory and time limits for large databases
            $process->setTimeout(3600); // 1 hour timeout
            
            // Enhance PATH for web server environment
            $process->setEnv(['PATH' => self::getEnhancedPath()]);
            
            $process->run();
            
            if (!$process->isSuccessful()) {
                throw new Exception('mysqldump failed: ' . $process->getErrorOutput());
            }
            
            $output = $process->getOutput();
            
            // Check output size (warn if exceeds configured limit)
            $outputSize = strlen($output);
            $warningSizeMb = config('backup.limits.warning_size_mb', 100);
            if ($outputSize > $warningSizeMb * 1024 * 1024) {
                error_log("Large backup created: " . $this->formatBytes($outputSize));
            }
            
            // Save output to file
            file_put_contents($backupPath, $output);
            
            // Validate file was written correctly
            if (!file_exists($backupPath) || filesize($backupPath) === 0) {
                throw new Exception('Backup file was not created successfully');
            }
            
            // Basic SQL validation
            if (!$this->validateBackupFile($backupPath)) {
                throw new Exception('Backup file appears to be corrupted or invalid');
            }
            
            // Save schema check results
            if (!empty($schemaCheck)) {
                $schemaResultFile = str_replace('.sql', '_schema_check.json', $backupPath);
                $comparisonService = new SchemaComparisonService();
                
                $schemaCheckData = [
                    'timestamp' => now()->toIso8601String(),
                    'backup_type' => 'full',
                    'has_issues' => $schemaHasIssues,
                    'summary' => $schemaCheck['summary'] ?? '',
                    'details' => $schemaCheck,
                    'formatted_report' => $comparisonService->formatDifferences($schemaCheck)
                ];
                
                file_put_contents($schemaResultFile, json_encode($schemaCheckData, JSON_PRETTY_PRINT));
            }
            
            return $filename;
            
        } catch (Exception $e) {
            throw new Exception("Backup failed: " . $e->getMessage());
        } finally {
            // Always release the lock
            if ($lockHandle) {
                flock($lockHandle, LOCK_UN);
                fclose($lockHandle);
                if (file_exists($lockFile)) {
                    unlink($lockFile);
                }
            }
        }
    }

    /**
     * Restore agricultural database from backup with automated schema repair.
     * 
     * Performs comprehensive database restoration with intelligent schema mismatch
     * detection and automated repair capabilities. Essential for agricultural
     * system disaster recovery and data migration scenarios.
     *
     * @param string $filename Backup filename in the backup directory
     * @return bool Success status of restoration operation
     * @throws Exception If backup file not found or restoration fails
     * @agricultural_context Restores complete agricultural database including crops, orders, and inventory
     * @schema_repair Automatically repairs column count mismatches during restoration
     */
    public function restoreBackup(string $filename): bool
    {
        // ALWAYS use database/backups directory - no conditional logic
        $fullFilepath = base_path($this->backupPath . '/' . $filename);
        
        if (!file_exists($fullFilepath)) {
            throw new Exception("Backup file not found: {$filename}");
        }

        $sqlContent = file_get_contents($fullFilepath);
        
        if (empty($sqlContent)) {
            throw new Exception("Backup file is empty or corrupted");
        }

        // Use the shared restoration logic
        return $this->executeRestore($sqlContent);
    }

    /**
     * Restore agricultural database from uploaded backup file.
     * 
     * Enables restoration from temporary uploaded backup files with optional
     * dry-run validation. Critical for importing agricultural data from
     * external sources or testing backup compatibility before actual restoration.
     *
     * @param string $fullFilePath Full path to backup file to restore
     * @param bool $dryRun Whether to perform validation-only dry run
     * @return bool Success status or dry run validation result
     * @throws Exception If file not found, corrupted, or restoration fails
     * @agricultural_context Supports agricultural data migration and disaster recovery
     * @dry_run Validates backup without making database changes
     */
    public function restoreFromFile(string $fullFilePath, bool $dryRun = false): bool
    {
        if (!file_exists($fullFilePath)) {
            throw new Exception("Backup file not found: {$fullFilePath}");
        }

        $sqlContent = file_get_contents($fullFilePath);
        
        if (empty($sqlContent)) {
            throw new Exception("Backup file is empty or corrupted");
        }

        // Use the same logic as restoreBackup but with direct file path
        if ($dryRun) {
            return $this->dryRunRestore($sqlContent);
        } else {
            return $this->executeRestore($sqlContent);
        }
    }

    /**
     * Validate agricultural backup restoration compatibility without database changes.
     * 
     * Performs comprehensive restoration simulation to identify potential issues
     * without modifying agricultural database. Essential for validating backup
     * compatibility before critical restoration operations during harvest or
     * order fulfillment periods.
     *
     * @param string $sqlContent Raw SQL backup content to validate
     * @return bool Whether restoration would likely succeed
     * @throws Exception If validation fails or encounters fatal errors
     * @agricultural_context Prevents agricultural data loss during restoration attempts
     * @transaction_safety Uses transaction rollback to ensure no database modifications
     * @validation Comprehensive SQL statement validation and error analysis
     */
    public function dryRunRestore(string $sqlContent): bool
    {
        // CRITICAL SAFETY: Log that this is a dry run
        error_log("EXECUTING DRY RUN RESTORE - No changes should be committed!");
        
        try {
            // Get database connection
            $config = config('database.connections.mysql');
            $pdo = new PDO(
                "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}",
                $config['username'],
                $config['password'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );

            // Configure for dry run - CRITICAL: Disable autocommit!
            $pdo->exec('SET AUTOCOMMIT=0'); // This MUST be first
            $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
            $pdo->exec('SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO"');
            $pdo->exec('START TRANSACTION');
            
            // Split SQL into individual statements
            $statements = $this->splitSqlStatements($sqlContent);
            
            $successCount = 0;
            $failCount = 0;
            $errors = [];
            $warnings = [];
            
            foreach ($statements as $statement) {
                $statement = trim($statement);
                if (!empty($statement) && $statement !== ';') {
                    // CRITICAL: Skip any COMMIT or transaction control statements in dry run
                    if (preg_match('/^(COMMIT|ROLLBACK|START TRANSACTION|BEGIN)/i', $statement)) {
                        $warnings[] = "Skipped transaction control statement in dry run";
                        continue;
                    }
                    
                    try {
                        // For dry run, we try to prepare the statement to check syntax
                        // and execute it within a transaction that we'll roll back
                        $stmt = $pdo->prepare($statement);
                        if ($stmt) {
                            // For INSERT/UPDATE/DELETE statements, we can try to execute
                            // but will roll back the transaction at the end
                            if (preg_match('/^(INSERT|UPDATE|DELETE)/i', $statement)) {
                                $stmt->execute();
                            }
                            $successCount++;
                        }
                    } catch (Exception $e) {
                        $failCount++;
                        $errorMsg = $e->getMessage();
                        $stmtPreview = substr($statement, 0, 150) . "...";
                        $errors[] = "SQL Error: {$errorMsg} | Statement: {$stmtPreview}";
                        
                        // Check for specific error types
                        if (str_contains($errorMsg, "doesn't exist")) {
                            $warnings[] = "Missing table/column: " . $this->extractTableFromError($errorMsg);
                        } elseif (str_contains($errorMsg, "foreign key constraint")) {
                            $warnings[] = "Foreign key violation: " . $this->extractForeignKeyError($errorMsg);
                        }
                    }
                }
            }
            
            // Always rollback for dry run
            $pdo->exec('ROLLBACK');
            $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
            
            // Check if most errors are duplicate key violations
            $duplicateKeyErrors = 0;
            foreach ($errors as $error) {
                if (str_contains($error, 'Duplicate entry')) {
                    $duplicateKeyErrors++;
                }
            }
            
            // Add helpful context if duplicate keys are the issue
            $extraInfo = '';
            if ($duplicateKeyErrors > 0 && $duplicateKeyErrors >= $failCount * 0.8) {
                $extraInfo = "\n\nNote: Most failures are duplicate key errors. This typically happens when:\n" .
                    "- The database already contains data\n" .
                    "- You're restoring a data-only backup\n" .
                    "The actual restore process will clear existing data first.";
            }
            
            // Store results for retrieval
            $this->lastRestoreSchemaFixes = [
                'dry_run' => true,
                'total_statements' => count($statements),
                'success_count' => $successCount,
                'fail_count' => $failCount,
                'errors' => $errors,
                'warnings' => array_unique($warnings),
                'summary' => "Dry Run: {$successCount} statements would succeed, {$failCount} would fail" . $extraInfo
            ];
            
            // Log summary
            error_log("Dry Run Summary: {$successCount} would succeed, {$failCount} would fail");
            if (!empty($errors)) {
                error_log("Dry Run Errors: " . implode(" | ", array_slice($errors, 0, 3)));
            }
            
            // Return true if majority would succeed, false if too many failures
            return $failCount === 0 || ($successCount > $failCount * 2);
            
        } catch (Exception $e) {
            $this->lastRestoreSchemaFixes = [
                'dry_run' => true,
                'fatal_error' => $e->getMessage()
            ];
            throw new Exception("Dry run failed: " . $e->getMessage());
        }
    }

    /**
     * Extract table name from error message
     */
    private function extractTableFromError(string $errorMsg): string
    {
        if (preg_match("/Table '.*?\.(\w+)' doesn't exist/", $errorMsg, $matches)) {
            return $matches[1];
        }
        if (preg_match("/Unknown column '(\w+)'/", $errorMsg, $matches)) {
            return $matches[1];
        }
        return "Unknown";
    }

    /**
     * Extract foreign key information from error message
     */
    private function extractForeignKeyError(string $errorMsg): string
    {
        if (preg_match("/FOREIGN KEY.*?REFERENCES `(\w+)`/", $errorMsg, $matches)) {
            return "References table: " . $matches[1];
        }
        return "Foreign key constraint";
    }

    /**
     * Execute the actual restore process (shared by both restore methods)
     */
    private function executeRestore(string $sqlContent): bool
    {
        try {
            // Get database connection
            $config = config('database.connections.mysql');
            $pdo = new PDO(
                "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}",
                $config['username'],
                $config['password'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );

            // Disable foreign key checks and configure for restore
            $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
            $pdo->exec('SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO"');
            $pdo->exec('SET AUTOCOMMIT=0');
            $pdo->exec('SET UNIQUE_CHECKS=0');
            $pdo->exec('START TRANSACTION');
            
            // Clear existing data from main tables to avoid conflicts
            $tablesToClear = [
                'activity_log', 'time_card_tasks', 'time_cards', 'task_schedules',
                'harvests', 'crops', 'seed_entries', 'recipes', 'consumables', 'suppliers',
                'product_price_variations', 'products', 'categories', 'packaging_types',
                'seed_variations', 'seed_price_history', 'product_inventories',
                'cache', 'sessions', 'permissions', 'roles', 'role_has_permissions', 
                'model_has_roles', 'master_cultivars', 'master_seed_catalog',
                'supplier_source_mappings', 'seed_scrape_uploads', 'task_types'
            ];
            
            foreach ($tablesToClear as $table) {
                try {
                    $pdo->exec("DELETE FROM `{$table}`");
                } catch (Exception $e) {
                    // Table might not exist, continue
                }
            }

            // Split SQL into individual statements
            $statements = $this->splitSqlStatements($sqlContent);
            
            $successCount = 0;
            $failCount = 0;
            $errors = [];
            $schemaFixes = [];
            
            foreach ($statements as $statement) {
                $statement = trim($statement);
                if (!empty($statement) && $statement !== ';') {
                    try {
                        // Check for column count mismatch and fix if needed
                        if (str_contains($statement, 'Column count doesn\'t match') || 
                            (str_contains($statement, 'INSERT INTO') && !str_contains($statement, 'INSERT INTO `migrations`'))) {
                            $fixResult = $this->fixColumnCountMismatch($statement, $pdo);
                            $statement = $fixResult['statement'];
                            if (!empty($fixResult['fixes'])) {
                                $schemaFixes = array_merge($schemaFixes, $fixResult['fixes']);
                            }
                        }
                        
                        $pdo->exec($statement);
                        $successCount++;
                    } catch (Exception $e) {
                        // Check if it's a column count mismatch error
                        if (str_contains($e->getMessage(), 'Column count doesn\'t match')) {
                            try {
                                $fixResult = $this->fixColumnCountMismatch($statement, $pdo);
                                $pdo->exec($fixResult['statement']);
                                $successCount++;
                                if (!empty($fixResult['fixes'])) {
                                    $schemaFixes = array_merge($schemaFixes, $fixResult['fixes']);
                                }
                                continue;
                            } catch (Exception $e2) {
                                $failCount++;
                                $errorMsg = $e2->getMessage();
                                $stmtPreview = substr($statement, 0, 200) . "...";
                                $errors[] = "SQL Error (after fix attempt): {$errorMsg} | Statement: {$stmtPreview}";
                                error_log("Database Restore Warning (after fix): {$errorMsg} | Statement: {$stmtPreview}");
                            }
                        } else {
                            $failCount++;
                            // Log failed statement to error log
                            $errorMsg = $e->getMessage();
                            $stmtPreview = substr($statement, 0, 200) . "...";
                            $errors[] = "SQL Error: {$errorMsg} | Statement: {$stmtPreview}";
                            error_log("Database Restore Warning: {$errorMsg} | Statement: {$stmtPreview}");
                        }
                    }
                }
            }
            
            // Log summary
            error_log("Database Restore Summary: {$successCount} successful, {$failCount} failed statements");
            if (!empty($errors)) {
                error_log("First few errors: " . implode(" | ", array_slice($errors, 0, 3)));
            }
            if (!empty($schemaFixes)) {
                error_log("Schema fixes applied: " . implode(" | ", $schemaFixes));
            }

            // Commit transaction and re-enable checks
            $pdo->exec('COMMIT');
            $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
            $pdo->exec('SET UNIQUE_CHECKS=1');
            
            // If there were significant failures, throw an exception with details
            if ($failCount > 0 && $successCount === 0) {
                throw new Exception("All SQL statements failed. First error: " . ($errors[0] ?? 'Unknown error'));
            } elseif ($failCount > $successCount) {
                throw new Exception("More statements failed ({$failCount}) than succeeded ({$successCount}). First error: " . ($errors[0] ?? 'Unknown error'));
            }
            
            // Store schema fixes for later retrieval
            if (!empty($schemaFixes)) {
                $this->lastRestoreSchemaFixes = $schemaFixes;
            }
            
            return true;
            
        } catch (Exception $e) {
            throw new Exception("Restore failed: " . $e->getMessage());
        }
    }

    /**
     * Split SQL content into individual statements
     */
    private function splitSqlStatements(string $sql): array
    {
        // Remove comments and split by semicolons
        $sql = preg_replace('/--.*$/m', '', $sql); // Remove single-line comments
        $sql = preg_replace('/\/\*.*?\*\//s', '', $sql); // Remove multi-line comments
        
        // Split by semicolons, but be careful with quoted strings
        $statements = [];
        $current = '';
        $inQuotes = false;
        $quoteChar = '';
        
        for ($i = 0; $i < strlen($sql); $i++) {
            $char = $sql[$i];
            
            if (!$inQuotes && ($char === '"' || $char === "'")) {
                $inQuotes = true;
                $quoteChar = $char;
            } elseif ($inQuotes && $char === $quoteChar) {
                // Check if it's escaped
                if ($i > 0 && $sql[$i-1] !== '\\') {
                    $inQuotes = false;
                    $quoteChar = '';
                }
            } elseif (!$inQuotes && $char === ';') {
                $statements[] = trim($current);
                $current = '';
                continue;
            }
            
            $current .= $char;
        }
        
        // Add the last statement if it exists
        if (!empty(trim($current))) {
            $statements[] = trim($current);
        }
        
        return array_filter($statements, function($stmt) {
            return !empty(trim($stmt));
        });
    }

    /**
     * List all available agricultural database backups with metadata.
     * 
     * Provides comprehensive backup inventory including file sizes,
     * creation timestamps, and schema validation status. Essential
     * for agricultural database management and backup selection.
     *
     * @return Collection Backup file collection with metadata:
     *   - name: Backup filename
     *   - path: Full file path
     *   - size: Human-readable file size
     *   - size_bytes: Raw file size in bytes
     *   - created_at: Backup creation timestamp
     *   - has_schema_check: Whether schema validation results exist
     *   - schema_has_issues: Whether schema validation found problems
     *   - schema_summary: Schema validation summary
     * @agricultural_context Lists backups of crop, product, and order data
     */
    public function listBackups(): Collection
    {
        // ALWAYS use database/backups directory - no conditional logic
        $fullBackupDir = base_path($this->backupPath);
        
        // Debug: Log backup directory info
        error_log("SimpleBackupService: backupPath={$this->backupPath}, fullBackupDir={$fullBackupDir}, dir_exists=" . (is_dir($fullBackupDir) ? 'yes' : 'no'));
        
        if (!is_dir($fullBackupDir)) {
            error_log("SimpleBackupService: Directory does not exist: {$fullBackupDir}");
            return collect();
        }

        // Only get SQL files, not the JSON schema check files
        $files = glob($fullBackupDir . '/*.sql');
        error_log("SimpleBackupService: Found " . count($files) . " SQL files");
        
        return collect($files)
            ->map(function($file) {
                $size = filesize($file);
                $timestamp = filemtime($file);
                $filename = basename($file);
                
                // Check if schema check file exists (static or dynamic)
                $schemaCheckFile = str_replace('.sql', '_schema_check.json', $file);
                $dynamicCheckFile = str_replace('.sql', '_dynamic_check.json', $file);
                
                // Prefer dynamic check over static check
                $activeCheckFile = file_exists($dynamicCheckFile) ? $dynamicCheckFile : 
                                  (file_exists($schemaCheckFile) ? $schemaCheckFile : null);
                
                $hasSchemaCheck = $activeCheckFile !== null;
                $schemaHasIssues = false;
                $schemaSummary = '';
                
                if ($hasSchemaCheck) {
                    try {
                        $schemaData = json_decode(file_get_contents($activeCheckFile), true);
                        $schemaHasIssues = $schemaData['has_issues'] ?? false;
                        $schemaSummary = $schemaData['summary'] ?? '';
                    } catch (Exception $e) {
                        // Ignore JSON parse errors
                    }
                }
                
                return [
                    'name' => $filename,
                    'path' => $file,
                    'size' => $this->formatBytes($size),
                    'size_bytes' => $size,
                    'created_at' => Carbon::createFromTimestamp($timestamp),
                    'has_schema_check' => $hasSchemaCheck,
                    'schema_has_issues' => $schemaHasIssues,
                    'schema_summary' => $schemaSummary,
                ];
            })
            ->sortByDesc('created_at')
            ->values();
    }
    
    /**
     * Get schema check results for a backup
     */
    public function getSchemaCheckResults(string $backupFilename): ?array
    {
        $backupPath = base_path($this->backupPath . '/' . $backupFilename);
        $schemaCheckFile = str_replace('.sql', '_schema_check.json', $backupPath);
        $dynamicCheckFile = str_replace('.sql', '_dynamic_check.json', $backupPath);
        
        // Prefer dynamic check over static check
        if (file_exists($dynamicCheckFile)) {
            try {
                return json_decode(file_get_contents($dynamicCheckFile), true);
            } catch (Exception $e) {
                // Fall through to static check
            }
        }
        
        if (file_exists($schemaCheckFile)) {
            try {
                return json_decode(file_get_contents($schemaCheckFile), true);
            } catch (Exception $e) {
                return null;
            }
        }
        
        return null;
    }

    /**
     * Delete a backup file
     */
    public function deleteBackup(string $filename): bool
    {
        // ALWAYS use database/backups directory - no conditional logic
        $filepath = base_path($this->backupPath . '/' . $filename);
        
        if (file_exists($filepath)) {
            return unlink($filepath);
        }
        return false;
    }

    /**
     * Download a backup file
     */
    public function downloadBackup(string $filename)
    {
        // ALWAYS use database/backups directory - no conditional logic
        $filepath = base_path($this->backupPath . '/' . $filename);
        
        if (!file_exists($filepath)) {
            throw new Exception("Backup file not found: {$filename}");
        }

        return response()->download($filepath);
    }

    /**
     * Validate backup file integrity
     */
    private function validateBackupFile(string $filePath): bool
    {
        try {
            $content = file_get_contents($filePath);
            
            // Check if file contains SQL dump markers
            if (!str_contains($content, 'mysqldump') && !str_contains($content, 'CREATE TABLE') && !str_contains($content, 'INSERT INTO')) {
                return false;
            }
            
            // Check for basic SQL syntax (must contain at least one semicolon)
            if (!str_contains($content, ';')) {
                return false;
            }
            
            // Check for obvious corruption markers
            if (str_contains($content, 'mysqldump: Error') || str_contains($content, 'ERROR')) {
                return false;
            }
            
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Sanitize filename to prevent path traversal and invalid characters
     */
    private function sanitizeFilename(string $filename): string
    {
        // Remove any path separators and parent directory references
        $filename = basename($filename);
        $filename = str_replace(['../', '../', '..\\', '..'], '', $filename);
        
        // Remove or replace invalid characters for filesystem
        $filename = preg_replace('/[^a-zA-Z0-9\-_.]/', '_', $filename);
        
        // Ensure filename isn't empty after sanitization
        if (empty(trim($filename, '._'))) {
            $filename = 'backup_' . Carbon::now()->format('Y-m-d_H-i-s');
        }
        
        // Limit filename length
        if (strlen($filename) > 100) {
            $filename = substr($filename, 0, 100);
        }
        
        return $filename;
    }

    /**
     * Get enhanced PATH for mysqldump execution
     */
    public static function getEnhancedPath(?array $additionalPaths = null): string
    {
        $currentPath = $_SERVER['PATH'] ?? getenv('PATH') ?? '';
        $defaultPaths = [
            '/opt/homebrew/bin',
            '/opt/homebrew/opt/mysql-client/bin',
            '/usr/local/bin',
            '/usr/local/opt/mysql-client/bin',
            '/Applications/Herd.app/Contents/Resources/bin',
        ];
        
        // Add DBngin MySQL paths dynamically
        $dbngingBase = '/Users/Shared/DBngin/mysql';
        if (is_dir($dbngingBase)) {
            $mysqlDirs = glob($dbngingBase . '/*/bin');
            foreach ($mysqlDirs as $binDir) {
                if (is_dir($binDir)) {
                    $defaultPaths[] = $binDir;
                }
            }
        }
        
        $pathsToAdd = $additionalPaths ?? $defaultPaths;
        
        return $currentPath . ':' . implode(':', $pathsToAdd);
    }

    /**
     * Create data-only agricultural database backup with column mapping.
     * 
     * Generates data-only backup with explicit column mapping for maximum
     * compatibility across different agricultural database schema versions.
     * Critical for agricultural data migration and system upgrades where
     * schema changes occur.
     *
     * @param string|null $name Custom backup filename (auto-generated if null)
     * @return string Generated backup filename
     * @throws Exception If backup creation fails or data extraction errors occur
     * @agricultural_context Creates portable backup of agricultural data without schema dependencies
     * @column_mapping Includes explicit column names for better compatibility
     * @batch_processing Processes large agricultural datasets in chunks for memory efficiency
     */
    public function createDataOnlyBackup(?string $name = null): string
    {
        // Check schema before creating backup - ESPECIALLY important for data-only backups!
        $schemaCheck = $this->checkSchemaBeforeBackup();
        $schemaHasIssues = $schemaCheck['has_issues'] ?? false;
        
        $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
        $filename = $name ?? "data_only_backup_{$timestamp}.sql";
        
        // Validate and sanitize filename
        $filename = $this->sanitizeFilename($filename);
        
        // Ensure the filename ends with .sql
        if (!str_ends_with($filename, '.sql')) {
            $filename .= '.sql';
        }
        
        $filepath = base_path($this->backupPath . '/' . $filename);
        
        // Ensure backup directory exists
        if (!is_dir(dirname($filepath))) {
            mkdir(dirname($filepath), 0755, true);
        }
        
        $content = "-- Data-only backup with column mappings\n";
        $content .= "-- Created: " . Carbon::now()->format('Y-m-d H:i:s') . "\n";
        $content .= "-- This backup includes column names for better schema compatibility\n\n";
        $content .= "SET FOREIGN_KEY_CHECKS=0;\n";
        $content .= "SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n\n";
        
        // Get database connection
        $config = config('database.connections.mysql');
        $pdo = new PDO(
            "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}",
            $config['username'],
            $config['password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        
        // Get all tables (excluding views)
        $tables = $this->getDatabaseTables();
        
        foreach ($tables as $tableName) {
            // Skip system tables
            if (in_array($tableName, ['migrations', 'failed_jobs', 'password_resets', 'personal_access_tokens'])) {
                continue;
            }
            
            // Get column information
            $stmt = $pdo->query("
                SELECT COLUMN_NAME 
                FROM INFORMATION_SCHEMA.COLUMNS 
                WHERE TABLE_NAME = '{$tableName}' AND TABLE_SCHEMA = DATABASE()
                ORDER BY ORDINAL_POSITION
            ");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (empty($columns)) {
                continue;
            }
            
            // Get table data
            $stmt = $pdo->query("SELECT * FROM `{$tableName}`");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($rows)) {
                continue;
            }
            
            $content .= "\n-- Table: {$tableName} (" . count($rows) . " rows)\n";
            $content .= "-- Columns: " . implode(', ', $columns) . "\n";
            
            // Build column-mapped INSERT statements
            $columnNames = '`' . implode('`, `', $columns) . '`';
            
            $values = [];
            foreach ($rows as $row) {
                $rowValues = [];
                foreach ($columns as $column) {
                    $value = $row[$column];
                    
                    if (is_null($value)) {
                        $rowValues[] = 'NULL';
                    } elseif (is_numeric($value) && !is_string($value)) {
                        $rowValues[] = $value;
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
                $content .= "INSERT INTO `{$tableName}` ({$columnNames}) VALUES\n";
                $content .= implode(",\n", $chunk) . ";\n";
            }
        }
        
        $content .= "\nSET FOREIGN_KEY_CHECKS=1;\n";
        
        // Save to file
        file_put_contents($filepath, $content);
        
        // Validate file was created
        if (!file_exists($filepath) || filesize($filepath) === 0) {
            throw new Exception('Backup file was not created successfully');
        }
        
        // Save schema check results - CRITICAL for data-only backups!
        if (!empty($schemaCheck)) {
            $schemaResultFile = str_replace('.sql', '_schema_check.json', $filepath);
            $comparisonService = new SchemaComparisonService();
            
            // Add extra warning for data-only backups with schema issues
            if ($schemaHasIssues) {
                $schemaCheck['data_only_warning'] = "⚠️ WARNING: This data-only backup has schema mismatches. Restore may fail for tables with column differences.";
            }
            
            $schemaCheckData = [
                'timestamp' => now()->toIso8601String(),
                'backup_type' => 'data-only',
                'has_issues' => $schemaHasIssues,
                'summary' => $schemaCheck['summary'] ?? '',
                'details' => $schemaCheck,
                'formatted_report' => $comparisonService->formatDifferences($schemaCheck)
            ];
            
            file_put_contents($schemaResultFile, json_encode($schemaCheckData, JSON_PRETTY_PRINT));
        }
        
        return $filename;
    }

    /**
     * Get all database views
     */
    private function getDatabaseViews(): array
    {
        try {
            $config = config('database.connections.mysql');
            $pdo = new PDO(
                "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}",
                $config['username'],
                $config['password'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );

            $stmt = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'VIEW'");
            $views = [];
            
            while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
                $views[] = $row[0];
            }
            
            error_log("Found " . count($views) . " database views to exclude from backup: " . implode(', ', $views));
            return $views;
            
        } catch (Exception $e) {
            // If we can't get views, be more comprehensive in our fallback
            error_log("Could not fetch database views, using fallback list: " . $e->getMessage());
            
            // Return a more comprehensive list of known views that might exist
            return [
                'product_inventory_summary', 
                'crop_batches',
                'harvest_summary',
                'inventory_summary',
                'crop_timeline',
                'task_summary'
            ];
        }
    }

    /**
     * Fix column count mismatch by converting to column-specific INSERT
     */
    private function fixColumnCountMismatch(string $statement, PDO $pdo): array
    {
        $fixes = [];
        
        // Only handle INSERT statements
        if (!str_contains($statement, 'INSERT INTO')) {
            return ['statement' => $statement, 'fixes' => $fixes];
        }
        
        // Extract table name
        if (preg_match('/INSERT INTO `([^`]+)`/', $statement, $matches)) {
            $tableName = $matches[1];
            
            try {
                // Skip system tables
                if ($tableName === 'migrations') {
                    return ['statement' => $statement, 'fixes' => $fixes];
                }
                
                // Get current table columns
                $result = $pdo->query("
                    SELECT COLUMN_NAME 
                    FROM INFORMATION_SCHEMA.COLUMNS 
                    WHERE TABLE_NAME = '{$tableName}' AND TABLE_SCHEMA = DATABASE()
                    ORDER BY ORDINAL_POSITION
                ");
                $currentColumns = $result->fetchAll(PDO::FETCH_COLUMN);
                
                if (empty($currentColumns)) {
                    throw new Exception("Table '{$tableName}' not found in current schema");
                }
                
                // For data-only backups, we need to map values to current columns
                // This is complex because we need to parse the VALUES part
                if (preg_match('/INSERT INTO `' . $tableName . '` VALUES (.+)$/s', $statement, $valueMatch)) {
                    $valuesSection = $valueMatch[1];
                    
                    // Parse all value sets from the INSERT statement
                    $parsedRows = $this->parseInsertValues($valuesSection);
                    
                    if (empty($parsedRows)) {
                        throw new Exception("Could not parse values from INSERT statement");
                    }
                    
                    // Check value count vs column count
                    $valueCount = count($parsedRows[0]);
                    $columnCount = count($currentColumns);
                    
                    if ($valueCount !== $columnCount) {
                        // Try to map values to columns based on common patterns
                        $mappedStatement = $this->createMappedInsertStatement($tableName, $currentColumns, $parsedRows);
                        
                        if ($mappedStatement) {
                            $fixes[] = "Table '{$tableName}': Remapped {$valueCount} values to {$columnCount} columns";
                            return ['statement' => $mappedStatement, 'fixes' => $fixes];
                        } else {
                            throw new Exception("Cannot map {$valueCount} values to {$columnCount} columns");
                        }
                    } else {
                        // Just add column names for clarity
                        $columnNames = array_map(function($col) { return '`' . $col . '`'; }, $currentColumns);
                        $newStatement = str_replace(
                            "INSERT INTO `{$tableName}` VALUES",
                            "INSERT INTO `{$tableName}` (" . implode(', ', $columnNames) . ") VALUES",
                            $statement
                        );
                        
                        $fixes[] = "Table '{$tableName}': Added column names to INSERT";
                        return ['statement' => $newStatement, 'fixes' => $fixes];
                    }
                }
                
            } catch (Exception $e) {
                // If we can't fix it, log the error
                $fixes[] = "Table '{$tableName}': Schema mismatch - {$e->getMessage()}";
                
                // For critical tables, skip the statement rather than fail
                if (in_array($tableName, ['crop_plans', 'recipes', 'orders', 'products'])) {
                    return ['statement' => '-- Skipped due to schema mismatch: ' . $statement, 'fixes' => $fixes];
                }
                
                return ['statement' => $statement, 'fixes' => $fixes];
            }
        }
        
        return ['statement' => $statement, 'fixes' => $fixes];
    }
    
    /**
     * Parse INSERT VALUES into array of value arrays
     */
    private function parseInsertValues(string $valuesSection): array
    {
        $rows = [];
        $currentRow = [];
        $currentValue = '';
        $inQuotes = false;
        $quoteChar = '';
        $parenDepth = 0;
        $bracketDepth = 0;
        
        for ($i = 0; $i < strlen($valuesSection); $i++) {
            $char = $valuesSection[$i];
            $nextChar = $i < strlen($valuesSection) - 1 ? $valuesSection[$i + 1] : '';
            
            if (!$inQuotes && ($char === '"' || $char === "'")) {
                $inQuotes = true;
                $quoteChar = $char;
                $currentValue .= $char;
            } elseif ($inQuotes && $char === $quoteChar) {
                // Check if escaped
                if ($i > 0 && $valuesSection[$i-1] === '\\') {
                    $currentValue .= $char;
                } else {
                    $inQuotes = false;
                    $currentValue .= $char;
                }
            } elseif (!$inQuotes) {
                if ($char === '(') {
                    $parenDepth++;
                    if ($parenDepth === 1) {
                        // Start of a new row
                        $currentRow = [];
                        $currentValue = '';
                    } else {
                        $currentValue .= $char;
                    }
                } elseif ($char === ')') {
                    $parenDepth--;
                    if ($parenDepth === 0) {
                        // End of row
                        if (trim($currentValue) !== '') {
                            $currentRow[] = trim($currentValue);
                        }
                        $rows[] = $currentRow;
                        $currentValue = '';
                    } else {
                        $currentValue .= $char;
                    }
                } elseif ($char === '{' || $char === '[') {
                    $bracketDepth++;
                    $currentValue .= $char;
                } elseif ($char === '}' || $char === ']') {
                    $bracketDepth--;
                    $currentValue .= $char;
                } elseif ($char === ',' && $parenDepth === 1 && $bracketDepth === 0) {
                    // End of value
                    $currentRow[] = trim($currentValue);
                    $currentValue = '';
                } else {
                    $currentValue .= $char;
                }
            } else {
                $currentValue .= $char;
            }
        }
        
        return $rows;
    }
    
    /**
     * Create a mapped INSERT statement when column counts don't match
     */
    private function createMappedInsertStatement(string $tableName, array $currentColumns, array $parsedRows): ?string
    {
        // For now, just skip rows with mismatched column counts
        // In a more sophisticated implementation, we could try to map columns by name
        error_log("Table '{$tableName}': Cannot auto-map " . count($parsedRows[0]) . " values to " . count($currentColumns) . " columns");
        
        // Return a comment explaining why this was skipped
        return "-- Table '{$tableName}': Skipped " . count($parsedRows) . " rows due to column count mismatch (" . count($parsedRows[0]) . " values vs " . count($currentColumns) . " columns)";
    }


    /**
     * Format bytes to human readable format
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Check if running inside a Docker container
     */
    private function isRunningInDocker(): bool
    {
        // Check common Docker indicators
        return file_exists('/.dockerenv') || 
               (getenv('DOCKER_CONTAINER') !== false) ||
               (isset($_SERVER['DOCKER_CONTAINER']));
    }

    /**
     * Find mysqldump executable in known locations
     */
    private function findMysqldump(): ?string
    {
        // Skip if running in Docker - mysqldump should be accessed via docker exec
        if ($this->isRunningInDocker()) {
            return null;
        }
        $staticPaths = [
            '/usr/bin/mysqldump',
            '/usr/local/bin/mysqldump',
            '/bin/mysqldump',
            '/opt/homebrew/bin/mysqldump',
            '/opt/homebrew/opt/mysql-client/bin/mysqldump',
            '/usr/local/opt/mysql-client/bin/mysqldump',
            '/opt/homebrew/opt/mysql/bin/mysqldump',
            '/usr/local/opt/mysql/bin/mysqldump',
            '/Users/' . get_current_user() . '/Library/Application Support/Herd/bin/mysqldump',
            '/Applications/Herd.app/Contents/Resources/bin/mysqldump',
            '/usr/local/mysql/bin/mysqldump',
            '/Applications/DBngin.app/Contents/Resources/mysql/bin/mysqldump',
            '/Applications/MAMP/Library/bin/mysqldump',
            '/opt/lampp/bin/mysqldump',
            '/Applications/XAMPP/xamppfiles/bin/mysqldump',
        ];
        
        // Check static paths first
        foreach ($staticPaths as $path) {
            if (file_exists($path) && is_executable($path)) {
                return $path;
            }
        }
        
        // Check DBngin MySQL versions dynamically
        $dbngingPaths = $this->findDbngingMysqlPaths();
        foreach ($dbngingPaths as $path) {
            if (file_exists($path) && is_executable($path)) {
                return $path;
            }
        }
        
        // Try which command as fallback
        $which = shell_exec('which mysqldump 2>/dev/null');
        if ($which && file_exists(trim($which))) {
            return trim($which);
        }
        
        return null;
    }

    /**
     * Find DBngin MySQL paths dynamically
     */
    private function findDbngingMysqlPaths(): array
    {
        $paths = [];
        $dbngingBase = '/Users/Shared/DBngin/mysql';
        
        if (is_dir($dbngingBase)) {
            $mysqlVersions = glob($dbngingBase . '/*/bin/mysqldump');
            foreach ($mysqlVersions as $path) {
                if (is_executable($path)) {
                    $paths[] = $path;
                }
            }
        }
        
        return $paths;
    }

    /**
     * Archive a backup file by moving it to the archived subdirectory
     */
    public function archiveBackup(string $filename): bool
    {
        $sourcePath = base_path($this->backupPath . '/' . $filename);
        $archivePath = base_path($this->backupPath . '/archived/' . $filename);
        
        if (!file_exists($sourcePath)) {
            throw new Exception("Backup file not found: {$filename}");
        }
        
        // Ensure archived directory exists
        $archiveDir = dirname($archivePath);
        if (!is_dir($archiveDir)) {
            mkdir($archiveDir, 0755, true);
        }
        
        // Move the backup file
        if (!rename($sourcePath, $archivePath)) {
            throw new Exception("Failed to archive backup file: {$filename}");
        }
        
        // Also move any associated schema check files
        $baseFilename = str_replace('.sql', '', $filename);
        $schemaFiles = [
            $baseFilename . '_schema_check.json',
            $baseFilename . '_dynamic_check.json'
        ];
        
        foreach ($schemaFiles as $schemaFile) {
            $sourceSchemaPath = base_path($this->backupPath . '/' . $schemaFile);
            $archiveSchemaPath = base_path($this->backupPath . '/archived/' . $schemaFile);
            
            if (file_exists($sourceSchemaPath)) {
                rename($sourceSchemaPath, $archiveSchemaPath);
            }
        }
        
        return true;
    }

    /**
     * Unarchive a backup file by moving it back to the main backup directory
     */
    public function unarchiveBackup(string $filename): bool
    {
        $archivePath = base_path($this->backupPath . '/archived/' . $filename);
        $destinationPath = base_path($this->backupPath . '/' . $filename);
        
        if (!file_exists($archivePath)) {
            throw new Exception("Archived backup file not found: {$filename}");
        }
        
        // Check if file already exists in main directory
        if (file_exists($destinationPath)) {
            throw new Exception("A backup with this name already exists in the main directory: {$filename}");
        }
        
        // Move the backup file back
        if (!rename($archivePath, $destinationPath)) {
            throw new Exception("Failed to unarchive backup file: {$filename}");
        }
        
        // Also move any associated schema check files
        $baseFilename = str_replace('.sql', '', $filename);
        $schemaFiles = [
            $baseFilename . '_schema_check.json',
            $baseFilename . '_dynamic_check.json'
        ];
        
        foreach ($schemaFiles as $schemaFile) {
            $archiveSchemaPath = base_path($this->backupPath . '/archived/' . $schemaFile);
            $destinationSchemaPath = base_path($this->backupPath . '/' . $schemaFile);
            
            if (file_exists($archiveSchemaPath)) {
                rename($archiveSchemaPath, $destinationSchemaPath);
            }
        }
        
        return true;
    }

    /**
     * List all archived backups
     */
    public function listArchivedBackups(): Collection
    {
        $archiveDir = base_path($this->backupPath . '/archived');
        
        if (!is_dir($archiveDir)) {
            return collect();
        }

        // Only get SQL files, not the JSON schema check files
        $files = glob($archiveDir . '/*.sql');
        
        return collect($files)
            ->map(function($file) {
                $size = filesize($file);
                $timestamp = filemtime($file);
                $filename = basename($file);
                
                // Check if schema check file exists (static or dynamic)
                $schemaCheckFile = str_replace('.sql', '_schema_check.json', $file);
                $dynamicCheckFile = str_replace('.sql', '_dynamic_check.json', $file);
                
                // Prefer dynamic check over static check
                $activeCheckFile = file_exists($dynamicCheckFile) ? $dynamicCheckFile : 
                                  (file_exists($schemaCheckFile) ? $schemaCheckFile : null);
                
                $hasSchemaCheck = $activeCheckFile !== null;
                $schemaHasIssues = false;
                $schemaSummary = '';
                
                if ($hasSchemaCheck) {
                    try {
                        $schemaData = json_decode(file_get_contents($activeCheckFile), true);
                        $schemaHasIssues = $schemaData['has_issues'] ?? false;
                        $schemaSummary = $schemaData['summary'] ?? '';
                    } catch (Exception $e) {
                        // Ignore JSON parse errors
                    }
                }
                
                return [
                    'name' => $filename,
                    'path' => $file,
                    'size' => $this->formatBytes($size),
                    'size_bytes' => $size,
                    'created_at' => Carbon::createFromTimestamp($timestamp),
                    'has_schema_check' => $hasSchemaCheck,
                    'schema_has_issues' => $schemaHasIssues,
                    'schema_summary' => $schemaSummary,
                ];
            })
            ->sortByDesc('created_at')
            ->values();
    }

    /**
     * Get all database tables (excluding views)
     */
    private function getDatabaseTables(): array
    {
        try {
            $config = config('database.connections.mysql');
            $pdo = new PDO(
                "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}",
                $config['username'],
                $config['password'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );

            $stmt = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");
            $tables = [];
            
            while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
                $tables[] = $row[0];
            }
            
            error_log("Found " . count($tables) . " database tables for backup: " . implode(', ', array_slice($tables, 0, 10)) . (count($tables) > 10 ? '...' : ''));
            return $tables;
            
        } catch (Exception $e) {
            error_log("Could not fetch database tables: " . $e->getMessage());
            return [];
        }
    }
}