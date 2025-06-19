<?php

namespace App\Services\ImportExport;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class ResourceExportService
{
    /**
     * Export all tables for a resource
     */
    public function exportResource(string $resource, array $options = []): string
    {
        $format = $options['format'] ?? 'json';
        $includeTimestamps = $options['include_timestamps'] ?? false;
        $whereConditions = $options['where'] ?? [];
        
        // Get tables to export in proper order
        $tables = ResourceDefinitions::getExportOrder($resource);
        $definition = ResourceDefinitions::getResourceDependencies()[$resource];
        
        // Create export directory
        $timestamp = now()->format('Y-m-d_His');
        $exportDir = "exports/{$resource}_{$timestamp}";
        Storage::makeDirectory($exportDir);
        
        $exportedFiles = [];
        $manifest = [
            'resource' => $resource,
            'exported_at' => now()->toIso8601String(),
            'tables' => [],
            'statistics' => []
        ];
        
        foreach ($tables as $table) {
            // Skip if table doesn't exist
            if (!DB::getSchemaBuilder()->hasTable($table)) {
                continue;
            }
            
            $filename = "{$table}.{$format}";
            $filepath = storage_path("app/{$exportDir}/{$filename}");
            
            // Build command options
            $commandOptions = [
                'table' => $table,
                '--format' => $format,
                '--output' => $filepath,
            ];
            
            if ($includeTimestamps) {
                $commandOptions['--with-timestamps'] = true;
            }
            
            // Add WHERE conditions
            if (isset($whereConditions[$table])) {
                foreach ($whereConditions[$table] as $condition) {
                    $commandOptions['--where'][] = $condition;
                }
            }
            
            // Run export command
            $exitCode = Artisan::call('db:export', $commandOptions);
            
            if ($exitCode === 0 && file_exists($filepath)) {
                $exportedFiles[] = $filename;
                
                // Get actual record count from the exported file
                $recordCount = 0;
                if ($format === 'json') {
                    $content = file_get_contents($filepath);
                    $data = json_decode($content, true);
                    $recordCount = is_array($data) ? count($data) : 0;
                } else {
                    // For CSV, count lines minus header
                    $lineCount = count(file($filepath));
                    $recordCount = max(0, $lineCount - 1);
                }
                
                $fileSize = filesize($filepath);
                
                $manifest['tables'][] = [
                    'name' => $table,
                    'file' => $filename,
                    'records' => $recordCount,
                    'size' => $fileSize
                ];
                
                $manifest['statistics'][$table] = $recordCount;
            }
        }
        
        // Save manifest
        $manifestPath = storage_path("app/{$exportDir}/manifest.json");
        file_put_contents($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT));
        
        // Create ZIP archive
        $zipPath = storage_path("app/exports/{$resource}_{$timestamp}.zip");
        
        // Get all files to add
        $filesToAdd = [];
        $exportDirPath = storage_path("app/{$exportDir}");
        
        if (is_dir($exportDirPath)) {
            $files = scandir($exportDirPath);
            foreach ($files as $file) {
                if ($file !== '.' && $file !== '..') {
                    $localPath = $exportDirPath . '/' . $file;
                    if (is_file($localPath)) {
                        $filesToAdd[$file] = $localPath;
                    }
                }
            }
        }
        
        if (empty($filesToAdd)) {
            throw new \Exception("No files to add to ZIP archive");
        }
        
        // Create ZIP using shell command as fallback
        $zip = new ZipArchive();
        $result = $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        
        if ($result === true) {
            foreach ($filesToAdd as $name => $path) {
                $zip->addFile($path, $name);
            }
            $zip->close();
        } else {
            // Fallback to shell command
            $exportDirPath = storage_path("app/{$exportDir}");
            $command = "cd " . escapeshellarg($exportDirPath) . " && zip -r " . escapeshellarg($zipPath) . " .";
            exec($command, $output, $returnCode);
            
            if ($returnCode !== 0) {
                throw new \Exception("Failed to create ZIP archive using both ZipArchive and shell command");
            }
        }
        
        // Verify ZIP was created
        if (!file_exists($zipPath) || filesize($zipPath) === 0) {
            throw new \Exception("ZIP file was not created properly");
        }
        
        // Clean up individual files
        Storage::deleteDirectory($exportDir);
        
        return $zipPath;
    }
    
    /**
     * Get available resources for export
     */
    public static function getAvailableResources(): array
    {
        return array_keys(ResourceDefinitions::getResourceDependencies());
    }
    
    /**
     * Get ZIP error message
     */
    private function getZipError($code): string
    {
        return match($code) {
            ZipArchive::ER_OK => 'No error',
            ZipArchive::ER_MULTIDISK => 'Multi-disk zip archives not supported',
            ZipArchive::ER_RENAME => 'Renaming temporary file failed',
            ZipArchive::ER_CLOSE => 'Closing zip archive failed',
            ZipArchive::ER_SEEK => 'Seek error',
            ZipArchive::ER_READ => 'Read error',
            ZipArchive::ER_WRITE => 'Write error',
            ZipArchive::ER_CRC => 'CRC error',
            ZipArchive::ER_ZIPCLOSED => 'Containing zip archive was closed',
            ZipArchive::ER_NOENT => 'No such file',
            ZipArchive::ER_EXISTS => 'File already exists',
            ZipArchive::ER_OPEN => 'Cannot open file',
            ZipArchive::ER_TMPOPEN => 'Failure to create temporary file',
            ZipArchive::ER_ZLIB => 'Zlib error',
            ZipArchive::ER_MEMORY => 'Memory allocation failure',
            ZipArchive::ER_CHANGED => 'Entry has been changed',
            ZipArchive::ER_COMPNOTSUPP => 'Compression method not supported',
            ZipArchive::ER_EOF => 'Premature EOF',
            ZipArchive::ER_INVAL => 'Invalid argument',
            ZipArchive::ER_NOZIP => 'Not a zip archive',
            ZipArchive::ER_INTERNAL => 'Internal error',
            ZipArchive::ER_INCONS => 'Zip archive inconsistent',
            ZipArchive::ER_REMOVE => 'Cannot remove file',
            ZipArchive::ER_DELETED => 'Entry has been deleted',
            default => 'Unknown error'
        };
    }
}