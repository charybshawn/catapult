<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use Ifsnop\Mysqldump\Mysqldump;

class SimpleBackupService
{
    private $disk;
    private $backupPath;

    public function __construct()
    {
        $this->disk = Storage::disk('local');
        $this->backupPath = 'backups/database';
    }

    /**
     * Create a database backup using mysqldump-php
     */
    public function createBackup(?string $name = null): string
    {
        $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
        $filename = $name ?? "database_backup_{$timestamp}.sql";
        
        // Ensure the filename ends with .sql
        if (!str_ends_with($filename, '.sql')) {
            $filename .= '.sql';
        }

        // Ensure directory exists
        $this->disk->makeDirectory($this->backupPath);
        
        // Get database connection details
        $config = config('database.connections.mysql');
        $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}";
        
        // Create temp file in system temp directory
        $tempFilePath = tempnam(sys_get_temp_dir(), 'backup_');
        
        try {
            // Create mysqldump instance with minimal options
            $dump = new Mysqldump($dsn, $config['username'], $config['password'], [
                'compress' => Mysqldump::NONE,
                'single-transaction' => true,
                'lock-tables' => false,
                'add-drop-table' => true,
                'default-character-set' => Mysqldump::UTF8,
                'exclude-tables' => ['crop_batches', 'product_inventory_summary'], // Exclude problematic views
            ]);
            
            // Create the backup to temp file
            $dump->start($tempFilePath);
            
            // Move the file to Laravel storage
            $finalPath = $this->backupPath . '/' . $filename;
            $this->disk->put($finalPath, file_get_contents($tempFilePath));
            
            // Clean up temp file
            unlink($tempFilePath);
            
            return $filename;
            
        } catch (\Exception $e) {
            // Clean up temp file if it exists
            if (file_exists($tempFilePath)) {
                unlink($tempFilePath);
            }
            throw new \Exception("Backup failed: " . $e->getMessage());
        }
    }

    /**
     * Restore database from backup
     */
    public function restoreBackup(string $filename): bool
    {
        $filepath = $this->backupPath . '/' . $filename;
        
        if (!$this->disk->exists($filepath)) {
            throw new \Exception("Backup file not found: {$filename}");
        }

        $sqlContent = $this->disk->get($filepath);
        
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
                        // Log individual statement errors but continue
                        \Log::warning("SQL statement failed during restore: " . $e->getMessage());
                        \Log::warning("Statement: " . substr($statement, 0, 200) . "...");
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
        if (!$this->disk->exists($this->backupPath)) {
            return collect();
        }

        $files = $this->disk->allFiles($this->backupPath);
        
        return collect($files)
            ->filter(fn($file) => str_ends_with($file, '.sql') || str_ends_with($file, '.json'))
            ->map(function($file) {
                $size = $this->disk->size($file);
                $timestamp = $this->disk->lastModified($file);
                
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
        $filepath = $this->backupPath . '/' . $filename;
        return $this->disk->delete($filepath);
    }

    /**
     * Download a backup file
     */
    public function downloadBackup(string $filename)
    {
        $filepath = $this->backupPath . '/' . $filename;
        
        if (!$this->disk->exists($filepath)) {
            throw new \Exception("Backup file not found: {$filename}");
        }

        return $this->disk->download($filepath);
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