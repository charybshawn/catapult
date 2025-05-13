{{-- Redesigned Weekly Planning View with proper Filament integration --}}
<x-filament::page>
    <div class="space-y-6">
        {{-- Header with date selection --}}
        <div class="flex items-center justify-between flex-wrap gap-4">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                Weekly Planning for: <span class="text-primary-600 dark:text-primary-400">{{ $harvestDate->format('M d, Y') }}</span> (Harvest Date)
            </h2>
            
            <div class="flex-shrink-0">
                {{ $this->form }}
            </div>
        </div>
        
        {{-- Harvest Summary Section --}}
        <x-filament::section>
            <x-slot name="heading">
                <h2 class="text-lg font-medium">Harvest Summary</h2>
            </x-slot>
            
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                {{-- Orders Panel --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
                    <div class="px-4 py-3 bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-base font-medium text-gray-900 dark:text-white">Orders ({{ $harvests->count() }})</h3>
                    </div>
                    
                    @if($harvests->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead>
                                    <tr>
                                        <th class="px-6 py-3 bg-gray-50 dark:bg-gray-800 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Order ID</th>
                                        <th class="px-6 py-3 bg-gray-50 dark:bg-gray-800 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Customer</th>
                                        <th class="px-6 py-3 bg-gray-50 dark:bg-gray-800 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Type</th>
                                        <th class="px-6 py-3 bg-gray-50 dark:bg-gray-800 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 bg-gray-50 dark:bg-gray-800 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach($harvests as $order)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">{{ $order->id }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">{{ $order->user->name }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <x-filament::badge>
                                                    {{ $order->customer_type }}
                                                </x-filament::badge>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <x-filament::badge color="{{ $order->status === 'completed' ? 'success' : ($order->status === 'cancelled' ? 'danger' : 'warning') }}">
                                                    {{ $order->status }}
                                                </x-filament::badge>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">${{ number_format($order->totalAmount(), 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="p-6 text-center text-sm text-gray-500 dark:text-gray-400">
                            <x-filament::icon-empty
                                icon="heroicon-o-calendar"
                                class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500 mb-4"
                            />
                            <p>No orders scheduled for harvest on this date.</p>
                        </div>
                    @endif
                </div>
                
                {{-- Product Totals Panel --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
                    <div class="px-4 py-3 bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-base font-medium text-gray-900 dark:text-white">Product Totals</h3>
                    </div>
                    
                    @if(count($productTotals) > 0)
                        <div class="overflow-x-auto">
                            <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead>
                                    <tr>
                                        <th class="px-6 py-3 bg-gray-50 dark:bg-gray-800 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Product</th>
                                        <th class="px-6 py-3 bg-gray-50 dark:bg-gray-800 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total Quantity</th>
                                        <th class="px-6 py-3 bg-gray-50 dark:bg-gray-800 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Customers</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach($productTotals as $productName => $data)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">{{ $productName }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">{{ $data['quantity'] }}</td>
                                            <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">
                                                <div class="space-y-1">
                                                    @foreach($data['orders'] as $order)
                                                        <div class="text-xs px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded">
                                                            <span class="font-medium">{{ $order['customer'] }}:</span> {{ $order['quantity'] }}
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="p-6 text-center text-sm text-gray-500 dark:text-gray-400">
                            <x-filament::icon-empty
                                icon="heroicon-o-cube"
                                class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500 mb-4"
                            />
                            <p>No products to harvest on this date.</p>
                        </div>
                    @endif
                </div>
            </div>
        </x-filament::section>
        
        {{-- Planting Recommendations --}}
        <x-filament::section>
            <x-slot name="heading">
                <h2 class="text-lg font-medium">Planting Recommendations</h2>
            </x-slot>
            
            @if(count($plantingRecommendations) > 0)
                <div class="overflow-x-auto">
                    <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead>
                            <tr>
                                <th class="px-6 py-3 bg-gray-50 dark:bg-gray-800 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Product</th>
                                <th class="px-6 py-3 bg-gray-50 dark:bg-gray-800 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Recipe</th>
                                <th class="px-6 py-3 bg-gray-50 dark:bg-gray-800 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Plant By</th>
                                <th class="px-6 py-3 bg-gray-50 dark:bg-gray-800 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Growth Period</th>
                                <th class="px-6 py-3 bg-gray-50 dark:bg-gray-800 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Trays Needed</th>
                                <th class="px-6 py-3 bg-gray-50 dark:bg-gray-800 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Existing Trays</th>
                                <th class="px-6 py-3 bg-gray-50 dark:bg-gray-800 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Additional Trays</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($plantingRecommendations as $data)
                                <tr class="{{ $data['additional_trays_needed'] > 0 ? 'bg-amber-50 dark:bg-amber-900/20' : '' }}">
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">{{ $data['name'] }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">{{ $data['recipe']->name }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">
                                        <x-filament::badge color="gray">
                                            {{ $data['plant_by_date']->format('M d, Y') }}
                                        </x-filament::badge>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">{{ $data['total_days'] }} days</td>
                                    <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">{{ $data['trays_needed'] }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">{{ $data['existing_trays'] }}</td>
                                    <td class="px-6 py-4">
                                        @if($data['additional_trays_needed'] > 0)
                                            <x-filament::badge color="warning" size="lg">
                                                {{ $data['additional_trays_needed'] }}
                                            </x-filament::badge>
                                        @else
                                            <x-filament::badge color="success" size="lg">
                                                0
                                            </x-filament::badge>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="p-6 text-center text-sm text-gray-500 dark:text-gray-400">
                    <x-filament::icon-empty
                        icon="heroicon-o-flag"
                        class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500 mb-4"
                    />
                    <p>No planting recommendations for this harvest date.</p>
                </div>
            @endif
        </x-filament::section>
        
        {{-- Active Crops --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center">
                    <h2 class="text-lg font-medium">Active Crops</h2>
                    <span class="ml-2 px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-100 text-primary-800 dark:bg-primary-800 dark:text-primary-200">
                        {{ $activeCrops->count() }}
                    </span>
                </div>
            </x-slot>
            
            @if($activeCrops->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead>
                            <tr>
                                <th class="px-6 py-3 bg-gray-50 dark:bg-gray-800 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tray</th>
                                <th class="px-6 py-3 bg-gray-50 dark:bg-gray-800 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Recipe</th>
                                <th class="px-6 py-3 bg-gray-50 dark:bg-gray-800 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Stage</th>
                                <th class="px-6 py-3 bg-gray-50 dark:bg-gray-800 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Planted On</th>
                                <th class="px-6 py-3 bg-gray-50 dark:bg-gray-800 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Expected Harvest</th>
                                <th class="px-6 py-3 bg-gray-50 dark:bg-gray-800 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Days in Stage</th>
                                <th class="px-6 py-3 bg-gray-50 dark:bg-gray-800 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Customer</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($activeCrops as $crop)
                                <tr>
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">{{ $crop->tray_number }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">{{ $crop->recipe->name }}</td>
                                    <td class="px-6 py-4">
                                        <x-filament::badge 
                                            :color="match($crop->current_stage) {
                                                'germination' => 'info',
                                                'blackout' => 'warning',
                                                'light' => 'success',
                                                default => 'gray',
                                            }"
                                            size="lg"
                                        >
                                            {{ ucfirst($crop->current_stage) }}
                                        </x-filament::badge>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">{{ $crop->planted_at->format('M d, Y') }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">
                                        @if($crop->expectedHarvestDate())
                                            {{ $crop->expectedHarvestDate()->format('M d, Y') }}
                                        @else
                                            <span class="text-gray-400 dark:text-gray-500">Unknown</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">
                                        {{ $crop->daysInCurrentStage() }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">
                                        {{ $crop->order?->user?->name ?? 'N/A' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="p-6 text-center text-sm text-gray-500 dark:text-gray-400">
                    <x-filament::icon-empty
                        icon="heroicon-o-beaker"
                        class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500 mb-4"
                    />
                    <p>No active crops found.</p>
                </div>
            @endif
        </x-filament::section>
    </div>
</x-filament::page> 