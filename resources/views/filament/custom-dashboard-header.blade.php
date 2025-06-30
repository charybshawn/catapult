@php
    use Illuminate\Support\Str;
    
    $activeTab = request()->query('tab', session('dashboard_tab', 'operations'));
    session(['dashboard_tab' => $activeTab]);
@endphp

<x-filament-panels::page>
<div class="space-y-8" 
     x-data="{
        activeTab: '{{ $activeTab }}',
        autoRefresh: false,
        isRefreshing: false,
        lastUpdated: new Date().toLocaleTimeString(),
        refreshData() {
            this.isRefreshing = true;
            // Simulate refresh
            setTimeout(() => {
                this.isRefreshing = false;
                this.lastUpdated = new Date().toLocaleTimeString();
                window.location.reload();
            }, 1000);
        },
        toggleAutoRefresh() {
            this.autoRefresh = !this.autoRefresh;
        }
     }">
    <div class="flex flex-col sm:flex-row justify-between items-center gap-4 pb-4">
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
            :active="$activeTab === 'alerts'"
            x-on:click="activeTab = 'alerts'; history.replaceState(null, null, '?tab=alerts');"
            icon="heroicon-m-bell-alert"
            :badge="($alertsSummary['overdue'] ?? 0) > 0 ? ($alertsSummary['overdue'] ?? 0) : (($alertsSummary['today'] ?? 0) > 0 ? ($alertsSummary['today'] ?? 0) : null)"
            :badge-color="($alertsSummary['overdue'] ?? 0) > 0 ? 'danger' : 'warning'"
        >
            Alerts
        </x-filament::tabs.item>

        <x-filament::tabs.item
            :active="$activeTab === 'inventory'"
            x-on:click="activeTab = 'inventory'; history.replaceState(null, null, '?tab=inventory');"
            icon="heroicon-m-archive-box-arrow-down"
        >
            Inventory
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
            :active="$activeTab === 'time'"
            x-on:click="activeTab = 'time'; history.replaceState(null, null, '?tab=time');"
            icon="heroicon-m-clock"
            :badge="($timeCardsSummary['flagged_time_cards'] ?? 0) > 0 ? ($timeCardsSummary['flagged_time_cards'] ?? 0) : null"
            :badge-color="'danger'"
        >
            Time Management
        </x-filament::tabs.item>

        <x-filament::tabs.item
            :active="$activeTab === 'analytics'"
            x-on:click="activeTab = 'analytics'; history.replaceState(null, null, '?tab=analytics');"
            icon="heroicon-m-chart-bar"
        >
            Analytics & Reports
        </x-filament::tabs.item>
    </x-filament::tabs>

    <div class="pt-6">
        <!-- Operations Dashboard Tab -->
        <div x-show="activeTab === 'operations'" x-cloak>
            <!-- Production Pipeline - 4 Column Layout with Better Spacing -->
            <div class="space-y-8">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Production Pipeline</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            
                    <!-- Seeded Card -->
                    <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700 p-6 hover:shadow-md transition-shadow space-y-4">
                        <!-- Header -->
                        <div class="flex items-center space-x-3">
                            <div class="w-3 h-3 rounded-full bg-blue-500"></div>
                            <h3 class="text-sm font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wide">Seeded</h3>
                        </div>
                        
                        <!-- Stats -->
                        <div class="space-y-2">
                            <div class="flex items-baseline space-x-1">
                                <span class="text-3xl font-bold text-gray-900 dark:text-white">{{ $cropsByStage['planting']['count'] ?? 0 }}</span>
                                <span class="text-gray-500 dark:text-gray-400">crops</span>
                            </div>
                            @if(($cropsByStage['planting']['overdue_count'] ?? 0) > 0)
                                <p class="text-sm text-orange-600 dark:text-orange-400">{{ $cropsByStage['planting']['overdue_count'] ?? 0 }} need attention</p>
                            @else
                                <p class="text-sm text-gray-500 dark:text-gray-400">All on schedule</p>
                            @endif
                        </div>
                        
                        <!-- Varieties & Trays -->
                        @php
                            $plantingCrops = ($cropsByStage['planting']['crops'] ?? collect())->groupBy(function($crop) {
                                return $crop->recipe->seedEntry->cultivar_name ?? $crop->recipe->name ?? 'Unknown';
                            });
                        @endphp
                        @if($plantingCrops->count() > 0)
                            <div class="space-y-3 border-t border-gray-200 dark:border-gray-700 pt-4">
                                @foreach($plantingCrops->take(3) as $variety => $crops)
                                    <div class="space-y-1">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $variety }}</div>
                                        <div class="flex flex-wrap gap-1">
                                            @foreach($crops->take(10) as $crop)
                                                <x-filament::badge color="primary" size="sm">
                                                    {{ $crop->tray_number }}
                                                </x-filament::badge>
                                            @endforeach
                                            @if($crops->count() > 10)
                                                <span class="text-xs text-gray-500 dark:text-gray-400">+{{ $crops->count() - 10 }} more</span>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                                @if($plantingCrops->count() > 3)
                                    <div class="text-xs text-gray-500 dark:text-gray-400">+{{ $plantingCrops->count() - 3 }} more varieties</div>
                                @endif
                            </div>
                        @endif
                    </div>

                    <!-- Germination Card -->
                    <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700 p-6 hover:shadow-md transition-shadow">
                        <div class="flex gap-6">
                            <!-- Left Column: Stats -->
                            <div class="flex-shrink-0 min-w-[120px] space-y-4">
                                <div class="flex items-center space-x-3">
                                    <div class="w-3 h-3 rounded-full bg-green-500"></div>
                                    <h3 class="text-sm font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wide">Germination</h3>
                                </div>
                                <div class="space-y-2">
                                    <div class="flex items-baseline space-x-1">
                                        <span class="text-3xl font-bold text-gray-900 dark:text-white">{{ $cropsByStage['germination']['count'] }}</span>
                                        <span class="text-gray-500 dark:text-gray-400">crops</span>
                                    </div>
                                    @if($cropsByStage['germination']['overdue_count'] > 0)
                                        <p class="text-sm text-orange-600 dark:text-orange-400">{{ $cropsByStage['germination']['overdue_count'] }} ready for blackout</p>
                                    @else
                                        <p class="text-sm text-gray-500 dark:text-gray-400">All germinating well</p>
                                    @endif
                                </div>
                            </div>
                            
                            <!-- Right Column: Varieties & Trays -->
                            <div class="flex-1 min-w-0 border-l border-gray-200 dark:border-gray-700 pl-4">
                                @php
                                    $germinationCrops = ($cropsByStage['germination']['crops'] ?? collect())->groupBy(function($crop) {
                                        return $crop->recipe->seedEntry->cultivar_name ?? $crop->recipe->name ?? 'Unknown';
                                    });
                                @endphp
                                @if($germinationCrops->count() > 0)
                                    <div class="space-y-3 text-left">
                                        @foreach($germinationCrops->take(3) as $variety => $crops)
                                            <div class="space-y-1">
                                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $variety }}</div>
                                                <div class="flex flex-wrap gap-1">
                                                    @foreach($crops->take(10) as $crop)
                                                        <x-filament::badge color="success" size="sm">
                                                            {{ $crop->tray_number }}
                                                        </x-filament::badge>
                                                    @endforeach
                                                    @if($crops->count() > 10)
                                                        <span class="text-xs text-gray-500 dark:text-gray-400">+{{ $crops->count() - 10 }} more</span>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                        @if($germinationCrops->count() > 3)
                                            <div class="text-xs text-gray-500 dark:text-gray-400 text-left">+{{ $germinationCrops->count() - 3 }} more varieties</div>
                                        @endif
                                    </div>
                                @else
                                    <p class="text-sm text-gray-500 dark:text-gray-400">No crops in this stage</p>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Blackout Card -->
                    <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700 p-6 hover:shadow-md transition-shadow">
                        <div class="flex gap-6">
                            <!-- Left Column: Stats -->
                            <div class="flex-shrink-0 min-w-[120px] space-y-4">
                                <div class="flex items-center space-x-3">
                                    <div class="w-3 h-3 rounded-full bg-gray-500"></div>
                                    <h3 class="text-sm font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wide">Blackout</h3>
                                </div>
                                <div class="space-y-2">
                                    <div class="flex items-baseline space-x-1">
                                        <span class="text-3xl font-bold text-gray-900 dark:text-white">{{ $cropsByStage['blackout']['count'] }}</span>
                                        <span class="text-gray-500 dark:text-gray-400">crops</span>
                                    </div>
                                    @if($cropsByStage['blackout']['overdue_count'] > 0)
                                        <p class="text-sm text-orange-600 dark:text-orange-400">{{ $cropsByStage['blackout']['overdue_count'] }} ready for light</p>
                                    @else
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Developing properly</p>
                                    @endif
                                </div>
                            </div>
                            
                            <!-- Right Column: Varieties & Trays -->
                            <div class="flex-1 min-w-0 border-l border-gray-200 dark:border-gray-700 pl-4">
                                @php
                                    $blackoutCrops = ($cropsByStage['blackout']['crops'] ?? collect())->groupBy(function($crop) {
                                        return $crop->recipe->seedEntry->cultivar_name ?? $crop->recipe->name ?? 'Unknown';
                                    });
                                @endphp
                                @if($blackoutCrops->count() > 0)
                                    <div class="space-y-3 text-left">
                                        @foreach($blackoutCrops->take(3) as $variety => $crops)
                                            <div class="space-y-1">
                                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $variety }}</div>
                                                <div class="flex flex-wrap gap-1">
                                                    @foreach($crops->take(10) as $crop)
                                                        <x-filament::badge color="gray" size="sm">
                                                            {{ $crop->tray_number }}
                                                        </x-filament::badge>
                                                    @endforeach
                                                    @if($crops->count() > 10)
                                                        <span class="text-xs text-gray-500 dark:text-gray-400">+{{ $crops->count() - 10 }} more</span>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                        @if($blackoutCrops->count() > 3)
                                            <div class="text-xs text-gray-500 dark:text-gray-400 text-left">+{{ $blackoutCrops->count() - 3 }} more varieties</div>
                                        @endif
                                    </div>
                                @else
                                    <p class="text-sm text-gray-500 dark:text-gray-400">No crops in this stage</p>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Light Card -->
                    <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700 p-6 hover:shadow-md transition-shadow">
                        <div class="flex gap-6">
                            <!-- Left Column: Stats -->
                            <div class="flex-shrink-0 min-w-[120px] space-y-4">
                                <div class="flex items-center space-x-3">
                                    <div class="w-3 h-3 rounded-full bg-yellow-500"></div>
                                    <h3 class="text-sm font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wide">Light</h3>
                                </div>
                                <div class="space-y-2">
                                    <div class="flex items-baseline space-x-1">
                                        <span class="text-3xl font-bold text-gray-900 dark:text-white">{{ $cropsByStage['light']['count'] }}</span>
                                        <span class="text-gray-500 dark:text-gray-400">crops</span>
                                    </div>
                                    @if($cropsByStage['light']['overdue_count'] > 0)
                                        <p class="text-sm text-green-600 dark:text-green-400">{{ $cropsByStage['light']['overdue_count'] }} ready to harvest</p>
                                    @else
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Growing under lights</p>
                                    @endif
                                </div>
                            </div>
                            
                            <!-- Right Column: Varieties & Trays -->
                            <div class="flex-1 min-w-0 border-l border-gray-200 dark:border-gray-700 pl-4">
                                @php
                                    $lightCrops = ($cropsByStage['light']['crops'] ?? collect())->groupBy(function($crop) {
                                        return $crop->recipe->seedEntry->cultivar_name ?? $crop->recipe->name ?? 'Unknown';
                                    });
                                @endphp
                                @if($lightCrops->count() > 0)
                                    <div class="space-y-3 text-left">
                                        @foreach($lightCrops->take(3) as $variety => $crops)
                                            <div class="space-y-1">
                                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $variety }}</div>
                                                <div class="flex flex-wrap gap-1">
                                                    @foreach($crops->take(10) as $crop)
                                                        <x-filament::badge color="warning" size="sm">
                                                            {{ $crop->tray_number }}
                                                        </x-filament::badge>
                                                    @endforeach
                                                    @if($crops->count() > 10)
                                                        <span class="text-xs text-gray-500 dark:text-gray-400">+{{ $crops->count() - 10 }} more</span>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                        @if($lightCrops->count() > 3)
                                            <div class="text-xs text-gray-500 dark:text-gray-400 text-left">+{{ $lightCrops->count() - 3 }} more varieties</div>
                                        @endif
                                    </div>
                                @else
                                    <p class="text-sm text-gray-500 dark:text-gray-400">No crops in this stage</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Alerts Tab -->
        <div x-show="activeTab === 'alerts'" x-cloak>
            <!-- Header Section -->
            <div class="flex justify-between items-center py-8">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Alerts Overview</h2>
                <a href="{{ route('filament.admin.resources.crop-alerts.index') }}" 
                   class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    Manage All Alerts
                </a>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 py-12">
                <!-- Overdue Alerts -->
                <div class="bg-white dark:bg-gray-900 rounded-xl border border-red-200 dark:border-red-700 p-6 shadow-sm hover:shadow-md transition-all">
                    <div class="flex items-center mb-3">
                        <div class="w-2 h-2 rounded-full bg-red-500 mr-3"></div>
                        <h3 class="text-xs font-semibold text-red-600 dark:text-red-400 uppercase tracking-wider">Overdue</h3>
                    </div>
                    <div class="mb-1">
                        <span class="text-2xl font-bold text-gray-900 dark:text-white">{{ $alertsSummary['overdue'] ?? 0 }}</span>
                    </div>
                    @if(($alertsSummary['overdue'] ?? 0) > 0)
                        <p class="text-xs text-red-600 dark:text-red-400">Require immediate attention</p>
                    @else
                        <p class="text-xs text-gray-500 dark:text-gray-400">All up to date</p>
                    @endif
                </div>

                <!-- Today's Alerts -->
                <div class="bg-white dark:bg-gray-900 rounded-xl border border-orange-200 dark:border-orange-700 p-6 shadow-sm hover:shadow-md transition-all">
                    <div class="flex items-center mb-3">
                        <div class="w-2 h-2 rounded-full bg-orange-500 mr-3"></div>
                        <h3 class="text-xs font-semibold text-orange-600 dark:text-orange-400 uppercase tracking-wider">Today</h3>
                    </div>
                    <div class="mb-1">
                        <span class="text-2xl font-bold text-gray-900 dark:text-white">{{ $alertsSummary['today'] ?? 0 }}</span>
                    </div>
                    @if(($alertsSummary['today'] ?? 0) > 0)
                        <p class="text-xs text-orange-600 dark:text-orange-400">Scheduled for today</p>
                    @else
                        <p class="text-xs text-gray-500 dark:text-gray-400">No alerts today</p>
                    @endif
                </div>

                <!-- This Week -->
                <div class="bg-white dark:bg-gray-900 rounded-xl border border-blue-200 dark:border-blue-700 p-6 shadow-sm hover:shadow-md transition-all">
                    <div class="flex items-center mb-3">
                        <div class="w-2 h-2 rounded-full bg-blue-500 mr-3"></div>
                        <h3 class="text-xs font-semibold text-blue-600 dark:text-blue-400 uppercase tracking-wider">This Week</h3>
                    </div>
                    <div class="mb-1">
                        <span class="text-2xl font-bold text-gray-900 dark:text-white">{{ $alertsSummary['this_week'] ?? 0 }}</span>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Coming up this week</p>
                </div>

                <!-- Total Upcoming -->
                <div class="bg-white dark:bg-gray-900 rounded-xl border border-green-200 dark:border-green-700 p-6 shadow-sm hover:shadow-md transition-all">
                    <div class="flex items-center mb-3">
                        <div class="w-2 h-2 rounded-full bg-green-500 mr-3"></div>
                        <h3 class="text-xs font-semibold text-green-600 dark:text-green-400 uppercase tracking-wider">Total Upcoming</h3>
                    </div>
                    <div class="mb-1">
                        <span class="text-2xl font-bold text-gray-900 dark:text-white">{{ $alertsSummary['total'] ?? 0 }}</span>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Next 7 days</p>
                </div>
            </div>

            <!-- Overdue Alerts Section -->
            @if(!empty($overdueAlerts) && count($overdueAlerts) > 0)
            <div class="py-10">
                <h3 class="text-lg font-semibold text-red-600 dark:text-red-400 pb-6 flex items-center">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                    Overdue Alerts ({{ count($overdueAlerts) }})
                </h3>
                <div class="space-y-4">
                    @foreach($overdueAlerts as $alert)
                    <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded-xl p-6 cursor-pointer hover:shadow-md transition-all"
                         x-data="{ showDetails: false }"
                         @click="showDetails = true">
                        <div class="flex justify-between items-center">
                            <!-- Left Side: Main Info -->
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-2">
                                    <h4 class="text-xl font-bold text-gray-900 dark:text-white">{{ $alert->variety }}</h4>
                                    <x-filament::badge color="danger" size="sm">
                                        OVERDUE
                                    </x-filament::badge>
                                </div>
                                <div class="flex items-center gap-4 text-sm text-gray-600 dark:text-gray-400">
                                    <x-filament::badge color="info" size="sm">
                                        {{ $alert->alert_type }}
                                    </x-filament::badge>
                                    <span>{{ $alert->tray_count }} trays</span>
                                    @foreach(array_slice($alert->tray_numbers, 0, 8) as $trayNumber)
                                        <x-filament::badge color="gray" size="xs">
                                            {{ $trayNumber }}
                                        </x-filament::badge>
                                    @endforeach
                                    @if(count($alert->tray_numbers) > 8)
                                        <span class="text-xs text-gray-500">+{{ count($alert->tray_numbers) - 8 }}</span>
                                    @endif
                                </div>
                            </div>
                            
                            <!-- Right Side: Timing & Planted Date -->
                            <div class="text-right space-y-1">
                                <div class="text-red-600 dark:text-red-400 font-semibold text-lg">{{ $alert->time_until }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Planted: {{ $alert->planting_at ? \Carbon\Carbon::parse($alert->planting_at)->format('M j') : 'Unknown' }}</div>
                            </div>
                        </div>
                        
                        <!-- Slideout Modal -->
                        <div x-show="showDetails" 
                             class="fixed inset-0 z-50"
                             x-transition.opacity
                             style="display: none;">
                            <!-- Backdrop -->
                            <div class="absolute inset-0 bg-black bg-opacity-50" 
                                 @click="showDetails = false"></div>
                            
                            <!-- Slideout Panel -->
                            <div class="fixed top-0 right-0 h-full w-96 bg-white dark:bg-gray-900 shadow-xl overflow-hidden z-60"
                                 style="transform: translateX(0);"
                                 x-transition:enter="transition-transform ease-in-out duration-300"
                                 x-transition:enter-start="transform translate-x-full"
                                 x-transition:enter-end="transform translate-x-0"
                                 x-transition:leave="transition-transform ease-in-out duration-300"
                                 x-transition:leave-start="transform translate-x-0"
                                 x-transition:leave-end="transform translate-x-full"
                                 @click.stop>
                                <div class="p-6 h-full overflow-y-auto">
                                    <div class="flex justify-between items-center mb-6">
                                        <h3 class="text-lg font-semibold">Alert Details</h3>
                                        <button @click="showDetails = false" class="text-gray-400 hover:text-gray-600">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                        </button>
                                    </div>
                                    
                                    <div class="space-y-6">
                                        <div>
                                            <h4 class="font-semibold text-xl text-gray-900 dark:text-white mb-2">{{ $alert->variety }}</h4>
                                            <p class="text-gray-600 dark:text-gray-400">{{ $alert->alert_type }}</p>
                                        </div>
                                        
                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Status</div>
                                                <x-filament::badge color="danger">OVERDUE</x-filament::badge>
                                            </div>
                                            <div>
                                                <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Tray Count</div>
                                                <div class="text-gray-900 dark:text-white font-medium">{{ $alert->tray_count }}</div>
                                            </div>
                                        </div>
                                        
                                        <div>
                                            <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Due Date</div>
                                            <div class="text-gray-900 dark:text-white">{{ $alert->next_run_at->format('M j, Y g:i A') }}</div>
                                            <div class="text-red-600 font-medium">{{ $alert->time_until }}</div>
                                        </div>
                                        
                                        <div>
                                            <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Planted Date</div>
                                            <div class="text-gray-900 dark:text-white">{{ $alert->planting_at ? \Carbon\Carbon::parse($alert->planting_at)->format('M j, Y') : 'Unknown' }}</div>
                                        </div>
                                        
                                        <div>
                                            <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Stage Progress</div>
                                            <div class="flex items-center gap-2">
                                                <x-filament::badge color="info" size="sm">{{ ucfirst($alert->current_stage) }}</x-filament::badge>
                                                <span class="text-gray-500">→</span>
                                                <x-filament::badge :color="match($alert->target_stage) {
                                                    'germination' => 'info',
                                                    'blackout' => 'warning', 
                                                    'light' => 'success',
                                                    'harvested' => 'danger',
                                                    default => 'gray'
                                                }" size="sm">{{ ucfirst($alert->target_stage) }}</x-filament::badge>
                                            </div>
                                        </div>
                                        
                                        <div>
                                            <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Tray Numbers</div>
                                            <div class="flex flex-wrap gap-1">
                                                @foreach($alert->tray_numbers as $trayNumber)
                                                    <x-filament::badge color="gray" size="xs">{{ $trayNumber }}</x-filament::badge>
                                                @endforeach
                                            </div>
                                        </div>
                                        
                                        <div class="pt-4 border-t">
                                            <div class="space-y-3">
                                                <!-- Action Buttons -->
                                                <div class="flex gap-3">
                                                    <button type="button"
                                                            x-data
                                                            @click="
                                                                if (confirm('Advance {{ $alert->tray_count }} crops to {{ $alert->target_stage }}?')) {
                                                                    fetch('{{ route('dashboard.advance-crops') }}', {
                                                                        method: 'POST',
                                                                        headers: {
                                                                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                                                            'Content-Type': 'application/json',
                                                                        },
                                                                        body: JSON.stringify({
                                                                            alert_ids: {{ json_encode($alert->alert_ids) }}
                                                                        })
                                                                    })
                                                                    .then(response => response.json())
                                                                    .then(data => {
                                                                        if (data.success) {
                                                                            alert(data.message);
                                                                            window.location.reload();
                                                                        } else {
                                                                            alert('Error: ' + data.message);
                                                                        }
                                                                    })
                                                                    .catch(error => {
                                                                        alert('Error: ' + error);
                                                                    });
                                                                }
                                                            "
                                                            class="flex-1 text-center py-2 px-4 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                                                        Advance Stage
                                                    </button>
                                                    <button type="button"
                                                            x-data
                                                            @click="
                                                                if (confirm('Rollback {{ $alert->tray_count }} crops to previous stage?')) {
                                                                    fetch('{{ route('dashboard.rollback-crops') }}', {
                                                                        method: 'POST',
                                                                        headers: {
                                                                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                                                            'Content-Type': 'application/json',
                                                                        },
                                                                        body: JSON.stringify({
                                                                            alert_ids: {{ json_encode($alert->alert_ids) }}
                                                                        })
                                                                    })
                                                                    .then(response => response.json())
                                                                    .then(data => {
                                                                        if (data.success) {
                                                                            alert(data.message);
                                                                            window.location.reload();
                                                                        } else {
                                                                            alert('Error: ' + data.message);
                                                                        }
                                                                    })
                                                                    .catch(error => {
                                                                        alert('Error: ' + error);
                                                                    });
                                                                }
                                                            "
                                                            class="flex-1 text-center py-2 px-4 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors">
                                                        Rollback Stage
                                                    </button>
                                                </div>
                                                
                                                <!-- Other Links -->
                                                <div class="flex gap-3">
                                                    <a href="{{ route('filament.admin.resources.crop-alerts.edit', ['record' => $alert->id]) }}" 
                                                       class="flex-1 text-center py-2 px-4 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                                        Edit Alert
                                                    </a>
                                                    <a href="{{ route('filament.admin.resources.crops.index') }}" 
                                                       class="flex-1 text-center py-2 px-4 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                                                        View Batch
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Today's Alerts Section -->
            @if(!empty($todaysAlerts) && count($todaysAlerts) > 0)
            <div class="py-10">
                <h3 class="text-lg font-semibold text-orange-600 dark:text-orange-400 pb-6 flex items-center">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Today's Alerts ({{ count($todaysAlerts) }})
                </h3>
                <div class="space-y-4">
                    @foreach($todaysAlerts as $alert)
                    <div class="bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-700 rounded-xl p-6 cursor-pointer hover:shadow-md transition-all"
                         x-data="{ showDetails: false }"
                         @click="showDetails = true">
                        <div class="flex justify-between items-center">
                            <!-- Left Side: Main Info -->
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-2">
                                    <h4 class="text-xl font-bold text-gray-900 dark:text-white">{{ $alert->variety }}</h4>
                                    <x-filament::badge color="warning" size="sm">
                                        TODAY
                                    </x-filament::badge>
                                </div>
                                <div class="flex items-center gap-4 text-sm text-gray-600 dark:text-gray-400">
                                    <x-filament::badge color="info" size="sm">
                                        {{ $alert->alert_type }}
                                    </x-filament::badge>
                                    <span>{{ $alert->tray_count }} trays</span>
                                    @foreach(array_slice($alert->tray_numbers, 0, 8) as $trayNumber)
                                        <x-filament::badge color="gray" size="xs">
                                            {{ $trayNumber }}
                                        </x-filament::badge>
                                    @endforeach
                                    @if(count($alert->tray_numbers) > 8)
                                        <span class="text-xs text-gray-500">+{{ count($alert->tray_numbers) - 8 }}</span>
                                    @endif
                                </div>
                            </div>
                            
                            <!-- Right Side: Timing & Planted Date -->
                            <div class="text-right space-y-1">
                                <div class="text-orange-600 dark:text-orange-400 font-semibold text-lg">{{ $alert->time_until }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Planted: {{ $alert->planting_at ? \Carbon\Carbon::parse($alert->planting_at)->format('M j') : 'Unknown' }}</div>
                            </div>
                        </div>
                        
                        <!-- Slideout Modal -->
                        <div x-show="showDetails" 
                             class="fixed inset-0 z-50"
                             x-transition.opacity
                             style="display: none;">
                            <!-- Backdrop -->
                            <div class="absolute inset-0 bg-black bg-opacity-50" 
                                 @click="showDetails = false"></div>
                            
                            <!-- Slideout Panel -->
                            <div class="fixed top-0 right-0 h-full w-96 bg-white dark:bg-gray-900 shadow-xl overflow-hidden z-60"
                                 style="transform: translateX(0);"
                                 x-transition:enter="transition-transform ease-in-out duration-300"
                                 x-transition:enter-start="transform translate-x-full"
                                 x-transition:enter-end="transform translate-x-0"
                                 x-transition:leave="transition-transform ease-in-out duration-300"
                                 x-transition:leave-start="transform translate-x-0"
                                 x-transition:leave-end="transform translate-x-full"
                                 @click.stop>
                                <div class="p-6 h-full overflow-y-auto">
                                    <div class="flex justify-between items-center mb-6">
                                        <h3 class="text-lg font-semibold">Alert Details</h3>
                                        <button @click="showDetails = false" class="text-gray-400 hover:text-gray-600">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                        </button>
                                    </div>
                                    
                                    <div class="space-y-6">
                                        <div>
                                            <h4 class="font-semibold text-xl text-gray-900 dark:text-white mb-2">{{ $alert->variety }}</h4>
                                            <p class="text-gray-600 dark:text-gray-400">{{ $alert->alert_type }}</p>
                                        </div>
                                        
                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Status</div>
                                                <x-filament::badge color="warning">TODAY</x-filament::badge>
                                            </div>
                                            <div>
                                                <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Tray Count</div>
                                                <div class="text-gray-900 dark:text-white font-medium">{{ $alert->tray_count }}</div>
                                            </div>
                                        </div>
                                        
                                        <div>
                                            <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Due Time</div>
                                            <div class="text-gray-900 dark:text-white">{{ $alert->next_run_at->format('g:i A') }}</div>
                                            <div class="text-orange-600 font-medium">{{ $alert->time_until }}</div>
                                        </div>
                                        
                                        <div>
                                            <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Planted Date</div>
                                            <div class="text-gray-900 dark:text-white">{{ $alert->planting_at ? \Carbon\Carbon::parse($alert->planting_at)->format('M j, Y') : 'Unknown' }}</div>
                                        </div>
                                        
                                        <div>
                                            <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Stage Progress</div>
                                            <div class="flex items-center gap-2">
                                                <x-filament::badge color="info" size="sm">{{ ucfirst($alert->current_stage) }}</x-filament::badge>
                                                <span class="text-gray-500">→</span>
                                                <x-filament::badge :color="match($alert->target_stage) {
                                                    'germination' => 'info',
                                                    'blackout' => 'warning', 
                                                    'light' => 'success',
                                                    'harvested' => 'danger',
                                                    default => 'gray'
                                                }" size="sm">{{ ucfirst($alert->target_stage) }}</x-filament::badge>
                                            </div>
                                        </div>
                                        
                                        <div>
                                            <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Tray Numbers</div>
                                            <div class="flex flex-wrap gap-1">
                                                @foreach($alert->tray_numbers as $trayNumber)
                                                    <x-filament::badge color="gray" size="xs">{{ $trayNumber }}</x-filament::badge>
                                                @endforeach
                                            </div>
                                        </div>
                                        
                                        <div class="pt-4 border-t">
                                            <div class="space-y-3">
                                                <!-- Action Buttons -->
                                                <div class="flex gap-3">
                                                    <button type="button"
                                                            x-data
                                                            @click="
                                                                if (confirm('Advance {{ $alert->tray_count }} crops to {{ $alert->target_stage }}?')) {
                                                                    fetch('{{ route('dashboard.advance-crops') }}', {
                                                                        method: 'POST',
                                                                        headers: {
                                                                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                                                            'Content-Type': 'application/json',
                                                                        },
                                                                        body: JSON.stringify({
                                                                            alert_ids: {{ json_encode($alert->alert_ids) }}
                                                                        })
                                                                    })
                                                                    .then(response => response.json())
                                                                    .then(data => {
                                                                        if (data.success) {
                                                                            alert(data.message);
                                                                            window.location.reload();
                                                                        } else {
                                                                            alert('Error: ' + data.message);
                                                                        }
                                                                    })
                                                                    .catch(error => {
                                                                        alert('Error: ' + error);
                                                                    });
                                                                }
                                                            "
                                                            class="flex-1 text-center py-2 px-4 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                                                        Advance Stage
                                                    </button>
                                                    <button type="button"
                                                            x-data
                                                            @click="
                                                                if (confirm('Rollback {{ $alert->tray_count }} crops to previous stage?')) {
                                                                    fetch('{{ route('dashboard.rollback-crops') }}', {
                                                                        method: 'POST',
                                                                        headers: {
                                                                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                                                            'Content-Type': 'application/json',
                                                                        },
                                                                        body: JSON.stringify({
                                                                            alert_ids: {{ json_encode($alert->alert_ids) }}
                                                                        })
                                                                    })
                                                                    .then(response => response.json())
                                                                    .then(data => {
                                                                        if (data.success) {
                                                                            alert(data.message);
                                                                            window.location.reload();
                                                                        } else {
                                                                            alert('Error: ' + data.message);
                                                                        }
                                                                    })
                                                                    .catch(error => {
                                                                        alert('Error: ' + error);
                                                                    });
                                                                }
                                                            "
                                                            class="flex-1 text-center py-2 px-4 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors">
                                                        Rollback Stage
                                                    </button>
                                                </div>
                                                
                                                <!-- Other Links -->
                                                <div class="flex gap-3">
                                                    <a href="{{ route('filament.admin.resources.crop-alerts.edit', ['record' => $alert->id]) }}" 
                                                       class="flex-1 text-center py-2 px-4 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                                        Edit Alert
                                                    </a>
                                                    <a href="{{ route('filament.admin.resources.crops.index') }}" 
                                                       class="flex-1 text-center py-2 px-4 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                                                        View Batch
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Upcoming Alerts Section -->
            <div class="py-10">
                <h3 class="text-lg font-semibold text-blue-600 dark:text-blue-400 pb-6 flex items-center">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    Upcoming Alerts (Next 7 Days)
                </h3>
                @if(!empty($upcomingAlerts) && count($upcomingAlerts) > 0)
                <div class="space-y-4">
                    @foreach($upcomingAlerts->take(15) as $alert)
                    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl p-6 cursor-pointer hover:shadow-md transition-all"
                         x-data="{ showDetails: false }"
                         @click="showDetails = true">
                        <div class="flex justify-between items-center">
                            <!-- Left Side: Main Info -->
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-2">
                                    <h4 class="text-xl font-bold text-gray-900 dark:text-white">{{ $alert->variety }}</h4>
                                    <x-filament::badge :color="match($alert->priority) {
                                        'critical' => 'danger',
                                        'high' => 'warning',
                                        'medium' => 'info',
                                        'low' => 'gray',
                                        default => 'gray'
                                    }" size="sm">
                                        {{ strtoupper($alert->priority) }}
                                    </x-filament::badge>
                                </div>
                                <div class="flex items-center gap-4 text-sm text-gray-600 dark:text-gray-400">
                                    <x-filament::badge color="info" size="sm">
                                        {{ $alert->alert_type }}
                                    </x-filament::badge>
                                    <span>{{ $alert->tray_count }} trays</span>
                                    @foreach(array_slice($alert->tray_numbers, 0, 8) as $trayNumber)
                                        <x-filament::badge color="gray" size="xs">
                                            {{ $trayNumber }}
                                        </x-filament::badge>
                                    @endforeach
                                    @if(count($alert->tray_numbers) > 8)
                                        <span class="text-xs text-gray-500">+{{ count($alert->tray_numbers) - 8 }}</span>
                                    @endif
                                </div>
                            </div>
                            
                            <!-- Right Side: Timing & Planted Date -->
                            <div class="text-right space-y-1">
                                <div class="text-blue-600 dark:text-blue-400 font-semibold text-lg">{{ $alert->time_until }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Planted: {{ $alert->planting_at ? \Carbon\Carbon::parse($alert->planting_at)->format('M j') : 'Unknown' }}</div>
                            </div>
                        </div>
                        
                        <!-- Slideout Modal -->
                        <div x-show="showDetails" 
                             class="fixed inset-0 z-50"
                             x-transition.opacity
                             style="display: none;">
                            <!-- Backdrop -->
                            <div class="absolute inset-0 bg-black bg-opacity-50" 
                                 @click="showDetails = false"></div>
                            
                            <!-- Slideout Panel -->
                            <div class="fixed top-0 right-0 h-full w-96 bg-white dark:bg-gray-900 shadow-xl overflow-hidden z-60"
                                 style="transform: translateX(0);"
                                 x-transition:enter="transition-transform ease-in-out duration-300"
                                 x-transition:enter-start="transform translate-x-full"
                                 x-transition:enter-end="transform translate-x-0"
                                 x-transition:leave="transition-transform ease-in-out duration-300"
                                 x-transition:leave-start="transform translate-x-0"
                                 x-transition:leave-end="transform translate-x-full"
                                 @click.stop>
                                <div class="p-6 h-full overflow-y-auto">
                                    <div class="flex justify-between items-center mb-6">
                                        <h3 class="text-lg font-semibold">Alert Details</h3>
                                        <button @click="showDetails = false" class="text-gray-400 hover:text-gray-600">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                        </button>
                                    </div>
                                    
                                    <div class="space-y-6">
                                        <div>
                                            <h4 class="font-semibold text-xl text-gray-900 dark:text-white mb-2">{{ $alert->variety }}</h4>
                                            <p class="text-gray-600 dark:text-gray-400">{{ $alert->alert_type }}</p>
                                        </div>
                                        
                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Priority</div>
                                                <x-filament::badge :color="match($alert->priority) {
                                                    'critical' => 'danger',
                                                    'high' => 'warning',
                                                    'medium' => 'info',
                                                    'low' => 'gray',
                                                    default => 'gray'
                                                }">{{ strtoupper($alert->priority) }}</x-filament::badge>
                                            </div>
                                            <div>
                                                <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Tray Count</div>
                                                <div class="text-gray-900 dark:text-white font-medium">{{ $alert->tray_count }}</div>
                                            </div>
                                        </div>
                                        
                                        <div>
                                            <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Due Date</div>
                                            <div class="text-gray-900 dark:text-white">{{ $alert->next_run_at->format('M j, Y g:i A') }}</div>
                                            <div class="text-blue-600 font-medium">{{ $alert->time_until }}</div>
                                        </div>
                                        
                                        <div>
                                            <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Planted Date</div>
                                            <div class="text-gray-900 dark:text-white">{{ $alert->planting_at ? \Carbon\Carbon::parse($alert->planting_at)->format('M j, Y') : 'Unknown' }}</div>
                                        </div>
                                        
                                        <div>
                                            <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Stage Progress</div>
                                            <div class="flex items-center gap-2">
                                                <x-filament::badge color="info" size="sm">{{ ucfirst($alert->current_stage) }}</x-filament::badge>
                                                <span class="text-gray-500">→</span>
                                                <x-filament::badge :color="match($alert->target_stage) {
                                                    'germination' => 'info',
                                                    'blackout' => 'warning', 
                                                    'light' => 'success',
                                                    'harvested' => 'danger',
                                                    default => 'gray'
                                                }" size="sm">{{ ucfirst($alert->target_stage) }}</x-filament::badge>
                                            </div>
                                        </div>
                                        
                                        <div>
                                            <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Tray Numbers</div>
                                            <div class="flex flex-wrap gap-1">
                                                @foreach($alert->tray_numbers as $trayNumber)
                                                    <x-filament::badge color="gray" size="xs">{{ $trayNumber }}</x-filament::badge>
                                                @endforeach
                                            </div>
                                        </div>
                                        
                                        <div class="pt-4 border-t">
                                            <div class="space-y-3">
                                                <!-- Action Buttons -->
                                                <div class="flex gap-3">
                                                    <button type="button"
                                                            x-data
                                                            @click="
                                                                if (confirm('Advance {{ $alert->tray_count }} crops to {{ $alert->target_stage }}?')) {
                                                                    fetch('{{ route('dashboard.advance-crops') }}', {
                                                                        method: 'POST',
                                                                        headers: {
                                                                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                                                            'Content-Type': 'application/json',
                                                                        },
                                                                        body: JSON.stringify({
                                                                            alert_ids: {{ json_encode($alert->alert_ids) }}
                                                                        })
                                                                    })
                                                                    .then(response => response.json())
                                                                    .then(data => {
                                                                        if (data.success) {
                                                                            alert(data.message);
                                                                            window.location.reload();
                                                                        } else {
                                                                            alert('Error: ' + data.message);
                                                                        }
                                                                    })
                                                                    .catch(error => {
                                                                        alert('Error: ' + error);
                                                                    });
                                                                }
                                                            "
                                                            class="flex-1 text-center py-2 px-4 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                                                        Advance Stage
                                                    </button>
                                                    <button type="button"
                                                            x-data
                                                            @click="
                                                                if (confirm('Rollback {{ $alert->tray_count }} crops to previous stage?')) {
                                                                    fetch('{{ route('dashboard.rollback-crops') }}', {
                                                                        method: 'POST',
                                                                        headers: {
                                                                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                                                            'Content-Type': 'application/json',
                                                                        },
                                                                        body: JSON.stringify({
                                                                            alert_ids: {{ json_encode($alert->alert_ids) }}
                                                                        })
                                                                    })
                                                                    .then(response => response.json())
                                                                    .then(data => {
                                                                        if (data.success) {
                                                                            alert(data.message);
                                                                            window.location.reload();
                                                                        } else {
                                                                            alert('Error: ' + data.message);
                                                                        }
                                                                    })
                                                                    .catch(error => {
                                                                        alert('Error: ' + error);
                                                                    });
                                                                }
                                                            "
                                                            class="flex-1 text-center py-2 px-4 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors">
                                                        Rollback Stage
                                                    </button>
                                                </div>
                                                
                                                <!-- Other Links -->
                                                <div class="flex gap-3">
                                                    <a href="{{ route('filament.admin.resources.crop-alerts.edit', ['record' => $alert->id]) }}" 
                                                       class="flex-1 text-center py-2 px-4 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                                        Edit Alert
                                                    </a>
                                                    <a href="{{ route('filament.admin.resources.crops.index') }}" 
                                                       class="flex-1 text-center py-2 px-4 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                                                        View Batch
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                    @if(count($upcomingAlerts) > 15)
                    <div class="text-center py-6">
                        <a href="{{ route('filament.admin.resources.crop-alerts.index') }}" 
                           class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-blue-600 hover:text-blue-800 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors">
                            View all {{ count($upcomingAlerts) }} upcoming alerts
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </a>
                    </div>
                    @endif
                </div>
                @else
                <div class="text-center py-12">
                    <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No upcoming alerts</h4>
                    <p class="text-gray-500 dark:text-gray-400">No alerts scheduled for the next 7 days</p>
                    <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">All your crops are on schedule!</p>
                </div>
                @endif
            </div>
        </div>

        <!-- Inventory & Alerts Tab -->
        <div x-show="activeTab === 'inventory'" x-cloak>
            <p class="text-gray-500 text-center py-8">Inventory & Alerts content will go here</p>
        </div>

        <!-- Harvest & Yield Tab -->
        <div x-show="activeTab === 'harvest'" x-cloak>
            <p class="text-gray-500 text-center py-8">Harvest & Yield content will go here</p>
        </div>

        <!-- Planning & Predictions Tab -->
        <div x-show="activeTab === 'planning'" x-cloak>
            @php
                $urgentCrops = $urgentCropPlans ?? collect();
                $overdueCrops = $overdueCropPlans ?? collect();
                $upcomingOrders = $upcomingOrdersNeedingPlans ?? collect();
                $calendarEvents = $cropPlanningCalendarEvents ?? [];
            @endphp
            
            <div class="space-y-8">
                <!-- Header Section -->
                <div class="flex justify-between items-center">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Crop Planning & Predictions</h2>
                    <div class="flex gap-3">
                        <a href="{{ route('filament.admin.resources.crop-plans.index') }}" 
                           class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                            All Crop Plans
                        </a>
                    </div>
                </div>

                {{-- Overdue Crops Alert --}}
                @if($overdueCrops->isNotEmpty())
                    <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-6">
                        <div class="flex items-center mb-4">
                            <svg class="w-6 h-6 text-red-600 dark:text-red-400 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                            </svg>
                            <h3 class="text-lg font-medium text-red-900 dark:text-red-100">
                                ⚠️ Overdue Plantings ({{ $overdueCrops->count() }})
                            </h3>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                            @foreach($overdueCrops->take(6) as $plan)
                                <div class="bg-white dark:bg-red-900/40 border border-red-200 dark:border-red-700 rounded-lg p-3">
                                    <div class="flex justify-between items-center">
                                        <div>
                                            <span class="font-medium text-red-900 dark:text-red-100">
                                                {{ $plan->recipe->seedEntry->common_name }}
                                            </span>
                                            <span class="text-sm text-red-700 dark:text-red-300 ml-2">
                                                {{ $plan->trays_needed }} trays
                                            </span>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-sm font-medium text-red-900 dark:text-red-100">
                                                Due: {{ $plan->plant_by_date->format('M j') }}
                                            </div>
                                            <div class="text-xs text-red-700 dark:text-red-300">
                                                {{ $plan->plant_by_date->diffForHumans() }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        @if($overdueCrops->count() > 6)
                            <div class="mt-3 text-sm text-red-700 dark:text-red-300">
                                And {{ $overdueCrops->count() - 6 }} more overdue plans...
                            </div>
                        @endif
                    </div>
                @endif
                
                {{-- Urgent Crops Section --}}
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex justify-between items-center">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                    🌱 Crops to Plant Soon
                                </h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    Next 7 days planting schedule
                                </p>
                            </div>
                            @if($urgentCrops->isNotEmpty())
                                <div class="text-right">
                                    <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                                        {{ $urgentCrops->flatten()->count() }}
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ Str::plural('plan', $urgentCrops->flatten()->count()) }}
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                    
                    <div class="p-6">
                        @if($urgentCrops->isNotEmpty())
                            <div class="space-y-4">
                                @foreach($urgentCrops as $date => $plansForDate)
                                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 bg-gray-50 dark:bg-gray-900">
                                        <div class="flex justify-between items-center mb-3">
                                            <h4 class="font-medium text-gray-900 dark:text-gray-100">
                                                {{ \Carbon\Carbon::parse($date)->format('l, F j') }}
                                            </h4>
                                            <span class="text-sm text-gray-600 dark:text-gray-400">
                                                {{ $plansForDate->count() }} {{ Str::plural('variety', $plansForDate->count()) }}
                                            </span>
                                        </div>
                                        
                                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                            @foreach($plansForDate as $plan)
                                                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-3">
                                                    <div class="font-medium text-gray-900 dark:text-gray-100">
                                                        {{ $plan->recipe->seedEntry->common_name }}
                                                    </div>
                                                    <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                                        {{ $plan->trays_needed }} trays needed
                                                    </div>
                                                    <div class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                                                        Order #{{ $plan->order_id }}
                                                    </div>
                                                    <div class="mt-2">
                                                        <x-filament::badge color="success" size="sm">
                                                            {{ ucfirst($plan->status) }}
                                                        </x-filament::badge>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-8">
                                <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">No urgent plantings</h3>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    All crops are on schedule for the next 7 days.
                                </p>
                            </div>
                        @endif
                    </div>
                </div>
                
                {{-- Upcoming Orders Needing Plans --}}
                @if($upcomingOrders->isNotEmpty())
                    <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-6">
                        <div class="flex items-center mb-4">
                            <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <h3 class="text-lg font-medium text-yellow-900 dark:text-yellow-100">
                                📋 Orders Needing Crop Plans ({{ $upcomingOrders->count() }})
                            </h3>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                            @foreach($upcomingOrders->take(6) as $order)
                                <div class="bg-white dark:bg-yellow-900/40 border border-yellow-200 dark:border-yellow-700 rounded-lg p-4">
                                    <div class="flex justify-between items-start mb-3">
                                        <div>
                                            <div class="font-medium text-yellow-900 dark:text-yellow-100">
                                                Order #{{ $order->id }}
                                            </div>
                                            <div class="text-sm text-yellow-700 dark:text-yellow-300">
                                                {{ $order->customer->contact_name }}
                                            </div>
                                            <div class="text-sm text-yellow-700 dark:text-yellow-300 mt-1">
                                                Delivery: {{ $order->delivery_date->format('M j, Y') }}
                                            </div>
                                            <div class="text-xs text-yellow-600 dark:text-yellow-400 mt-1">
                                                {{ $order->orderItems->count() }} {{ Str::plural('item', $order->orderItems->count()) }}
                                            </div>
                                        </div>
                                        <div class="flex flex-col gap-1">
                                            <button 
                                                onclick="generateCropPlan({{ $order->id }})"
                                                class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium bg-green-600 text-white rounded-md hover:bg-green-700 transition-colors"
                                                title="Generate crop plan for this order"
                                            >
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                                </svg>
                                                Plan
                                            </button>
                                            <a 
                                                href="{{ route('filament.admin.resources.orders.edit', $order->id) }}"
                                                class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors"
                                                title="View order details"
                                            >
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                </svg>
                                                View
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        @if($upcomingOrders->count() > 6)
                            <div class="mt-3 text-sm text-yellow-700 dark:text-yellow-300">
                                And {{ $upcomingOrders->count() - 6 }} more orders needing plans...
                            </div>
                        @endif
                    </div>
                @endif
                
                {{-- Calendar Section --}}
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                            📅 Planting & Delivery Calendar
                        </h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            View delivery dates (green) and planting schedules (blue/red). Click events for details.
                        </p>
                    </div>
                    
                    <div class="p-6">
                        {{-- Temporarily disabled until FullCalendar package is installed --}}
                        {{-- @livewire('crop-planning-calendar', ['events' => $calendarEvents]) --}}
                        <div class="text-center text-gray-500 py-8">
                            Calendar view temporarily unavailable
                        </div>
                    </div>
                </div>
                
            </div>
        </div>

        <!-- Time Management Tab -->
        <div x-show="activeTab === 'time'" x-cloak>
            <div class="space-y-8">
                <!-- Header Section -->
                <div class="flex justify-between items-center">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Time Management</h2>
                    <a href="{{ route('filament.admin.resources.time-cards.index') }}" 
                       class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Manage Time Cards
                    </a>
                </div>

                <!-- Summary Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <!-- Employees Clocked In -->
                    <div class="bg-white dark:bg-gray-900 rounded-xl border border-blue-200 dark:border-blue-700 p-6 shadow-sm hover:shadow-md transition-all">
                        <div class="flex items-center mb-3">
                            <div class="w-2 h-2 rounded-full bg-blue-500 mr-3"></div>
                            <h3 class="text-xs font-semibold text-blue-600 dark:text-blue-400 uppercase tracking-wider">Currently Working</h3>
                        </div>
                        <div class="mb-1">
                            <span class="text-2xl font-bold text-gray-900 dark:text-white">{{ $timeCardsSummary['employees_clocked_in'] ?? 0 }}</span>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Employees clocked in</p>
                    </div>

                    <!-- Flagged Time Cards -->
                    <div class="bg-white dark:bg-gray-900 rounded-xl border border-red-200 dark:border-red-700 p-6 shadow-sm hover:shadow-md transition-all">
                        <div class="flex items-center mb-3">
                            <div class="w-2 h-2 rounded-full bg-red-500 mr-3"></div>
                            <h3 class="text-xs font-semibold text-red-600 dark:text-red-400 uppercase tracking-wider">Need Review</h3>
                        </div>
                        <div class="mb-1">
                            <span class="text-2xl font-bold text-gray-900 dark:text-white">{{ $timeCardsSummary['flagged_time_cards'] ?? 0 }}</span>
                        </div>
                        @if(($timeCardsSummary['flagged_time_cards'] ?? 0) > 0)
                            <p class="text-xs text-red-600 dark:text-red-400">Exceeded 8-hour limit</p>
                        @else
                            <p class="text-xs text-gray-500 dark:text-gray-400">All shifts normal</p>
                        @endif
                    </div>

                    <!-- Today's Hours -->
                    <div class="bg-white dark:bg-gray-900 rounded-xl border border-green-200 dark:border-green-700 p-6 shadow-sm hover:shadow-md transition-all">
                        <div class="flex items-center mb-3">
                            <div class="w-2 h-2 rounded-full bg-green-500 mr-3"></div>
                            <h3 class="text-xs font-semibold text-green-600 dark:text-green-400 uppercase tracking-wider">Today's Hours</h3>
                        </div>
                        <div class="mb-1">
                            <span class="text-2xl font-bold text-gray-900 dark:text-white">{{ $timeCardsSummary['total_hours_today'] ?? '0.0' }}</span>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Total hours worked</p>
                    </div>

                    <!-- Weekly Hours -->
                    <div class="bg-white dark:bg-gray-900 rounded-xl border border-purple-200 dark:border-purple-700 p-6 shadow-sm hover:shadow-md transition-all">
                        <div class="flex items-center mb-3">
                            <div class="w-2 h-2 rounded-full bg-purple-500 mr-3"></div>
                            <h3 class="text-xs font-semibold text-purple-600 dark:text-purple-400 uppercase tracking-wider">This Week</h3>
                        </div>
                        <div class="mb-1">
                            <span class="text-2xl font-bold text-gray-900 dark:text-white">{{ $timeCardsSummary['total_hours_this_week'] ?? '0.0' }}</span>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Total hours this week</p>
                    </div>
                </div>

                <!-- Active Employees Section -->
                @if(!empty($activeEmployees) && count($activeEmployees) > 0)
                <div class="space-y-6">
                    <h3 class="text-lg font-semibold text-blue-600 dark:text-blue-400 flex items-center">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Currently Clocked In ({{ count($activeEmployees) }})
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($activeEmployees as $employee)
                        <div class="bg-white dark:bg-gray-900 border {{ $employee['is_flagged'] ? 'border-red-200 dark:border-red-700' : 'border-gray-200 dark:border-gray-700' }} rounded-lg p-4 hover:shadow-md transition-all">
                            <div class="flex justify-between items-start mb-2">
                                <h4 class="font-semibold text-gray-900 dark:text-white">{{ $employee['user_name'] }}</h4>
                                @if($employee['is_flagged'])
                                    <x-filament::badge color="danger" size="sm">REVIEW</x-filament::badge>
                                @elseif($employee['needs_attention'])
                                    <x-filament::badge color="warning" size="sm">NEAR LIMIT</x-filament::badge>
                                @endif
                            </div>
                            <div class="space-y-1 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-500 dark:text-gray-400">Clock In:</span>
                                    <span class="text-gray-900 dark:text-white">{{ \Carbon\Carbon::parse($employee['clock_in'])->format('g:i A') }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-500 dark:text-gray-400">Elapsed:</span>
                                    <span class="font-mono {{ $employee['is_flagged'] ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white' }}">{{ $employee['elapsed_time'] }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-500 dark:text-gray-400">Hours:</span>
                                    <span class="text-gray-900 dark:text-white">{{ number_format($employee['hours_worked'], 1) }}h</span>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @else
                <div class="text-center py-12">
                    <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No employees currently clocked in</h4>
                    <p class="text-gray-500 dark:text-gray-400">All employees have clocked out for the day</p>
                </div>
                @endif

                <!-- Flagged Time Cards Section -->
                @if(!empty($flaggedTimeCards) && count($flaggedTimeCards) > 0)
                <div class="space-y-6">
                    <h3 class="text-lg font-semibold text-red-600 dark:text-red-400 flex items-center">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                        Time Cards Requiring Review ({{ count($flaggedTimeCards) }})
                    </h3>
                    <div class="space-y-4">
                        @foreach($flaggedTimeCards as $timeCard)
                        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded-lg p-4">
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <div class="flex items-center gap-3 mb-2">
                                        <h4 class="font-semibold text-gray-900 dark:text-white">{{ $timeCard['user_name'] }}</h4>
                                        <x-filament::badge color="danger" size="sm">EXCEEDED 8 HOURS</x-filament::badge>
                                    </div>
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                                        <div>
                                            <span class="text-gray-500 dark:text-gray-400">Date:</span>
                                            <div class="text-gray-900 dark:text-white">{{ \Carbon\Carbon::parse($timeCard['work_date'])->format('M j, Y') }}</div>
                                        </div>
                                        <div>
                                            <span class="text-gray-500 dark:text-gray-400">Clock In:</span>
                                            <div class="text-gray-900 dark:text-white">{{ \Carbon\Carbon::parse($timeCard['clock_in'])->format('g:i A') }}</div>
                                        </div>
                                        <div>
                                            <span class="text-gray-500 dark:text-gray-400">Total Time:</span>
                                            <div class="font-mono text-red-600 dark:text-red-400">{{ $timeCard['elapsed_time'] }}</div>
                                        </div>
                                        <div>
                                            <span class="text-gray-500 dark:text-gray-400">Exceeded:</span>
                                            <div class="text-gray-900 dark:text-white">{{ \Carbon\Carbon::parse($timeCard['exceeded_at'])->format('g:i A') }}</div>
                                        </div>
                                    </div>
                                    @if(!empty($timeCard['flags']))
                                    <div class="mt-2 flex flex-wrap gap-1">
                                        @foreach($timeCard['flags'] as $flag)
                                            <x-filament::badge color="warning" size="xs">{{ $flag }}</x-filament::badge>
                                        @endforeach
                                    </div>
                                    @endif
                                </div>
                                <div class="ml-4">
                                    <a href="{{ route('filament.admin.resources.time-cards.edit', ['record' => $timeCard['id']]) }}" 
                                       class="inline-flex items-center gap-1 px-3 py-1 text-xs font-medium bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                        Review
                                    </a>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </div>

        <!-- Analytics & Reports Tab -->
        <div x-show="activeTab === 'analytics'" x-cloak>
            <p class="text-gray-500 text-center py-8">Analytics & Reports content will go here</p>
        </div>
    </div>
</div>

<script>
function generateCropPlan(orderId) {
    // Show loading state
    const button = event.target;
    const originalText = button.innerHTML;
    button.disabled = true;
    button.innerHTML = `
        <svg class="w-3 h-3 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
        </svg>
        Generating...
    `;
    
    // Get CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') 
        || document.querySelector('input[name="_token"]')?.value 
        || '{{ csrf_token() }}';
    
    // Make AJAX request to generate crop plan
    fetch(`/admin/generate-crop-plan/${orderId}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success message
            button.innerHTML = `
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                Generated!
            `;
            button.className = button.className.replace('bg-green-600 hover:bg-green-700', 'bg-green-500');
            
            // Refresh the dashboard after a short delay
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            throw new Error(data.message || 'Failed to generate crop plan');
        }
    })
    .catch(error => {
        console.error('Error generating crop plan:', error);
        
        // Show error state
        button.innerHTML = `
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
            Error
        `;
        button.className = button.className.replace('bg-green-600 hover:bg-green-700', 'bg-red-500');
        
        // Reset button after delay
        setTimeout(() => {
            button.disabled = false;
            button.innerHTML = originalText;
            button.className = button.className.replace('bg-red-500', 'bg-green-600 hover:bg-green-700');
        }, 3000);
    });
}
</script>
</x-filament-panels::page>