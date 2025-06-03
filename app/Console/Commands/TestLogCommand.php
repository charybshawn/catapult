<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestLogCommand extends Command
{
    protected $signature = 'test:log';
    protected $description = 'Writes a test message to the Laravel log.';

    public function handle()
    {
        Log::info('This is a test log message from TestLogCommand.');
        $this->info('Test log message sent. Please check storage/logs/laravel.log');
        
        // Also try a different log level
        Log::debug('This is a test DEBUG message from TestLogCommand.');
        Log::error('This is a test ERROR message from TestLogCommand.');
        
        // Try to log directly to a file to bypass some Laravel logging layers
        $logFilePath = storage_path('logs/direct_test.log');
        $timestamp = now()->toDateTimeString();
        $directMessage = "[{$timestamp}] Direct log test: This message was written directly.\n";
        
        if (file_put_contents($logFilePath, $directMessage, FILE_APPEND) === false) {
            $this->error('Failed to write to direct_test.log');
        } else {
            $this->info('Also attempted to write to storage/logs/direct_test.log');
        }
        
        return Command::SUCCESS;
    }
} 