@php
    $statePath = $getStatePath();
    $columns = $getColumns();
    $options = $getOptions();
    $optionLabel = $getOptionLabel();
    $optionValue = $getOptionValue();
    $showTotals = $getShowTotals();
    $totalColumn = $getTotalColumn();
    $expectedTotal = $getExpectedTotal();
    $addButtonLabel = $getAddButtonLabel();
    $minItems = $getMinItems();
    $maxItems = $getMaxItems();
    $items = $getState();
    
    // Calculate current total if needed
    $currentTotal = 0;
    if ($showTotals && $totalColumn) {
        foreach ($items as $item) {
            if (isset($item[$totalColumn]) && is_numeric($item[$totalColumn])) {
                $currentTotal += floatval($item[$totalColumn]);
            }
        }
    }
@endphp

<div 
    x-data="{
        items: @entangle($statePath),
        columns: @js($columns),
        options: @js($options),
        optionLabel: @js($optionLabel),
        optionValue: @js($optionValue),
        showTotals: @js($showTotals),
        totalColumn: @js($totalColumn),
        expectedTotal: @js($expectedTotal),
        minItems: @js($minItems),
        maxItems: @js($maxItems),
        
        init() {
            // Ensure minimum items
            while (this.items.length < this.minItems) {
                this.addItem();
            }
        },
        
        addItem() {
            if (this.items.length >= this.maxItems) return;
            
            let newItem = {};
            
            // Initialize default values for each column
            Object.keys(this.columns).forEach(key => {
                const column = this.columns[key];
                if (column.default !== undefined) {
                    newItem[key] = column.default;
                } else if (column.type === 'number') {
                    newItem[key] = 0;
                } else {
                    newItem[key] = '';
                }
            });
            
            this.items.push(newItem);
        },
        
        removeItem(index) {
            if (this.items.length <= this.minItems) return;
            this.items.splice(index, 1);
        },
        
        getTotal() {
            if (!this.showTotals || !this.totalColumn) return 0;
            
            return this.items.reduce((sum, item) => {
                const value = parseFloat(item[this.totalColumn] || 0);
                return sum + (isNaN(value) ? 0 : value);
            }, 0);
        },
        
        getTotalStatus() {
            if (!this.expectedTotal) return 'none';
            
            const total = this.getTotal();
            const diff = Math.abs(total - this.expectedTotal);
            
            if (diff < 0.01) return 'valid';
            return total < this.expectedTotal ? 'under' : 'over';
        },
        
        getOptionDisplay(value) {
            if (!value) return 'Select...';
            
            const option = this.options.find(opt => {
                if (typeof opt === 'object' && opt.value !== undefined) {
                    return opt.value == value;
                }
                return opt == value;
            });
            
            if (!option) return value;
            
            if (typeof option === 'object' && option.label !== undefined) {
                return option.label;
            }
            
            return option;
        }
    }"
    class="w-full"
    wire:key="compact-relation-manager-{{ $getStatePath() }}"
