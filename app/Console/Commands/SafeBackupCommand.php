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
        $this->info('Starting safe backup process...');
        
        // Check prerequisites
        if (!$this->checkPrerequisites()) {
            return 1;
        }
        
        // Step 1: Create database backup
        $this->info('1. Creating database backup...');
        try {
            $backupFiles = $this->createCustomBackup();
            if (is_array($backupFiles)) {
                foreach ($backupFiles as $file) {
                    $this->info("Backup created: {$file}");
                }
            } else {
                $this->info("Backup created: {$backupFiles}");
            }
        } catch (\Exception $e) {
            $this->error("Backup failed: {$e->getMessage()}");
            return 1;
        }

        // Step 2: Check git status
        $this->info('2. Checking git status...');
        $gitStatus = $this->runProcess(['git', 'status', '--porcelain']);
        
        if (empty(trim($gitStatus))) {
            $this->info('No changes to commit.');
            return 0;
        }

        // Step 3: Add all changes
        $this->info('3. Adding changes to git...');
        try {
            $this->runProcess(['git', 'add', '.']);
            $this->info('Changes staged');
        } catch (\Exception $e) {
            $this->error("Failed to stage changes: " . $e->getMessage());
            return 1;
        }

        // Step 4: Commit changes
        $this->info('4. Committing changes...');
        $commitMessage = $this->option('commit-message') ?: 'Safe backup: ' . now()->format('Y-m-d H:i:s');
        
        try {
            $this->runProcess(['git', 'commit', '-m', $commitMessage]);
            $this->info("Changes committed: {$commitMessage}");
        } catch (\Exception $e) {
            $this->error("Failed to commit changes: " . $e->getMessage());
            return 1;
        }

        // Step 5: Push to origin (unless --no-push is specified)
        if (!$this->option('no-push')) {
            $this->info('5. Pushing to origin...');
            try {
                $this->runProcess(['git', 'push']);
                $this->info('Changes pushed to origin');
            } catch (\Exception $e) {
                $this->warn("Push failed: {$e->getMessage()}");
                $this->info('You may need to push manually later');
            }
        } else {
            $this->info('Skipping git push (--no-push specified)');
        }

        $this->info('Safe backup process completed successfully!');
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
            $this->error('git not found. Git is required for this command.');
            $this->info('Install git or check PATH configuration');
            return false;
        }

        // Check if mysqldump is available (warn but don't fail)
        if (!$this->isMysqldumpAvailable()) {
            $this->warn('mysqldump not found. Using PHP-based backup instead.');
            $this->line('For better performance, install MySQL client tools:');
            $this->line('   macOS: brew install mysql-client');
            $this->line('   Ubuntu/Debian: apt install mysql-client');
            $this->line('   CentOS/RHEL: yum install mysql');
            $this->line('   Or use Laravel Herd, DBngin, MAMP, etc.');
            $this->newLine();
        }

        return true;
    }

    /**
     * Create backup based on options
     */
    protected function createCustomBackup()
    {
        // Check if mysqldump is available for advanced options
        $mysqldumpAvailable = $this->isMysqldumpAvailable();
        
        if (!$mysqldumpAvailable && ($this->option('separate') || $this->option('schema-only') || $this->option('data-only'))) {
            $this->warn('Advanced backup options require mysqldump. Falling back to full backup.');
            $this->line('Install MySQL client tools for schema/data separation:');
            $this->line('   brew install mysql-client');
            $this->newLine();
        }

        if ($mysqldumpAvailable && ($this->option('separate') || $this->option('schema-only') || $this->option('data-only'))) {
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
            }
        }
        
        // Default: use existing service for combined backup (works without mysqldump)
        return $this->backupService->createBackup();
    }

    /**
     * Check if mysqldump is available
     */
    protected function isMysqldumpAvailable(): bool
    {
        $mysqldumpPaths = [
            // Standard system locations (Linux/Unix)
            '/usr/bin/mysqldump',
            '/usr/local/bin/mysqldump',
            '/bin/mysqldump',
            
            // Homebrew (macOS) - Intel & Apple Silicon
            '/usr/local/bin/mysqldump',                              // Homebrew Intel
            '/opt/homebrew/bin/mysqldump',                           // Homebrew Apple Silicon
            '/usr/local/opt/mysql-client/bin/mysqldump',             // Homebrew mysql-client Intel
            '/opt/homebrew/opt/mysql-client/bin/mysqldump',          // Homebrew mysql-client Apple Silicon
            '/usr/local/opt/mysql/bin/mysqldump',                    // Homebrew mysql Intel
            '/opt/homebrew/opt/mysql/bin/mysqldump',                 // Homebrew mysql Apple Silicon
            
            // Laravel Herd (macOS)
            '/Users/' . get_current_user() . '/Library/Application Support/Herd/bin/mysqldump',
            '/Applications/Herd.app/Contents/Resources/bin/mysqldump',
            
            // DBngin (macOS)
            '/usr/local/mysql/bin/mysqldump',
            '/Applications/DBngin.app/Contents/Resources/mysql/bin/mysqldump',
            
            // MAMP/XAMPP (macOS/Windows/Linux)
            '/Applications/MAMP/Library/bin/mysqldump',              // MAMP macOS
            '/opt/lampp/bin/mysqldump',                              // XAMPP Linux
            '/Applications/XAMPP/xamppfiles/bin/mysqldump',          // XAMPP macOS
            'C:\\xampp\\mysql\\bin\\mysqldump.exe',                 // XAMPP Windows
            'C:\\mamp\\bin\\mysql\\bin\\mysqldump.exe',             // MAMP Windows
            
            // Laragon (Windows)
            'C:\\laragon\\bin\\mysql\\mysql-8.0.30-winx64\\bin\\mysqldump.exe',
            
            // Docker Desktop MySQL
            '/usr/local/bin/docker',                                 // Check if we can use docker
            
            // Linux package managers
            '/usr/bin/mariadb-dump',                                 // MariaDB on some Linux distros
            '/snap/bin/mysql.mysqldump',                            // Snap packages
            
            // FreeBSD
            '/usr/local/bin/mysqldump',
            
            // Alternative installations
            '/opt/mysql/bin/mysqldump',
            '/usr/mysql/bin/mysqldump',
        ];
        
        foreach ($mysqldumpPaths as $mysqldumpPath) {
            if (file_exists($mysqldumpPath) && is_executable($mysqldumpPath)) {
                $this->line("Found mysqldump at: {$mysqldumpPath}");
                return true;
            }
        }
        
        // Also try PATH lookup as fallback
        $pathLookup = shell_exec('which mysqldump 2>/dev/null');
        if (!empty($pathLookup) && file_exists(trim($pathLookup))) {
            $this->line("Found mysqldump in PATH: " . trim($pathLookup));
            return true;
        }
        
        // Check if it's available through Docker
        $dockerMysql = shell_exec('docker --version 2>/dev/null');
        if (!empty($dockerMysql)) {
            $this->line("Docker available - mysqldump could be used via MySQL container");
        }
        
        return false;
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
            '--no-tablespaces',
            '--single-transaction',
            '--skip-add-locks',
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
            '--no-tablespaces',
            '--single-transaction',
            '--skip-add-locks',
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
        
        // Enhance PATH for web server environment
        $currentPath = $_SERVER['PATH'] ?? getenv('PATH') ?? '';
        $additionalPaths = [
            '/opt/homebrew/bin',
            '/opt/homebrew/opt/mysql-client/bin',
            '/usr/local/bin',
            '/usr/local/opt/mysql-client/bin',
            '/Applications/Herd.app/Contents/Resources/bin',
        ];
        
        $enhancedPath = $currentPath . ':' . implode(':', $additionalPaths);
        $process->setEnv(['PATH' => $enhancedPath]);
        
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
        $process = new Process($command, base_path());
        
        // Enhance PATH for web server environment to find git and other tools
        $currentPath = $_SERVER['PATH'] ?? getenv('PATH') ?? '';
        $additionalPaths = [
            '/opt/homebrew/bin',
            '/usr/local/bin',
            '/usr/bin',
            '/bin',
        ];
        
        $enhancedPath = $currentPath . ':' . implode(':', $additionalPaths);
        $process->setEnv(['PATH' => $enhancedPath]);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException($process->getErrorOutput() ?: $process->getOutput());
        }

        return $process->getOutput();
    }
}