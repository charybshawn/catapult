<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use Symfony\Component\Process\Process;

class SimpleBackupService
{
    private $backupPath;

    public function __construct()
    {
        // Use direct file system instead of Laravel disk to avoid path confusion
        $this->backupPath = 'backups/database';
    }

    /**
     * Create a database backup using native mysqldump
     */
    public function createBackup(?string $name = null, bool $excludeViews = true): string
    {
        $lockFile = storage_path('app/backups/.backup.lock');
        $lockHandle = null;
        
        try {
            // Ensure backup directory exists
            $fullBackupDir = storage_path('app/' . $this->backupPath);
            if (!is_dir($fullBackupDir)) {
                mkdir($fullBackupDir, 0755, true);
            }
            
            // Create lock directory if it doesn't exist
            $lockDir = dirname($lockFile);
            if (!is_dir($lockDir)) {
                mkdir($lockDir, 0755, true);
            }
            
            // Acquire exclusive lock to prevent concurrent backups
            $lockHandle = fopen($lockFile, 'w');
            if (!$lockHandle || !flock($lockHandle, LOCK_EX | LOCK_NB)) {
                throw new \Exception('Another backup operation is already in progress. Please wait and try again.');
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
            
            // Full path to backup file
            $backupPath = storage_path('app/' . $this->backupPath . '/' . $filename);
            
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
                '--set-gtid-purged=OFF',
                '--column-statistics=0',
                '--skip-routines',
                '--skip-triggers',
                '--skip-events',
            ];
            
            // Add views exclusion if requested
            if ($excludeViews) {
                // Get all views and exclude them
                $views = $this->getDatabaseViews();
                foreach ($views as $view) {
                    $command[] = '--ignore-table=' . $config['database'] . '.' . $view;
                }
            }
            
            $command[] = $config['database'];
            
            $process = new Process($command, base_path());
            
            // Set memory and time limits for large databases
            $process->setTimeout(3600); // 1 hour timeout
            
            // Enhance PATH for web server environment
            $process->setEnv(['PATH' => self::getEnhancedPath()]);
            
            $process->run();
            
            if (!$process->isSuccessful()) {
                throw new \Exception('mysqldump failed: ' . $process->getErrorOutput());
            }
            
            $output = $process->getOutput();
            
            // Check output size (warn if > 100MB)
            $outputSize = strlen($output);
            if ($outputSize > 100 * 1024 * 1024) {
                error_log("Large backup created: " . $this->formatBytes($outputSize));
            }
            
            // Save output to file
            file_put_contents($backupPath, $output);
            
            // Validate file was written correctly
            if (!file_exists($backupPath) || filesize($backupPath) === 0) {
                throw new \Exception('Backup file was not created successfully');
            }
            
            // Basic SQL validation
            if (!$this->validateBackupFile($backupPath)) {
                throw new \Exception('Backup file appears to be corrupted or invalid');
            }
            
            return $filename;
            
        } catch (\Exception $e) {
            throw new \Exception("Backup failed: " . $e->getMessage());
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
     * Restore database from backup
     */
    public function restoreBackup(string $filename): bool
    {
        $filepath = $this->backupPath . '/' . $filename;
        
        $fullFilepath = storage_path('app/' . $filepath);
        
        if (!file_exists($fullFilepath)) {
            throw new \Exception("Backup file not found: {$filename}");
        }

        $sqlContent = file_get_contents($fullFilepath);
        
        if (empty($sqlContent)) {
            throw new \Exception("Backup file is empty or corrupted");
        }

        try {
            // Get database connection
            $config = config('database.connections.mysql');
            $pdo = new \PDO(
                "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}",
                $config['username'],
                $config['password'],
                [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
            );

            // Disable foreign key checks
            $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
            $pdo->exec('SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO"');
            $pdo->exec('SET AUTOCOMMIT=0');
            $pdo->exec('START TRANSACTION');

            // Split SQL into individual statements
            $statements = $this->splitSqlStatements($sqlContent);
            
            foreach ($statements as $statement) {
                $statement = trim($statement);
                if (!empty($statement) && $statement !== ';') {
                    try {
                        $pdo->exec($statement);
                    } catch (\Exception $e) {
                        // Log failed statement to error log (not Laravel Log to avoid class issues)
                        $errorMsg = "SQL statement failed during restore: " . $e->getMessage();
                        $stmtPreview = substr($statement, 0, 100) . "...";
                        error_log("Database Restore Warning: {$errorMsg} | Statement: {$stmtPreview}");
                        // Continue with next statement
                    }
                }
            }

            // Commit transaction and re-enable foreign key checks
            $pdo->exec('COMMIT');
            $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
            
            return true;
            
        } catch (\Exception $e) {
            throw new \Exception("Restore failed: " . $e->getMessage());
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
     * List all available backups
     */
    public function listBackups(): Collection
    {
        $fullBackupDir = storage_path('app/' . $this->backupPath);
        
        if (!is_dir($fullBackupDir)) {
            return collect();
        }

        $files = glob($fullBackupDir . '/*.{sql,json}', GLOB_BRACE);
        
        return collect($files)
            ->map(function($file) {
                $size = filesize($file);
                $timestamp = filemtime($file);
                
                return [
                    'name' => basename($file),
                    'path' => $file,
                    'size' => $this->formatBytes($size),
                    'size_bytes' => $size,
                    'created_at' => Carbon::createFromTimestamp($timestamp),
                ];
            })
            ->sortByDesc('created_at')
            ->values();
    }

    /**
     * Delete a backup file
     */
    public function deleteBackup(string $filename): bool
    {
        $filepath = storage_path('app/' . $this->backupPath . '/' . $filename);
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
        $filepath = storage_path('app/' . $this->backupPath . '/' . $filename);
        
        if (!file_exists($filepath)) {
            throw new \Exception("Backup file not found: {$filename}");
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
        } catch (\Exception $e) {
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
        
        $pathsToAdd = $additionalPaths ?? $defaultPaths;
        
        return $currentPath . ':' . implode(':', $pathsToAdd);
    }

    /**
     * Get all database views
     */
    private function getDatabaseViews(): array
    {
        try {
            $config = config('database.connections.mysql');
            $pdo = new \PDO(
                "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}",
                $config['username'],
                $config['password'],
                [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
            );

            $stmt = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'VIEW'");
            $views = [];
            
            while ($row = $stmt->fetch(\PDO::FETCH_NUM)) {
                $views[] = $row[0];
            }
            
            return $views;
        } catch (\Exception $e) {
            // If we can't get views, fall back to known problematic ones
            error_log("Could not fetch database views: " . $e->getMessage());
            return ['product_inventory_summary', 'crop_batches'];
        }
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
}