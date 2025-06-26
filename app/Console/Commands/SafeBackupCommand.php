<?php

namespace App\Console\Commands;

use App\Services\SimpleBackupService;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class SafeBackupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'safe:backup 
                            {--commit-message= : Custom git commit message}
                            {--no-push : Skip git push after commit}
                            {--schema-only : Backup database structure only}
                            {--data-only : Backup data only (no structure)}
                            {--separate : Create separate schema and data files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create database backup, commit changes, and push to git';

    protected SimpleBackupService $backupService;

    public function __construct(SimpleBackupService $backupService)
    {
        parent::__construct();
        $this->backupService = $backupService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Starting safe backup process...');
        
        // Check prerequisites
        if (!$this->checkPrerequisites()) {
            return 1;
        }
        
        // Step 1: Create database backup
        $this->info('1️⃣ Creating database backup...');
        try {
            $backupFiles = $this->createCustomBackup();
            if (is_array($backupFiles)) {
                foreach ($backupFiles as $file) {
                    $this->info("✅ Backup created: {$file}");
                }
            } else {
                $this->info("✅ Backup created: {$backupFiles}");
            }
        } catch (\Exception $e) {
            $this->error("❌ Backup failed: {$e->getMessage()}");
            return 1;
        }

        // Step 2: Check git status
        $this->info('2️⃣ Checking git status...');
        $gitStatus = $this->runProcess(['git', 'status', '--porcelain']);
        
        if (empty(trim($gitStatus))) {
            $this->info('✅ No changes to commit.');
            return 0;
        }

        // Step 3: Add all changes
        $this->info('3️⃣ Adding changes to git...');
        $this->runProcess(['git', 'add', '.']);
        $this->info('✅ Changes staged');

        // Step 4: Commit changes
        $this->info('4️⃣ Committing changes...');
        $commitMessage = $this->option('commit-message') ?: 'Safe backup: ' . now()->format('Y-m-d H:i:s');
        
        $this->runProcess(['git', 'commit', '-m', $commitMessage]);
        $this->info("✅ Changes committed: {$commitMessage}");

        // Step 5: Push to origin (unless --no-push is specified)
        if (!$this->option('no-push')) {
            $this->info('5️⃣ Pushing to origin...');
            try {
                $this->runProcess(['git', 'push']);
                $this->info('✅ Changes pushed to origin');
            } catch (\Exception $e) {
                $this->warn("⚠️  Push failed: {$e->getMessage()}");
                $this->info('💡 You may need to push manually later');
            }
        } else {
            $this->info('⏭️  Skipping git push (--no-push specified)');
        }

        $this->info('🎉 Safe backup process completed successfully!');
        return 0;
    }

    /**
     * Check if required tools are available
     */
    protected function checkPrerequisites(): bool
    {
        // Check for git in common locations
        $gitPaths = ['/usr/bin/git', '/opt/homebrew/bin/git', '/usr/local/bin/git'];
        $gitFound = false;
        
        foreach ($gitPaths as $gitPath) {
            if (file_exists($gitPath) && is_executable($gitPath)) {
                $gitFound = true;
                break;
            }
        }
        
        if (!$gitFound) {
            $this->error('❌ git not found. Git is required for this command.');
            $this->info('💡 Install git or check PATH configuration');
            return false;
        }

        // Check if mysqldump is available (warn but don't fail)
        $mysqldumpPaths = [
            '/usr/bin/mysqldump', 
            '/opt/homebrew/bin/mysqldump',
            '/opt/homebrew/opt/mysql-client/bin/mysqldump',
            '/usr/local/bin/mysqldump'
        ];
        
        $mysqldumpFound = false;
        foreach ($mysqldumpPaths as $mysqldumpPath) {
            if (file_exists($mysqldumpPath) && is_executable($mysqldumpPath)) {
                $mysqldumpFound = true;
                break;
            }
        }
        
        if (!$mysqldumpFound) {
            $this->warn('⚠️  mysqldump not found. Using PHP-based backup instead.');
            $this->line('💡 For better performance, install MySQL client tools:');
            $this->line('   brew install mysql-client');
            $this->newLine();
        }

        return true;
    }

    /**
     * Create backup based on options
     */
    protected function createCustomBackup()
    {
        $dbName = config('database.connections.mysql.database');
        $timestamp = now()->format('Y-m-d_H-i-s');
        $backupDir = storage_path('app/backups/database');
        
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        if ($this->option('separate')) {
            return $this->createSeparateBackups($dbName, $timestamp, $backupDir);
        } elseif ($this->option('schema-only')) {
            return $this->createSchemaBackup($dbName, $timestamp, $backupDir);
        } elseif ($this->option('data-only')) {
            return $this->createDataBackup($dbName, $timestamp, $backupDir);
        } else {
            // Default: use existing service for combined backup (works without mysqldump)
            return $this->backupService->createBackup();
        }
    }

    /**
     * Create separate schema and data backups
     */
    protected function createSeparateBackups(string $dbName, string $timestamp, string $backupDir): array
    {
        $schemaFile = "{$dbName}_schema_{$timestamp}.sql";
        $dataFile = "{$dbName}_data_{$timestamp}.sql";
        
        $this->createSchemaBackup($dbName, $timestamp, $backupDir, $schemaFile);
        $this->createDataBackup($dbName, $timestamp, $backupDir, $dataFile);
        
        return [$schemaFile, $dataFile];
    }

    /**
     * Create schema-only backup
     */
    protected function createSchemaBackup(string $dbName, string $timestamp, string $backupDir, string $filename = null): string
    {
        $filename = $filename ?: "{$dbName}_schema_{$timestamp}.sql";
        $filePath = "{$backupDir}/{$filename}";
        
        $command = [
            'mysqldump',
            '--host=' . config('database.connections.mysql.host'),
            '--port=' . config('database.connections.mysql.port'),
            '--user=' . config('database.connections.mysql.username'),
            '--password=' . config('database.connections.mysql.password'),
            '--no-data',
            '--routines',
            '--triggers',
            $dbName
        ];
        
        $this->runMysqlDump($command, $filePath);
        return $filename;
    }

    /**
     * Create data-only backup
     */
    protected function createDataBackup(string $dbName, string $timestamp, string $backupDir, string $filename = null): string
    {
        $filename = $filename ?: "{$dbName}_data_{$timestamp}.sql";
        $filePath = "{$backupDir}/{$filename}";
        
        $command = [
            'mysqldump',
            '--host=' . config('database.connections.mysql.host'),
            '--port=' . config('database.connections.mysql.port'),
            '--user=' . config('database.connections.mysql.username'),
            '--password=' . config('database.connections.mysql.password'),
            '--no-create-info',
            '--skip-triggers',
            $dbName
        ];
        
        $this->runMysqlDump($command, $filePath);
        return $filename;
    }

    /**
     * Run mysqldump command and save to file
     */
    protected function runMysqlDump(array $command, string $outputPath): void
    {
        $process = new Process($command);
        $process->run();
        
        if (!$process->isSuccessful()) {
            throw new \RuntimeException('mysqldump failed: ' . $process->getErrorOutput());
        }
        
        file_put_contents($outputPath, $process->getOutput());
    }

    /**
     * Run a shell command and return output
     */
    protected function runProcess(array $command): string
    {
        // Use absolute path for git commands
        if ($command[0] === 'git') {
            $gitPaths = ['/usr/bin/git', '/opt/homebrew/bin/git', '/usr/local/bin/git'];
            foreach ($gitPaths as $gitPath) {
                if (file_exists($gitPath) && is_executable($gitPath)) {
                    $command[0] = $gitPath;
                    break;
                }
            }
        }
        
        $process = new Process($command);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException($process->getErrorOutput() ?: $process->getOutput());
        }

        return $process->getOutput();
    }
}