<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\TimeCard;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class TimeCardSummaryWidget extends BaseWidget
{
    protected static ?int $sort = 10;

    protected function getStats(): array
    {
        $user = Auth::user();
        if (!$user) {
            return [];
        }

        return [
            Stat::make('Today\'s Hours', $this->getTodaysHours())
                ->description($this->getTodaysStatus())
                ->descriptionIcon('heroicon-m-clock')
                ->color($this->getTodaysColor()),
                
            Stat::make('This Week', $this->getWeekHours())
                ->description($this->getWeekDescription())
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('info'),
                
            Stat::make('This Month', $this->getMonthHours())
                ->description($this->getMonthDescription())
                ->descriptionIcon('heroicon-m-calendar')
                ->color('success'),
        ];
    }

    private function getTodaysHours(): string
    {
        $user = Auth::user();
        $todayCards = TimeCard::where('user_id', $user->id)
            ->whereDate('work_date', today())
            ->get();

        if ($todayCards->isEmpty()) {
            return '0.0';
        }

        $totalMinutes = 0;
        foreach ($todayCards as $card) {
            if ($card->status === 'active') {
                $totalMinutes += $card->clock_in->diffInMinutes(now());
            } else {
                $totalMinutes += $card->duration_minutes ?? 0;
            }
        }

        return number_format($totalMinutes / 60, 1);
    }

    private function getTodaysStatus(): string
    {
        $user = Auth::user();
        $activeCard = TimeCard::getActiveForUser($user->id);
        
        if ($activeCard) {
            return 'Currently clocked in';
        }
        
        return 'Not clocked in';
    }

    private function getTodaysColor(): string
    {
        $user = Auth::user();
        $activeCard = TimeCard::getActiveForUser($user->id);
        
        return $activeCard ? 'warning' : 'gray';
    }

    private function getWeekHours(): string
    {
        $user = Auth::user();
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();

        $weekCards = TimeCard::where('user_id', $user->id)
            ->whereBetween('work_date', [$startOfWeek, $endOfWeek])
            ->get();

        $totalMinutes = 0;
        foreach ($weekCards as $card) {
            if ($card->status === 'active') {
                $totalMinutes += $card->clock_in->diffInMinutes(now());
            } else {
                $totalMinutes += $card->duration_minutes ?? 0;
            }
        }

        return number_format($totalMinutes / 60, 1);
    }

    private function getWeekDescription(): string
    {
        $hours = (float) str_replace(',', '', $this->getWeekHours());
        
        if ($hours < 20) {
            return 'Below average';
        } elseif ($hours > 45) {
            return 'Above average';
        } else {
            return 'On track';
        }
    }

    private function getMonthHours(): string
    {
        $user = Auth::user();
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $monthCards = TimeCard::where('user_id', $user->id)
            ->whereBetween('work_date', [$startOfMonth, $endOfMonth])
            ->get();

        $totalMinutes = 0;
        foreach ($monthCards as $card) {
            if ($card->status === 'active') {
                $totalMinutes += $card->clock_in->diffInMinutes(now());
            } else {
                $totalMinutes += $card->duration_minutes ?? 0;
            }
        }

        return number_format($totalMinutes / 60, 1);
    }

    private function getMonthDescription(): string
    {
        $hours = (float) str_replace(',', '', $this->getMonthHours());
        $daysInMonth = Carbon::now()->daysInMonth;
        $daysPassed = Carbon::now()->day;
        $expectedHours = ($daysPassed / $daysInMonth) * 160; // Assuming 160 hours/month target
        
        if ($hours >= $expectedHours * 0.9) {
            return 'Meeting expectations';
        } else {
            return 'Below target';
        }
    }
}