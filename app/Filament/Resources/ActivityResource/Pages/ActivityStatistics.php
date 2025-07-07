<?php

namespace App\Filament\Resources\ActivityResource\Pages;

use App\Filament\Resources\ActivityResource;
use App\Services\MetricsService;
use Filament\Resources\Pages\Page;
use Filament\Actions;
use Illuminate\Contracts\View\View;
use Carbon\Carbon;

class ActivityStatistics extends Page
{
    protected static string $resource = ActivityResource::class;

    protected static string $view = 'filament.resources.activity-resource.pages.activity-statistics';
    
    protected static ?string $title = 'Activity Statistics';
    
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    
    public array $stats = [];
    public string $period = 'week';
    public ?string $from = null;
    public ?string $to = null;
    
    protected MetricsService $metricsService;
    
    public function boot(MetricsService $metricsService): void
    {
        $this->metricsService = $metricsService;
    }
    
    public function mount(): void
    {
        $this->from = now()->subDays(30)->format('Y-m-d');
        $this->to = now()->format('Y-m-d');
        $this->loadStatistics();
    }
    
    public function loadStatistics(): void
    {
        $from = $this->from ? Carbon::parse($this->from) : null;
        $to = $this->to ? Carbon::parse($this->to) : null;
        
        $this->stats = $this->metricsService->getSystemMetrics($from, $to);
    }
    
    public function updated($property): void
    {
        if (in_array($property, ['from', 'to', 'period'])) {
            $this->loadStatistics();
        }
    }
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('refresh')
                ->label('Refresh')
                ->icon('heroicon-o-arrow-path')
                ->action('loadStatistics'),
            Actions\Action::make('export')
                ->label('Export Stats')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(function () {
                    return response()->json($this->stats)
                        ->header('Content-Disposition', 'attachment; filename="activity-stats-' . now()->format('Y-m-d') . '.json"');
                }),
        ];
    }
    
    public function getViewData(): array
    {
        return [
            'stats' => $this->stats,
            'period' => $this->period,
            'from' => $this->from,
            'to' => $this->to,
        ];
    }
}