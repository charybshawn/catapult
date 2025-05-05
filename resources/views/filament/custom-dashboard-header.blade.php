@php
    $activeTab = request()->query('tab', session('dashboard_tab', 'active-crops'));
    session(['dashboard_tab' => $activeTab]);
@endphp

<div class="w-full px-4 sm:px-6 lg:px-8 py-4" x-data="{ activeTab: '{{ $activeTab }}' }">
    <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Farm Dashboard</h1>
    </div>

    <x-filament::tabs id="dashboard-tabs">
        <x-filament::tabs.item
            :active="$activeTab === 'active-crops'"
            x-on:click="activeTab = 'active-crops'; history.replaceState(null, null, '?tab=active-crops');"
            icon="heroicon-m-squares-2x2"
        >
            Active Crops
        </x-filament::tabs.item>

        <x-filament::tabs.item
            :active="$activeTab === 'crop-alerts'"
            x-on:click="activeTab = 'crop-alerts'; history.replaceState(null, null, '?tab=crop-alerts');"
            icon="heroicon-m-bell-alert"
        >
            Crop Alerts
        </x-filament::tabs.item>

        <x-filament::tabs.item
            :active="$activeTab === 'inventory-alerts'"
            x-on:click="activeTab = 'inventory-alerts'; history.replaceState(null, null, '?tab=inventory-alerts');"
            icon="heroicon-m-archive-box-arrow-down"
        >
            Inventory/Consumable Alerts
        </x-filament::tabs.item>
    </x-filament::tabs>

    <div class="mt-6">
        <!-- Active Crops Tab -->
        <div x-show="activeTab === 'active-crops'" x-cloak>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <x-filament::section class="col-span-1">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Active Crops</h2>
                        <x-filament::link
                            color="primary"
                            tag="a"
                            :href="route('filament.admin.resources.crops.index')"
                            size="sm"
                        >
                            View All
                        </x-filament::link>
                    </div>
                    <p class="text-3xl font-bold text-primary-600 dark:text-primary-400">{{ $activeCropsCount }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Crops in production</p>
                </x-filament::section>

                <x-filament::section class="col-span-1">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Active Trays</h2>
                    <p class="text-3xl font-bold text-primary-600 dark:text-primary-400">{{ $activeTraysCount }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Trays in use</p>
                </x-filament::section>

                <x-filament::section class="col-span-1">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Crop Alerts</h2>
                        <x-filament::link
                            color="primary"
                            tag="a"
                            :href="route('filament.admin.resources.task-schedules.index')"
                            size="sm"
                        >
                            Manage Alerts
                        </x-filament::link>
                    </div>
                    <p class="text-3xl font-bold text-primary-600 dark:text-primary-400">{{ $tasksCount }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Active alerts</p>
                    @if($overdueTasksCount > 0)
                        <p class="mt-2 text-sm font-medium text-danger-600 dark:text-danger-400">{{ $overdueTasksCount }} overdue</p>
                    @endif
                </x-filament::section>

                <x-filament::section class="col-span-1">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Low Stock Items</h2>
                        <x-filament::link
                            color="primary"
                            tag="a"
                            :href="route('filament.admin.resources.consumables.index')"
                            size="sm"
                        >
                            View Inventory
                        </x-filament::link>
                    </div>
                    <p class="text-3xl font-bold text-warning-600 dark:text-warning-400">{{ $lowStockCount }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Items below threshold</p>
                </x-filament::section>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mt-6">
                @if($cropsNeedingHarvest->count() > 0)
                    <x-filament::section>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Ready to Harvest</h2>
                        <div class="space-y-3">
                            @foreach($cropsNeedingHarvest as $crop)
                                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800/50 rounded-lg">
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-white">{{ $crop->recipe->seedVariety->name }}</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            Tray #{{ $crop->tray_number }} • Planted: {{ $crop->planted_at->format('M d') }} • Days: {{ $crop->planted_at->diffInDays(now()) }}
                                        </p>
                                    </div>
                                    <div class="flex gap-2">
                                        <x-filament::icon-button
                                            icon="heroicon-o-eye"
                                            tag="a"
                                            :href="route('filament.admin.resources.crops.edit', $crop)"
                                            tooltip="View Crop"
                                            size="sm"
                                        />
                                        <x-filament::icon-button
                                            icon="heroicon-o-scissors"
                                            color="success"
                                            tag="a"
                                            :href="route('filament.admin.resources.crops.harvest', $crop)"
                                            tooltip="Harvest Crop"
                                            size="sm"
                                        />
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </x-filament::section>
                @endif

                @if($recentlySowedCrops->count() > 0)
                    <x-filament::section>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Recently Sowed</h2>
                        <div class="space-y-3">
                            @foreach($recentlySowedCrops as $crop)
                                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800/50 rounded-lg">
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-white">{{ $crop->recipe->seedVariety->name }}</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            Tray #{{ $crop->tray_number }} • Planted: {{ $crop->planted_at->format('M d') }} • Stage: {{ ucfirst($crop->current_stage) }}
                                        </p>
                                    </div>
                                    <x-filament::icon-button
                                        icon="heroicon-o-eye"
                                        tag="a"
                                        :href="route('filament.admin.resources.crops.edit', $crop)"
                                        tooltip="View Crop"
                                        size="sm"
                                    />
                                </div>
                            @endforeach
                        </div>
                    </x-filament::section>
                @endif
            </div>
        </div>

        <!-- Crop Alerts Tab -->
        <div x-show="activeTab === 'crop-alerts'" x-cloak>
             <div class="space-y-6">
                <livewire:app.filament.widgets.todays-crop-alerts-widget />
                <livewire:app.filament.widgets.grouped-crop-alerts-widget />
             </div>
        </div>

        <!-- Inventory/Consumable Alerts Tab -->
        <div x-show="activeTab === 'inventory-alerts'" x-cloak>
            <x-filament::section>
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Inventory & Consumable Alerts</h2>
                    <x-filament::link
                        color="primary"
                        tag="a"
                        :href="route('filament.admin.resources.consumables.index')"
                        size="sm"
                    >
                        View All Inventory
                    </x-filament::link>
                </div>
                @if($lowStockItems->count() > 0)
                    <div class="space-y-3">
                        @foreach($lowStockItems as $item)
                            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800/50 rounded-lg">
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-white">{{ $item->name }}</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        Current: <span class="font-semibold {{ $item->current_stock <= $item->restock_threshold ? 'text-danger-600 dark:text-danger-400' : 'text-gray-700 dark:text-gray-200' }}">{{ $item->current_stock }}</span> • Threshold: {{ $item->restock_threshold }}
                                    </p>
                                </div>
                                <div class="flex gap-2">
                                    <x-filament::icon-button
                                        icon="heroicon-o-eye"
                                        tag="a"
                                        :href="route('filament.admin.resources.consumables.edit', $item)"
                                        tooltip="View Item"
                                        size="sm"
                                    />
                                    <x-filament::icon-button
                                        icon="heroicon-o-shopping-cart"
                                        color="warning"
                                        tag="a"
                                        :href="route('filament.admin.resources.consumables.adjust-stock', $item)" 
                                        tooltip="Restock Item"
                                        size="sm"
                                    />
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-center text-gray-500 dark:text-gray-400 py-4">No items are currently low on stock.</p>
                @endif
            </x-filament::section>
        </div>
    </div>
</div>

<style>
    [x-cloak] { display: none !important; }
</style> 