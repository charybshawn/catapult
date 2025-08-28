<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use App\Services\LightweightSchemaChecker;
use Illuminate\Support\Facades\Cache;

class UpdateMigrationSchemaCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'schema:update-cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update the cached migration schema after deployments';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Updating migration schema cache...');
        
        try {
            $startTime = microtime(true);
            
            $checker = new LightweightSchemaChecker();
            $checker->updateMigrationSchemaCache();
            
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);
            
            $this->info("✅ Migration schema cache updated successfully!");
            $this->info("Execution time: {$executionTime}ms");
            
            // Show when it was last updated
            $lastUpdated = Cache::get('migration_schema_updated_at');
            if ($lastUpdated) {
                $this->info("Cache updated at: {$lastUpdated}");
            }
            
            return Command::SUCCESS;
            
        } catch (Exception $e) {
            $this->error("Failed to update migration schema cache: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}