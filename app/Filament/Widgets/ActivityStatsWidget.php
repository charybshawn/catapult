<?php

namespace App\Filament\Widgets;

use App\Models\Activity;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class ActivityStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;
    
    protected int | string | array $columnSpan = 'full';
    
    protected function getStats(): array
    {
        $totalActivities = Activity::count();
        $todayActivities = Activity::whereDate('created_at', today())->count();
        $weekActivities = Activity::where('created_at', '>=', now()->subDays(7))->count();
        $uniqueUsers = Activity::distinct('causer_id')->whereNotNull('causer_id')->count('causer_id');
        
        // Calculate trends
        $yesterdayActivities = Activity::whereDate('created_at', today()->subDay())->count();
        $todayTrend = $yesterdayActivities > 0 
            ? round((($todayActivities - $yesterdayActivities) / $yesterdayActivities) * 100) 
            : 0;
        
        $lastWeekActivities = Activity::whereBetween('created_at', [
            now()->subDays(14),
            now()->subDays(7)
        ])->count();
        $weekTrend = $lastWeekActivities > 0 
            ? round((($weekActivities - $lastWeekActivities) / $lastWeekActivities) * 100) 
            : 0;
        
        return [
            Stat::make('Total Activities', Number::format($totalActivities))
                ->description('All time')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('primary'),
            Stat::make('Today', Number::format($todayActivities))
                ->description($todayTrend >= 0 ? "{$todayTrend}% increase" : "{$todayTrend}% decrease")
                ->descriptionIcon($todayTrend >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($todayTrend >= 0 ? 'success' : 'danger')
                ->chart($this->getHourlyChart()),
            Stat::make('This Week', Number::format($weekActivities))
                ->description($weekTrend >= 0 ? "{$weekTrend}% increase" : "{$weekTrend}% decrease")
                ->descriptionIcon($weekTrend >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($weekTrend >= 0 ? 'success' : 'danger')
                ->chart($this->getDailyChart()),
            Stat::make('Active Users', Number::format($uniqueUsers))
                ->description('Unique users')
                ->descriptionIcon('heroicon-m-users')
                ->color('info'),
        ];
    }
    
    protected function getHourlyChart(): array
    {
        $data = [];
        for ($i = 23; $i >= 0; $i--) {
            $hour = now()->subHours($i);
            $count = Activity::whereBetween('created_at', [
                $hour->copy()->startOfHour(),
                $hour->copy()->endOfHour()
            ])->count();
            $data[] = $count;
        }
        return $data;
    }
    
    protected function getDailyChart(): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = now()->subDays($i);
            $count = Activity::whereDate('created_at', $day)->count();
            $data[] = $count;
        }
        return $data;
    }
}