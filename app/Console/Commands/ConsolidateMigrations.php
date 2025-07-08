<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ConsolidateMigrations extends Command
{
    protected $signature = 'migrations:consolidate {--dry-run : Show what would be done without actually doing it}';
    protected $description = 'Generate consolidated migration files - one per table';

    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        
        if ($isDryRun) {
            $this->info('🔍 DRY RUN MODE - No files will be created');
        }
        
        $this->info('📋 Analyzing current database schema...');
        
        // Get all tables
        $tables = $this->getAllTables();
        
        $this->info("Found {$tables->count()} tables to consolidate");
        
        foreach ($tables as $table) {
            $this->info("Processing table: {$table}");
            
            if (!$isDryRun) {
                $this->generateTableMigration($table);
            } else {
                $this->line("  → Would generate: database/migrations/consolidated/create_{$table}_table.php");
            }
        }
        
        if (!$isDryRun) {
            $this->info('✅ Consolidated migrations created in database/migrations/consolidated/');
            $this->warn('⚠️  Remember to:');
            $this->warn('   1. Review the generated files');
            $this->warn('   2. Test on a copy of your database');
            $this->warn('   3. Backup your data before using these');
        }
    }
    
    private function getAllTables()
    {
        $database = config('database.connections.mysql.database');
        
        return collect(DB::select("
            SELECT TABLE_NAME 
            FROM INFORMATION_SCHEMA.TABLES 
            WHERE TABLE_SCHEMA = ? 
            AND TABLE_TYPE = 'BASE TABLE'
            AND TABLE_NAME NOT IN ('migrations', 'failed_jobs', 'cache', 'cache_locks', 'sessions')
            ORDER BY TABLE_NAME
        ", [$database]))->pluck('TABLE_NAME');
    }
    
    private function generateTableMigration($table)
    {
        $className = 'Create' . Str::studly($table) . 'Table';
        $timestamp = date('Y_m_d_His', time() + $this->getTableOrder($table));
        
        $migrationPath = database_path("migrations/consolidated/{$timestamp}_create_{$table}_table.php");
        
        // Create consolidated directory if it doesn't exist
        $dir = dirname($migrationPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        $schema = $this->getTableSchema($table);
        
        $content = $this->generateMigrationContent($className, $table, $schema);
        
        file_put_contents($migrationPath, $content);
        
        $this->line("  ✅ Created: {$migrationPath}");
    }
    
    private function getTableOrder($table)
    {
        // Define order for tables with dependencies
        $order = [
            'users' => 1,
            'roles' => 2,
            'permissions' => 3,
            'suppliers' => 10,
            'consumables' => 20,
            'products' => 30,
            'orders' => 40,
            'crops' => 50,
            // Add more as needed
        ];
        
        return $order[$table] ?? 100;
    }
    
    private function getTableSchema($table)
    {
        return DB::select("DESCRIBE {$table}");
    }
    
    private function generateMigrationContent($className, $table, $schema)
    {
        $fields = $this->generateFields($schema);
        
        return "<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('{$table}', function (Blueprint \$table) {
{$fields}
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('{$table}');
    }
};";
    }
    
    private function generateFields($schema)
    {
        $fields = [];
        
        foreach ($schema as $column) {
            $field = $this->convertColumnToMigration($column);
            if ($field) {
                $fields[] = "            {$field}";
            }
        }
        
        return implode("\n", $fields);
    }
    
    private function convertColumnToMigration($column)
    {
        $name = $column->Field;
        $type = $column->Type;
        $null = $column->Null;
        $default = $column->Default;
        $extra = $column->Extra;
        
        // Handle common types
        if ($name === 'id' && str_contains($extra, 'auto_increment')) {
            return "\$table->id();";
        }
        
        if ($name === 'created_at' || $name === 'updated_at') {
            return null; // Will be handled by timestamps()
        }
        
        if ($name === 'created_at' && $name === 'updated_at') {
            return "\$table->timestamps();";
        }
        
        // Parse type
        if (preg_match('/^varchar\((\d+)\)/', $type, $matches)) {
            $length = $matches[1];
            $field = "\$table->string('{$name}', {$length})";
        } elseif (str_contains($type, 'bigint') && str_contains($type, 'unsigned')) {
            $field = "\$table->unsignedBigInteger('{$name}')";
        } elseif (str_contains($type, 'int')) {
            $field = "\$table->integer('{$name}')";
        } elseif (str_contains($type, 'decimal')) {
            preg_match('/decimal\((\d+),(\d+)\)/', $type, $matches);
            $precision = $matches[1] ?? 8;
            $scale = $matches[2] ?? 2;
            $field = "\$table->decimal('{$name}', {$precision}, {$scale})";
        } elseif (str_contains($type, 'text')) {
            $field = "\$table->text('{$name}')";
        } elseif (str_contains($type, 'json')) {
            $field = "\$table->json('{$name}')";
        } elseif (str_contains($type, 'timestamp')) {
            $field = "\$table->timestamp('{$name}')";
        } elseif (str_contains($type, 'datetime')) {
            $field = "\$table->dateTime('{$name}')";
        } elseif (str_contains($type, 'date')) {
            $field = "\$table->date('{$name}')";
        } elseif (str_contains($type, 'tinyint(1)')) {
            $field = "\$table->boolean('{$name}')";
        } elseif (str_contains($type, 'enum')) {
            preg_match('/enum\((.+)\)/', $type, $matches);
            $values = $matches[1] ?? "''";
            $field = "\$table->enum('{$name}', [{$values}])";
        } else {
            $field = "\$table->string('{$name}')"; // Fallback
        }
        
        // Add nullable
        if ($null === 'YES' && $default !== 'CURRENT_TIMESTAMP') {
            $field .= "->nullable()";
        }
        
        // Add default
        if ($default !== null && $default !== 'CURRENT_TIMESTAMP') {
            if ($default === 'NULL') {
                $field .= "->default(null)";
            } elseif (is_numeric($default)) {
                $field .= "->default({$default})";
            } else {
                $field .= "->default('{$default}')";
            }
        }
        
        return $field . ";";
    }
}