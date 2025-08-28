<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ListTables extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:tables 
                            {--filter= : Filter tables by name}
                            {--with-counts : Include record counts}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all database tables with optional record counts';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filter = $this->option('filter');
        $withCounts = $this->option('with-counts');
        
        // Get all tables
        $tables = DB::select("SHOW TABLES");
        $tableList = array_map(function($t) {
            return array_values((array)$t)[0];
        }, $tables);
        
        // Apply filter if provided
        if ($filter) {
            $tableList = array_filter($tableList, function($table) use ($filter) {
                return str_contains(strtolower($table), strtolower($filter));
            });
        }
        
        // Sort tables alphabetically
        sort($tableList);
        
        if (empty($tableList)) {
            $this->warn("No tables found matching filter: {$filter}");
            return 0;
        }
        
        // Prepare table data
        $tableData = [];
        foreach ($tableList as $table) {
            $row = [$table];
            
            if ($withCounts) {
                try {
                    $count = DB::table($table)->count();
                    $row[] = number_format($count);
                } catch (Exception $e) {
                    $row[] = 'Error';
                }
            }
            
            $tableData[] = $row;
        }
        
        // Display results
        $headers = ['Table Name'];
        if ($withCounts) {
            $headers[] = 'Record Count';
        }
        
        $this->table($headers, $tableData);
        $this->info("Total tables: " . count($tableList));
        
        return 0;
    }
}