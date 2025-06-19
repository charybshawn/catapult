<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ExportTable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:export 
                            {table : The table name to export}
                            {--format=json : Export format (json or csv)}
                            {--output= : Output file path (defaults to storage/app/exports/)}
                            {--with-timestamps : Include created_at and updated_at columns}
                            {--without-id : Exclude id column from export}
                            {--limit= : Limit number of records}
                            {--where=* : Where conditions in format column:value}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export database table contents to JSON or CSV file';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $table = $this->argument('table');
        $format = strtolower($this->option('format'));
        $withTimestamps = $this->option('with-timestamps');
        $withoutId = $this->option('without-id');
        $limit = $this->option('limit');
        $whereConditions = $this->option('where');
        
        // Validate table exists
        if (!Schema::hasTable($table)) {
            $this->error("Table '{$table}' does not exist!");
            
            // Show available tables
            $tables = DB::select("SHOW TABLES");
            $tableList = array_map(function($t) {
                return array_values((array)$t)[0];
            }, $tables);
            
            $this->info("Available tables:");
            $this->table(['Table Name'], array_map(fn($t) => [$t], $tableList));
            
            return 1;
        }
        
        // Validate format
        if (!in_array($format, ['json', 'csv'])) {
            $this->error("Invalid format. Use 'json' or 'csv'.");
            return 1;
        }
        
        // Build query
        $query = DB::table($table);
        
        // Apply where conditions
        foreach ($whereConditions as $condition) {
            if (str_contains($condition, ':')) {
                $parts = explode(':', $condition, 2);
                if (count($parts) === 2) {
                    [$column, $value] = $parts;
                    
                    // Handle operators
                    if (str_starts_with($column, '>=')) {
                        $column = substr($column, 2);
                        $query->where($column, '>=', $value);
                    } elseif (str_starts_with($column, '<=')) {
                        $column = substr($column, 2);
                        $query->where($column, '<=', $value);
                    } elseif (str_starts_with($column, '>')) {
                        $column = substr($column, 1);
                        $query->where($column, '>', $value);
                    } elseif (str_starts_with($column, '<')) {
                        $column = substr($column, 1);
                        $query->where($column, '<', $value);
                    } elseif (str_contains($value, ',')) {
                        // Handle IN clause for comma-separated values
                        $query->whereIn($column, explode(',', $value));
                    } else {
                        $query->where($column, $value);
                    }
                }
            }
        }
        
        // Apply limit
        if ($limit) {
            $query->limit($limit);
        }
        
        // Get columns to export
        $columns = Schema::getColumnListing($table);
        if (!$withTimestamps) {
            $columns = array_values(array_diff($columns, ['created_at', 'updated_at']));
        }
        if ($withoutId) {
            $columns = array_values(array_diff($columns, ['id']));
        }
        
        // Exclude virtual/generated columns that cannot be inserted
        $virtualColumns = $this->getVirtualColumns($table);
        if (!empty($virtualColumns)) {
            $columns = array_values(array_diff($columns, $virtualColumns));
            $this->info("Excluding virtual columns: " . implode(', ', $virtualColumns));
        }
        
        // Ensure we have columns to export
        if (empty($columns)) {
            $this->error("No columns to export after exclusions.");
            return 1;
        }
        
        // Get data
        $this->info("Exporting table: {$table}");
        $data = $query->select($columns)->get();
        $count = $data->count();
        
        if ($count === 0) {
            $this->warn("No records found to export.");
            return 0;
        }
        
        // Prepare output path
        $timestamp = now()->format('Y-m-d_His');
        $filename = "{$table}_{$timestamp}.{$format}";
        $outputPath = $this->option('output') ?: storage_path("app/exports/{$filename}");
        
        // Ensure directory exists
        $directory = dirname($outputPath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
        
        // Export based on format
        if ($format === 'json') {
            $this->exportJson($data, $outputPath);
        } else {
            $this->exportCsv($data, $outputPath, $columns);
        }
        
        $this->info("Exported {$count} records to: {$outputPath}");
        
        // Show file size
        $size = $this->formatBytes(filesize($outputPath));
        $this->info("File size: {$size}");
        
        return 0;
    }
    
    /**
     * Export data to JSON format
     */
    protected function exportJson($data, $outputPath)
    {
        $json = json_encode($data->toArray(), JSON_PRETTY_PRINT);
        file_put_contents($outputPath, $json);
    }
    
    /**
     * Export data to CSV format
     */
    protected function exportCsv($data, $outputPath, $columns)
    {
        $handle = fopen($outputPath, 'w');
        
        // Write headers
        fputcsv($handle, $columns);
        
        // Write data
        foreach ($data as $row) {
            $rowArray = [];
            foreach ($columns as $column) {
                $value = $row->$column;
                
                // Handle special data types
                if (is_array($value) || is_object($value)) {
                    $value = json_encode($value);
                } elseif (is_bool($value)) {
                    $value = $value ? 'true' : 'false';
                } elseif (is_null($value)) {
                    $value = '';
                }
                
                $rowArray[] = $value;
            }
            fputcsv($handle, $rowArray);
        }
        
        fclose($handle);
    }
    
    /**
     * Format bytes to human readable format
     */
    protected function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
    
    /**
     * Get virtual/generated columns for a table
     */
    protected function getVirtualColumns($table)
    {
        // For now, manually specify known virtual columns per table
        // This is more reliable than querying information_schema
        $tableVirtualColumns = [
            'harvests' => ['average_weight_per_tray', 'week_start_date'],
        ];
        
        return $tableVirtualColumns[$table] ?? [];
    }
}