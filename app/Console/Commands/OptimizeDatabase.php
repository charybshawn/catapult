<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OptimizeDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:optimize 
                            {--analyze : Run ANALYZE on tables}
                            {--optimize : Run OPTIMIZE on tables}
                            {--tables= : Specific tables to optimize, comma separated}
                            {--all : Optimize all tables}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Optimize database tables for better performance';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $connection = DB::getDefaultConnection();
        $driver = config("database.connections.{$connection}.driver");
        
        if (!in_array($driver, ['mysql', 'mariadb'])) {
            $this->error("Database optimization is only supported for MySQL and MariaDB currently.");
            return 1;
        }
        
        $tables = $this->getTablesToOptimize();
        
        if (empty($tables)) {
            $this->error("No tables found to optimize.");
            return 1;
        }
        
        $this->info("Starting database optimization for {$driver}...");
        
        // Run optimization procedures
        if ($this->option('analyze') || (!$this->option('analyze') && !$this->option('optimize'))) {
            $this->analyzeTablesMySQL($tables);
        }
        
        if ($this->option('optimize')) {
            $this->optimizeTablesMySQL($tables);
        }
        
        $this->info("Database optimization completed.");
        
        return 0;
    }
    
    /**
     * Get the list of tables to optimize
     */
    protected function getTablesToOptimize(): array
    {
        if ($tablesOption = $this->option('tables')) {
            return explode(',', $tablesOption);
        }
        
        if ($this->option('all')) {
            return $this->getAllTables();
        }
        
        // Default important tables to optimize if no option is specified
        return [
            'products',
            'price_variations',
            'orders',
            'order_items',
            'crops',
            'consumables',
            'recipes',
            'categories',
        ];
    }
    
    /**
     * Get all database tables
     */
    protected function getAllTables(): array
    {
        $tables = [];
        
        $results = DB::select('SHOW TABLES');
        
        foreach ($results as $result) {
            $properties = get_object_vars($result);
            $tables[] = reset($properties);
        }
        
        return $tables;
    }
    
    /**
     * Analyze tables in MySQL/MariaDB
     */
    protected function analyzeTablesMySQL(array $tables): void
    {
        $this->info("Analyzing tables to update statistics...");
        $bar = $this->output->createProgressBar(count($tables));
        $bar->start();
        
        foreach ($tables as $table) {
            try {
                DB::statement("ANALYZE TABLE `{$table}`");
                $bar->advance();
            } catch (\Exception $e) {
                Log::error("Failed to analyze table {$table}: " . $e->getMessage());
                $this->newLine();
                $this->error("Error analyzing table {$table}: " . $e->getMessage());
            }
        }
        
        $bar->finish();
        $this->newLine(2);
        $this->info("Table analysis completed.");
    }
    
    /**
     * Optimize tables in MySQL/MariaDB
     */
    protected function optimizeTablesMySQL(array $tables): void
    {
        $this->info("Optimizing tables to reclaim space and improve performance...");
        $bar = $this->output->createProgressBar(count($tables));
        $bar->start();
        
        foreach ($tables as $table) {
            try {
                DB::statement("OPTIMIZE TABLE `{$table}`");
                $bar->advance();
            } catch (\Exception $e) {
                Log::error("Failed to optimize table {$table}: " . $e->getMessage());
                $this->newLine();
                $this->error("Error optimizing table {$table}: " . $e->getMessage());
            }
        }
        
        $bar->finish();
        $this->newLine(2);
        $this->info("Table optimization completed.");
    }
}
