<?php

namespace App\Console\Commands;

if (!defined('STDIN')) {
    define('STDIN', fopen('php://input', 'r'));
}

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use League\Csv\Reader;

class ImportTable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:import 
                            {table : The table name to import into}
                            {file : Path to the file to import}
                            {--format= : Import format (json or csv) - auto-detected if not specified}
                            {--mode=insert : Import mode: replace (truncate first), insert (add new only), upsert (update existing and add new)}
                            {--truncate : Truncate table before import (deprecated - use --mode=replace)}
                            {--validate : Validate data without importing}
                            {--chunk=1000 : Number of records to insert per chunk}
                            {--unique-by=* : Column(s) to determine uniqueness for insert/upsert modes}
                            {--map=* : Column mappings in format source:destination}
                            {--force : Skip confirmation prompts}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import data from JSON or CSV file into database table';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $table = $this->argument('table');
        $file = $this->argument('file');
        $mode = $this->option('mode');
        $truncate = $this->option('truncate');
        $validateOnly = $this->option('validate');
        $chunkSize = (int) $this->option('chunk');
        $uniqueBy = $this->option('unique-by');
        $mappings = $this->parseMappings($this->option('map'));
        
        // Handle deprecated truncate option
        if ($truncate) {
            $this->warn("The --truncate option is deprecated. Use --mode=replace instead.");
            $mode = 'replace';
        }
        
        // Validate mode
        if (!in_array($mode, ['replace', 'insert', 'upsert'])) {
            $this->error("Invalid mode. Use 'replace', 'insert', or 'upsert'.");
            return 1;
        }
        
        // Validate file exists
        if (!file_exists($file)) {
            $this->error("File not found: {$file}");
            return 1;
        }
        
        // Validate table exists
        if (!Schema::hasTable($table)) {
            $this->error("Table '{$table}' does not exist!");
            return 1;
        }
        
        // Handle ZIP files
        $originalFile = $file;
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if ($extension === 'zip') {
            $file = $this->extractZipFile($file);
            if (!$file) {
                $this->error("Failed to extract ZIP file or no suitable files found inside.");
                return 1;
            }
        }
        
        // Auto-detect format if not specified
        $format = $this->option('format');
        if (!$format) {
            $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (in_array($extension, ['json', 'csv'])) {
                $format = $extension;
            } else {
                $this->error("Cannot auto-detect format. Please specify --format=json or --format=csv");
                return 1;
            }
        }
        
        // Validate format
        if (!in_array($format, ['json', 'csv'])) {
            $this->error("Invalid format. Use 'json' or 'csv'.");
            return 1;
        }
        
        $this->info("Importing from: {$file}");
        $this->info("Target table: {$table}");
        $this->info("Format: {$format}");
        $this->info("Mode: {$mode}");
        
        // Get table columns
        $tableColumns = Schema::getColumnListing($table);
        $this->info("Table columns: " . implode(', ', $tableColumns));
        
        // Load data
        try {
            Log::info("Loading data from file: {$file}, format: {$format}");
            if ($format === 'json') {
                $data = $this->loadJsonData($file);
            } else {
                $data = $this->loadCsvData($file);
            }
            Log::info("Loaded data successfully", ['count' => count($data), 'sample' => array_slice($data, 0, 2)]);
        } catch (\Exception $e) {
            Log::error("Error loading file: " . $e->getMessage());
            $this->error("Error loading file: " . $e->getMessage());
            return 1;
        }
        
        if (empty($data)) {
            Log::warning("No data found in file");
            $this->warn("No data found in file.");
            return 0;
        }
        
        $this->info("Found " . count($data) . " records to import.");
        Log::info("Ready to import", ['record_count' => count($data)]);
        
        // Special protection for users table - get admin users early
        $preservedUsers = [];
        if ($table === 'users') {
            $preservedUsers = DB::table('users')
                ->where('email', 'like', '%@gmail.com')
                ->where(function($query) {
                    $query->where('name', 'like', '%admin%')
                          ->orWhere('email', 'charybshawn@gmail.com');
                })
                ->get()
                ->toArray();
            
            if (!empty($preservedUsers)) {
                $this->info("Will preserve " . count($preservedUsers) . " admin user(s) during import.");
            }
        }
        
        // Apply column mappings
        if (!empty($mappings)) {
            $data = $this->applyMappings($data, $mappings);
        }
        
        // Validate data structure
        $validation = $this->validateData($data, $tableColumns);
        if (!$validation['valid']) {
            $this->error("Data validation failed:");
            foreach ($validation['errors'] as $error) {
                $this->error("  - " . $error);
            }
            
            if (!$validateOnly) {
                return 1;
            }
        }
        
        if ($validateOnly) {
            $this->info("Validation complete. Use without --validate to import.");
            return 0;
        }
        
        // Handle different import modes
        if ($mode === 'replace') {
            if (!$this->option('force') && !$this->confirm("This will DELETE ALL existing data in '{$table}'. Continue?")) {
                $this->info("Import cancelled.");
                return 0;
            }
            
            // Admin users protection is handled globally above
            
            try {
                // Try to truncate first (faster if no foreign key constraints)
                if ($table === 'users' && !empty($preservedUsers)) {
                    // For users table with preserved users, use selective delete
                    $preservedIds = array_column($preservedUsers, 'id');
                    DB::table($table)->whereNotIn('id', $preservedIds)->delete();
                    $this->info("Table data deleted (preserving admin users).");
                } else {
                    DB::table($table)->truncate();
                    $this->info("Table truncated.");
                }
            } catch (\Exception $e) {
                // If truncate fails due to foreign key constraints, use delete instead
                if (strpos($e->getMessage(), 'foreign key constraint') !== false || strpos($e->getMessage(), '1701') !== false) {
                    $this->info("Cannot truncate due to foreign key constraints. Using delete instead...");
                    if ($table === 'users' && !empty($preservedUsers)) {
                        $preservedIds = array_column($preservedUsers, 'id');
                        DB::table($table)->whereNotIn('id', $preservedIds)->delete();
                        $this->info("Table data deleted (preserving admin users).");
                    } else {
                        DB::table($table)->delete();
                        $this->info("Table data deleted.");
                    }
                } else {
                    throw $e; // Re-throw if it's a different error
                }
            }
        }
        
        // Determine unique columns for insert/upsert modes
        $uniqueColumns = [];
        if (in_array($mode, ['insert', 'upsert'])) {
            if (!empty($uniqueBy)) {
                $uniqueColumns = $uniqueBy;
            } else {
                // Try to auto-detect unique columns
                $uniqueColumns = $this->detectUniqueColumns($table);
                if (empty($uniqueColumns)) {
                    $this->info("No unique columns detected. Using content-based comparison (slower for large tables).");
                } else {
                    $this->info("Using unique columns: " . implode(', ', $uniqueColumns));
                }
            }
        }
        
        // Detect and exclude generated columns
        $generatedColumns = $this->getGeneratedColumns($table);
        if (!empty($generatedColumns)) {
            $this->info("Excluding generated columns: " . implode(', ', $generatedColumns));
            // Remove generated columns from all data records
            $data = array_map(function($record) use ($generatedColumns) {
                return array_diff_key($record, array_flip($generatedColumns));
            }, $data);
        }

        // Import data
        $this->info("Importing data...");
        $progressBar = $this->output->createProgressBar(count($data));
        $progressBar->start();
        
        $imported = 0;
        $skipped = 0;
        $updated = 0;
        $failed = 0;
        
        foreach (array_chunk($data, $chunkSize) as $chunkIndex => $chunk) {
            try {
                Log::info("Processing chunk {$chunkIndex}", ['chunk_size' => count($chunk), 'mode' => $mode]);
                switch ($mode) {
                    case 'replace':
                        if ($table === 'users') {
                            // Filter out records that would conflict with preserved admin users
                            $preservedEmails = [];
                            if (isset($preservedUsers) && !empty($preservedUsers)) {
                                $preservedEmails = array_column($preservedUsers, 'email');
                            }
                            
                            $filteredChunk = array_filter($chunk, function($record) use ($preservedEmails) {
                                return !in_array($record['email'] ?? '', $preservedEmails);
                            });
                            
                            if (count($filteredChunk) < count($chunk)) {
                                $skippedCount = count($chunk) - count($filteredChunk);
                                $this->info("Skipped {$skippedCount} user(s) to preserve admin accounts.");
                                $skipped += $skippedCount;
                            }
                            
                            if (!empty($filteredChunk)) {
                                try {
                                    DB::table($table)->insert(array_values($filteredChunk));
                                    $imported += count($filteredChunk);
                                } catch (\Exception $e) {
                                    $this->error("Failed to insert chunk: " . $e->getMessage());
                                    $failed += count($filteredChunk);
                                }
                            }
                        } else {
                            try {
                                DB::table($table)->insert($chunk);
                                $imported += count($chunk);
                            } catch (\Exception $e) {
                                $this->error("Failed to insert chunk: " . $e->getMessage());
                                $failed += count($chunk);
                            }
                        }
                        Log::info("Inserted chunk via replace mode", ['records' => count($chunk)]);
                        break;
                        
                    case 'insert':
                        foreach ($chunk as $recordIndex => $record) {
                            $exists = $this->recordExists($table, $record, $uniqueColumns);
                            if (!$exists) {
                                try {
                                    DB::table($table)->insert($record);
                                    $imported++;
                                    Log::debug("Inserted record {$recordIndex}");
                                } catch (\Exception $e) {
                                    $this->error("Failed to insert record {$recordIndex}: " . $e->getMessage());
                                    $failed++;
                                }
                            } else {
                                $skipped++;
                                Log::debug("Skipped existing record {$recordIndex}");
                            }
                        }
                        break;
                        
                    case 'upsert':
                        foreach ($chunk as $record) {
                            $where = [];
                            foreach ($uniqueColumns as $col) {
                                if (isset($record[$col])) {
                                    $where[$col] = $record[$col];
                                }
                            }
                            
                            if (!empty($where)) {
                                try {
                                    $existing = DB::table($table)->where($where)->exists();
                                    if ($existing) {
                                        DB::table($table)->where($where)->update($record);
                                        $updated++;
                                    } else {
                                        DB::table($table)->insert($record);
                                        $imported++;
                                    }
                                } catch (\Exception $e) {
                                    $this->error("Failed to upsert record: " . $e->getMessage());
                                    $failed++;
                                }
                            } else {
                                try {
                                    DB::table($table)->insert($record);
                                    $imported++;
                                } catch (\Exception $e) {
                                    $this->error("Failed to insert record: " . $e->getMessage());
                                    $failed++;
                                }
                            }
                        }
                        break;
                }
            } catch (\Exception $e) {
                $failed += count($chunk);
                Log::error("Error importing chunk {$chunkIndex}: " . $e->getMessage(), [
                    'chunk_size' => count($chunk),
                    'sample_record' => isset($chunk[0]) ? $chunk[0] : null,
                    'stack_trace' => $e->getTraceAsString()
                ]);
                $this->newLine();
                $this->error("Error importing chunk: " . $e->getMessage());
            }
            
            $progressBar->advance(count($chunk));
        }
        
        $progressBar->finish();
        $this->newLine(2);
        
        $this->info("Import complete for table: {$table}");
        $this->info("Mode: {$mode}");
        
        Log::info("Import completed", [
            'mode' => $mode,
            'imported' => $imported,
            'skipped' => $skipped,
            'updated' => $updated,
            'failed' => $failed,
            'table' => $table
        ]);
        
        if ($imported > 0) {
            $this->info("Successfully imported: {$imported} records to {$table}");
        }
        
        if ($skipped > 0) {
            $this->info("Skipped existing: {$skipped} records in {$table}");
        }
        
        if ($updated > 0) {
            $this->info("Updated existing: {$updated} records in {$table}");
        }
        
        if ($failed > 0) {
            $this->error("Failed to import: {$failed} records to {$table}");
        }
        
        // Clean up extracted files if we extracted from ZIP
        if ($originalFile !== $file && strpos($file, sys_get_temp_dir()) === 0) {
            $extractDir = dirname($file);
            $this->deleteDirectory($extractDir);
        }
        
        // Return non-zero exit code if there were failures
        return $failed > 0 ? 1 : 0;
    }
    
    /**
     * Load data from JSON file
     */
    protected function loadJsonData($file)
    {
        $content = file_get_contents($file);
        $data = json_decode($content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception("Invalid JSON: " . json_last_error_msg());
        }
        
        // Ensure we have an array of records
        if (!is_array($data)) {
            throw new \Exception("JSON must contain an array of records");
        }
        
        // If it's a single record, wrap it in an array
        if (!isset($data[0]) && !empty($data)) {
            $data = [$data];
        }
        
        return $data;
    }
    
    /**
     * Load data from CSV file
     */
    protected function loadCsvData($file)
    {
        $data = [];
        $handle = fopen($file, 'r');
        
        if (!$handle) {
            throw new \Exception("Cannot open CSV file");
        }
        
        // Get headers from first row
        $headers = fgetcsv($handle);
        if (!$headers) {
            throw new \Exception("CSV file is empty or invalid");
        }
        
        // Read data rows
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) !== count($headers)) {
                continue; // Skip malformed rows
            }
            
            $record = array_combine($headers, $row);
            
            // Convert string representations back to proper types
            foreach ($record as $key => $value) {
                if ($value === '') {
                    $record[$key] = null;
                } elseif ($value === 'true') {
                    $record[$key] = true;
                } elseif ($value === 'false') {
                    $record[$key] = false;
                } elseif (is_numeric($value) && !str_contains($value, '.')) {
                    $record[$key] = (int) $value;
                } elseif (is_numeric($value)) {
                    $record[$key] = (float) $value;
                } elseif ($this->isJson($value)) {
                    $record[$key] = json_decode($value, true);
                }
            }
            
            $data[] = $record;
        }
        
        fclose($handle);
        return $data;
    }
    
    /**
     * Check if a string is valid JSON
     */
    protected function isJson($string)
    {
        if (!is_string($string)) {
            return false;
        }
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }
    
    /**
     * Parse column mappings
     */
    protected function parseMappings($mappings)
    {
        $parsed = [];
        foreach ($mappings as $mapping) {
            if (str_contains($mapping, ':')) {
                [$source, $destination] = explode(':', $mapping, 2);
                $parsed[$source] = $destination;
            }
        }
        return $parsed;
    }
    
    /**
     * Apply column mappings to data
     */
    protected function applyMappings($data, $mappings)
    {
        return array_map(function ($record) use ($mappings) {
            $mapped = [];
            foreach ($record as $key => $value) {
                $newKey = $mappings[$key] ?? $key;
                $mapped[$newKey] = $value;
            }
            return $mapped;
        }, $data);
    }
    
    /**
     * Validate data against table structure
     */
    protected function validateData($data, $tableColumns)
    {
        $errors = [];
        $valid = true;
        
        if (empty($data)) {
            $errors[] = "No data to validate";
            return ['valid' => false, 'errors' => $errors];
        }
        
        // Check first record for column compatibility
        $firstRecord = $data[0];
        $dataColumns = array_keys($firstRecord);
        
        // Find columns in data that don't exist in table
        $unknownColumns = array_diff($dataColumns, $tableColumns);
        if (!empty($unknownColumns)) {
            $errors[] = "Unknown columns in data: " . implode(', ', $unknownColumns);
            $valid = false;
        }
        
        // Find required columns (we'll check for 'id' as potentially required)
        $missingColumns = array_diff($tableColumns, $dataColumns);
        $ignoredColumns = ['id', 'created_at', 'updated_at', 'deleted_at'];
        $missingRequired = array_diff($missingColumns, $ignoredColumns);
        
        if (!empty($missingRequired)) {
            $this->warn("Missing columns (will be set to NULL): " . implode(', ', $missingRequired));
        }
        
        return ['valid' => $valid, 'errors' => $errors];
    }
    
    /**
     * Check if a record exists based on unique columns or content hash
     */
    protected function recordExists($table, $record, $uniqueColumns)
    {
        // Remove auto-increment and timestamp fields for comparison
        $compareRecord = $this->prepareRecordForComparison($record);
        
        if (!empty($uniqueColumns)) {
            // Use specified unique columns
            $query = DB::table($table);
            
            foreach ($uniqueColumns as $column) {
                if (isset($record[$column])) {
                    $query->where($column, $record[$column]);
                }
            }
            
            return $query->exists();
        } else {
            // No unique columns, use content hash comparison
            // Get all records from the table (this could be optimized for large tables)
            $existingRecords = DB::table($table)->get();
            
            foreach ($existingRecords as $existingRecord) {
                $existingArray = (array) $existingRecord;
                $existingCompare = $this->prepareRecordForComparison($existingArray);
                
                // Compare content hashes
                if ($this->getRecordHash($compareRecord) === $this->getRecordHash($existingCompare)) {
                    return true;
                }
            }
            
            return false;
        }
    }
    
    /**
     * Prepare record for comparison by removing auto-generated fields
     */
    protected function prepareRecordForComparison($record)
    {
        // Remove fields that are auto-generated or system-managed
        $excludeFields = ['id', 'created_at', 'updated_at', 'deleted_at'];
        
        $compareRecord = $record;
        foreach ($excludeFields as $field) {
            unset($compareRecord[$field]);
        }
        
        // Sort array by keys for consistent hashing
        ksort($compareRecord);
        
        return $compareRecord;
    }
    
    /**
     * Generate a hash of record content for comparison
     */
    protected function getRecordHash($record)
    {
        // Use JSON encoding for consistent string representation
        return md5(json_encode($record));
    }
    
    /**
     * Try to detect unique columns for a table
     */
    protected function detectUniqueColumns($table)
    {
        $uniqueColumns = [];
        
        // Table-specific unique column detection
        switch ($table) {
            case 'harvests':
                // For harvests, use combination of cultivar, user, and harvest date
                // as these together should be unique for a harvest entry
                $uniqueColumns = ['master_cultivar_id', 'user_id', 'harvest_date'];
                break;
            
            default:
                // Common unique column names (excluding 'id' since it's auto-increment)
                $commonUnique = ['uuid', 'email', 'username', 'slug', 'sku', 'code', 'reference', 'external_id'];
                
                $tableColumns = Schema::getColumnListing($table);
                
                foreach ($commonUnique as $column) {
                    if (in_array($column, $tableColumns)) {
                        $uniqueColumns[] = $column;
                        break; // Use first found
                    }
                }
                break;
        }
        
        return $uniqueColumns;
    }
    
    /**
     * Extract ZIP file and return the path to the first JSON/CSV file found
     */
    protected function extractZipFile($zipPath)
    {
        if (!class_exists('ZipArchive')) {
            throw new \Exception("ZipArchive class not available. Please enable the zip extension.");
        }
        
        $zip = new \ZipArchive;
        $result = $zip->open($zipPath);
        
        if ($result !== TRUE) {
            throw new \Exception("Cannot open ZIP file: " . $zipPath);
        }
        
        $extractPath = sys_get_temp_dir() . '/import_' . uniqid();
        mkdir($extractPath, 0755, true);
        
        $zip->extractTo($extractPath);
        $zip->close();
        
        // Find the first JSON or CSV file
        $files = glob($extractPath . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                if (in_array($ext, ['json', 'csv'])) {
                    $this->info("Extracted file: " . basename($file));
                    return $file;
                }
            }
        }
        
        // Clean up
        $this->deleteDirectory($extractPath);
        return null;
    }
    
    /**
     * Get generated/virtual columns for a table
     */
    protected function getGeneratedColumns($table)
    {
        try {
            $columns = DB::select("SHOW COLUMNS FROM `{$table}`");
            $generatedColumns = [];
            
            foreach ($columns as $column) {
                // Check if the column has VIRTUAL or STORED in the Extra field
                if (isset($column->Extra) && 
                    (str_contains(strtoupper($column->Extra), 'VIRTUAL') || 
                     str_contains(strtoupper($column->Extra), 'STORED'))) {
                    $generatedColumns[] = $column->Field;
                }
            }
            
            return $generatedColumns;
        } catch (\Exception $e) {
            $this->warn("Could not detect generated columns for table {$table}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Recursively delete a directory
     */
    protected function deleteDirectory($dir)
    {
        if (!is_dir($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
}