<?php

namespace App\Filament\Resources\CropPlanResource\Pages;

use App\Filament\Resources\CropPlanResource;
use App\Filament\Widgets\CropPlanCalendarWidget;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;
use Filament\Notifications\Notification;

class CalendarCropPlans extends Page
{
    protected static string $resource = CropPlanResource::class;

    protected static string $view = 'filament.resources.crop-plan-resource.pages.calendar-crop-plans';

    protected static ?string $title = 'Crop Planning Calendar';

    protected static ?string $navigationLabel = 'Calendar';

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('regenerate_crop_plans')
                ->label('Regenerate Crop Plans')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->modalHeading('Crop Plan Generation Results')
                ->modalWidth('4xl')
                ->action(function () {
                    $cropPlanningService = app(\App\Services\CropPlanningService::class);
                    
                    try {
                        $startDate = now()->toDateString();
                        $endDate = now()->addDays(30)->toDateString();
                        
                        // First, check what orders are available
                        $orders = \App\Models\Order::with(['customer', 'status'])
                            ->where('harvest_date', '>=', $startDate)
                            ->where('harvest_date', '<=', $endDate)
                            ->where('is_recurring', false)
                            ->whereHas('status', function ($query) {
                                $query->whereIn('code', ['draft', 'pending', 'confirmed', 'in_production']);
                            })
                            ->get();
                        
                        // Generate crop plans for orders in the next 30 days
                        $cropPlans = $cropPlanningService->generateIndividualPlansForAllOrders($startDate, $endDate);
                        
                        $count = $cropPlans->count();
                        $orderCount = $orders->count();
                        
                        // Store results in session for modal display
                        session([
                            'crop_plan_results' => [
                                'success' => true,
                                'start_date' => $startDate,
                                'end_date' => $endDate,
                                'order_count' => $orderCount,
                                'plan_count' => $count,
                                'orders' => $orders,
                                'plans' => $cropPlans,
                                'plans_by_order' => $cropPlans->groupBy('order_id'),
                                'variety_breakdown' => $cropPlans->groupBy('variety.common_name')->map->count(),
                            ]
                        ]);
                        
                        // Refresh the calendar widget
                        $this->dispatch('refresh-calendar');
                        
                        // Redirect to show modal
                        $this->dispatch('open-results-modal');
                            
                    } catch (\Exception $e) {
                        session([
                            'crop_plan_results' => [
                                'success' => false,
                                'error' => $e->getMessage(),
                            ]
                        ]);
                        
                        $this->dispatch('open-results-modal');
                    }
                })
                ->modalContent(view('filament.modals.crop-plan-generation-results'))
                ->requiresConfirmation()
                ->modalHeading('Regenerate Crop Plans')
                ->modalDescription('This will generate crop plans for all valid orders in the next 30 days. Existing draft plans for the same orders will be replaced.')
                ->modalSubmitActionLabel('Generate Plans'),
                
            Action::make('list')
                ->label('List View')
                ->icon('heroicon-o-list-bullet')
                ->url(CropPlanResource::getUrl('list'))
                ->color('gray'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            CropPlanCalendarWidget::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [];
    }

    /**
     * Override to use full width for calendar
     */
    public function getMaxContentWidth(): ?string
    {
        return 'full';
    }
}