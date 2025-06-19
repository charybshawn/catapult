<?php

namespace App\Services\ImportExport;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class ResourceImportService
{
    /**
     * Import all tables for a resource from a ZIP file
     */
    public function importResource(string $zipPath, array $options = []): array
    {
        $mode = $options['mode'] ?? 'insert';
        $validateOnly = $options['validate_only'] ?? false;
        $uniqueColumns = $options['unique_columns'] ?? [];
        
        // Extract ZIP to temporary directory
        $tempDir = 'imports/temp_' . uniqid();
        Storage::makeDirectory($tempDir);
        
        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new \Exception("Cannot open ZIP file");
        }
        
        $zip->extractTo(storage_path("app/{$tempDir}"));
        $zip->close();
        
        // Read manifest
        $manifestPath = storage_path("app/{$tempDir}/manifest.json");
        if (!file_exists($manifestPath)) {
            throw new \Exception("No manifest found in import file");
        }
        
        $manifest = json_decode(file_get_contents($manifestPath), true);
        $resource = $manifest['resource'];
        
        // Get import order
        $tables = ResourceDefinitions::getImportOrder($resource);
        
        $results = [
            'resource' => $resource,
            'imported_at' => now()->toIso8601String(),
            'tables' => [],
            'errors' => [],
            'warnings' => []
        ];
        
        // Begin transaction if not validating
        if (!$validateOnly) {
            DB::beginTransaction();
        }
        
        try {
            foreach ($tables as $table) {
                // Find file for this table
                $file = null;
                foreach ($manifest['tables'] as $tableInfo) {
                    if ($tableInfo['name'] === $table) {
                        $file = $tableInfo['file'];
                        break;
                    }
                }
                
                if (!$file) {
                    continue;
                }
                
                $filepath = storage_path("app/{$tempDir}/{$file}");
                if (!file_exists($filepath)) {
                    $results['warnings'][] = "File not found for table: {$table}";
                    continue;
                }
                
                // Build import command
                $commandOptions = [
                    'table' => $table,
                    'file' => $filepath,
                    '--mode' => $mode,
                ];
                
                if ($validateOnly) {
                    $commandOptions['--validate'] = true;
                }
                
                // Add unique columns if specified
                if (!empty($uniqueColumns)) {
                    foreach ($uniqueColumns as $col) {
                        $commandOptions['--unique-by'][] = $col;
                    }
                }
                
                // Run import command
                $exitCode = Artisan::call('db:import', $commandOptions);
                $output = Artisan::output();
                
                if ($exitCode === 0) {
                    $results['tables'][] = [
                        'name' => $table,
                        'status' => 'success',
                        'message' => trim($output)
                    ];
                } else {
                    $results['errors'][] = "Failed to import {$table}: " . trim($output);
                    
                    if (!$validateOnly) {
                        throw new \Exception("Import failed for table: {$table}");
                    }
                }
            }
            
            if (!$validateOnly) {
                DB::commit();
            }
            
        } catch (\Exception $e) {
            if (!$validateOnly) {
                DB::rollBack();
            }
            
            $results['errors'][] = $e->getMessage();
            
            // Clean up temp directory
            Storage::deleteDirectory($tempDir);
            
            throw $e;
        }
        
        // Clean up temp directory
        Storage::deleteDirectory($tempDir);
        
        return $results;
    }
}