>
    {{-- Total Display --}}
    @if ($showTotals && $totalColumn && $expectedTotal)
        <div 
            x-show="showTotals && totalColumn && expectedTotal"
            x-transition
            class="mb-4"
        >
            <template x-if="getTotalStatus() === 'valid'">
                <div class="text-center p-3 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
                    <p class="text-lg font-semibold text-green-600 dark:text-green-400">
                        ✓ Total: <span x-text="getTotal().toFixed(2)"></span>{{ $columns[$totalColumn]['suffix'] ?? '' }}
                    </p>
                    <p class="text-sm text-green-600 dark:text-green-400">Perfect!</p>
                </div>
            </template>
            
            <template x-if="getTotalStatus() === 'under'">
                <div class="text-center p-3 bg-amber-50 dark:bg-amber-900/20 rounded-lg border border-amber-200 dark:border-amber-800">
                    <p class="text-lg font-semibold text-amber-600 dark:text-amber-400">
                        ⚠️ Total: <span x-text="getTotal().toFixed(2)"></span>{{ $columns[$totalColumn]['suffix'] ?? '' }}
                    </p>
                    <p class="text-sm text-amber-600 dark:text-amber-400">
                        Add <span x-text="(expectedTotal - getTotal()).toFixed(2)"></span>{{ $columns[$totalColumn]['suffix'] ?? '' }} more
                    </p>
                </div>
            </template>
            
            <template x-if="getTotalStatus() === 'over'">
                <div class="text-center p-3 bg-amber-50 dark:bg-amber-900/20 rounded-lg border border-amber-200 dark:border-amber-800">
                    <p class="text-lg font-semibold text-amber-600 dark:text-amber-400">
                        ⚠️ Total: <span x-text="getTotal().toFixed(2)"></span>{{ $columns[$totalColumn]['suffix'] ?? '' }}
                    </p>
                    <p class="text-sm text-amber-600 dark:text-amber-400">
                        Remove <span x-text="(getTotal() - expectedTotal).toFixed(2)"></span>{{ $columns[$totalColumn]['suffix'] ?? '' }}
                    </p>
                </div>
            </template>
        </div>
    @endif
    
    {{-- Table --}}
    <div class="overflow-hidden rounded-lg border border-gray-300 dark:border-gray-700">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    @foreach ($columns as $key => $column)
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            {{ $column['label'] ?? $key }}
                        </th>
                    @endforeach
                    <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Actions
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                <template x-for="(item, index) in items" :key="index">
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                        @foreach ($columns as $key => $column)
                            <td class="px-3 py-2 whitespace-nowrap">
                                @if ($column['type'] === 'select')
                                    <select
                                        x-model="item.{{ $key }}"
                                        class="block w-full text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-800 rounded-md shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                        :class="{ 'opacity-50': item._readonly }"
                                        :disabled="item._readonly"
                                    >
                                        <option value="">{{ $column['placeholder'] ?? 'Select...' }}</option>
                                        @if ($key === array_key_first($columns) && !empty($options))
                                            @foreach ($options as $optionKey => $optionData)
                                                @if (is_array($optionData))
                                                    <option value="{{ $optionData['value'] ?? $optionKey }}">
                                                        {{ $optionData['label'] ?? $optionData['value'] ?? $optionKey }}
                                                    </option>
                                                @else
                                                    <option value="{{ $optionKey }}">{{ $optionData }}</option>
                                                @endif
                                            @endforeach
                                        @else
                                            <template x-for="(option, optKey) in (columns['{{ $key }}'].options || [])" :key="optKey">
                                                <option :value="optKey" x-text="option"></option>
                                            </template>
                                        @endif
                                    </select>
                                @elseif ($column['type'] === 'number')
                                    <input
                                        type="number"
                                        x-model.number="item.{{ $key }}"
                                        class="block w-full text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-800 rounded-md shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                        :class="{ 'opacity-50': item._readonly }"
                                        :disabled="item._readonly"
                                        min="{{ $column['min'] ?? 0 }}"
                                        max="{{ $column['max'] ?? '' }}"
                                        step="{{ $column['step'] ?? 'any' }}"
                                        placeholder="{{ $column['placeholder'] ?? '' }}"
                                    >
                                    @if (isset($column['suffix']))
                                        <span class="ml-1 text-sm text-gray-500">{{ $column['suffix'] }}</span>
                                    @endif
                                @else
                                    <input
                                        type="text"
                                        x-model="item.{{ $key }}"
                                        class="block w-full text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-800 rounded-md shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                        :class="{ 'opacity-50': item._readonly }"
                                        :disabled="item._readonly"
                                        placeholder="{{ $column['placeholder'] ?? '' }}"
                                    >
                                @endif
                            </td>
                        @endforeach
                        <td class="px-3 py-2 whitespace-nowrap text-right text-sm">
                            <button
                                type="button"
                                @click="removeItem(index)"
                                x-show="items.length > minItems && !item._readonly"
                                class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>
                        </td>
                    </tr>
                </template>
                
                {{-- Empty state --}}
                <tr x-show="items.length === 0" x-cloak>
                    <td :colspan="Object.keys(columns).length + 1" class="px-3 py-8 text-center">
                        <p class="text-sm text-gray-500 dark:text-gray-400">No items added yet</p>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    
    {{-- Add button --}}
    <div class="mt-3">
        <button
            type="button"
            @click="addItem()"
            x-show="items.length < maxItems"
            class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 dark:bg-primary-500 dark:hover:bg-primary-600"
        >
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            {{ $addButtonLabel }}
        </button>
    </div>
</div>