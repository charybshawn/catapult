<?php

namespace App\Filament\Widgets;

use App\Models\Activity;
use App\Models\User;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;

class TopActiveUsersWidget extends Widget
{
    protected string $view = 'filament.widgets.top-active-users';
    
    protected static ?int $sort = 6;
    
    protected int | string | array $columnSpan = '1/2';
    
    protected static ?string $heading = 'Top Active Users';
    
    public function getTopUsers(): array
    {
        return Activity::select('causer_id', DB::raw('COUNT(*) as activity_count'))
            ->where('causer_type', User::class)
            ->whereNotNull('causer_id')
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('causer_id')
            ->orderByDesc('activity_count')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                $user = User::find($item->causer_id);
                return [
                    'name' => $user?->name ?? 'Unknown User',
                    'email' => $user?->email ?? '',
                    'count' => $item->activity_count,
                    'percentage' => 0, // Will be calculated below
                ];
            })
            ->toArray();
    }
    
    public function getViewData(): array
    {
        $users = $this->getTopUsers();
        
        // Calculate percentages
        $maxCount = collect($users)->max('count') ?? 1;
        foreach ($users as &$user) {
            $user['percentage'] = round(($user['count'] / $maxCount) * 100);
        }
        
        return [
            'users' => $users,
        ];
    }
}