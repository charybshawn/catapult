<?php

namespace App\Filament\Widgets;

use App\Models\CropPlanAggregate;
use App\Models\CropPlan;
use App\Models\CropPlanStatus;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Saade\FilamentFullCalendar\Actions\EditAction;
use Saade\FilamentFullCalendar\Actions\ViewAction;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;

class CropPlanCalendarWidget extends FullCalendarWidget
{
    protected static ?string $heading = 'Crop Planning Calendar';
    
    public Model|string|int|null $record = null;

    /**
     * FullCalendar config options
     */
    public function config(): array
    {
        return [
            'initialView' => 'dayGridMonth',
            'headerToolbar' => [
                'left' => 'prev,next today',
                'center' => 'title',
                'right' => 'dayGridMonth,timeGridWeek,listWeek',
            ],
            'eventDisplay' => 'block',
            'eventTimeFormat' => [
                'hour' => 'numeric',
                'minute' => '2-digit',
                'meridiem' => 'short',
            ],
            'slotMinTime' => '06:00:00',
            'slotMaxTime' => '20:00:00',
            'expandRows' => true,
            'height' => 'auto',
        ];
    }

    /**
     * Fetch events from individual crop plans and aggregate them for display
     */
    public function fetchEvents(array $info): array
    {
        $start = Carbon::parse($info['start']);
        $end = Carbon::parse($info['end']);

        // Fetch all individual crop plans within the date range
        $cropPlans = CropPlan::with(['variety', 'order.customer', 'status'])
            ->where(function ($query) use ($start, $end) {
                $query->whereBetween('plant_by_date', [$start, $end])
                    ->orWhereBetween('seed_soak_date', [$start, $end]);
            })
            ->get();

        // Group plans by variety and date for aggregation
        $groupedPlans = $cropPlans->groupBy(function ($plan) {
            $eventDate = $plan->seed_soak_date ?? $plan->plant_by_date;
            $variety = $plan->variety->common_name ?? 'Unknown';
            $cultivar = $plan->cultivar ? " ({$plan->cultivar})" : '';
            return $eventDate->format('Y-m-d') . '|' . $variety . $cultivar;
        });

        $events = [];

        foreach ($groupedPlans as $key => $plans) {
            [$date, $varietyName] = explode('|', $key);
            
            // Calculate totals for this variety/date combination
            $totalGrams = $plans->sum('grams_needed');
            $totalTrays = $plans->sum('trays_needed');
            $orderCount = $plans->count();
            $firstPlan = $plans->first();
            
            // Determine if this is seed soak or plant date
            $isSeedSoak = $plans->contains(fn($plan) => $plan->seed_soak_date && $plan->seed_soak_date->format('Y-m-d') == $date);
            
            // Get status color (use most common status)
            $statusCounts = $plans->groupBy('status.code')->map->count();
            $dominantStatus = $statusCounts->sortDesc()->keys()->first() ?? 'draft';
            $color = $this->getStatusColor($dominantStatus);

            // Build aggregated event title
            $title = sprintf(
                "%s\n%.1fg (%d trays)\n%d orders%s",
                $varietyName,
                $totalGrams,
                $totalTrays,
                $orderCount,
                $isSeedSoak ? "\n[Seed Soak]" : ""
            );

            $events[] = [
                'id' => 'variety_' . md5($key),
                'title' => $title,
                'start' => $date,
                'backgroundColor' => $color,
                'borderColor' => $color,
                'textColor' => $this->getTextColor($color),
                'extendedProps' => [
                    'type' => 'aggregated_variety',
                    'variety' => $varietyName,
                    'date' => $date,
                    'total_grams' => $totalGrams,
                    'total_trays' => $totalTrays,
                    'order_count' => $orderCount,
                    'is_seed_soak' => $isSeedSoak,
                    'status' => $dominantStatus,
                    'individual_plans' => $plans->map(function ($plan) {
                        return [
                            'id' => $plan->id,
                            'order_id' => $plan->order_id,
                            'customer' => $plan->order->customer->contact_name ?? 'Unknown',
                            'grams_needed' => $plan->grams_needed,
                            'trays_needed' => $plan->trays_needed,
                            'status' => $plan->status->name ?? 'Unknown',
                            'plant_date' => $plan->plant_by_date?->format('Y-m-d'),
                            'seed_soak_date' => $plan->seed_soak_date?->format('Y-m-d'),
                            'harvest_date' => $plan->expected_harvest_date?->format('Y-m-d'),
                        ];
                    })->toArray(),
                ],
            ];
        }

        return $events;
    }

    /**
     * Get color based on status
     */
    protected function getStatusColor(string $status): string
    {
        return match ($status) {
            'draft' => '#eab308',        // yellow-500
            'confirmed', 'active' => '#3b82f6',   // blue-500
            'in_progress' => '#22c55e',  // green-500
            'completed' => '#6b7280',    // gray-500
            'cancelled' => '#ef4444',    // red-500
            default => '#6b7280',        // gray-500
        };
    }

    /**
     * Lighten a color for secondary events
     */
    protected function lightenColor(string $color): string
    {
        // Simple lightening by adjusting the hex values
        $hex = str_replace('#', '', $color);
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        // Lighten by 30%
        $r = min(255, $r + (255 - $r) * 0.3);
        $g = min(255, $g + (255 - $g) * 0.3);
        $b = min(255, $b + (255 - $b) * 0.3);

        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }

