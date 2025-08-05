<x-filament-panels::page>
    <div class="space-y-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="p-6">
                <h2 class="text-lg font-medium mb-4">Select Products & Quantities</h2>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">
                    Enter quantities for the products you want to include in your order simulation. Only products with quantities greater than 0 will be calculated.
                </p>
                
                {{ $this->table }}
            </div>
        </div>

        @php
            $results = $this->getResults();
        @endphp

        @if (!empty($results))
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700" 
                 x-data="{ activeTab: 'totals' }"
                 wire:poll.5s="$refresh">
                <div class="p-6">
                    <h2 class="text-lg font-medium mb-4">Calculation Results</h2>
                    
                    <!-- Tabs -->
                    <div class="border-b border-gray-200 dark:border-gray-700 mb-6">
                        <nav class="-mb-px flex space-x-8">
                            <button @click="activeTab = 'totals'"
                                    :class="activeTab === 'totals' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                    class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition">
                                Variety Totals
                            </button>
                            <button @click="activeTab = 'breakdown'"
                                    :class="activeTab === 'breakdown' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                    class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition">
                                Item Breakdown
                            </button>
                        </nav>
                    </div>

                    <!-- Variety Totals Tab -->
                    <div x-show="activeTab === 'totals'" x-cloak>
                        @if (!empty($results['variety_totals']))
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead>
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Variety
                                            </th>
                                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Total Grams Needed
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                        @foreach ($results['variety_totals'] as $variety)
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                                    {{ $variety['variety_name'] }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-gray-100">
                                                    {{ number_format($variety['total_grams'], 2) }}g
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot class="bg-gray-50 dark:bg-gray-900">
                                        <tr>
                                            <td class="px-6 py-3 text-sm font-medium text-gray-900 dark:text-gray-100">
                                                Total
                                            </td>
                                            <td class="px-6 py-3 text-sm font-bold text-right text-gray-900 dark:text-gray-100">
                                                {{ number_format($results['summary']['total_grams'], 2) }}g
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        @else
                            <p class="text-gray-500">No varieties to display.</p>
                        @endif
                    </div>

                    <!-- Item Breakdown Tab -->
                    <div x-show="activeTab === 'breakdown'" x-cloak>
                        @if (!empty($results['item_breakdown']))
                            <div class="space-y-4">
                                @foreach ($results['item_breakdown'] as $item)
                                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                        <div class="flex justify-between items-start mb-2">
                                            <div>
                                                <h4 class="font-medium text-gray-900 dark:text-gray-100">
                                                    {{ $item['product_name'] }}
                                                </h4>
                                                <p class="text-sm text-gray-500">
                                                    {{ $item['package_size'] }} - Qty: {{ $item['quantity'] }}
                                                </p>
                                            </div>
                                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                {{ number_format($item['total_grams'], 2) }}g total
                                            </span>
                                        </div>
                                        
                                        @if ($item['type'] === 'mix')
                                            <div class="mt-3 space-y-1">
                                                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Mix Components:</p>
                                                @foreach ($item['varieties'] as $component)
                                                    <div class="flex justify-between text-sm">
                                                        <span class="text-gray-600 dark:text-gray-400">
                                                            {{ $component['name'] }} ({{ $component['percentage'] }}%)
                                                        </span>
                                                        <span class="text-gray-900 dark:text-gray-100">
                                                            {{ number_format($component['grams'], 2) }}g
                                                        </span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-gray-500">No items to display.</p>
                        @endif
                    </div>

                    <!-- Summary Stats -->
                    <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                        <div class="grid grid-cols-3 gap-4 text-center">
                            <div>
                                <p class="text-sm text-gray-500">Total Items</p>
                                <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                    {{ $results['summary']['total_items'] }}
                                </p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Total Varieties</p>
                                <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                    {{ $results['summary']['total_varieties'] }}
                                </p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Total Weight</p>
                                <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                    {{ number_format($results['summary']['total_grams'], 2) }}g
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    @push('scripts')
        <script>
            document.addEventListener('livewire:initialized', () => {
                Livewire.on('refresh-results', () => {
                    // Results will be refreshed automatically via wire:poll
                });
            });
        </script>
    @endpush
</x-filament-panels::page>