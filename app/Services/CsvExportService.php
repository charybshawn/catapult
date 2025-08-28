<?php

namespace App\Services;

use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * Agricultural data CSV export service with intelligent data filtering.
 * 
 * Provides comprehensive CSV export functionality for agricultural data including
 * crops, products, orders, and inventory information. Features intelligent empty
 * column filtering, relationship data handling, and agricultural-specific formatting
 * for reports and data analysis.
 *
 * @business_domain Agricultural data export and reporting
 * @used_by Filament resources, bulk export actions, reporting systems
 * @file_management Automatic cleanup of old export files
 * @agricultural_context Handles agricultural data formats and relationships
 */
class CsvExportService
{
    /**
     * Export agricultural data to CSV format with intelligent optimization.
     * 
     * Generates CSV exports from database queries with automatic filename generation,
     * empty column filtering, and agricultural data formatting. Supports complex
     * relationships and handles agricultural-specific data types appropriately.
     *
     * @param Builder $query Database query builder for data to export
     * @param array $columns Column names to include in export
     * @param string|null $filename Custom filename (auto-generated if null)
     * @param array|null $headers Custom headers (auto-generated if null)
     * @return string Generated filename for download
     * @throws Exception If no data available to export
     * @agricultural_context Optimized for agricultural data relationships and formats
     * @optimization Automatically filters mostly empty columns to reduce clutter
     */
    public function export(
        Builder $query,
        array $columns,
        string $filename = null,
        array $headers = null
    ): string {
        $data = $query->get();
        
        if ($data->isEmpty()) {
            throw new Exception('No data to export');
        }
        
        // Filter out columns that are mostly empty (optional optimization)
        $filteredColumns = $this->filterMostlyEmptyColumns($data, $columns);
        
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
        $csvContent = $this->generateCsvContent($data, $filteredColumns, $headers);
        
        // Save to storage
        $filePath = storage_path('app/exports/' . $filename);
        $this->ensureDirectoryExists(dirname($filePath));
        
        file_put_contents($filePath, $csvContent);
        
        return $filename;
    }
    
    /**
     * Generate properly formatted CSV content from agricultural data.
     * 
     * Creates CSV content with appropriate formatting for agricultural data types
     * including dates, boolean values, JSON fields, and relationship data.
     *
     * @param Collection $data Data collection to export
     * @param array $columns Column names to include
     * @param array|null $headers Custom headers (auto-generated if null)
     * @return string Properly formatted CSV content
     * @agricultural_context Handles agricultural data formatting requirements
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
     * Extract column value from agricultural model with intelligent null handling.
     * 
     * Handles dot notation for relationships, differentiates between meaningful
     * zeros and null values, and applies agricultural context for proper
     * data interpretation.
     *
     * @param Model $item Agricultural model instance
     * @param string $column Column name (supports dot notation for relationships)
     * @return mixed Column value with appropriate null handling
     * @agricultural_context Preserves meaningful agricultural data like quantities and IDs
     */
    private function getColumnValue(Model $item, string $column)
    {
        // Handle dot notation for relationships
        if (str_contains($column, '.')) {
            $value = data_get($item, $column);
            
            // Return null for truly empty values
            if ($value === null || $value === '' || $value === 0) {
                return null;
            }
            
            return $value;
        }
        
        // Handle direct attributes
        $value = $item->getAttribute($column);
        
        // Handle specific column types more intelligently
        if ($value === null) {
            return null;
        }
        
        // Keep empty strings as empty for text fields that might legitimately be empty
        if ($value === '') {
            // Only convert to null for non-essential text fields
            $textFields = ['notes', 'description', 'comments'];
            if (in_array($column, $textFields)) {
                return null;
            }
            return $value;
        }
        
        // Handle zero values - keep zeros for numeric fields that should show zero
        if ($value === 0) {
            $numericFields = ['id', 'tray_number', 'tray_count', 'total_weight_grams', 'quantity', 'price'];
            if (in_array($column, $numericFields)) {
                return $value;
            }
            // Convert other zero values to null (like foreign keys that are 0)
            return null;
        }
        
        return $value;
    }
    
    /**
     * Format values for CSV output with agricultural data considerations.
     * 
     * Applies appropriate formatting for agricultural data types including
     * dates, booleans, JSON fields, and null values.
     *
     * @param mixed $value Raw value from model
     * @return string Formatted value for CSV output
     * @agricultural_context Formats dates and booleans for agricultural reporting
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
     * Get full file path for agricultural data export download.
     * 
     * Returns complete file path for generated agricultural data exports
     * in the storage/app/exports directory.
     *
     * @param string $filename Export filename
     * @return string Complete file path for download
     * @agricultural_context Used for downloading agricultural data exports
     */
    public function getFilePath(string $filename): string
    {
        return storage_path('app/exports/' . $filename);
    }
    
    /**
     * Intelligently filter empty columns to optimize agricultural data exports.
     * 
     * Removes columns that are mostly empty (80%+ null values) while preserving
     * essential agricultural data fields like IDs and timestamps. Uses sampling
     * for performance on large datasets.
     *
     * @param Collection $data Data collection to analyze
     * @param array $columns Column names to evaluate
     * @return array Filtered column list with empty columns removed
     * @agricultural_context Preserves essential agricultural data fields regardless of emptiness
     * @performance Uses sampling to avoid analyzing entire large datasets
     */
    private function filterMostlyEmptyColumns(Collection $data, array $columns): array
    {
        // Don't filter if we have very few records
        if ($data->count() < 10) {
            return $columns;
        }
        
        $filtered = [];
        $threshold = 0.8; // Remove columns that are 80% or more empty
        
        foreach ($columns as $column) {
            $nonEmptyCount = 0;
            
            // Sample first 20 records to check for emptiness
            $sampleSize = min(20, $data->count());
            for ($i = 0; $i < $sampleSize; $i++) {
                $value = $this->getColumnValue($data[$i], $column);
                if ($value !== null && $value !== '') {
                    $nonEmptyCount++;
                }
            }
            
            $emptyRatio = 1 - ($nonEmptyCount / $sampleSize);
            
            // Keep columns that have some data or are essential fields
            $essentialFields = ['id', 'name', 'created_at', 'updated_at'];
            if ($emptyRatio < $threshold || in_array($column, $essentialFields)) {
                $filtered[] = $column;
            }
        }
        
        return $filtered;
    }
    
    /**
     * Clean up old agricultural data export files to manage storage.
     * 
     * Removes CSV export files older than specified threshold to prevent
     * storage accumulation from frequent agricultural data exports.
     *
     * @param int $hoursOld Age threshold in hours (default: 24)
     * @return void
     * @agricultural_context Manages storage for frequent agricultural export operations
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