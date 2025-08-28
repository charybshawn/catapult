<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixBackupViews extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'backup:fix-views {--drop : Drop problematic views instead of fixing them}';

    /**
     * The console command description.
     */
    protected $description = 'Fix database views that prevent mysqldump backups from working';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $shouldDrop = $this->option('drop');
        
        $this->info('Checking problematic database views...');
        
        try {
            // Check if the problematic view exists
            $views = DB::select("SHOW FULL TABLES WHERE Table_type = 'VIEW' AND Tables_in_catapult = 'product_inventory_summary'");
            
            if (empty($views)) {
                $this->info('No problematic views found. Backup should work now.');
                return 0;
            }
            
            if ($shouldDrop) {
                $this->warn('Dropping product_inventory_summary view...');
                DB::statement('DROP VIEW IF EXISTS product_inventory_summary');
                $this->info('✓ View dropped successfully');
                $this->info('You can now run your backup. The view can be recreated later if needed.');
            } else {
                $this->info('Found problematic view: product_inventory_summary');
                $this->info('Options to fix the backup issue:');
                $this->line('');
                $this->line('1. Drop the view temporarily:');
                $this->line('   php artisan backup:fix-views --drop');
                $this->line('');
                $this->line('2. Or run backup excluding views:');
                $this->line('   php artisan db:backup --exclude-views');
                $this->line('');
                $this->line('3. Check if view definition is correct:');
                
                try {
                    $viewDef = DB::select('SHOW CREATE VIEW product_inventory_summary');
                    if (!empty($viewDef)) {
                        $this->line('   View definition exists and is accessible from Laravel');
                        $this->line('   Issue is likely mysqldump permissions or view definer');
                    }
                } catch (Exception $e) {
                    $this->error('   View definition error: ' . $e->getMessage());
                }
            }
            
        } catch (Exception $e) {
            $this->error('Error checking views: ' . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}