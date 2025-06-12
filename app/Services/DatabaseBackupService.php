<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Process;
use Carbon\Carbon;
use Exception;

class DatabaseBackupService
{
    protected string $backupPath = 'backups/database';

    public function createBackup(): array
    {
        try {
            $filename = $this->generateBackupFilename();
            $config = config('database.connections.' . config('database.default'));
            
            // Create backup directory if it doesn't exist
            // Using direct path to avoid Storage disk configuration issues
            $backupDirectory = storage_path("app/{$this->backupPath}");
            if (!file_exists($backupDirectory)) {
                mkdir($backupDirectory, 0755, true);
            }

            $backupFilePath = storage_path("app/{$this->backupPath}/{$filename}");

            // Check if mysqldump is available, if not use PHP-based backup
            $mysqldumpCheck = Process::run('which mysqldump');
            if ($mysqldumpCheck->failed()) {
                return $this->createPhpBackup($config, $backupFilePath, $filename);
            }

            // Build mysqldump command
            $command = $this->buildMysqldumpCommand($config, $backupFilePath);
            
            // Execute backup
            $result = Process::run($command);
            
            if ($result->failed()) {
                $errorOutput = $result->errorOutput();
                $output = $result->output();
                // Fall back to PHP backup if mysqldump fails
                return $this->createPhpBackup($config, $backupFilePath, $filename);
            }

            // Verify backup file was created and has content
            if (!file_exists($backupFilePath) || filesize($backupFilePath) === 0) {
                throw new Exception('Backup file was not created or is empty');
            }

            return [
                'success' => true,
                'filename' => $filename,
                'path' => $backupFilePath,
                'size' => $this->formatBytes(filesize($backupFilePath)),
                'created_at' => now()->toDateTimeString(),
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function restoreBackup(string $backupFilePath): array
    {
        try {
            if (!file_exists($backupFilePath)) {
                throw new Exception('Backup file not found');
            }

            $config = config('database.connections.' . config('database.default'));
            
            // Check if mysql CLI is available
            $mysqlCheck = Process::run('which mysql');

            if ($mysqlCheck->successful()) {
                // Build mysql restore command
                $command = $this->buildMysqlRestoreCommand($config, $backupFilePath);

                // Execute restore
                $result = Process::run($command);

                if ($result->successful()) {
                    return [
                        'success' => true,
                        'method' => 'mysql CLI',
                        'message' => 'Database restored successfully',
                        'restored_at' => now()->toDateTimeString(),
                    ];
                }

                // If CLI restore failed, fall back to PHP restore below
            }

            // ----- Fallback: PHP-based restore -----
            $phpRestoreResult = $this->restoreUsingPhp($backupFilePath);

            if ($phpRestoreResult) {
                return [
                    'success' => true,
                    'method' => 'PHP-based restore',
                    'message' => 'Database restored successfully (PHP fallback)',
                    'restored_at' => now()->toDateTimeString(),
                ];
            } else {
                throw new Exception('PHP-based restore failed');
            }

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function listBackups(): array
    {
        $backups = [];
        $backupDirectory = storage_path("app/{$this->backupPath}");
        
        // Check if directory exists
        if (!file_exists($backupDirectory)) {
            return $backups;
        }
        
        // Get all SQL files in the backup directory
        $files = glob($backupDirectory . '/*.sql');
        
        foreach ($files as $file) {
            $backups[] = [
                'filename' => basename($file),
                'path' => $file,
                'size' => $this->formatBytes(filesize($file)),
                'created_at' => Carbon::createFromTimestamp(filemtime($file))->toDateTimeString(),
            ];
        }

        // Sort by creation time, newest first
        usort($backups, function ($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });

        return $backups;
    }

    public function deleteBackup(string $filename): bool
    {
        $filePath = storage_path("app/{$this->backupPath}/{$filename}");
        
        if (file_exists($filePath)) {
            return unlink($filePath);
        }
        
        return false;
    }

    public function downloadBackup(string $filename): ?string
    {
        $filePath = storage_path("app/{$this->backupPath}/{$filename}");
        
        if (file_exists($filePath)) {
            return $filePath;
        }
        
        return null;
    }

    protected function generateBackupFilename(): string
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        $dbName = config('database.connections.' . config('database.default') . '.database');
        
        return "backup_{$dbName}_{$timestamp}.sql";
    }

    protected function buildMysqldumpCommand(array $config, string $outputPath): string
    {
        $command = 'mysqldump';
        
        // Add connection parameters
        $command .= " --host={$config['host']}";
        $command .= " --port={$config['port']}";
        $command .= " --user={$config['username']}";
        
        if (!empty($config['password'])) {
            $command .= " --password=" . escapeshellarg($config['password']);
        }
        
        // Add dump options for better restoration
        $command .= " --single-transaction";
        $command .= " --routines";
        $command .= " --triggers";
        $command .= " --add-drop-table";
        $command .= " --extended-insert";
        
        // Add database name and output file
        $command .= " {$config['database']}";
        $command .= " > " . escapeshellarg($outputPath);
        
        return $command;
    }

    protected function buildMysqlRestoreCommand(array $config, string $inputPath): string
    {
        $command = 'mysql';
        
        // Add connection parameters
        $command .= " --host={$config['host']}";
        $command .= " --port={$config['port']}";
        $command .= " --user={$config['username']}";
        
        if (!empty($config['password'])) {
            $command .= " --password=" . escapeshellarg($config['password']);
        }
        
        // Add database name and input file
        $command .= " {$config['database']}";
        $command .= " < " . escapeshellarg($inputPath);
        
        return $command;
    }

    protected function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }

    protected function createPhpBackup(array $config, string $backupFilePath, string $filename): array
    {
        try {
            // Get all tables and views
            $tables = DB::select('SHOW FULL TABLES');
            
            $sql = '';
            $sql .= "-- Database Backup Created with PHP\n";
            $sql .= "-- Database: {$config['database']}\n";
            $sql .= "-- Created: " . now()->toDateTimeString() . "\n\n";
            $sql .= "SET FOREIGN_KEY_CHECKS=0;\n";
            $sql .= "SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n\n";

            // Separate tables and views
            $realTables = [];
            $views = [];
            
            foreach ($tables as $table) {
                $tableName = $table->{'Tables_in_' . $config['database']};
                $tableType = $table->Table_type ?? 'BASE TABLE';
                
                if ($tableType === 'VIEW') {
                    $views[] = $tableName;
                } else {
                    $realTables[] = $tableName;
                }
            }

            // Process real tables first
            foreach ($realTables as $tableName) {
                try {
                    // Get CREATE TABLE statement
                    $createTable = DB::select("SHOW CREATE TABLE `{$tableName}`");
                    $sql .= "-- Table structure for `{$tableName}`\n";
                    $sql .= "DROP TABLE IF EXISTS `{$tableName}`;\n";
                    $sql .= $createTable[0]->{'Create Table'} . ";\n\n";
                    
                    // Get table data
                    $rows = DB::table($tableName)->get();
                    if ($rows->count() > 0) {
                        $sql .= "-- Data for table `{$tableName}`\n";
                        $sql .= "INSERT INTO `{$tableName}` VALUES\n";
                        
                        $values = [];
                        foreach ($rows as $row) {
                            $rowValues = [];
                            foreach ((array)$row as $value) {
                                if ($value === null) {
                                    $rowValues[] = 'NULL';
                                } elseif (is_numeric($value)) {
                                    $rowValues[] = $value;
                                } else {
                                    $rowValues[] = "'" . addslashes($value) . "'";
                                }
                            }
                            $values[] = '(' . implode(',', $rowValues) . ')';
                        }
                        
                        $sql .= implode(",\n", $values) . ";\n\n";
                    }
                } catch (Exception $e) {
                    $sql .= "-- Error backing up table `{$tableName}`: " . $e->getMessage() . "\n\n";
                }
            }

            // Process views after tables
            foreach ($views as $viewName) {
                try {
                    $createView = DB::select("SHOW CREATE VIEW `{$viewName}`");
                    $sql .= "-- View structure for `{$viewName}`\n";
                    $sql .= "DROP VIEW IF EXISTS `{$viewName}`;\n";
                    $sql .= $createView[0]->{'Create View'} . ";\n\n";
                } catch (Exception $e) {
                    $sql .= "-- Error backing up view `{$viewName}`: " . $e->getMessage() . "\n\n";
                }
            }
            
            $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
            
            // Write to file
            file_put_contents($backupFilePath, $sql);
            
            if (!file_exists($backupFilePath) || filesize($backupFilePath) === 0) {
                throw new Exception('PHP backup file was not created or is empty');
            }

            return [
                'success' => true,
                'filename' => $filename,
                'path' => $backupFilePath,
                'size' => $this->formatBytes(filesize($backupFilePath)),
                'created_at' => now()->toDateTimeString(),
                'method' => 'PHP-based backup (mysqldump not available)',
            ];

        } catch (Exception $e) {
            throw new Exception('PHP backup failed: ' . $e->getMessage());
        }
    }

    /**
     * Restore a database from an SQL file using pure PHP (no mysql CLI).
     * Iterates through the SQL file and executes statements one by one.
     *
     * @param string $backupFilePath
     * @return bool true on success, false on failure
     */
    protected function restoreUsingPhp(string $backupFilePath): bool
    {
        try {
            // Turn off foreign key checks for the duration of the import
            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            $handle = fopen($backupFilePath, 'r');
            if (!$handle) {
                throw new Exception('Unable to read backup file');
            }

            $statement = '';
            while (($line = fgets($handle)) !== false) {
                $trimmedLine = trim($line);

                // Skip comments and empty lines
                if ($trimmedLine === '' || str_starts_with($trimmedLine, '--') || str_starts_with($trimmedLine, '/*')) {
                    continue;
                }

                $statement .= $line;

                // Check for statement delimiter (semicolon at line end)
                if (preg_match('/;\s*$/', $trimmedLine)) {
                    // Execute the accumulated statement
                    DB::unprepared($statement);
                    $statement = '';
                }
            }

            // In case there's any remaining statement without semicolon (unlikely)
            if (trim($statement) !== '') {
                DB::unprepared($statement);
            }

            fclose($handle);
            DB::statement('SET FOREIGN_KEY_CHECKS=1');

            return true;

        } catch (Exception $e) {
            // Ensure FK checks are re-enabled even on failure
            try {
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            } catch (Exception $inner) {
                // ignore
            }

            // Re-throw to outer catch
            throw $e;
        }
    }
}
