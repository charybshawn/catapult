<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Input\InputOption;

class SafeMigrate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'migrate:safe';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run migrations with additional safety measures for production environments';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Check if we're in production
        if (App::environment('production')) {
            // In production, require explicit confirmation
            if (!$this->option('force') && !$this->confirm(
                'You are running migrations in PRODUCTION environment. This can be dangerous. Are you sure you want to proceed?',
                false
            )) {
                $this->error('Migration cancelled for safety.');
                return 1;
            }
            
            // Check for database backup
            if (!$this->option('skip-backup-check') && !$this->confirm(
                'Have you taken a complete backup of the database before proceeding?',
                false
            )) {
                $this->error('Please take a backup before running migrations in production.');
                return 1;
            }
            
            // Require a second verification for destructive migrations
            if ($this->hasMigrationsWithDrops() && !$this->option('allow-drops') && !$this->confirm(
                'WARNING: Some migrations contain destructive operations like DROP TABLE. Continue anyway?',
                false
            )) {
                $this->error('Migration cancelled due to potentially destructive operations.');
                return 1;
            }
            
            // Log who ran this migration in production
            $user = exec('whoami');
            $this->info("Running migrations in PRODUCTION as user: {$user}");
            Log::channel('daily')->info("Production migrations executed by {$user}");
            
            // Add a 5-second countdown to allow cancellation
            $this->info("Starting migrations in: ");
            for ($i = 5; $i > 0; $i--) {
                $this->output->write("\r{$i}... ");
                sleep(1);
            }
            $this->output->writeln("\rRunning migrations now!");
        }
        
        // Call the standard migrate command
        $this->info('Executing migrations...');
        
        $options = [
            '--force' => true, // We've already confirmed, so force it
        ];
        
        // Add all other options passed to our command
        foreach ($this->options() as $key => $value) {
            if (in_array($key, ['force', 'skip-backup-check', 'allow-drops'])) {
                continue;
            }
            
            $options["--{$key}"] = $value;
        }
        
        // Run the actual migration
        $result = $this->call('migrate', $options);
        
        // Send notification in production
        if (App::environment('production') && $result === 0) {
            // You might want to add Slack/email notifications here
            Log::channel('daily')->info("Production migrations completed successfully");
        }
        
        return $result;
    }
    
    /**
     * Check if any pending migrations contain DROP statements
     */
    protected function hasMigrationsWithDrops(): bool
    {
        $migrator = app('migrator');
        $files = $migrator->getMigrationFiles($migrator->paths());
        
        // Get list of migrations already run
        $ran = $migrator->getRepository()->getRan();
        
        // Filter to pending migrations
        $pending = array_diff(array_keys($files), $ran);
        
        foreach ($pending as $migration) {
            $path = $files[$migration];
            $content = file_get_contents($path);
            
            // Check for potentially destructive operations
            if (preg_match('/drop\s+table|drop\s+column|dropColumn|dropTable|dropIfExists/i', $content)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Get the console command options.
     */
    protected function getOptions(): array
    {
        return [
            ['database', null, InputOption::VALUE_OPTIONAL, 'The database connection to use'],
            ['force', 'f', InputOption::VALUE_NONE, 'Force the operation to run in production without additional confirmations'],
            ['path', null, InputOption::VALUE_OPTIONAL, 'The path to the migrations files to be executed'],
            ['realpath', null, InputOption::VALUE_NONE, 'Indicate any provided migration file paths are pre-resolved absolute paths'],
            ['pretend', null, InputOption::VALUE_NONE, 'Dump the SQL queries that would be run'],
            ['seed', null, InputOption::VALUE_NONE, 'Indicates if the seed task should be re-run'],
            ['step', null, InputOption::VALUE_NONE, 'Force the migrations to be run so they can be rolled back individually'],
            ['skip-backup-check', null, InputOption::VALUE_NONE, 'Skip checking for database backup confirmation'],
            ['allow-drops', null, InputOption::VALUE_NONE, 'Allow destructive migrations without additional confirmation'],
        ];
    }
}
