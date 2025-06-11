<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Date Selection Form -->
        <div class="bg-white rounded-lg shadow p-6">
            {{ $this->form }}
        </div>

        @if($this->orders && $this->orders->isNotEmpty())
            <!-- Orders Summary -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">
                        Orders for {{ \Carbon\Carbon::parse($this->data['delivery_date'])->format('F j, Y') }}
                    </h3>
                    <p class="text-sm text-gray-600">
                        {{ $this->orders->count() }} {{ Str::plural('order', $this->orders->count()) }} found
                    </p>
                </div>
                
                <div class="p-6">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Orders List -->
                        <div>
                            <h4 class="font-medium text-gray-900 mb-4">Order Details</h4>
                            <div class="space-y-3">
                                @foreach($this->orders as $order)
                                    <div class="border border-gray-200 rounded-lg p-4">
                                        <div class="flex justify-between items-start mb-2">
                                            <span class="font-medium">Order #{{ $order->id }}</span>
                                            <span class="text-sm text-gray-600">{{ $order->user->name }}</span>
                                        </div>
                                        <div class="text-sm text-gray-600">
                                            {{ $order->orderItems->count() }} {{ Str::plural('item', $order->orderItems->count()) }}
                                        </div>
                                        <div class="mt-2 space-y-1">
                                            @foreach($order->orderItems as $item)
                                                <div class="text-xs text-gray-500">
                                                    {{ $item->quantity }}x {{ $item->product->name }}
                                                    @if($item->priceVariation)
                                                        ({{ $item->priceVariation->packagingType?->name ?? 'Unknown packaging' }})
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- Planting Plan -->
                        @if($this->plantingPlan)
                            <div>
                                <h4 class="font-medium text-gray-900 mb-4">Planting Requirements</h4>
                                <div class="space-y-3">
                                    @foreach($this->plantingPlan as $seedEntryId => $requirement)
                                        <div class="border border-green-200 bg-green-50 rounded-lg p-4">
                                            <div class="font-medium text-green-900">
                                                {{ $requirement['seed_entry']->common_name }}
                                            </div>
                                            @if($requirement['seed_entry']->scientific_name)
                                                <div class="text-sm text-green-700 italic">
                                                    {{ $requirement['seed_entry']->scientific_name }}
                                                </div>
                                            @endif
                                            <div class="mt-2 grid grid-cols-2 gap-4 text-sm">
                                                <div>
                                                    <span class="font-medium">Trays Needed:</span>
                                                    <span class="text-lg font-bold text-green-900">
                                                        {{ $requirement['total_trays_needed'] }}
                                                    </span>
                                                </div>
                                                <div>
                                                    <span class="font-medium">Total Grams:</span>
                                                    <span class="text-green-900">
                                                        {{ number_format($requirement['total_grams_needed'], 1) }}g
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="mt-2 text-xs text-green-600">
                                                Orders: {{ implode(', ', array_map(fn($o) => "#{$o['order_id']} ({$o['customer']})", $requirement['orders'])) }}
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            @if($this->plantingPlan)
                <!-- Summary Card -->
                <div class="bg-green-50 border border-green-200 rounded-lg p-6">
                    <h3 class="text-lg font-medium text-green-900 mb-4">Planting Summary</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="text-center">
                            <div class="text-2xl font-bold text-green-900">
                                {{ count($this->plantingPlan) }}
                            </div>
                            <div class="text-sm text-green-700">
                                {{ Str::plural('Variety', count($this->plantingPlan)) }} to Plant
                            </div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-green-900">
                                {{ array_sum(array_column($this->plantingPlan, 'total_trays_needed')) }}
                            </div>
                            <div class="text-sm text-green-700">Total Trays</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-green-900">
                                {{ number_format(array_sum(array_column($this->plantingPlan, 'total_grams_needed')), 1) }}g
                            </div>
                            <div class="text-sm text-green-700">Total Seed Weight</div>
                        </div>
                    </div>
                </div>
            @endif

        @elseif($this->data['delivery_date'])
            <!-- No Orders Found -->
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 text-center">
                <div class="text-yellow-800">
                    <svg class="mx-auto h-12 w-12 text-yellow-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                    </svg>
                    <h3 class="text-lg font-medium text-yellow-800 mb-2">No Orders Found</h3>
                    <p class="text-yellow-700">
                        There are no active orders scheduled for delivery on 
                        {{ \Carbon\Carbon::parse($this->data['delivery_date'])->format('F j, Y') }}.
                    </p>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>