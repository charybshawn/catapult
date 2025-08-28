<?php

namespace App\Filament\Widgets;

use App\Services\CropPlanMonitorService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;

class CropPlanStatusWidget extends BaseWidget
{
    protected static ?int $sort = 2;
    
    protected ?string $pollingInterval = '30s';
    
    protected int $daysToLookAhead = 7;

    protected function getStats(): array
    {
        $monitorService = app(CropPlanMonitorService::class);
        
        // Get status counts
        $status = $monitorService->checkPlanStatuses();
        $upcomingPlans = $monitorService->getUpcomingPlans($this->daysToLookAhead);
        $overduePlans = $monitorService->getOverduePlans();
        
        // Calculate additional metrics
        $todayPlans = $upcomingPlans->filter(function ($plan) {
            return $plan->plant_by_date->isToday();
        });
        
        $tomorrowPlans = $upcomingPlans->filter(function ($plan) {
            return $plan->plant_by_date->isTomorrow();
        });
        
        return [
            Stat::make('Overdue Plans', $status['overdue'])
                ->description($status['overdue'] > 0 ? 'Require immediate attention' : 'All plans on schedule')
                ->color($status['overdue'] > 0 ? 'danger' : 'success')
                ->icon($status['overdue'] > 0 ? 'heroicon-o-exclamation-triangle' : 'heroicon-o-check-circle')
                ->chart($this->getOverdueChart($overduePlans)),
                
            Stat::make('Today\'s Plantings', $todayPlans->count())
                ->description($todayPlans->sum('trays_needed') . ' trays total')
                ->color($todayPlans->count() > 0 ? 'warning' : 'gray')
                ->icon('heroicon-o-calendar')
                ->extraAttributes([
                    'class' => $todayPlans->count() > 0 ? 'ring-2 ring-warning-500' : '',
                ]),
                
            Stat::make('Tomorrow\'s Plantings', $tomorrowPlans->count())
                ->description($tomorrowPlans->sum('trays_needed') . ' trays total')
                ->color('info')
                ->icon('heroicon-o-clock'),
                
            Stat::make('Next 7 Days', $upcomingPlans->count())
                ->description($upcomingPlans->sum('trays_needed') . ' trays total')
                ->color('primary')
                ->icon('heroicon-o-calendar-days')
                ->chart($this->getUpcomingChart($upcomingPlans)),
        ];
    }
    
    protected function getOverdueChart($overduePlans): array
    {
        // Group by days overdue
        $chartData = [];
        for ($i = 1; $i <= 7; $i++) {
            $count = $overduePlans->filter(function ($plan) use ($i) {
                $daysOverdue = Carbon::now()->diffInDays($plan->plant_by_date);
                return $daysOverdue == $i;
            })->count();
            $chartData[] = $count;
        }

        return array_reverse($chartData); // Most recent first
    }
    
    protected function getUpcomingChart($upcomingPlans): array
    {
        // Group by days ahead
        $chartData = [];
        for ($i = 0; $i < 7; $i++) {
            $date = Carbon::now()->addDays($i);
            $count = $upcomingPlans->filter(function ($plan) use ($date) {
                return $plan->plant_by_date->isSameDay($date);
            })->count();
            $chartData[] = $count;
        }
        
        return $chartData;
    }
}