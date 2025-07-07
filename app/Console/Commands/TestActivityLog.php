<?php

namespace App\Console\Commands;

use App\Models\Activity;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestActivityLog extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'activity:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the enhanced activity log functionality';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Testing enhanced activity log functionality...');

        // Get or create a test user
        $user = User::first();
        if (!$user) {
            $this->error('No users found in the database. Please create at least one user first.');
            return 1;
        }

        // Test 1: Create a basic activity log with enhanced fields
        $this->info('Creating basic activity log...');
        
        activity()
            ->causedBy($user)
            ->performedOn($user)
            ->event('test.basic')
            ->withProperties([
                'test' => 'Basic activity log test',
                'timestamp' => now()->toIso8601String(),
            ])
            ->log('Testing basic activity log functionality');

        // Get the last activity and update it with enhanced fields
        $activity = Activity::latest()->first();
        $activity->update([
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Console Test/1.0',
            'request_method' => 'CLI',
            'request_url' => 'artisan activity:test',
            'response_status' => 200,
            'execution_time_ms' => rand(10, 500),
            'memory_usage_mb' => rand(5, 50),
            'query_count' => rand(1, 20),
            'context' => ['environment' => 'testing', 'version' => '1.0'],
            'tags' => ['test', 'console', 'enhanced'],
            'severity_level' => 'info',
        ]);

        $this->info('Basic activity log created with ID: ' . $activity->id);

        // Test 2: Create activities with different severity levels
        $this->info('Creating activities with different severity levels...');
        
        $severityLevels = ['debug', 'info', 'notice', 'warning', 'error', 'critical'];
        
        foreach ($severityLevels as $level) {
            activity()
                ->causedBy($user)
                ->event('test.severity')
                ->withProperties(['severity_test' => $level])
                ->log("Testing {$level} severity level");
            
            $activity = Activity::latest()->first();
            $activity->update([
                'severity_level' => $level,
                'ip_address' => fake()->ipv4(),
                'execution_time_ms' => rand(10, 2000),
                'memory_usage_mb' => rand(5, 100),
            ]);
        }

        $this->info('Created activities with various severity levels.');

        // Test 3: Test query scopes
        $this->info('Testing query scopes...');
        
        $slowQueries = Activity::slowQueries(100)->count();
        $this->info("Found {$slowQueries} slow queries (>100ms)");
        
        $highMemory = Activity::highMemoryUsage(30)->count();
        $this->info("Found {$highMemory} high memory usage activities (>30MB)");
        
        $errors = Activity::bySeverity(['error', 'critical'])->count();
        $this->info("Found {$errors} error/critical activities");

        // Test 4: Generate statistics
        $this->info('Generating activity statistics...');
        
        $stats = Activity::getStatistics(now()->startOfDay(), now()->endOfDay());
        
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Activities', $stats['total_activities']],
                ['Unique Users', $stats['unique_users']],
                ['Unique IPs', $stats['unique_ips']],
                ['Avg Execution Time', $stats['performance']['avg_execution_time_ms'] . ' ms'],
                ['Max Execution Time', $stats['performance']['max_execution_time_ms'] . ' ms'],
                ['Error Count', $stats['error_count']],
                ['Warning Count', $stats['warning_count']],
            ]
        );

        // Test 5: Test trend data
        $this->info('Getting trend data...');
        
        $trends = Activity::getTrendData(now()->subDays(7), now());
        $this->info('Found ' . count($trends) . ' trend data points');

        $this->info('Enhanced activity log testing completed successfully!');
        
        return 0;
    }
}