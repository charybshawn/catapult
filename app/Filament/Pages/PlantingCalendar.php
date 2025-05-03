<?php

namespace App\Filament\Pages;

use App\Models\PlantingSchedule;
use Carbon\Carbon;
use Filament\Pages\Page;
use Illuminate\Contracts\View\View;
use Filament\Forms;
use Filament\Forms\Form;

class PlantingCalendar extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calendar';
    protected static ?string $navigationLabel = 'Planting Calendar';
    protected static ?string $title = 'Planting Calendar';
    protected static ?string $navigationGroup = 'Farm Operations';
    protected static ?int $navigationSort = 5;
    
    protected static string $view = 'filament.pages.planting-calendar';
    
    public $selectedMonth;
    public $events = [];
    
    public function mount(): void
    {
        $this->form->fill([
            'selectedMonth' => now()->format('Y-m'),
        ]);
        
        $this->selectedMonth = now()->format('Y-m');
        $this->loadEvents();
    }
    
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('selectedMonth')
                    ->label('Select Month')
                    ->type('month')
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state) {
                        $this->selectedMonth = $state;
                        $this->loadEvents();
                    }),
            ]);
    }
    
    public function loadEvents(): void
    {
        $month = Carbon::createFromFormat('Y-m', $this->selectedMonth);
        $startDate = $month->copy()->startOfMonth()->startOfWeek();
        $endDate = $month->copy()->endOfMonth()->endOfWeek();
        
        // Get planting schedules
        $plantingSchedules = PlantingSchedule::inDateRange($startDate, $endDate)
            ->with('recipe')
            ->get();
        
        $events = [];
        
        // Process planting events
        foreach ($plantingSchedules as $schedule) {
            $events[] = [
                'id' => "plant_{$schedule->id}",
                'title' => "Plant {$schedule->recipe->name}",
                'start' => $schedule->planting_date->format('Y-m-d'),
                'end' => $schedule->planting_date->format('Y-m-d'),
                'type' => 'planting',
                'status' => $schedule->status,
                'trays' => $schedule->trays_required,
                'url' => route('filament.admin.resources.planting-schedules.edit', $schedule),
                'color' => '#28a745', // Green for planting
            ];
            
            $events[] = [
                'id' => "harvest_{$schedule->id}",
                'title' => "Harvest {$schedule->recipe->name}",
                'start' => $schedule->target_harvest_date->format('Y-m-d'),
                'end' => $schedule->target_harvest_date->format('Y-m-d'),
                'type' => 'harvesting',
                'status' => $schedule->status,
                'trays' => $schedule->trays_required,
                'url' => route('filament.admin.resources.planting-schedules.edit', $schedule),
                'color' => '#dc3545', // Red for harvesting
            ];
        }
        
        $this->events = $events;
    }
    
    public function getViewData(): array
    {
        $month = Carbon::createFromFormat('Y-m', $this->selectedMonth);
        
        return [
            'events' => $this->events,
            'monthLabel' => $month->format('F Y'),
            'selectedMonth' => $this->selectedMonth,
        ];
    }
} 