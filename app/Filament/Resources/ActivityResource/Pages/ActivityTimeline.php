<?php

namespace App\Filament\Resources\ActivityResource\Pages;

use Filament\Actions\Action;
use App\Filament\Resources\ActivityResource;
use App\Models\Activity;
use Filament\Resources\Pages\Page;
use Filament\Actions;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class ActivityTimeline extends Page
{
    protected static string $resource = ActivityResource::class;

    protected string $view = 'filament.resources.activity-resource.pages.activity-timeline';
    
    protected static ?string $title = 'Activity Timeline';
    
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-clock';
    
    public Collection $activities;
    public array $filters = [
        'date' => null,
        'user_id' => null,
        'log_name' => null,
    ];
    
    public function mount(): void
    {
        $this->filters['date'] = now()->format('Y-m-d');
        $this->loadActivities();
    }
    
    public function loadActivities(): void
    {
        $query = Activity::with(['causer', 'subject'])
            ->orderBy('created_at', 'desc');
        
        if ($this->filters['date']) {
            $query->whereDate('created_at', $this->filters['date']);
        }
        
        if ($this->filters['user_id']) {
            $query->where('causer_id', $this->filters['user_id'])
                  ->where('causer_type', 'App\Models\User');
        }
        
        if ($this->filters['log_name']) {
            $query->where('log_name', $this->filters['log_name']);
        }
        
        $this->activities = $query->limit(500)->get();
    }
    
    public function updated($property): void
    {
        if (str_starts_with($property, 'filters.')) {
            $this->loadActivities();
        }
    }
    
    public function previousDay(): void
    {
        $this->filters['date'] = Carbon::parse($this->filters['date'])->subDay()->format('Y-m-d');
        $this->loadActivities();
    }
    
    public function nextDay(): void
    {
        $this->filters['date'] = Carbon::parse($this->filters['date'])->addDay()->format('Y-m-d');
        $this->loadActivities();
    }
    
    public function today(): void
    {
        $this->filters['date'] = now()->format('Y-m-d');
        $this->loadActivities();
    }
    
    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Refresh')
                ->icon('heroicon-o-arrow-path')
                ->action('loadActivities'),
        ];
    }
    
    public function getViewData(): array
    {
        return [
            'activities' => $this->activities,
            'filters' => $this->filters,
            'groupedActivities' => $this->activities->groupBy(function ($activity) {
                return $activity->created_at->format('H:00');
            }),
        ];
    }
}