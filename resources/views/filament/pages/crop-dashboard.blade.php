<x-filament-panels::page>
    <x-filament::section>
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold tracking-tight">Crop Alerts Dashboard</h2>
            <x-filament::button
                :href="route('filament.admin.pages.manage-crop-tasks')"
                color="primary"
            >
                Manage Tasks
            </x-filament::button>
        </div>
        <p class="text-gray-500 dark:text-gray-400">
            View and manage tasks for crops requiring attention.
        </p>
    </x-filament::section>

    @if (count($this->getHeaderWidgets()) > 0)
        <x-filament-widgets::widgets
            :widgets="$this->getHeaderWidgets()"
            :columns="$this->getHeaderWidgetsColumns()"
        />
    @endif

    @if (count($this->getFooterWidgets()) > 0)
        <x-filament-widgets::widgets
            :widgets="$this->getFooterWidgets()"
            :columns="$this->getFooterWidgetsColumns()"
        />
    @endif
</x-filament-panels::page> 