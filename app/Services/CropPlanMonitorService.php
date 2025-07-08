<?php

namespace App\Services;

use App\Models\CropPlan;
use App\Models\User;
use App\Notifications\CropPlanPlantingReminder;
use App\Notifications\CropPlanOverdue;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Service for monitoring crop plans and sending notifications
 */
class CropPlanMonitorService
{
    /**
     * Get upcoming crop plans that need to be planted soon
     * 
     * @param int $days Number of days to look ahead
     * @return Collection
     */
    public function getUpcomingPlans(int $days = 7): Collection
    {
        $endDate = Carbon::now()->addDays($days)->endOfDay();
        
        return CropPlan::with(['order.customer', 'recipe', 'status', 'variety'])
            ->whereHas('status', function ($query) {
                $query->whereIn('code', ['draft', 'active']);
            })
            ->where('plant_by_date', '>=', Carbon::now()->startOfDay())
            ->where('plant_by_date', '<=', $endDate)
            ->orderBy('plant_by_date')
            ->get();
    }

    /**
     * Get overdue crop plans that should have been planted
     * 
     * @return Collection
     */
    public function getOverduePlans(): Collection
    {
        return CropPlan::with(['order.customer', 'recipe', 'status', 'variety'])
            ->whereHas('status', function ($query) {
                $query->whereIn('code', ['draft', 'active']);
            })
            ->where('plant_by_date', '<', Carbon::now()->startOfDay())
            ->orderBy('plant_by_date')
            ->get();
    }

