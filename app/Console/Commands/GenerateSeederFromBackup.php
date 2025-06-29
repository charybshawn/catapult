<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class GenerateSeederFromBackup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:generate-seeders 
                            {file : The backup SQL file path}
                            {--tables= : Comma-separated list of tables to extract (optional)}
                            {--exclude= : Comma-separated list of tables to exclude}
                            {--output= : Output directory for seeders (default: database/seeders)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate Laravel seeders from an SQL backup file';

    private array $excludeDefaultTables = [
        'migrations',
        'password_reset_tokens', 
        'sessions',
        'cache',
        'cache_locks',
        'failed_jobs',
        'job_batches',
        'jobs',
        'personal_access_tokens',
        'activity_log'
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filePath = $this->argument('file');
        $outputDir = $this->option('output') ?: database_path('seeders');
        
        if (!File::exists($filePath)) {
            $this->error("Backup file not found: {$filePath}");
            return 1;
        }

        $this->info("Processing backup file: {$filePath}");

        // Parse the SQL file and extract table data
        $tableData = $this->parseSqlFile($filePath);
        
        if (empty($tableData)) {
            $this->error("No table data found in backup file");
            return 1;
        }

        // Filter tables based on options
        $tables = $this->filterTables(array_keys($tableData));
        
        $this->info("Found " . count($tables) . " tables to process");

        // Generate seeders for each table
        $generatedSeeders = [];
        foreach ($tables as $table) {
            if (!isset($tableData[$table])) {
                continue;
            }

            $seederPath = $this->generateSeeder($table, $tableData[$table], $outputDir);
            if ($seederPath) {
                $generatedSeeders[] = $seederPath;
                $this->info("Generated seeder: {$seederPath}");
            }
        }

        // Generate a master seeder that calls all individual seeders
        $this->generateMasterSeeder($generatedSeeders, $outputDir);

        $this->info("Successfully generated " . count($generatedSeeders) . " seeders!");
        $this->line("Run: php artisan db:seed --class=RogueSpyBackupSeeder");

        return 0;
    }

    private function parseSqlFile(string $filePath): array
    {
        $content = File::get($filePath);
        $tableData = [];
        $currentTable = null;
        $insideInsert = false;
        $insertData = '';

        // Split content by lines for processing
        $lines = explode("\n", $content);
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Skip comments and empty lines
            if (empty($line) || str_starts_with($line, '--') || str_starts_with($line, '/*')) {
                continue;
            }

            // Detect table creation
            if (preg_match('/CREATE TABLE `([^`]+)`/', $line, $matches)) {
                $currentTable = $matches[1];
                continue;
            }

            // Detect start of INSERT statements
            if (preg_match('/INSERT INTO `([^`]+)`/', $line, $matches)) {
                $currentTable = $matches[1];
                $insideInsert = true;
                $insertData = $line;
                
                // Check if the INSERT statement is complete on this line
                if (str_ends_with($line, ';')) {
                    $this->processInsertStatement($currentTable, $insertData, $tableData);
                    $insideInsert = false;
                    $insertData = '';
                }
                continue;
            }

            // Continue collecting INSERT data if we're inside an INSERT statement
            if ($insideInsert) {
                $insertData .= ' ' . $line;
                
                // Check if this line completes the INSERT statement
                if (str_ends_with($line, ';')) {
                    $this->processInsertStatement($currentTable, $insertData, $tableData);
                    $insideInsert = false;
                    $insertData = '';
                }
            }
        }

        return $tableData;
    }

    private function processInsertStatement(string $table, string $insertSql, array &$tableData): void
    {
        // Parse INSERT statement to extract column names and values
        if (preg_match('/INSERT INTO `[^`]+` \(([^)]+)\) VALUES (.+);/', $insertSql, $matches)) {
            $columnsStr = $matches[1];
            $valuesStr = $matches[2];

            // Parse column names
            $columns = array_map(function($col) {
                return trim($col, '` ');
            }, explode(',', $columnsStr));

            // Parse VALUES - this is complex because values can contain commas, quotes, etc.
            $rows = $this->parseValuesClause($valuesStr);

            if (!isset($tableData[$table])) {
                $tableData[$table] = [
                    'columns' => $columns,
                    'rows' => []
                ];
            }

            foreach ($rows as $row) {
                $tableData[$table]['rows'][] = $row;
            }
        }
    }

    private function parseValuesClause(string $valuesStr): array
    {
        $rows = [];
        $current = '';
        $inQuotes = false;
        $quoteChar = '';
        $parenLevel = 0;

        for ($i = 0; $i < strlen($valuesStr); $i++) {
            $char = $valuesStr[$i];
            $nextChar = $i + 1 < strlen($valuesStr) ? $valuesStr[$i + 1] : '';
            $prevChar = $i > 0 ? $valuesStr[$i - 1] : '';

            if (!$inQuotes) {
                if ($char === '(' && $parenLevel === 0) {
                    $parenLevel = 1;
                    continue;
                } elseif ($char === ')' && $parenLevel === 1) {
                    $parenLevel = 0;
                    // Parse the row values
                    $rowValues = $this->parseRowValues($current);
                    if (!empty($rowValues)) {
                        $rows[] = $rowValues;
                    }
                    $current = '';
                    continue;
                } elseif ($char === "'" && $prevChar !== '\\') {
                    $inQuotes = true;
                    $quoteChar = $char;
                }
            } else {
                // Handle escaped quotes
                if ($char === $quoteChar && $prevChar !== '\\') {
                    $inQuotes = false;
                    $quoteChar = '';
                }
            }

            if ($parenLevel === 1) {
                $current .= $char;
            }
        }

        return $rows;
    }

    private function parseRowValues(string $rowStr): array
    {
        $values = [];
        $current = '';
        $inQuotes = false;
        $quoteChar = '';

        for ($i = 0; $i < strlen($rowStr); $i++) {
            $char = $rowStr[$i];
            $prevChar = $i > 0 ? $rowStr[$i - 1] : '';

            if (!$inQuotes) {
                if ($char === "'" && $prevChar !== '\\') {
                    $inQuotes = true;
                    $quoteChar = $char;
                    continue;
                } elseif ($char === ',') {
                    $values[] = $this->normalizeValue(trim($current));
                    $current = '';
                    continue;
                }
            } else {
                if ($char === $quoteChar && $prevChar !== '\\') {
                    $inQuotes = false;
                    $quoteChar = '';
                    continue;
                }
                // Handle escaped quotes (\')
                if ($char === "'" && $prevChar === '\\') {
                    // Remove the backslash and add the quote
                    $current = substr($current, 0, -1) . "'";
                    continue;
                }
            }

            $current .= $char;
        }

        // Add the last value
        if (!empty($current) || $current === 'unit') {
            $values[] = $this->normalizeValue(trim($current));
        }

        return $values;
    }

    private function normalizeValue(string $value): mixed
    {
        if ($value === 'NULL') {
            return null;
        }
        
        if (is_numeric($value)) {
            return str_contains($value, '.') ? (float) $value : (int) $value;
        }

        // Handle boolean values
        if ($value === '1' || $value === 'unit') {
            return (int) $value;
        }

        return $value;
    }

    private function normalizeRowForTable(string $table, array $columns, array $row): array
    {
        $normalizedRow = [];
        
        foreach ($columns as $index => $column) {
            $value = $row[$index] ?? null;
            
            // Handle specific schema differences
            if ($table === 'users' && $column === 'wholesale_discount_percentage' && $value === null) {
                $value = 0.00; // Set default for NOT NULL field
            }
            
            // Handle master_cultivars schema difference: cultivar_name -> name
            if ($table === 'master_cultivars' && $column === 'cultivar_name') {
                $column = 'name'; // Map to correct column name
            }
            
            $normalizedRow[$column] = $value;
        }
        
        return $normalizedRow;
    }

    private function filterTables(array $tables): array
    {
        $includeTables = $this->option('tables');
        $excludeTables = $this->option('exclude');

        // Start with all tables
        $filtered = $tables;

        // Apply include filter if specified
        if ($includeTables) {
            $includeList = array_map('trim', explode(',', $includeTables));
            $filtered = array_intersect($filtered, $includeList);
        }

        // Apply exclude filter
        $excludeList = $this->excludeDefaultTables;
        if ($excludeTables) {
            $excludeList = array_merge($excludeList, array_map('trim', explode(',', $excludeTables)));
        }
        
        $filtered = array_diff($filtered, $excludeList);

        return array_values($filtered);
    }

    private function generateSeeder(string $table, array $tableData, string $outputDir): ?string
    {
        if (empty($tableData['rows'])) {
            $this->warn("Skipping table '{$table}' - no data found");
            return null;
        }

        $className = Str::studly($table) . 'TableSeeder';
        $seederPath = $outputDir . '/' . $className . '.php';

        // Generate the seeder content
        $content = $this->generateSeederContent($className, $table, $tableData);

        // Ensure output directory exists
        if (!File::exists($outputDir)) {
            File::makeDirectory($outputDir, 0755, true);
        }

        File::put($seederPath, $content);

        return $className;
    }

    private function generateSeederContent(string $className, string $table, array $tableData): string
    {
        $columns = $tableData['columns'];
        $rows = $tableData['rows'];

        // Build the data array
        $dataCode = "[\n";
        foreach ($rows as $row) {
            $dataCode .= "            [\n";
            
            // Normalize row data for schema compatibility
            $normalizedRow = $this->normalizeRowForTable($table, $columns, $row);
            
            foreach ($normalizedRow as $column => $value) {
                $dataCode .= "                '{$column}' => " . $this->formatPhpValue($value) . ",\n";
            }
            $dataCode .= "            ],\n";
        }
        $dataCode .= "        ]";

        return <<<PHP
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class {$className} extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Disable foreign key checks temporarily
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        // Clear existing data
        DB::table('{$table}')->truncate();

        // Insert data
        DB::table('{$table}')->insert({$dataCode});
        
        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
PHP;
    }

    private function formatPhpValue(mixed $value): string
    {
        if ($value === null) {
            return 'null';
        }
        
        if (is_string($value)) {
            // Check if the string is a JSON array
            if (str_starts_with($value, '[') && str_ends_with($value, ']')) {
                $decoded = json_decode($value);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    // It's a valid JSON array, format it as PHP array
                    $phpArray = [];
                    foreach ($decoded as $item) {
                        $phpArray[] = "'" . addslashes($item) . "'";
                    }
                    return '[' . implode(', ', $phpArray) . ']';
                }
            }
            
            return "'" . addslashes($value) . "'";
        }
        
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        
        return (string) $value;
    }

    private function generateMasterSeeder(array $seederClasses, string $outputDir): void
    {
        $className = 'RogueSpyBackupSeeder';
        $seederPath = $outputDir . '/' . $className . '.php';

        $callsCode = '';
        foreach ($seederClasses as $seederClass) {
            $callsCode .= "        \$this->call({$seederClass}::class);\n";
        }

        $content = <<<PHP
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class {$className} extends Seeder
{
    /**
     * Seed the application's database with data from RogueSpyBackup.
     */
    public function run(): void
    {
{$callsCode}
    }
}
PHP;

        File::put($seederPath, $content);
        $this->info("Generated master seeder: {$className}");
    }
}
