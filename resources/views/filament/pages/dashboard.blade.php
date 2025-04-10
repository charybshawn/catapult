<x-filament-panels::page>
    <style>
        .dashboard-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 1rem;
        }
        
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .dashboard-tabs {
            display: flex;
            border-bottom: 1px solid #e5e7eb;
            margin-bottom: 1.5rem;
            overflow-x: auto;
        }
        
        .dashboard-tab {
            padding: 0.75rem 1rem;
            font-weight: 500;
            border-bottom: 2px solid transparent;
            cursor: pointer;
            white-space: nowrap;
        }
        
        .dashboard-tab.active {
            border-bottom-color: #10b981;
            color: #10b981;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .dashboard-card {
            background-color: white;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
            height: 100%;
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 0.75rem;
        }
        
        .card-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: #111827;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #10b981;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: #6b7280;
            font-size: 0.875rem;
        }
        
        .task-list {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        
        .task-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem;
            border-radius: 0.375rem;
            background-color: #f9fafb;
        }
        
        .task-info {
            flex: 1;
        }
        
        .task-detail {
            font-size: 0.875rem;
            color: #6b7280;
            margin-top: 0.25rem;
        }
        
        .task-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        @media (max-width: 768px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .task-item {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .task-actions {
                margin-top: 0.75rem;
                width: 100%;
                justify-content: flex-end;
            }
        }
    </style>

    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1 class="text-2xl font-bold">Farm Dashboard</h1>
            <div>
                <button onclick="window.location.href='{{ route('filament.admin.resources.crops.create') }}'" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                    Start New Crop
                </button>
            </div>
        </div>
        
        <div class="dashboard-tabs">
            <div class="dashboard-tab active" onclick="switchTab('active-crops')">Active Crops</div>
            <div class="dashboard-tab" onclick="switchTab('stats')">Stats</div>
            <div class="dashboard-tab" onclick="switchTab('crop-alerts')">Crop Alerts</div>
            <div class="dashboard-tab" onclick="switchTab('inventory-alerts')">Inventory Alerts</div>
        </div>
        
        <!-- Active Crops Tab -->
        <div id="active-crops" class="tab-content active">
            <div class="dashboard-grid">
                <div class="dashboard-card">
                    <div class="card-header">
                        <h2 class="card-title">Active Crops</h2>
                        <a href="{{ route('filament.admin.resources.crops.index') }}" class="text-sm text-blue-600 hover:underline">View All</a>
                    </div>
                    <div class="stat-value">{{ $activeCropsCount }}</div>
                    <div class="stat-label">Crops in production</div>
                </div>
                
                <div class="dashboard-card">
                    <div class="card-header">
                        <h2 class="card-title">Active Trays</h2>
                    </div>
                    <div class="stat-value">{{ $activeTraysCount }}</div>
                    <div class="stat-label">Trays in use</div>
                </div>
                
                <div class="dashboard-card">
                    <div class="card-header">
                        <h2 class="card-title">Crop Tasks</h2>
                        <a href="{{ route('filament.admin.pages.manage-crop-tasks') }}" class="text-sm text-blue-600 hover:underline">Manage Tasks</a>
                    </div>
                    <div>
                        <div class="stat-value">{{ $tasksCount }}</div>
                        <div class="stat-label">Active tasks</div>
                        @if($overdueTasksCount > 0)
                            <div class="mt-2 text-red-600 font-medium">{{ $overdueTasksCount }} overdue</div>
                        @endif
                    </div>
                </div>
                
                <div class="dashboard-card">
                    <div class="card-header">
                        <h2 class="card-title">Low Stock Items</h2>
                        <a href="{{ route('filament.admin.resources.consumables.index') }}" class="text-sm text-blue-600 hover:underline">View Inventory</a>
                    </div>
                    <div class="stat-value">{{ $lowStockCount }}</div>
                    <div class="stat-label">Items below threshold</div>
                </div>
            </div>
            
            @if($cropsNeedingHarvest->count() > 0)
                <div class="dashboard-card mb-6">
                    <div class="card-header">
                        <h2 class="card-title">Ready to Harvest</h2>
                    </div>
                    <div class="task-list">
                        @foreach($cropsNeedingHarvest as $crop)
                            <div class="task-item">
                                <div class="task-info">
                                    <div class="font-medium">{{ $crop->recipe->seedVariety->name }}</div>
                                    <div class="task-detail">
                                        Tray #{{ $crop->tray_number }} • 
                                        Planted: {{ $crop->planted_at->format('M d') }} • 
                                        Days grown: {{ $crop->planted_at->diffInDays(now()) }}
                                    </div>
                                </div>
                                <div class="task-actions">
                                    <a href="{{ route('filament.admin.resources.crops.edit', $crop) }}" class="px-3 py-1 bg-blue-100 text-blue-800 rounded hover:bg-blue-200">View</a>
                                    <a href="{{ route('filament.admin.resources.crops.harvest', $crop) }}" class="px-3 py-1 bg-green-100 text-green-800 rounded hover:bg-green-200">Harvest</a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
            
            @if($recentlySowedCrops->count() > 0)
                <div class="dashboard-card">
                    <div class="card-header">
                        <h2 class="card-title">Recently Sowed</h2>
                    </div>
                    <div class="task-list">
                        @foreach($recentlySowedCrops as $crop)
                            <div class="task-item">
                                <div class="task-info">
                                    <div class="font-medium">{{ $crop->recipe->seedVariety->name }}</div>
                                    <div class="task-detail">
                                        Tray #{{ $crop->tray_number }} • 
                                        Planted: {{ $crop->planted_at->format('M d') }} • 
                                        Stage: {{ ucfirst($crop->current_stage) }}
                                    </div>
                                </div>
                                <div class="task-actions">
                                    <a href="{{ route('filament.admin.resources.crops.edit', $crop) }}" class="px-3 py-1 bg-blue-100 text-blue-800 rounded hover:bg-blue-200">View</a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
        
        <!-- Stats Tab -->
        <div id="stats" class="tab-content">
            <div class="dashboard-grid">
                <div class="dashboard-card">
                    <div class="card-header">
                        <h2 class="card-title">Production Stats</h2>
                    </div>
                    <div class="p-4">
                        <div class="mb-4">
                            <div class="stat-value">{{ $totalHarvestedCrops }}</div>
                            <div class="stat-label">Total crops harvested</div>
                        </div>
                        <div class="mb-4">
                            <div class="stat-value">{{ $totalHarvestedWeight }} oz</div>
                            <div class="stat-label">Total weight harvested</div>
                        </div>
                        <div>
                            <div class="stat-value">${{ number_format($totalHarvestedValue, 2) }}</div>
                            <div class="stat-label">Total harvest value</div>
                        </div>
                    </div>
                </div>
                
                <!-- Add more stats cards as needed -->
            </div>
        </div>
        
        <!-- Crop Alerts Tab -->
        <div id="crop-alerts" class="tab-content">
            <div class="dashboard-card">
                <div class="card-header">
                    <h2 class="card-title">Crop Alerts</h2>
                </div>
                <div>
                    <div class="space-y-6">
                        <div class="text-lg font-medium flex items-center justify-between border-b pb-2">
                            <span>Crops Needing Attention</span>
                            <a href="{{ route('filament.admin.pages.manage-crop-tasks') }}" class="text-sm text-blue-600 hover:underline">
                                Manage Tasks
                            </a>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <!-- Seeded Stage -->
                            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                                <div class="flex items-center gap-2 font-medium text-lg mb-3">
                                    <span>Seeded Crops</span>
                                    <span class="ml-auto bg-blue-100 text-blue-800 rounded-full px-2 py-0.5 text-xs">
                                        New
                                    </span>
                                </div>
                                
                                @php
                                    $seedingCrops = App\Models\Crop::where('current_stage', 'planting')
                                        ->with('recipe.seedVariety')
                                        ->take(3)
                                        ->get();
                                @endphp
                                
                                <div class="space-y-3">
                                    @if($seedingCrops->count() > 0)
                                        @foreach($seedingCrops as $crop)
                                            <div class="border-b pb-3">
                                                <div class="flex items-center justify-between">
                                                    <div>
                                                        <div class="font-medium">{{ $crop->recipe->seedVariety->name }}</div>
                                                        <div class="text-sm text-gray-500">Tray: {{ $crop->tray_number }}</div>
                                                    </div>
                                                    <div class="text-right">
                                                        <div class="font-medium">
                                                            {{ $crop->planting_at ? $crop->planting_at->diffInDays(now()) : 0 }} days
                                                        </div>
                                                        <div class="text-xs text-gray-500">
                                                            (Rec: 1 day)
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="mt-2 flex justify-end">
                                                    <a href="{{ route('filament.admin.resources.crops.edit', $crop) }}" 
                                                       class="px-3 py-1 bg-blue-100 text-blue-800 rounded text-xs hover:bg-blue-200">
                                                        Manage
                                                    </a>
                                                </div>
                                            </div>
                                        @endforeach
                                    @else
                                        <div class="text-gray-500 text-sm py-2 text-center">
                                            No crops in seeding stage need attention
                                        </div>
                                    @endif
                                </div>
                            </div>
                            
                            <!-- Blackout Stage -->
                            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                                <div class="flex items-center gap-2 font-medium text-lg mb-3">
                                    <span>Blackout Stage</span>
                                </div>
                                
                                @php
                                    $blackoutCrops = App\Models\Crop::where('current_stage', 'blackout')
                                        ->with('recipe.seedVariety')
                                        ->take(3)
                                        ->get();
                                @endphp
                                
                                <div class="space-y-3">
                                    @if($blackoutCrops->count() > 0)
                                        @foreach($blackoutCrops as $crop)
                                            <div class="border-b pb-3">
                                                <div class="flex items-center justify-between">
                                                    <div>
                                                        <div class="font-medium">{{ $crop->recipe->seedVariety->name }}</div>
                                                        <div class="text-sm text-gray-500">Tray: {{ $crop->tray_number }}</div>
                                                    </div>
                                                    <div class="text-right">
                                                        <div class="font-medium">
                                                            {{ $crop->blackout_at ? $crop->blackout_at->diffInDays(now()) : 0 }} days
                                                        </div>
                                                        <div class="text-xs text-gray-500">
                                                            (Rec: {{ $crop->recipe->blackout_days ?? 3 }} days)
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="mt-2 flex justify-end">
                                                    <a href="{{ route('filament.admin.resources.crops.edit', $crop) }}" 
                                                       class="px-3 py-1 bg-blue-100 text-blue-800 rounded text-xs hover:bg-blue-200">
                                                        Manage
                                                    </a>
                                                </div>
                                            </div>
                                        @endforeach
                                    @else
                                        <div class="text-gray-500 text-sm py-2 text-center">
                                            No crops in blackout stage need attention
                                        </div>
                                    @endif
                                </div>
                            </div>
                            
                            <!-- Growing Stage -->
                            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                                <div class="flex items-center gap-2 font-medium text-lg mb-3">
                                    <span>Growing Crops</span>
                                </div>
                                
                                @php
                                    $growingCrops = App\Models\Crop::where('current_stage', 'light')
                                        ->with('recipe.seedVariety')
                                        ->take(3)
                                        ->get();
                                @endphp
                                
                                <div class="space-y-3">
                                    @if($growingCrops->count() > 0)
                                        @foreach($growingCrops as $crop)
                                            <div class="border-b pb-3">
                                                <div class="flex items-center justify-between">
                                                    <div>
                                                        <div class="font-medium">{{ $crop->recipe->seedVariety->name }}</div>
                                                        <div class="text-sm text-gray-500">Tray: {{ $crop->tray_number }}</div>
                                                    </div>
                                                    <div class="text-right">
                                                        <div class="font-medium">
                                                            {{ $crop->light_at ? $crop->light_at->diffInDays(now()) : 0 }} days
                                                        </div>
                                                        <div class="text-xs text-gray-500">
                                                            (Rec: {{ $crop->recipe->light_days ?? 7 }} days)
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="mt-2 flex justify-end">
                                                    <a href="{{ route('filament.admin.resources.crops.edit', $crop) }}" 
                                                       class="px-3 py-1 bg-blue-100 text-blue-800 rounded text-xs hover:bg-blue-200">
                                                        Manage
                                                    </a>
                                                </div>
                                            </div>
                                        @endforeach
                                    @else
                                        <div class="text-gray-500 text-sm py-2 text-center">
                                            No crops in growing stage need attention
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Inventory Alerts Tab -->
        <div id="inventory-alerts" class="tab-content">
            <div class="dashboard-card">
                <div class="card-header">
                    <h2 class="card-title">Low Stock Alerts</h2>
                    <a href="{{ route('filament.admin.resources.consumables.index') }}" class="text-sm text-blue-600 hover:underline">View All Inventory</a>
                </div>
                <div class="task-list">
                    @foreach($lowStockItems as $item)
                        <div class="task-item">
                            <div class="task-info">
                                <div class="font-medium">{{ $item->name }}</div>
                                <div class="task-detail">
                                    Current stock: <span class="text-red-600 font-medium">{{ $item->current_stock }}</span> • 
                                    Threshold: {{ $item->restock_threshold }}
                                </div>
                            </div>
                            <div class="task-actions">
                                <a href="{{ route('filament.admin.resources.consumables.edit', $item) }}" class="px-3 py-1 bg-blue-100 text-blue-800 rounded hover:bg-blue-200">View</a>
                                <a href="{{ route('filament.admin.resources.consumables.restock', $item) }}" class="px-3 py-1 bg-yellow-100 text-yellow-800 rounded hover:bg-yellow-200">Restock</a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    
    @push('scripts')
    <script>
        function switchTab(tabId) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Deactivate all tabs
            document.querySelectorAll('.dashboard-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Activate selected tab
            document.getElementById(tabId).classList.add('active');
            
            // Find and activate the tab button
            document.querySelectorAll('.dashboard-tab').forEach(tab => {
                if (tab.textContent.trim().toLowerCase().includes(tabId.replace('-', ' '))) {
                    tab.classList.add('active');
                }
            });
        }
    </script>
    @endpush
</x-filament-panels::page> 