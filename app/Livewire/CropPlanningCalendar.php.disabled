<?php

namespace App\Livewire;

use Livewire\Component;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;

class CropPlanningCalendar extends FullCalendarWidget
{
    public array $events = [];

    public function mount(array $events = [])
    {
        $this->events = $events;
    }

    public function fetchEvents(array $fetchInfo): array
    {
        return $this->events;
    }

    public function getOptions(): array
    {
        return [
            'headerToolbar' => [
                'left' => 'prev,next today',
                'center' => 'title',
                'right' => 'dayGridMonth,timeGridWeek,timeGridDay'
            ],
            'initialView' => 'dayGridMonth',
            'height' => 'auto',
            'aspectRatio' => 1.8,
            'eventDisplay' => 'block',
            'displayEventTime' => false,
            'eventMaxStack' => 3,
            'themeSystem' => 'bootstrap5',
            'dayMaxEvents' => true,
            'moreLinkClick' => 'popover',
        ];
    }

    public function onEventClick(array $event): void
    {
        $eventType = $event['extendedProps']['type'] ?? 'unknown';
        
        if ($eventType === 'delivery') {
            $orderId = $event['extendedProps']['orderId'] ?? null;
            if ($orderId) {
                $this->dispatch('open-order-details', orderId: $orderId);
            }
        } elseif ($eventType === 'planting') {
            $planId = $event['extendedProps']['planId'] ?? null;
            if ($planId) {
                $this->dispatch('open-crop-plan-details', planId: $planId);
            }
        }
    }
}
