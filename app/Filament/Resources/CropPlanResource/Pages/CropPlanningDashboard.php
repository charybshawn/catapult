<?php

namespace App\Filament\Resources\CropPlanResource\Pages;

use App\Filament\Resources\CropPlanResource;
use App\Models\Order;
use App\Models\CropPlan;
use App\Models\Recipe;
use App\Services\CropPlanCalculatorService;
use App\Services\CropPlanDashboardService;
use App\Services\CalendarEventService;
use Filament\Resources\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class CropPlanningDashboard extends Page
{
    protected static string $resource = CropPlanResource::class;

    protected static string $view = 'filament.resources.crop-plan-resource.pages.crop-planning-dashboard';
    
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    
    protected static ?string $title = 'Crop Planning Dashboard';
    
    protected static ?string $slug = 'dashboard';

    protected CropPlanDashboardService $dashboardService;
    protected CalendarEventService $calendarService;

    public function mount(): void
    {
        $this->dashboardService = app(CropPlanDashboardService::class);
        $this->calendarService = app(CalendarEventService::class);
    }

    /**
     * Get crop plans that need urgent attention (next 7 days).
     * 
     * @return Collection
     */
    public function getUrgentCrops(): Collection
    {
        return $this->dashboardService->getUrgentCrops();
    }

    /**
     * Get overdue crop plans.
     * 
     * @return Collection
     */
    public function getOverdueCrops(): Collection
    {
        return $this->dashboardService->getOverdueCrops();
    }

    /**
     * Get upcoming orders that need crop plans.
     * 
     * @return Collection
     */
    public function getUpcomingOrders(): Collection
    {
        return $this->dashboardService->getUpcomingOrders();
    }

    /**
     * Get dashboard statistics.
     * 
     * @return array
     */
    public function getDashboardStats(): array
    {
        return $this->dashboardService->getDashboardStats();
    }

    /**
     * Get calendar events for the dashboard.
     * 
     * @return array
     */
    public function getCalendarEvents(): array
    {
        return $this->calendarService->getCropPlanningEvents();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('auto_generate_plans')
                ->label('Auto Generate Plans')
                ->icon('heroicon-o-bolt')
                ->color('warning')
                ->action(function () {
                    $calculator = app(CropPlanCalculatorService::class);
                    $upcomingOrders = $this->getUpcomingOrders();
                    
                    $plansCreated = 0;
                    foreach ($upcomingOrders as $order) {
                        // Logic to auto-generate crop plans
                        $plansCreated++;
                    }
                    
                    Notification::make()
                        ->title("Generated {$plansCreated} crop plans")
                        ->success()
                        ->send();
                }),
        ];
    }
}
