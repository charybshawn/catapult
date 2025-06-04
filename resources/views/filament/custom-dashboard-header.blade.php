@php
    $activeTab = request()->query('tab', session('dashboard_tab', 'operations'));
    session(['dashboard_tab' => $activeTab]);
@endphp

<div class="w-full px-4 sm:px-6 lg:px-8 py-4" 
     x-data="dashboardData" 
     x-init="initDashboard()">
    <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Farm Operations Dashboard</h1>
        <div class="flex items-center gap-4">
            <div class="flex items-center gap-2">
                <button 
                    @click="refreshData()" 
                    :disabled="isRefreshing"
                    class="flex items-center gap-1 px-3 py-1 text-sm bg-primary-100 text-primary-700 rounded-md hover:bg-primary-200 transition-colors disabled:opacity-50"
                    :class="{ 'animate-pulse': isRefreshing }"
                >
                    <svg class="w-4 h-4" :class="{ 'animate-spin': isRefreshing }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    <span x-text="isRefreshing ? 'Refreshing...' : 'Refresh'"></span>
                </button>
                
                <button 
                    @click="toggleAutoRefresh()" 
                    class="flex items-center gap-1 px-3 py-1 text-sm rounded-md transition-colors"
                    :class="autoRefresh ? 'bg-success-100 text-success-700 hover:bg-success-200' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300'"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span x-text="autoRefresh ? 'Auto: ON' : 'Auto: OFF'"></span>
                </button>
            </div>
            
            <div class="flex items-center gap-2">
                <div class="flex items-center gap-1">
                    <div class="w-2 h-2 rounded-full" :class="autoRefresh ? 'bg-success-500 animate-pulse' : 'bg-gray-400'"></div>
                    <span class="text-sm text-gray-500 dark:text-gray-400">Last updated: <span x-text="lastUpdated"></span></span>
                </div>
            </div>
        </div>
    </div>

    <x-filament::tabs id="dashboard-tabs">
        <x-filament::tabs.item
            :active="$activeTab === 'operations'"
            x-on:click="activeTab = 'operations'; history.replaceState(null, null, '?tab=operations');"
            icon="heroicon-m-squares-2x2"
        >
            Operations
        </x-filament::tabs.item>

        <x-filament::tabs.item
            :active="$activeTab === 'inventory'"
            x-on:click="activeTab = 'inventory'; history.replaceState(null, null, '?tab=inventory');"
            icon="heroicon-m-archive-box-arrow-down"
        >
            Inventory & Alerts
        </x-filament::tabs.item>

        <x-filament::tabs.item
            :active="$activeTab === 'harvest'"
            x-on:click="activeTab = 'harvest'; history.replaceState(null, null, '?tab=harvest');"
            icon="heroicon-m-scissors"
        >
            Harvest & Yield
        </x-filament::tabs.item>

        <x-filament::tabs.item
            :active="$activeTab === 'planning'"
            x-on:click="activeTab = 'planning'; history.replaceState(null, null, '?tab=planning');"
            icon="heroicon-m-calendar-days"
        >
            Planning & Predictions
        </x-filament::tabs.item>

        <x-filament::tabs.item
            :active="$activeTab === 'analytics'"
            x-on:click="activeTab = 'analytics'; history.replaceState(null, null, '?tab=analytics');"
            icon="heroicon-m-chart-bar"
        >
            Analytics & Reports
        </x-filament::tabs.item>
    </x-filament::tabs>

    <div class="mt-6">
        <!-- Operations Dashboard Tab -->
        <div x-show="activeTab === 'operations'" x-cloak>
            <!-- Quick Stats Bar -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
                <x-filament::section class="col-span-1">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300">Active Crops</h3>
                    </div>
                    <p class="text-2xl font-bold text-primary-600 dark:text-primary-400" data-stat="active-crops">{{ $activeCropsCount }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">crops in production</p>
                </x-filament::section>

                <x-filament::section class="col-span-1">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300">Active Trays</h3>
                    </div>
                    <p class="text-2xl font-bold text-primary-600 dark:text-primary-400" data-stat="active-trays">{{ $activeTraysCount }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">trays in use</p>
                </x-filament::section>

                <x-filament::section class="col-span-1">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300">Alerts</h3>
                    </div>
                    <p class="text-2xl font-bold {{ $overdueTasksCount > 0 ? 'text-danger-600 dark:text-danger-400' : 'text-success-600 dark:text-success-400' }}" data-stat="overdue-tasks">
                        {{ $overdueTasksCount }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">overdue tasks</p>
                </x-filament::section>

                <x-filament::section class="col-span-1">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300">Low Stock</h3>
                    </div>
                    <p class="text-2xl font-bold {{ $lowStockCount > 0 ? 'text-warning-600 dark:text-warning-400' : 'text-success-600 dark:text-success-400' }}" data-stat="low-stock">
                        {{ $lowStockCount }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">items need restock</p>
                </x-filament::section>

                <x-filament::section class="col-span-1">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300">Tray Utilization</h3>
                    </div>
                    <p class="text-2xl font-bold 
                        @if($trayUtilization['status'] === 'critical') text-danger-600 dark:text-danger-400
                        @elseif($trayUtilization['status'] === 'warning') text-warning-600 dark:text-warning-400
                        @else text-success-600 dark:text-success-400 @endif" data-stat="tray-utilization">
                        {{ $trayUtilization['utilization_percent'] }}%
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400" data-stat="available-trays">{{ $trayUtilization['available_trays'] }} available</p>
                </x-filament::section>
            </div>

            <!-- Stage Flow Overview -->
            <div class="mb-6">
                <x-filament::section>
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Production Pipeline</h2>
                        <span class="text-sm text-gray-500 dark:text-gray-400">{{ array_sum(array_column($cropsByStage, 'count')) }} total active crops</span>
                    </div>
                    
                    <div class="flex items-center justify-between space-x-4">
                        @php
                            $stages = [
                                'germination' => ['name' => 'Germination', 'icon' => 'seedling', 'color' => 'emerald'],
                                'blackout' => ['name' => 'Blackout', 'icon' => 'moon', 'color' => 'slate'],
                                'light' => ['name' => 'Under Light', 'icon' => 'sun', 'color' => 'amber']
                            ];
                            $totalCrops = array_sum(array_column($cropsByStage, 'count'));
                        @endphp
                        
                        @foreach($stages as $stageKey => $stageData)
                            @php
                                $stageCount = $cropsByStage[$stageKey]['count'];
                                $overdueCount = $cropsByStage[$stageKey]['overdue_count'];
                                $percentage = $totalCrops > 0 ? round(($stageCount / $totalCrops) * 100, 1) : 0;
                            @endphp
                            
                            <div class="flex-1 text-center">
                                <div class="relative">
                                    <!-- Stage Icon -->
                                    <div class="mx-auto w-16 h-16 rounded-full flex items-center justify-center mb-3 {{ $stageCount > 0 ? 'bg-'.$stageData['color'].'-100 border-2 border-'.$stageData['color'].'-200' : 'bg-gray-100 border-2 border-gray-200' }} dark:{{ $stageCount > 0 ? 'bg-'.$stageData['color'].'-900 border-'.$stageData['color'].'-800' : 'bg-gray-800 border-gray-700' }}">
                                        @if($stageData['icon'] === 'seedling')
                                            <svg class="w-8 h-8 {{ $stageCount > 0 ? 'text-'.$stageData['color'].'-600' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 3v1m0 3v1m0 3v1M9 21h6m-9-6h12a1 1 0 001-1V9a1 1 0 00-1-1H6a1 1 0 00-1 1v5a1 1 0 001 1z"></path>
                                            </svg>
                                        @elseif($stageData['icon'] === 'moon')
                                            <svg class="w-8 h-8 {{ $stageCount > 0 ? 'text-'.$stageData['color'].'-600' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                                            </svg>
                                        @else
                                            <svg class="w-8 h-8 {{ $stageCount > 0 ? 'text-'.$stageData['color'].'-600' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 3v1m0 3v1m0 3v1M9 21h6m-9-6h12a1 1 0 001-1V9a1 1 0 00-1-1H6a1 1 0 00-1 1v5a1 1 0 001 1z"></path>
                                            </svg>
                                        @endif
                                    </div>
                                    
                                    <!-- Overdue Badge -->
                                    @if($overdueCount > 0)
                                        <div class="absolute -top-1 -right-1 w-6 h-6 bg-danger-500 text-white text-xs font-bold rounded-full flex items-center justify-center">
                                            {{ $overdueCount }}
                                        </div>
                                    @endif
                                </div>
                                
                                <!-- Stage Info -->
                                <h3 class="font-medium text-gray-900 dark:text-white">{{ $stageData['name'] }}</h3>
                                <p class="text-2xl font-bold {{ $stageCount > 0 ? 'text-'.$stageData['color'].'-600' : 'text-gray-400' }}">{{ $stageCount }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $percentage }}% of total</p>
                                
                                @if($overdueCount > 0)
                                    <p class="text-xs text-danger-600 dark:text-danger-400 mt-1">{{ $overdueCount }} overdue</p>
                                @endif
                            </div>
                            
                            <!-- Arrow between stages (except after last stage) -->
                            @if($stageKey !== 'light')
                                <div class="flex-shrink-0">
                                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                                    </svg>
                                </div>
                            @endif
                        @endforeach
                        
                        <!-- Harvest Stage -->
                        <div class="flex-1 text-center">
                            @php $harvestCount = is_countable($cropsNeedingHarvest) ? count($cropsNeedingHarvest) : $cropsNeedingHarvest->count(); @endphp
                            <div class="mx-auto w-16 h-16 rounded-full flex items-center justify-center mb-3 {{ $harvestCount > 0 ? 'bg-success-100 border-2 border-success-200' : 'bg-gray-100 border-2 border-gray-200' }} dark:{{ $harvestCount > 0 ? 'bg-success-900 border-success-800' : 'bg-gray-800 border-gray-700' }}">
                                <svg class="w-8 h-8 {{ $harvestCount > 0 ? 'text-success-600' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1m4 0h1M9 16h6m-7 4h8a2 2 0 002-2V6a2 2 0 00-2-2H8a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                            <h3 class="font-medium text-gray-900 dark:text-white">Ready to Harvest</h3>
                            <p class="text-2xl font-bold {{ $harvestCount > 0 ? 'text-success-600' : 'text-gray-400' }}">{{ $harvestCount }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">crops ready</p>
                        </div>
                    </div>
                </x-filament::section>
            </div>

            <!-- Active Crops by Stage -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                @foreach(['germination' => 'Germination', 'blackout' => 'Blackout', 'light' => 'Under Light'] as $stage => $title)
                    <x-filament::section>
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $title }}</h2>
                            @if($cropsByStage[$stage]['overdue_count'] > 0)
                                <span class="px-2 py-1 bg-danger-100 text-danger-800 text-xs font-medium rounded-full dark:bg-danger-900 dark:text-danger-200">
                                    {{ $cropsByStage[$stage]['overdue_count'] }} overdue
                                </span>
                            @endif
                        </div>
                        
                        <div class="mb-4">
                            <p class="text-3xl font-bold text-primary-600 dark:text-primary-400">{{ $cropsByStage[$stage]['count'] }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">crops in {{ strtolower($title) }}</p>
                        </div>
                        
                        @if($cropsByStage[$stage]['crops']->count() > 0)
                            <div class="space-y-3">
                                @foreach($cropsByStage[$stage]['crops'] as $crop)
                                    @php
                                        // Calculate stage progress
                                        $stageStartTime = $crop->{$stage . '_at'};
                                        $stageDuration = match($stage) {
                                            'germination' => $crop->recipe->germination_days ?? 0,
                                            'blackout' => $crop->recipe->blackout_days ?? 0,
                                            'light' => $crop->recipe->light_days ?? 0,
                                            default => 0
                                        };
                                        
                                        $progress = 0;
                                        $isOverdue = false;
                                        $statusColor = 'bg-blue-500';
                                        
                                        if ($stageStartTime && $stageDuration > 0) {
                                            $hoursInStage = $stageStartTime->diffInHours(now());
                                            $totalHours = $stageDuration * 24;
                                            $progress = min(100, ($hoursInStage / $totalHours) * 100);
                                            
                                            if ($progress >= 100) {
                                                $isOverdue = true;
                                                $statusColor = 'bg-danger-500';
                                            } elseif ($progress >= 80) {
                                                $statusColor = 'bg-warning-500';
                                            } else {
                                                $statusColor = 'bg-success-500';
                                            }
                                        }
                                    @endphp
                                    
                                    <div class="p-3 bg-gray-50 dark:bg-gray-800/50 rounded-lg border {{ $isOverdue ? 'border-danger-200 dark:border-danger-800' : 'border-gray-200 dark:border-gray-700' }}">
                                        <div class="flex items-center justify-between mb-2">
                                            <div class="flex-1">
                                                <div class="flex items-center gap-2">
                                                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                                                        {{ $crop->recipe->seedVariety->name ?? 'Unknown' }}
                                                    </p>
                                                    @if($isOverdue)
                                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-danger-100 text-danger-800 dark:bg-danger-900 dark:text-danger-200">
                                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                                            </svg>
                                                            Ready
                                                        </span>
                                                    @endif
                                                </div>
                                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                                    Tray #{{ $crop->tray_number }} • {{ $crop->getStageAgeStatus() }}
                                                </p>
                                            </div>
                                            <div class="flex gap-1">
                                                @if($isOverdue)
                                                    <x-filament::icon-button
                                                        icon="heroicon-o-arrow-right-circle"
                                                        color="warning"
                                                        tag="a"
                                                        :href="route('filament.admin.resources.crops.edit', $crop)"
                                                        tooltip="Advance Stage"
                                                        size="sm"
                                                    />
                                                @endif
                                                <x-filament::icon-button
                                                    icon="heroicon-o-eye"
                                                    tag="a"
                                                    :href="route('filament.admin.resources.crops.edit', $crop)"
                                                    tooltip="View Crop"
                                                    size="sm"
                                                />
                                            </div>
                                        </div>
                                        
                                        <!-- Stage Progress Bar -->
                                        @if($stageDuration > 0)
                                            <div class="mb-2">
                                                <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mb-1">
                                                    <span>Stage Progress</span>
                                                    <span>{{ number_format($progress, 1) }}%</span>
                                                </div>
                                                <div class="w-full bg-gray-200 rounded-full h-2 dark:bg-gray-700">
                                                    <div class="h-2 rounded-full transition-all duration-300 {{ $statusColor }}" 
                                                         style="width: {{ min(100, $progress) }}%">
                                                    </div>
                                                </div>
                                                @if($progress > 100)
                                                    <p class="text-xs text-danger-600 dark:text-danger-400 mt-1">
                                                        {{ number_format($progress - 100, 1) }}% overdue
                                                    </p>
                                                @endif
                                            </div>
                                        @endif
                                        
                                        <!-- Expected Next Action -->
                                        @if(!$isOverdue && $stageDuration > 0)
                                            @php
                                                $timeToAdvance = $crop->timeToNextStage();
                                                if ($timeToAdvance && !str_contains($timeToAdvance, 'Ready to advance')) {
                                                    $nextAction = match($stage) {
                                                        'germination' => $crop->recipe->blackout_days > 0 ? 'Move to blackout' : 'Move to light',
                                                        'blackout' => 'Move to light',
                                                        'light' => 'Ready to harvest',
                                                        default => 'Next stage'
                                                    };
                                                    echo '<p class="text-xs text-blue-600 dark:text-blue-400">' . $nextAction . ' in ' . $timeToAdvance . '</p>';
                                                }
                                            @endphp
                                        @endif
                                    </div>
                                @endforeach
                                @if($cropsByStage[$stage]['count'] > 5)
                                    <div class="text-center pt-2">
                                        <x-filament::link
                                            :href="route('filament.admin.resources.crops.index', ['stage' => $stage])"
                                            size="sm"
                                        >
                                            View all {{ $cropsByStage[$stage]['count'] }} crops
                                        </x-filament::link>
                                    </div>
                                @endif
                            </div>
                        @else
                            <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-4">No crops in {{ strtolower($title) }}</p>
                        @endif
                    </x-filament::section>
                @endforeach
            </div>

            <!-- Ready to Harvest -->
            @php $harvestReadyCount = is_countable($cropsNeedingHarvest) ? count($cropsNeedingHarvest) : $cropsNeedingHarvest->count(); @endphp
            @if($harvestReadyCount > 0)
                <x-filament::section>
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Ready to Harvest</h2>
                        <span class="px-3 py-1 bg-success-100 text-success-800 text-sm font-medium rounded-full dark:bg-success-900 dark:text-success-200">
                            {{ $harvestReadyCount }} crops ready
                        </span>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($cropsNeedingHarvest as $crop)
                            <div class="flex items-center justify-between p-3 bg-success-50 dark:bg-success-900/20 rounded-lg border border-success-200 dark:border-success-800">
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-white">{{ $crop->recipe->seedVariety->name ?? 'Unknown' }}</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        Tray #{{ $crop->tray_number }} • {{ $crop->planted_at->diffInDays(now()) }} days old
                                    </p>
                                </div>
                                <div class="flex gap-1">
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
                                        :href="route('filament.admin.resources.crops.edit', ['record' => $crop, 'action' => 'harvest'])"
                                        tooltip="Harvest Crop"
                                        size="sm"
                                    />
                                </div>
                            </div>
                        @endforeach
                    </div>
                </x-filament::section>
            @endif
        </div>

        <!-- Inventory & Alerts Tab -->
        <div x-show="activeTab === 'inventory'" x-cloak>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Seed Inventory Alerts -->
                <x-filament::section>
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Seed Inventory Alerts</h2>
                        <x-filament::link
                            :href="route('filament.admin.resources.consumables.index', ['type' => 'seed'])"
                            size="sm"
                        >
                            View All Seeds
                        </x-filament::link>
                    </div>
                    @if($seedInventoryAlerts->count() > 0)
                        <div class="space-y-3">
                            @foreach($seedInventoryAlerts as $seed)
                                <div class="flex items-center justify-between p-3 bg-warning-50 dark:bg-warning-900/20 rounded-lg border border-warning-200 dark:border-warning-800">
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-white">{{ $seed->name }}</p>
                                        <p class="text-sm text-warning-600 dark:text-warning-400">
                                            {{ $seed->total_quantity }}{{ $seed->quantity_unit }} remaining (threshold: {{ $seed->restock_threshold }}{{ $seed->quantity_unit }})
                                        </p>
                                    </div>
                                    <div class="flex gap-1">
                                        <x-filament::icon-button
                                            icon="heroicon-o-shopping-cart"
                                            color="warning"
                                            tag="a"
                                            :href="route('filament.admin.resources.consumables.edit', $seed)"
                                            tooltip="Reorder"
                                            size="sm"
                                        />
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-center text-gray-500 dark:text-gray-400 py-8">All seed inventory levels are adequate.</p>
                    @endif
                </x-filament::section>

                <!-- Packaging Alerts -->
                <x-filament::section>
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Packaging Alerts</h2>
                        <x-filament::link
                            :href="route('filament.admin.resources.consumables.index', ['type' => 'packaging'])"
                            size="sm"
                        >
                            View All Packaging
                        </x-filament::link>
                    </div>
                    @if($packagingAlerts->count() > 0)
                        <div class="space-y-3">
                            @foreach($packagingAlerts as $packaging)
                                <div class="flex items-center justify-between p-3 bg-danger-50 dark:bg-danger-900/20 rounded-lg border border-danger-200 dark:border-danger-800">
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-white">{{ $packaging->name }}</p>
                                        <p class="text-sm text-danger-600 dark:text-danger-400">
                                            {{ $packaging->current_stock }} remaining (threshold: {{ $packaging->restock_threshold }})
                                        </p>
                                    </div>
                                    <div class="flex gap-1">
                                        <x-filament::icon-button
                                            icon="heroicon-o-shopping-cart"
                                            color="danger"
                                            tag="a"
                                            :href="route('filament.admin.resources.consumables.edit', $packaging)"
                                            tooltip="Reorder"
                                            size="sm"
                                        />
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-center text-gray-500 dark:text-gray-400 py-8">All packaging inventory levels are adequate.</p>
                    @endif
                </x-filament::section>
            </div>

            <!-- General Low Stock Items -->
            @if($lowStockItems->count() > 0)
                <div class="mt-6">
                    <x-filament::section>
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Other Low Stock Items</h2>
                            <x-filament::link
                                :href="route('filament.admin.resources.consumables.index')"
                                size="sm"
                            >
                                View All Inventory
                            </x-filament::link>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach($lowStockItems as $item)
                                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800/50 rounded-lg">
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-white">{{ $item->name }}</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ $item->current_stock }} {{ $item->unit }} (threshold: {{ $item->restock_threshold }})
                                        </p>
                                    </div>
                                    <x-filament::icon-button
                                        icon="heroicon-o-eye"
                                        tag="a"
                                        :href="route('filament.admin.resources.consumables.edit', $item)"
                                        tooltip="View Item"
                                        size="sm"
                                    />
                                </div>
                            @endforeach
                        </div>
                    </x-filament::section>
                </div>
            @endif
        </div>

        <!-- Harvest & Yield Tab -->
        <div x-show="activeTab === 'harvest'" x-cloak>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Upcoming Harvests -->
                <x-filament::section>
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Upcoming Harvests (7 days)</h2>
                    </div>
                    @if($upcomingHarvests->count() > 0)
                        <div class="space-y-3">
                            @foreach($upcomingHarvests as $crop)
                                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800/50 rounded-lg">
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-white">{{ $crop->recipe->seedVariety->name ?? 'Unknown' }}</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            Tray #{{ $crop->tray_number }} • Expected: {{ $crop->expectedHarvestDate()?->format('M d') }}
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $crop->recipe->expected_yield_grams }}g</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">estimated</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-center text-gray-500 dark:text-gray-400 py-8">No harvests scheduled for the next week.</p>
                    @endif
                </x-filament::section>

                <!-- Yield Estimates by Variety -->
                <x-filament::section>
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Yield Estimates by Variety</h2>
                    </div>
                    @if(count($yieldEstimates) > 0)
                        <div class="space-y-3">
                            @foreach($yieldEstimates as $estimate)
                                <div class="p-3 bg-gray-50 dark:bg-gray-800/50 rounded-lg">
                                    <div class="flex items-center justify-between mb-2">
                                        <div class="flex items-center gap-2">
                                            <p class="font-medium text-gray-900 dark:text-white">{{ $estimate['variety'] }}</p>
                                            @php
                                                $confidenceColors = [
                                                    'high' => 'bg-success-100 text-success-800 dark:bg-success-900 dark:text-success-200',
                                                    'medium' => 'bg-warning-100 text-warning-800 dark:bg-warning-900 dark:text-warning-200',
                                                    'low' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200'
                                                ];
                                            @endphp
                                            <span class="px-2 py-0.5 text-xs font-medium rounded-full {{ $confidenceColors[$estimate['confidence_level']] }}">
                                                {{ ucfirst($estimate['confidence_level']) }} confidence
                                            </span>
                                            @if($estimate['historical_data_available'])
                                                <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                                    Historical
                                                </span>
                                            @endif
                                        </div>
                                        <div class="text-right">
                                            <p class="text-sm font-bold text-primary-600 dark:text-primary-400">{{ $estimate['estimated_yield_kg'] }} kg</p>
                                            @if($estimate['ready_to_harvest'] > 0)
                                                <p class="text-xs text-success-600 dark:text-success-400">{{ $estimate['ready_to_harvest'] }} ready</p>
                                            @endif
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-center justify-between text-sm text-gray-500 dark:text-gray-400">
                                        <div>
                                            <span>{{ $estimate['trays'] }} trays growing</span>
                                            @if($estimate['historical_data_available'] && $estimate['last_30_days_avg'])
                                                <span class="ml-2">• Recent avg: {{ $estimate['last_30_days_avg'] }}g/tray</span>
                                            @endif
                                        </div>
                                        
                                        @if($estimate['historical_data_available'])
                                            <div class="text-xs">
                                                @if($estimate['last_30_days_avg'] && $estimate['recipe_estimate'])
                                                    @php
                                                        $variance = (($estimate['last_30_days_avg'] - $estimate['recipe_estimate']) / $estimate['recipe_estimate']) * 100;
                                                        $isPositive = $variance > 0;
                                                    @endphp
                                                    <span class="{{ $isPositive ? 'text-success-600 dark:text-success-400' : 'text-danger-600 dark:text-danger-400' }}">
                                                        {{ $isPositive ? '+' : '' }}{{ number_format($variance, 1) }}% vs recipe
                                                    </span>
                                                @endif
                                            </div>
                                        @else
                                            <div class="text-xs">
                                                <span class="text-gray-400">Recipe estimate only</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-center text-gray-500 dark:text-gray-400 py-8">No active crops for yield estimation.</p>
                    @endif
                </x-filament::section>
            </div>

            <!-- Weekly Harvest Schedule -->
            <div class="mt-6">
                <x-filament::section>
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Weekly Harvest Schedule</h2>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-7 gap-3">
                        @foreach($weeklyHarvestSchedule as $day)
                            <div class="text-center p-3 bg-gray-50 dark:bg-gray-800/50 rounded-lg">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $day['day_name'] }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">{{ $day['date']->format('M d') }}</p>
                                <p class="text-lg font-bold {{ $day['harvest_count'] > 0 ? 'text-primary-600 dark:text-primary-400' : 'text-gray-400' }}">
                                    {{ $day['harvest_count'] }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">harvests</p>
                            </div>
                        @endforeach
                    </div>
                </x-filament::section>
            </div>
        </div>

        <!-- Planning & Predictions Tab -->
        <div x-show="activeTab === 'planning'" x-cloak>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Planting Recommendations -->
                <x-filament::section>
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Planting Recommendations</h2>
                    </div>
                    @if(count($plantingRecommendations) > 0)
                        <div class="space-y-3">
                            @foreach($plantingRecommendations as $rec)
                                <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="font-medium text-gray-900 dark:text-white">{{ $rec['variety'] }}</p>
                                            <p class="text-sm text-blue-600 dark:text-blue-400">
                                                Plant by: {{ $rec['plant_by_date']->format('M d') }}
                                            </p>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-lg font-bold text-blue-600 dark:text-blue-400">{{ $rec['estimated_trays'] }}</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">trays needed</p>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-center text-gray-500 dark:text-gray-400 py-8">No upcoming orders requiring planting recommendations.</p>
                    @endif
                </x-filament::section>

                <!-- Tray Utilization Details -->
                <x-filament::section>
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Tray Utilization</h2>
                    </div>
                    
                    <div class="space-y-4">
                        <!-- Utilization Bar -->
                        <div>
                            <div class="flex justify-between text-sm mb-2">
                                <span class="text-gray-600 dark:text-gray-400">Capacity</span>
                                <span class="font-medium">{{ $trayUtilization['active_trays'] }}/{{ $trayUtilization['total_trays'] }}</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-3 dark:bg-gray-700">
                                <div class="h-3 rounded-full 
                                    @if($trayUtilization['status'] === 'critical') bg-danger-500
                                    @elseif($trayUtilization['status'] === 'warning') bg-warning-500
                                    @else bg-success-500 @endif"
                                    style="width: {{ $trayUtilization['utilization_percent'] }}%">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Stats -->
                        <div class="grid grid-cols-2 gap-4">
                            <div class="text-center p-3 bg-gray-50 dark:bg-gray-800/50 rounded-lg">
                                <p class="text-2xl font-bold text-primary-600 dark:text-primary-400">{{ $trayUtilization['active_trays'] }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">In Use</p>
                            </div>
                            <div class="text-center p-3 bg-gray-50 dark:bg-gray-800/50 rounded-lg">
                                <p class="text-2xl font-bold text-gray-600 dark:text-gray-400">{{ $trayUtilization['available_trays'] }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Available</p>
                            </div>
                        </div>
                        
                        <!-- Status Message -->
                        <div class="p-3 rounded-lg 
                            @if($trayUtilization['status'] === 'critical') bg-danger-50 dark:bg-danger-900/20 border border-danger-200 dark:border-danger-800
                            @elseif($trayUtilization['status'] === 'warning') bg-warning-50 dark:bg-warning-900/20 border border-warning-200 dark:border-warning-800
                            @else bg-success-50 dark:bg-success-900/20 border border-success-200 dark:border-success-800 @endif">
                            <p class="text-sm 
                                @if($trayUtilization['status'] === 'critical') text-danger-800 dark:text-danger-200
                                @elseif($trayUtilization['status'] === 'warning') text-warning-800 dark:text-warning-200
                                @else text-success-800 dark:text-success-200 @endif">
                                @if($trayUtilization['status'] === 'critical')
                                    Critical: Consider expanding capacity or harvesting crops earlier.
                                @elseif($trayUtilization['status'] === 'warning')
                                    Warning: Tray capacity is getting high. Plan harvests carefully.
                                @else
                                    Good: Healthy tray utilization with room for growth.
                                @endif
                            </p>
                        </div>
                    </div>
                </x-filament::section>
            </div>
        </div>

        <!-- Analytics & Reports Tab -->
        <div x-show="activeTab === 'analytics'" x-cloak>
            <div class="text-center py-16">
                <div class="mx-auto max-w-md">
                    <x-filament::icon
                        icon="heroicon-o-chart-bar"
                        class="mx-auto h-12 w-12 text-gray-400"
                    />
                    <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">Analytics Coming Soon</h3>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        Advanced analytics, custom reports, and business intelligence features will be available in the next update.
                    </p>
                    <div class="mt-6">
                        <x-filament::button
                            tag="a"
                            :href="route('filament.admin.pages.weekly-planning')"
                            color="primary"
                        >
                            View Weekly Planning
                        </x-filament::button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('dashboardData', () => ({
        activeTab: '{{ $activeTab }}',
        lastUpdated: '{{ now()->format('M d, H:i') }}',
        isRefreshing: false,
        autoRefresh: true,
        refreshInterval: null,
        
        initDashboard() {
            this.startAutoRefresh();
        },
        
        startAutoRefresh() {
            if (this.autoRefresh) {
                this.refreshInterval = setInterval(() => {
                    this.refreshData();
                }, 30000);
            }
        },
        
        stopAutoRefresh() {
            if (this.refreshInterval) {
                clearInterval(this.refreshInterval);
                this.refreshInterval = null;
            }
        },
        
        toggleAutoRefresh() {
            this.autoRefresh = !this.autoRefresh;
            if (this.autoRefresh) {
                this.startAutoRefresh();
            } else {
                this.stopAutoRefresh();
            }
        },
        
        async refreshData() {
            if (this.isRefreshing) return;
            
            this.isRefreshing = true;
            try {
                const response = await fetch('{{ route('dashboard.data') }}', {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                    }
                });
                
                if (response.ok) {
                    const data = await response.json();
                    this.updateDashboardData(data);
                    this.lastUpdated = new Date().toLocaleString('en-US', {
                        month: 'short',
                        day: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit',
                        hour12: false
                    });
                    
                    this.showUpdateFlash('success');
                } else {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
            } catch (error) {
                console.error('Dashboard refresh failed:', error);
                this.showUpdateFlash('error');
                
                if (this.autoRefresh) {
                    clearInterval(this.refreshInterval);
                    this.refreshInterval = setInterval(() => {
                        this.refreshData();
                    }, 60000);
                }
            } finally {
                this.isRefreshing = false;
            }
        },
        
        showUpdateFlash(type) {
            let flashEl = document.querySelector('.dashboard-update-flash');
            if (!flashEl) {
                flashEl = document.createElement('div');
                flashEl.className = 'dashboard-update-flash fixed top-4 right-4 px-3 py-1 rounded-md text-sm font-medium z-50 transition-all duration-300';
                document.body.appendChild(flashEl);
            }
            
            if (type === 'success') {
                flashEl.className = 'dashboard-update-flash fixed top-4 right-4 px-3 py-1 rounded-md text-sm font-medium z-50 transition-all duration-300 bg-green-100 text-green-800 border border-green-200';
                flashEl.textContent = 'Dashboard updated';
            } else {
                flashEl.className = 'dashboard-update-flash fixed top-4 right-4 px-3 py-1 rounded-md text-sm font-medium z-50 transition-all duration-300 bg-red-100 text-red-800 border border-red-200';
                flashEl.textContent = 'Update failed';
            }
            
            flashEl.style.opacity = '1';
            flashEl.style.transform = 'translateY(0)';
            
            setTimeout(() => {
                flashEl.style.opacity = '0';
                flashEl.style.transform = 'translateY(-10px)';
            }, 2000);
        },
        
        updateDashboardData(data) {
            const elements = {
                'active-crops': data.activeCropsCount || '0',
                'active-trays': data.activeTraysCount || '0',
                'overdue-tasks': data.overdueTasksCount || '0',
                'low-stock': data.lowStockCount || '0',
                'tray-utilization': (data.trayUtilization?.utilization_percent || '0') + '%'
            };
            
            Object.entries(elements).forEach(([stat, value]) => {
                const element = document.querySelector(`[data-stat="${stat}"]`);
                if (element) {
                    element.textContent = value;
                }
            });
            
            const overdueElement = document.querySelector('[data-stat="overdue-tasks"]');
            const lowStockElement = document.querySelector('[data-stat="low-stock"]');
            const trayUtilizationElement = document.querySelector('[data-stat="tray-utilization"]');
            
            if (overdueElement) {
                overdueElement.className = data.overdueTasksCount > 0 
                    ? 'text-2xl font-bold text-red-600 dark:text-red-400'
                    : 'text-2xl font-bold text-green-600 dark:text-green-400';
            }
                
            if (lowStockElement) {
                lowStockElement.className = data.lowStockCount > 0 
                    ? 'text-2xl font-bold text-yellow-600 dark:text-yellow-400'
                    : 'text-2xl font-bold text-green-600 dark:text-green-400';
            }
            
            if (trayUtilizationElement && data.trayUtilization) {
                let colorClass = 'text-green-600 dark:text-green-400';
                if (data.trayUtilization.status === 'critical') {
                    colorClass = 'text-red-600 dark:text-red-400';
                } else if (data.trayUtilization.status === 'warning') {
                    colorClass = 'text-yellow-600 dark:text-yellow-400';
                }
                trayUtilizationElement.className = `text-2xl font-bold ${colorClass}`;
            }
            
            const availableTraysElement = document.querySelector('[data-stat="available-trays"]');
            if (availableTraysElement && data.trayUtilization) {
                availableTraysElement.textContent = `${data.trayUtilization.available_trays} available`;
            }
        }
    }))
});
</script>

<style>
    [x-cloak] { display: none !important; }
</style>