    /**
     * Send planting reminders for upcoming plans
     * 
     * @param int $daysAhead Number of days ahead to check
     * @return array Summary of notifications sent
     */
    public function sendPlantingReminders(int $daysAhead = 2): array
    {
        $remindersSent = 0;
        $errors = [];

        try {
            // Get plans that need planting within the specified days
            $upcomingPlans = $this->getPlansNeedingReminder($daysAhead);

            foreach ($upcomingPlans as $plan) {
                try {
                    $this->sendReminderForPlan($plan);
                    $remindersSent++;
                } catch (\Exception $e) {
                    $errors[] = "Failed to send reminder for plan {$plan->id}: " . $e->getMessage();
                    Log::error('Failed to send planting reminder', [
                        'crop_plan_id' => $plan->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Send overdue notifications
            $overduePlans = $this->getOverduePlans();
            foreach ($overduePlans as $plan) {
                try {
                    $this->sendOverdueNotification($plan);
                    $remindersSent++;
                } catch (\Exception $e) {
                    $errors[] = "Failed to send overdue notification for plan {$plan->id}: " . $e->getMessage();
                    Log::error('Failed to send overdue notification', [
                        'crop_plan_id' => $plan->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

        } catch (\Exception $e) {
            Log::error('Failed to process planting reminders', [
                'error' => $e->getMessage()
            ]);
            $errors[] = 'Failed to process reminders: ' . $e->getMessage();
        }

        return [
            'reminders_sent' => $remindersSent,
            'errors' => $errors
        ];
    }

    /**
     * Check the status of all active crop plans
     * 
     * @return array Status summary
     */
    public function checkPlanStatuses(): array
    {
        $now = Carbon::now();
        
        $statuses = [
            'overdue' => 0,
            'urgent' => 0,
            'upcoming' => 0,
            'on_track' => 0,
            'issues' => []
        ];

        $activePlans = CropPlan::whereHas('status', function ($query) {
            $query->whereIn('code', ['draft', 'active']);
        })->get();

        foreach ($activePlans as $plan) {
            if ($plan->isOverdue()) {
                $statuses['overdue']++;
                $statuses['issues'][] = [
                    'plan_id' => $plan->id,
                    'order_id' => $plan->order_id,
                    'type' => 'overdue',
                    'days_overdue' => $now->diffInDays($plan->plant_by_date),
                    'variety' => $plan->variety?->name ?? 'Unknown'
                ];
            } elseif ($plan->isUrgent()) {
                $statuses['urgent']++;
            } elseif ($plan->days_until_planting <= 7) {
                $statuses['upcoming']++;
            } else {
                $statuses['on_track']++;
            }
        }

        return $statuses;
    }

    /**
     * Get plans needing reminder notifications
     * 
     * @param int $daysAhead
     * @return Collection
     */
    protected function getPlansNeedingReminder(int $daysAhead): Collection
    {
        $targetDate = Carbon::now()->addDays($daysAhead)->startOfDay();
        
        return CropPlan::with(['order.customer', 'recipe', 'variety'])
            ->whereHas('status', function ($query) {
                $query->whereIn('code', ['draft', 'active']);
            })
            ->whereDate('plant_by_date', $targetDate)
            ->get();
    }

    /**
     * Send reminder notification for a specific plan
     * 
     * @param CropPlan $plan
     * @return void
     */
    protected function sendReminderForPlan(CropPlan $plan): void
    {
        // Get users to notify
        $usersToNotify = $this->getUsersToNotify($plan);

        foreach ($usersToNotify as $user) {
            $user->notify(new CropPlanPlantingReminder($plan));
        }
    }

    /**
     * Send overdue notification for a specific plan
     * 
     * @param CropPlan $plan
     * @return void
     */
    protected function sendOverdueNotification(CropPlan $plan): void
    {
        // Get users to notify
        $usersToNotify = $this->getUsersToNotify($plan);

        foreach ($usersToNotify as $user) {
            $user->notify(new CropPlanOverdue($plan));
        }
    }

    /**
     * Get users who should be notified about a crop plan
     * 
     * @param CropPlan $plan
     * @return Collection
     */
    protected function getUsersToNotify(CropPlan $plan): Collection
    {
        $users = collect();

        // Add plan creator
        if ($plan->createdBy) {
            $users->push($plan->createdBy);
        }

        // Add order creator
        if ($plan->order && $plan->order->user) {
            $users->push($plan->order->user);
        }

        // Add users with grower role
        $growers = User::role(['admin', 'grower', 'manager'])->get();
        $users = $users->merge($growers);

        return $users->unique('id');
    }

    /**
     * Get summary of crop plans by status
     * 
     * @return array
     */
    public function getPlansSummaryByStatus(): array
    {
        $summary = [];

        // Get counts by status
        $statusCounts = CropPlan::selectRaw('status_id, count(*) as count')
            ->with('status')
            ->groupBy('status_id')
            ->get();

        foreach ($statusCounts as $statusCount) {
            $summary[$statusCount->status->code] = [
                'name' => $statusCount->status->name,
                'count' => $statusCount->count,
                'color' => $statusCount->status->color
            ];
        }

        // Add overdue and urgent counts
        $overduePlans = $this->getOverduePlans();
        $summary['overdue'] = [
            'name' => 'Overdue',
            'count' => $overduePlans->count(),
            'color' => 'danger'
        ];

        $urgentPlans = CropPlan::whereHas('status', function ($query) {
                $query->whereIn('code', ['draft', 'active']);
            })
            ->whereBetween('plant_by_date', [
                Carbon::now()->startOfDay(),
                Carbon::now()->addDays(2)->endOfDay()
            ])
            ->count();

        $summary['urgent'] = [
            'name' => 'Urgent (Next 2 Days)',
            'count' => $urgentPlans,
            'color' => 'warning'
        ];

        return $summary;
    }

    /**
     * Get crop plans grouped by planting date
     * 
     * @param int $days Number of days to look ahead
     * @return Collection
     */
    public function getPlansByPlantingDate(int $days = 14): Collection
    {
        $endDate = Carbon::now()->addDays($days)->endOfDay();
        
        $plans = CropPlan::with(['order.customer', 'recipe', 'status', 'variety'])
            ->whereHas('status', function ($query) {
                $query->whereIn('code', ['draft', 'active']);
            })
            ->where('plant_by_date', '>=', Carbon::now()->startOfDay())
            ->where('plant_by_date', '<=', $endDate)
            ->orderBy('plant_by_date')
            ->get();

        return $plans->groupBy(function ($plan) {
            return $plan->plant_by_date->format('Y-m-d');
        });
    }
}