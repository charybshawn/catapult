<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\CheckMaxShiftExceeded;

class ScheduleTimeCardChecks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'timecard:check-shifts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for time cards that have exceeded the 8-hour maximum shift';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Dispatching job to check for exceeded shifts...');
        
        CheckMaxShiftExceeded::dispatch();
        
        $this->info('Job dispatched successfully.');
        
        return 0;
    }
}
