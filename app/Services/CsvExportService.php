<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Carbon\Carbon;

class CsvExportService
{
    /**
     * Export data to CSV format
     */
    public function export(
        Builder $query,
        array $columns,
        string $filename = null,
        array $headers = null
    ): string {
        $data = $query->get();
        
        if ($data->isEmpty()) {
            throw new \Exception('No data to export');
        }
        
        // Generate filename if not provided
        if (!$filename) {
            $modelName = Str::plural(Str::snake(class_basename($query->getModel())));
            $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
            $filename = "{$modelName}_export_{$timestamp}.csv";
        }
        
        // Ensure filename ends with .csv
        if (!str_ends_with($filename, '.csv')) {
            $filename .= '.csv';
        }
        
        // Create CSV content
        $csvContent = $this->generateCsvContent($data, $columns, $headers);
        
        // Save to storage
        $filePath = storage_path('app/exports/' . $filename);
        $this->ensureDirectoryExists(dirname($filePath));
        
        file_put_contents($filePath, $csvContent);
        
        return $filename;
    }
    
    /**
     * Generate CSV content from data
     */
    private function generateCsvContent(
        Collection $data,
        array $columns,
        array $headers = null
    ): string {
        $output = fopen('php://temp', 'r+');
        
        // Write headers
        $csvHeaders = $headers ?: $this->generateHeaders($columns);
        fputcsv($output, $csvHeaders);
        
        // Write data rows
        foreach ($data as $item) {
            $row = [];
            foreach ($columns as $column) {
                $value = $this->getColumnValue($item, $column);
                $row[] = $this->formatCsvValue($value);
            }
            fputcsv($output, $row);
        }
        
        rewind($output);
        $csvContent = stream_get_contents($output);
        fclose($output);
        
        return $csvContent;
    }
    
    /**
     * Get value for a column from the model
     */
    private function getColumnValue(Model $item, string $column)
    {
        // Handle dot notation for relationships
        if (str_contains($column, '.')) {
            return data_get($item, $column);
        }
        
        // Handle direct attributes
        return $item->getAttribute($column);
    }
    
    /**
     * Format value for CSV output
     */
    private function formatCsvValue($value): string
    {
        if (is_null($value)) {
            return '';
        }
        
        if ($value instanceof Carbon) {
            return $value->format('Y-m-d H:i:s');
        }
        
        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }
        
        if (is_array($value) || is_object($value)) {
            return json_encode($value);
        }
        
        return (string) $value;
    }
    
    /**
     * Generate headers from column names
     */
    private function generateHeaders(array $columns): array
    {
        return array_map(function ($column) {
            // Remove relationship prefixes and convert to title case
            $header = str_replace('.', ' ', $column);
            return Str::title(str_replace('_', ' ', $header));
        }, $columns);
    }
    
    /**
     * Ensure directory exists
     */
    private function ensureDirectoryExists(string $directory): void
    {
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
    }
    
    /**
     * Get file path for download
     */
    public function getFilePath(string $filename): string
    {
        return storage_path('app/exports/' . $filename);
    }
    
    /**
     * Clean up old export files
     */
    public function cleanupOldFiles(int $hoursOld = 24): void
    {
        $exportDir = storage_path('app/exports');
        
        if (!is_dir($exportDir)) {
            return;
        }
        
        $files = glob($exportDir . '/*.csv');
        $cutoffTime = Carbon::now()->subHours($hoursOld)->timestamp;
        
        foreach ($files as $file) {
            if (filemtime($file) < $cutoffTime) {
                unlink($file);
            }
        }
    }
}