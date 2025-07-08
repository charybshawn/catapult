<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CropPlanMonitorService;
use Carbon\Carbon;

class CheckCropPlanStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crop-plans:check-status {--days=14 : Number of days to look ahead}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check the status of crop plans and display a summary';

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
        $days = (int) $this->option('days');
        
        $this->info("Crop Plan Status Report - " . Carbon::now()->format('Y-m-d H:i:s'));
        $this->info("Looking ahead {$days} days");
        $this->newLine();
        
        // Get status summary
        $summary = $this->monitorService->getPlansSummaryByStatus();
        
        $this->info('Plans by Status:');
        $statusTable = [];
        foreach ($summary as $code => $data) {
            $statusTable[] = [$data['name'], $data['count']];
        }
        $this->table(['Status', 'Count'], $statusTable);
        
        // Get overdue plans
        $overduePlans = $this->monitorService->getOverduePlans();
        if ($overduePlans->isNotEmpty()) {
            $this->newLine();
            $this->error("OVERDUE PLANS ({$overduePlans->count()} total):");
            foreach ($overduePlans as $plan) {
                $daysOverdue = Carbon::now()->diffInDays($plan->plant_by_date);
                $variety = $plan->variety?->name ?? 'Unknown';
                $customer = $plan->order?->customer?->contact_name ?? 'Unknown';
                $this->error("  - Plan #{$plan->id}: {$variety} for {$customer} - {$daysOverdue} days overdue");
            }
        }
        
        // Get upcoming plans grouped by date
        $this->newLine();
        $this->info("Upcoming Plans (Next {$days} days):");
        $plansByDate = $this->monitorService->getPlansByPlantingDate($days);
        
        if ($plansByDate->isEmpty()) {
            $this->info('  No upcoming plans in the next ' . $days . ' days.');
        } else {
            foreach ($plansByDate as $date => $plans) {
                $dateCarbon = Carbon::parse($date);
                $daysUntil = Carbon::now()->diffInDays($dateCarbon, false);
                
                $dateLabel = $dateCarbon->format('M j, Y');
                if ($daysUntil === 0) {
                    $dateLabel .= ' (TODAY)';
                } elseif ($daysUntil === 1) {
                    $dateLabel .= ' (Tomorrow)';
                } else {
                    $dateLabel .= " (in {$daysUntil} days)";
                }
                
                $this->info("  {$dateLabel}:");
                foreach ($plans as $plan) {
                    $variety = $plan->variety?->name ?? 'Unknown';
                    $customer = $plan->order?->customer?->contact_name ?? 'Unknown';
                    $status = $plan->status->name;
                    $this->info("    - {$variety} ({$plan->trays_needed} trays) for {$customer} [{$status}]");
                }
            }
        }
        
        // Get urgent plans needing attention
        $urgentPlans = $this->monitorService->getUpcomingPlans(2);
        if ($urgentPlans->isNotEmpty()) {
            $this->newLine();
            $this->warn("URGENT ATTENTION NEEDED ({$urgentPlans->count()} plans in next 2 days):");
            foreach ($urgentPlans as $plan) {
                if (!$plan->isApproved()) {
                    $this->warn("  - Plan #{$plan->id} needs approval before planting!");
                }
            }
        }
        
        return Command::SUCCESS;
    }
}