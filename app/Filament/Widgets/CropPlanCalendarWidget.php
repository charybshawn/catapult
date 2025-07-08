<?php

namespace App\Filament\Widgets;

use App\Models\AggregatedCropPlan;
use App\Models\CropPlan;
use App\Models\CropPlanStatus;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Illuminate\Database\Eloquent\Model;
use Saade\FilamentFullCalendar\Actions\EditAction;
use Saade\FilamentFullCalendar\Actions\ViewAction;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;

class CropPlanCalendarWidget extends FullCalendarWidget
{
    protected static ?string $heading = 'Crop Planning Calendar';

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
     * Fetch events from aggregated crop plans
     */
    public function fetchEvents(array $info): array
    {
        $start = Carbon::parse($info['start']);
        $end = Carbon::parse($info['end']);

        $events = [];

        // Fetch aggregated crop plans within the date range
        $aggregatedPlans = AggregatedCropPlan::with(['variety', 'cropPlans'])
            ->where(function ($query) use ($start, $end) {
                $query->whereBetween('plant_date', [$start, $end])
                    ->orWhereBetween('seed_soak_date', [$start, $end]);
            })
            ->get();

        foreach ($aggregatedPlans as $plan) {
            // Determine the primary date (seed soak if exists, otherwise plant date)
            $eventDate = $plan->seed_soak_date ?? $plan->plant_date;
            
            if (!$eventDate) {
                continue;
            }

            // Get color based on status
            $color = $this->getStatusColor($plan->status);

            // Build event title with key information
            $variety = $plan->variety->common_name ?? 'Unknown Variety';
            $title = sprintf(
                "%s\n%.1fg (%d trays)%s",
                $variety,
                $plan->total_grams_needed,
                $plan->total_trays_needed,
                $plan->seed_soak_date ? "\n[Seed Soak]" : ""
            );

            $events[] = [
                'id' => 'aggregated_' . $plan->id,
                'title' => $title,
                'start' => $eventDate->format('Y-m-d'),
                'backgroundColor' => $color,
                'borderColor' => $color,
                'textColor' => $this->getTextColor($color),
                'extendedProps' => [
                    'type' => 'aggregated',
                    'model_id' => $plan->id,
                    'variety' => $variety,
                    'total_grams' => $plan->total_grams_needed,
                    'total_trays' => $plan->total_trays_needed,
                    'seed_soak_required' => !is_null($plan->seed_soak_date),
                    'plant_date' => $plan->plant_date?->format('Y-m-d'),
                    'harvest_date' => $plan->harvest_date?->format('Y-m-d'),
                    'status' => $plan->status,
                    'order_count' => $plan->total_orders,
                ],
            ];

            // If there's a seed soak date AND a plant date, add a secondary event for planting
            if ($plan->seed_soak_date && $plan->plant_date && !$plan->seed_soak_date->isSameDay($plan->plant_date)) {
                $events[] = [
                    'id' => 'aggregated_plant_' . $plan->id,
                    'title' => $variety . "\n[Plant after soak]",
                    'start' => $plan->plant_date->format('Y-m-d'),
                    'backgroundColor' => $this->lightenColor($color),
                    'borderColor' => $color,
                    'textColor' => $this->getTextColor($color),
                    'extendedProps' => [
                        'type' => 'aggregated_planting',
                        'model_id' => $plan->id,
                        'variety' => $variety,
                        'is_secondary' => true,
                    ],
                ];
            }
        }

        // Also fetch individual crop plans that aren't part of aggregations
        $individualPlans = CropPlan::with(['variety', 'recipe', 'status', 'order.customer'])
            ->whereNull('aggregated_crop_plan_id')
            ->where(function ($query) use ($start, $end) {
                $query->whereBetween('plant_by_date', [$start, $end])
                    ->orWhereBetween('seed_soak_date', [$start, $end]);
            })
            ->get();

        foreach ($individualPlans as $plan) {
            // Determine the primary date
            $eventDate = $plan->seed_soak_date ?? $plan->plant_by_date;
            
            if (!$eventDate) {
                continue;
            }

            // Get color based on status
            $color = $plan->status ? $this->getStatusColor($plan->status->code) : '#6b7280';

            // Build event title
            $variety = $plan->variety->common_name ?? ($plan->recipe->name ?? 'Unknown');
            $customer = $plan->order->customer->contact_name ?? 'Unknown Customer';
            $title = sprintf(
                "%s\n%.1fg (%d trays)\n[%s]%s",
                $variety,
                $plan->grams_needed,
                $plan->trays_needed,
                $customer,
                $plan->seed_soak_date ? "\n[Seed Soak]" : ""
            );

            $events[] = [
                'id' => 'individual_' . $plan->id,
                'title' => $title,
                'start' => $eventDate->format('Y-m-d'),
                'backgroundColor' => $color,
                'borderColor' => $color,
                'textColor' => $this->getTextColor($color),
                'extendedProps' => [
                    'type' => 'individual',
                    'model_id' => $plan->id,
                    'variety' => $variety,
                    'grams_needed' => $plan->grams_needed,
                    'trays_needed' => $plan->trays_needed,
                    'seed_soak_required' => !is_null($plan->seed_soak_date),
                    'plant_date' => $plan->plant_by_date?->format('Y-m-d'),
                    'harvest_date' => $plan->expected_harvest_date?->format('Y-m-d'),
                    'status' => $plan->status?->code,
                    'customer' => $customer,
                    'order_id' => $plan->order_id,
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
            ->modalHeading(fn ($arguments) => $arguments['extendedProps']['variety'] ?? 'Crop Plan Details')
            ->modalDescription(function ($arguments) {
                $props = $arguments['extendedProps'];
                $details = [];

                if ($props['type'] === 'aggregated') {
                    $details[] = "Total Orders: {$props['order_count']}";
                    $details[] = "Total Grams: {$props['total_grams']}g";
                    $details[] = "Total Trays: {$props['total_trays']}";
                } else {
                    $details[] = "Customer: {$props['customer']}";
                    $details[] = "Grams Needed: {$props['grams_needed']}g";
                    $details[] = "Trays Needed: {$props['trays_needed']}";
                }

                if ($props['seed_soak_required']) {
                    $details[] = "Seed Soak Required: Yes";
                }

                if ($props['plant_date']) {
                    $details[] = "Plant Date: {$props['plant_date']}";
                }

                if ($props['harvest_date']) {
                    $details[] = "Expected Harvest: {$props['harvest_date']}";
                }

                $details[] = "Status: " . ucfirst($props['status']);

                return implode("\n", $details);
            })
            ->modalSubmitActionLabel('Close')
            ->modalCancelActionLabel('Edit')
            ->url(function ($arguments) {
                $props = $arguments['extendedProps'];
                
                if ($props['type'] === 'aggregated') {
                    // Link to aggregated crop plan view/edit page if it exists
                    return null; // Adjust this based on your routes
                } else {
                    // Link to individual crop plan edit page
                    return route('filament.admin.resources.crop-plans.edit', $props['model_id']);
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
                    $model = AggregatedCropPlan::find($props['model_id']);
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