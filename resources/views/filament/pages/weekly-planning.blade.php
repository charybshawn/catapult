<x-filament::page>
    <x-filament::section>
        <x-slot name="heading">
            Weekly Planning for: {{ $harvestDate->format('M d, Y') }} (Harvest Date)
        </x-slot>
        
        <x-slot name="headerEnd">
            {{ $this->form }}
        </x-slot>
        
        <!-- Harvest Summary Section -->
        <div class="space-y-6">
            <h2 class="text-xl font-bold">Harvest Summary</h2>
            
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <x-filament::section>
                    <x-slot name="heading">Orders ({{ $harvests->count() }})</x-slot>
                    
                    @if($harvests->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left">
                                <thead>
                                    <tr class="bg-gray-50 dark:bg-gray-700">
                                        <th class="px-4 py-2">Order ID</th>
                                        <th class="px-4 py-2">Customer</th>
                                        <th class="px-4 py-2">Type</th>
                                        <th class="px-4 py-2">Status</th>
                                        <th class="px-4 py-2">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($harvests as $order)
                                        <tr class="border-t">
                                            <td class="px-4 py-2">{{ $order->id }}</td>
                                            <td class="px-4 py-2">{{ $order->user->name }}</td>
                                            <td class="px-4 py-2">
                                                <x-filament::badge>
                                                    {{ $order->customer_type }}
                                                </x-filament::badge>
                                            </td>
                                            <td class="px-4 py-2">
                                                <x-filament::badge color="{{ $order->status === 'completed' ? 'success' : ($order->status === 'cancelled' ? 'danger' : 'warning') }}">
                                                    {{ $order->status }}
                                                </x-filament::badge>
                                            </td>
                                            <td class="px-4 py-2">${{ number_format($order->totalAmount(), 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="p-4 text-center text-gray-500">
                            No orders scheduled for harvest on this date.
                        </div>
                    @endif
                </x-filament::section>
                
                <x-filament::section>
                    <x-slot name="heading">Product Totals</x-slot>
                    
                    @if(count($productTotals) > 0)
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left">
                                <thead>
                                    <tr class="bg-gray-50 dark:bg-gray-700">
                                        <th class="px-4 py-2">Product</th>
                                        <th class="px-4 py-2">Total Quantity</th>
                                        <th class="px-4 py-2">Customers</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($productTotals as $productName => $data)
                                        <tr class="border-t">
                                            <td class="px-4 py-2">{{ $productName }}</td>
                                            <td class="px-4 py-2">{{ $data['quantity'] }}</td>
                                            <td class="px-4 py-2">
                                                @foreach($data['orders'] as $order)
                                                    <div class="text-xs">
                                                        {{ $order['customer'] }}: {{ $order['quantity'] }}
                                                    </div>
                                                @endforeach
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="p-4 text-center text-gray-500">
                            No products to harvest on this date.
                        </div>
                    @endif
                </x-filament::section>
            </div>
            
            <!-- Planting Recommendations -->
            <x-filament::section>
                <x-slot name="heading">Planting Recommendations</x-slot>
                
                @if(count($plantingRecommendations) > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead>
                                <tr class="bg-gray-50 dark:bg-gray-700">
                                    <th class="px-4 py-2">Product</th>
                                    <th class="px-4 py-2">Recipe</th>
                                    <th class="px-4 py-2">Plant By</th>
                                    <th class="px-4 py-2">Growth Period</th>
                                    <th class="px-4 py-2">Trays Needed</th>
                                    <th class="px-4 py-2">Existing Trays</th>
                                    <th class="px-4 py-2">Additional Trays</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($plantingRecommendations as $data)
                                    <tr class="border-t {{ $data['additional_trays_needed'] > 0 ? 'bg-yellow-50 dark:bg-yellow-900/20' : '' }}">
                                        <td class="px-4 py-2">{{ $data['name'] }}</td>
                                        <td class="px-4 py-2">{{ $data['recipe']->name }}</td>
                                        <td class="px-4 py-2">{{ $data['plant_by_date']->format('M d, Y') }}</td>
                                        <td class="px-4 py-2">{{ $data['total_days'] }} days</td>
                                        <td class="px-4 py-2">{{ $data['trays_needed'] }}</td>
                                        <td class="px-4 py-2">{{ $data['existing_trays'] }}</td>
                                        <td class="px-4 py-2">
                                            @if($data['additional_trays_needed'] > 0)
                                                <x-filament::badge color="warning">
                                                    {{ $data['additional_trays_needed'] }}
                                                </x-filament::badge>
                                            @else
                                                <x-filament::badge color="success">
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
                    <div class="p-4 text-center text-gray-500">
                        No planting recommendations for this harvest date.
                    </div>
                @endif
            </x-filament::section>
            
            <!-- Active Crops -->
            <x-filament::section>
                <x-slot name="heading">Active Crops ({{ $activeCrops->count() }})</x-slot>
                
                @if($activeCrops->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead>
                                <tr class="bg-gray-50 dark:bg-gray-700">
                                    <th class="px-4 py-2">Tray</th>
                                    <th class="px-4 py-2">Recipe</th>
                                    <th class="px-4 py-2">Stage</th>
                                    <th class="px-4 py-2">Planted On</th>
                                    <th class="px-4 py-2">Expected Harvest</th>
                                    <th class="px-4 py-2">Days in Stage</th>
                                    <th class="px-4 py-2">Customer</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($activeCrops as $crop)
                                    <tr class="border-t">
                                        <td class="px-4 py-2">{{ $crop->tray_number }}</td>
                                        <td class="px-4 py-2">{{ $crop->recipe->name }}</td>
                                        <td class="px-4 py-2">
                                            <x-filament::badge color="{{ $crop->current_stage === 'planting' ? 'gray' : ($crop->current_stage === 'germination' ? 'info' : ($crop->current_stage === 'blackout' ? 'warning' : 'success')) }}">
                                                {{ $crop->current_stage }}
                                            </x-filament::badge>
                                        </td>
                                        <td class="px-4 py-2">{{ $crop->planted_at->format('M d, Y') }}</td>
                                        <td class="px-4 py-2">{{ $crop->expectedHarvestDate()?->format('M d, Y') }}</td>
                                        <td class="px-4 py-2">{{ $crop->daysInCurrentStage() }}</td>
                                        <td class="px-4 py-2">{{ $crop->order?->user?->name ?? 'N/A' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="p-4 text-center text-gray-500">
                        No active crops found.
                    </div>
                @endif
            </x-filament::section>
        </div>
    </x-filament::section>
</x-filament::page> 