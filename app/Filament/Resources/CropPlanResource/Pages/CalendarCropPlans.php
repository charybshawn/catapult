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
                        
                        // Group plans by order for detailed feedback
                        $plansByOrder = $cropPlans->groupBy('order_id');
                        
                        $title = "Crop Plan Generation Complete";
                        $body = "Date range: {$startDate} to {$endDate}\n";
                        $body .= "📦 Found {$orderCount} orders eligible for crop planning\n";
                        $body .= "🌱 Generated {$count} crop plans total";
                        
                        if ($count > 0) {
                            $body .= "\n\nPlans by Order:\n";
                            foreach ($plansByOrder as $orderId => $plans) {
                                $order = $orders->find($orderId);
                                $customerName = $order?->customer?->contact_name ?? 'Unknown';
                                $harvestDate = $order?->harvest_date?->format('M d') ?? 'Unknown';
                                $body .= "• Order #{$orderId} ({$customerName}) - {$plans->count()} plans (Harvest: {$harvestDate})\n";
                            }
                            
                            // Show variety breakdown
                            $varietyBreakdown = $cropPlans->groupBy('variety.common_name')->map->count();
                            if ($varietyBreakdown->count() > 0) {
                                $body .= "\nVariety Breakdown:\n";
                                foreach ($varietyBreakdown as $variety => $planCount) {
                                    $body .= "• {$variety}: {$planCount} plans\n";
                                }
                            }
                        } else {
                            if ($orderCount === 0) {
                                $body .= "\n\nNo orders found in the date range. You may need to:\n";
                                $body .= "• Generate orders from recurring templates first\n";
                                $body .= "• Check that orders have harvest dates in the next 30 days\n";
                                $body .= "• Verify order statuses are valid (draft, pending, confirmed, in_production)";
                            } else {
                                $body .= "\n\nAll {$orderCount} orders already have crop plans or couldn't be processed.";
                            }
                        }
                        
                        $notificationType = $count > 0 ? 'success' : 'warning';
                        
                        Notification::make()
                            ->title($title)
                            ->body($body)
                            ->{$notificationType}()
                            ->persistent()
                            ->send();
                            
                        // Refresh the calendar widget
                        $this->dispatch('refresh-calendar');
                            
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Error Generating Crop Plans')
                            ->body("Failed to generate crop plans: {$e->getMessage()}\n\nPlease check the logs for more details.")
                            ->danger()
                            ->persistent()
                            ->send();
                    }
                })
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