    /**
     * Get appropriate text color based on background
     */
    protected function getTextColor(string $bgColor): string
    {
        // Simple luminance calculation
        $hex = str_replace('#', '', $bgColor);
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;

        return $luminance > 0.5 ? '#000000' : '#ffffff';
    }

    /**
     * Define view action for events
     */
    protected function viewAction(): ViewAction
    {
        return ViewAction::make()
            ->modalHeading(function ($arguments) {
                // Debug: Let's see what we're getting
                Log::info('Calendar modal arguments:', $arguments ?? []);
                
                // Extract event data from the click event
                $event = $arguments['event'] ?? [];
                $props = $event['extendedProps'] ?? [];
                $variety = $props['variety'] ?? 'Crop Plan';
                $date = $props['date'] ?? 'Details';
                return $variety . ' - ' . $date;
            })
            ->modalContent(function ($arguments) {
                // Extract event data from the click event
                $event = $arguments['event'] ?? [];
                $props = $event['extendedProps'] ?? [];
                
                Log::info('Calendar modal props:', $props);
                
                if (($props['type'] ?? '') === 'aggregated_variety') {
                    $individualPlans = $props['individual_plans'] ?? [];
                    
                    return view('filament.widgets.crop-plan-calendar-modal', [
                        'variety' => $props['variety'] ?? 'Unknown',
                        'date' => $props['date'] ?? 'Unknown',
                        'totalOrders' => $props['order_count'] ?? 0,
                        'totalGrams' => $props['total_grams'] ?? 0,
                        'totalTrays' => $props['total_trays'] ?? 0,
                        'status' => ucfirst($props['status'] ?? 'unknown'),
                        'isSeedSoak' => $props['is_seed_soak'] ?? false,
                        'individualPlans' => $individualPlans,
                    ]);
                } else {
                    return view('filament.widgets.crop-plan-calendar-modal-debug', [
                        'props' => $props,
                    ]);
                }
            })
            ->modalSubmitActionLabel('Close')
            ->modalCancelActionLabel('View List')
            ->url(function ($arguments) {
                $event = $arguments['event'] ?? [];
                $props = $event['extendedProps'] ?? [];
                
                if (($props['type'] ?? '') === 'aggregated_variety') {
                    // Link to crop plans list filtered by this variety and date
                    return route('filament.admin.resources.crop-plans.index', [
                        'tableFilters[plant_by_date][value]' => $props['date'] ?? null,
                    ]);
                } else {
                    // Link to individual crop plan edit page
                    return route('filament.admin.resources.crop-plans.edit', $props['model_id'] ?? 1);
                }
            });
    }

    /**
     * Define edit action for events (if needed)
     */
    protected function editAction(): EditAction
    {
        return EditAction::make()
            ->modalHeading(fn ($arguments) => 'Edit ' . ($arguments['extendedProps']['variety'] ?? 'Crop Plan'))
            ->form(function ($arguments) {
                $props = $arguments['extendedProps'];
                
                // Return appropriate form fields based on event type
                if ($props['type'] === 'aggregated') {
                    return [
                        Grid::make(2)->schema([
                            TextInput::make('total_grams_needed')
                                ->label('Total Grams')
                                ->numeric()
                                ->required(),
                            
                            TextInput::make('total_trays_needed')
                                ->label('Total Trays')
                                ->numeric()
                                ->required(),
                        ]),
                        
                        DatePicker::make('plant_date')
                            ->label('Plant Date')
                            ->required(),
                        
                        DatePicker::make('seed_soak_date')
                            ->label('Seed Soak Date'),
                        
                        Select::make('status')
                            ->options([
                                'draft' => 'Draft',
                                'confirmed' => 'Confirmed',
                                'in_progress' => 'In Progress',
                                'completed' => 'Completed',
                            ])
                            ->required(),
                    ];
                } else {
                    return [
                        // Individual crop plan form fields
                        Grid::make(2)->schema([
                            TextInput::make('grams_needed')
                                ->label('Grams Needed')
                                ->numeric()
                                ->required(),
                            
                            TextInput::make('trays_needed')
                                ->label('Trays Needed')
                                ->numeric()
                                ->required(),
                        ]),
                        
                        DatePicker::make('plant_by_date')
                            ->label('Plant By Date')
                            ->required(),
                        
                        DatePicker::make('seed_soak_date')
                            ->label('Seed Soak Date'),
                    ];
                }
            })
            ->mutateFormDataUsing(function (array $data, $arguments) {
                // Prepare data for saving
                return $data;
            })
            ->action(function (array $data, $arguments) {
                $props = $arguments['extendedProps'];
                
                if ($props['type'] === 'aggregated') {
                    $model = CropPlanAggregate::find($props['model_id']);
                } else {
                    $model = CropPlan::find($props['model_id']);
                }
                
                if ($model) {
                    $model->update($data);
                }
            });
    }

    /**
     * Override to handle event clicks
     */
    public function onEventClick(array $event): void
    {
        parent::onEventClick($event);
    }

    /**
     * Calendar actions
     */
    protected function getActions(): array
    {
        return [
            $this->viewAction(),
            // Optionally add edit action
            // $this->editAction(),
        ];
    }
}