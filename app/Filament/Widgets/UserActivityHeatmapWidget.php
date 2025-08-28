<?php

namespace App\Filament\Widgets;

use App\Models\Activity;
use App\Models\User;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;

class UserActivityHeatmapWidget extends Widget
{
    protected string $view = 'filament.widgets.user-activity-heatmap';
    
    protected static ?int $sort = 3;
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?string $heading = 'User Activity Heatmap';
    
    public function getHeatmapData(): array
    {
        $endDate = now();
        $startDate = now()->subDays(29);
        
        $activities = Activity::select(
                DB::raw('DATE(created_at) as date'),
                'causer_id',
                DB::raw('COUNT(*) as count')
            )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('causer_id')
            ->where('causer_type', User::class)
            ->groupBy('date', 'causer_id')
            ->get();
        
        // Get top 10 active users
        $topUsers = Activity::select('causer_id', DB::raw('COUNT(*) as total'))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('causer_id')
            ->where('causer_type', User::class)
            ->groupBy('causer_id')
            ->orderByDesc('total')
            ->limit(10)
            ->pluck('causer_id');
        
        $users = User::whereIn('id', $topUsers)->get()->keyBy('id');
        
        // Build heatmap data
        $heatmapData = [];
        $dates = [];
        
        for ($date = $startDate->copy(); $date <= $endDate; $date->addDay()) {
            $dates[] = $date->format('Y-m-d');
        }
        
        foreach ($topUsers as $userId) {
            $userData = [
                'user' => $users[$userId]->name ?? 'Unknown',
                'data' => []
            ];
            
            foreach ($dates as $date) {
                $activity = $activities->where('date', $date)
                    ->where('causer_id', $userId)
                    ->first();
                
                $userData['data'][] = [
                    'date' => $date,
                    'count' => $activity ? $activity->count : 0,
                ];
            }
            
            $heatmapData[] = $userData;
        }
        
        return [
            'dates' => $dates,
            'users' => $heatmapData,
        ];
    }
}