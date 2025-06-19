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
            
            // Add WHERE conditions for dependent tables
            if (isset($definition['tables'][$table]['foreign_key']) && !empty($whereConditions)) {
                $foreignKey = $definition['tables'][$table]['foreign_key'];
                
                // Get IDs from primary table
                $primaryTable = array_key_first(array_filter($definition['tables'], fn($t) => $t['primary'] ?? false));
                if ($primaryTable && isset($whereConditions[$primaryTable])) {
                    foreach ($whereConditions[$primaryTable] as $condition) {
                        if (str_contains($condition, ':')) {
                            [$column, $value] = explode(':', $condition, 2);
                            $ids = DB::table($primaryTable)->where($column, $value)->pluck('id')->toArray();
                            if (!empty($ids)) {
                                $commandOptions['--where'][] = "{$foreignKey}:" . implode(',', $ids);
                            }
                        }
                    }
                }
            } elseif (isset($whereConditions[$table])) {
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
        
        if ($zip->open($zipPath, ZipArchive::CREATE) === true) {
            // Add all exported files
            foreach (Storage::files($exportDir) as $file) {
                $zip->addFile(storage_path("app/{$file}"), basename($file));
            }
            $zip->close();
            
            // Clean up individual files
            Storage::deleteDirectory($exportDir);
            
            return $zipPath;
        }
        
        throw new \Exception("Failed to create ZIP archive");
    }
    
    /**
     * Get available resources for export
     */
    public static function getAvailableResources(): array
    {
        return array_keys(ResourceDefinitions::getResourceDependencies());
    }
}