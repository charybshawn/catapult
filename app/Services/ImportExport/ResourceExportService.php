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
                
                // Get statistics
                $count = DB::table($table)->count();
                $fileSize = filesize($filepath);
                
                $manifest['tables'][] = [
                    'name' => $table,
                    'file' => $filename,
                    'records' => $count,
                    'size' => $fileSize
                ];
                
                $manifest['statistics'][$table] = $count;
            }
        }
        
        // Save manifest
        $manifestPath = storage_path("app/{$exportDir}/manifest.json");
        file_put_contents($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT));
        
        // Create ZIP archive
        $zipPath = storage_path("app/exports/{$resource}_{$timestamp}.zip");
        $zip = new ZipArchive();
        
        $result = $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        if ($result !== true) {
            throw new \Exception("Failed to create ZIP archive: " . $this->getZipError($result));
        }
        
        // Add all exported files
        foreach (Storage::files($exportDir) as $file) {
            $localPath = storage_path("app/{$file}");
            if (file_exists($localPath)) {
                $zip->addFile($localPath, basename($file));
            }
        }
        
        $zip->close();
        
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