<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CropPlanMonitorService;

class SendCropPlanReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crop-plans:send-reminders {--days=2 : Number of days ahead to send reminders}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send planting reminders for upcoming crop plans';

    protected CropPlanMonitorService $monitorService;

    /**
     * Create a new command instance.
     */
    public function __construct(CropPlanMonitorService $monitorService)
    {
        parent::__construct();
        $this->monitorService = $monitorService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $daysAhead = (int) $this->option('days');
        
        $this->info("Sending crop plan reminders for plans due in {$daysAhead} days...");
        
        // Send reminders
        $result = $this->monitorService->sendPlantingReminders($daysAhead);
        
        // Display results
        $this->info("Reminders sent: {$result['reminders_sent']}");
        
        if (!empty($result['errors'])) {
            $this->error('Errors encountered:');
            foreach ($result['errors'] as $error) {
                $this->error(" - {$error}");
            }
        }
        
        // Get and display current status summary
        $this->newLine();
        $this->info('Current Crop Plan Status:');
        $status = $this->monitorService->checkPlanStatuses();
        
        $this->table(
            ['Status', 'Count'],
            [
                ['Overdue', $status['overdue']],
                ['Urgent (≤2 days)', $status['urgent']],
                ['Upcoming (≤7 days)', $status['upcoming']],
                ['On Track', $status['on_track']],
            ]
        );
        
        // Display overdue issues if any
        if (!empty($status['issues'])) {
            $this->newLine();
            $this->warn('Overdue Plans:');
            foreach ($status['issues'] as $issue) {
                $this->warn("- Plan #{$issue['plan_id']} for Order #{$issue['order_id']}: {$issue['variety']} ({$issue['days_overdue']} days overdue)");
            }
        }
        
        return Command::SUCCESS;
    }
}