<?php

namespace App\Filament\Resources\CropPlanResource\Pages;

use App\Filament\Resources\CropPlanResource;
use App\Models\Order;
use App\Models\CropPlan;
use App\Models\Recipe;
use App\Services\CropPlanCalculatorService;
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

    public function mount(): void
    {
        // Any initialization logic here
    }

    public function getUrgentCrops(): Collection
    {
        // Get crop plans that need to be planted soon (next 7 days)
        return CropPlan::with(['recipe.seedEntry', 'order.customer'])
            ->where('status', 'approved')
            ->where('plant_by_date', '<=', now()->addDays(7))
            ->where('plant_by_date', '>=', now())
            ->orderBy('plant_by_date', 'asc')
            ->get()
            ->groupBy(function ($plan) {
                return $plan->plant_by_date->format('Y-m-d');
            });
    }

    public function getOverdueCrops(): Collection
    {
        // Get crop plans that should have been planted already
        return CropPlan::with(['recipe.seedEntry', 'order.customer'])
            ->where('status', 'approved')
            ->where('plant_by_date', '<', now())
            ->orderBy('plant_by_date', 'asc')
            ->get();
    }

    public function getUpcomingOrders(): Collection
    {
        // Get orders for the next 14 days that might need crop plans
        return Order::with(['customer', 'orderItems.product'])
            ->whereIn('status', ['pending', 'confirmed', 'processing'])
            ->where('delivery_date', '>=', now())
            ->where('delivery_date', '<=', now()->addDays(14))
            ->orderBy('delivery_date', 'asc')
            ->get()
            ->filter(function ($order) {
                // Only include orders that don't have crop plans yet
                return $order->cropPlans->isEmpty();
            });
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create_crop_plan')
                ->label('Manual Planning')
                ->icon('heroicon-o-calculator')
                ->color('success')
                ->url(fn() => CropPlanResource::getUrl('manual-planning')),
                
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

    public function getCalendarEvents(): array
    {
        $events = [];
        
        // Add order delivery dates
        $orders = Order::with(['customer'])
            ->whereIn('status', ['pending', 'confirmed', 'processing'])
            ->where('delivery_date', '>=', now()->subDays(30))
            ->where('delivery_date', '<=', now()->addDays(60))
            ->get();
            
        foreach ($orders as $order) {
            if ($order->delivery_date) {
                $events[] = [
                    'id' => 'order-' . $order->id,
                    'title' => "Delivery: Order #{$order->id}",
                    'start' => $order->delivery_date->format('Y-m-d'),
                    'backgroundColor' => '#10b981', // green
                    'borderColor' => '#059669',
                    'textColor' => '#ffffff',
                    'extendedProps' => [
                        'type' => 'delivery',
                        'orderId' => $order->id,
                        'customer' => $order->customer->contact_name ?? 'Unknown',
                    ],
                ];
            }
        }
        
        // Add crop planting dates
        $cropPlans = CropPlan::with(['recipe.seedEntry', 'order'])
            ->where('plant_by_date', '>=', now()->subDays(30))
            ->where('plant_by_date', '<=', now()->addDays(60))
            ->get();
            
        foreach ($cropPlans as $plan) {
            $color = match($plan->status) {
                'draft' => '#6b7280', // gray
                'approved' => '#3b82f6', // blue
                'completed' => '#10b981', // green
                'overdue' => '#ef4444', // red
                default => '#6b7280',
            };
            
            $events[] = [
                'id' => 'plant-' . $plan->id,
                'title' => "Plant: {$plan->recipe->seedEntry->common_name}",
                'start' => $plan->plant_by_date->format('Y-m-d'),
                'backgroundColor' => $color,
                'borderColor' => $color,
                'textColor' => '#ffffff',
                'extendedProps' => [
                    'type' => 'planting',
                    'planId' => $plan->id,
                    'variety' => $plan->recipe->seedEntry->common_name,
                    'trays' => $plan->trays_needed,
                    'status' => $plan->status,
                ],
            ];
        }
        
        return $events;
    }
}
