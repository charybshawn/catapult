<x-filament-panels::page>
    <div class="mb-4">
        <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
            <div class="flex items-center gap-2">
                <span class="inline-block w-4 h-4 rounded" style="background-color: #eab308;"></span>
                <span>Draft</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-block w-4 h-4 rounded" style="background-color: #3b82f6;"></span>
                <span>Confirmed/Active</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-block w-4 h-4 rounded" style="background-color: #22c55e;"></span>
                <span>In Progress</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-block w-4 h-4 rounded" style="background-color: #6b7280;"></span>
                <span>Completed</span>
            </div>
        </div>
    </div>

    @livewire(\App\Filament\Widgets\CropPlanCalendarWidget::class)
</x-filament-panels::page>