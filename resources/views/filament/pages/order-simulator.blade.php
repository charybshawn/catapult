<x-filament-panels::page>
    <div 
        class="relative"
        x-data="{ 
            showHiddenPanel: @entangle('showHiddenPanel')
        }"
    >
        <!-- Main Content Area -->
        <div class="space-y-6">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="p-6">
                    <div class="mb-4">
                        <h2 class="text-lg font-medium mb-2">Select Products & Quantities</h2>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Enter quantities for the products you want to include in your order simulation. Only products with quantities greater than 0 will be calculated.
                        </p>
                    </div>
                    
                    {{ $this->table }}
                </div>
            </div>
            
            @php
                $results = $this->getResults();
            @endphp

        @if (!empty($results))
            {{-- Warning Banner for Missing Fill Weights --}}
            @if (!empty($results['missing_fill_weights']))
                <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl p-4 mb-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <x-heroicon-s-exclamation-triangle class="h-5 w-5 text-amber-600 dark:text-amber-400" />
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-amber-800 dark:text-amber-200">
                                Missing Fill Weight Data
                            </h3>
                            <div class="mt-2 text-sm text-amber-700 dark:text-amber-300">
                                <p>The following products were excluded from calculations due to missing fill weight data:</p>
                                <ul class="mt-2 list-disc list-inside space-y-1">
                                    @foreach($results['missing_fill_weights'] as $missing)
                                        <li>{{ $missing['product_name'] }} - {{ $missing['variation_name'] }} (Qty: {{ $missing['quantity'] }})</li>
                                    @endforeach
                                </ul>
                                <p class="mt-2 font-medium">Please update the fill weight (grams) for these product variations in the product settings.</p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
            
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700" 
                 x-data="{ activeTab: 'totals' }"
                 wire:poll.5s="$refresh">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-lg font-medium">Calculation Results</h2>
                        
                        <!-- Header Actions -->
                        <div class="flex gap-3">
                            @foreach($this->getHeaderActions() as $action)
                                {{ $action }}
                            @endforeach
                        </div>
                    </div>
                    
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
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-center">
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

        <!-- Hidden Items Slideout Overlay -->
        @if (count($this->hiddenRows) > 0)
            <div 
                x-show="showHiddenPanel"
                x-cloak
                class="fixed inset-0 z-50"
                x-transition:enter="transition-opacity ease-linear duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition-opacity ease-linear duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                x-trap.inert.noscroll="showHiddenPanel"
            >
                <!-- Backdrop -->
                <div 
                    class="fixed inset-0 bg-black/50 backdrop-blur-sm" 
                    @click="$wire.set('showHiddenPanel', false)"
                    aria-hidden="true"
                ></div>
                
                <!-- Slideout Panel -->
                <div 
                    class="fixed inset-y-0 right-0 w-full max-w-md sm:max-w-lg bg-white dark:bg-gray-900 shadow-2xl border-l border-gray-200 dark:border-gray-700"
                    x-transition:enter="transform transition ease-in-out duration-300"
                    x-transition:enter-start="translate-x-full"
                    x-transition:enter-end="translate-x-0"
                    x-transition:leave="transform transition ease-in-out duration-300"
                    x-transition:leave-start="translate-x-0"
                    x-transition:leave-end="translate-x-full"
                    id="hidden-items-panel"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="hidden-items-title"
                >
                    <!-- Panel Header -->
                    <div class="flex items-center justify-between p-4 sm:p-6 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                        <div class="flex-1">
                            <h3 id="hidden-items-title" class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                Hidden Items
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                {{ count($this->hiddenRows) }} items hidden from the main table
                            </p>
                        </div>
                        <button 
                            @click="$wire.set('showHiddenPanel', false)"
                            class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900 rounded-lg transition-colors"
                            aria-label="Close panel"
                        >
                            <x-heroicon-s-x-mark class="w-6 h-6" />
                        </button>
                    </div>
                    
                    <!-- Panel Content -->
                    <div class="flex-1 overflow-y-auto overscroll-contain">
                        <div class="p-4 sm:p-6 space-y-4">
                            @foreach ($this->hiddenRows as $variationId => $hiddenItem)
                                <div class="group bg-gray-50 dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg p-4 border border-gray-200 dark:border-gray-600 transition-all duration-200"
                                     wire:loading.class="opacity-50 pointer-events-none"
                                     wire:target="showHiddenItem('{{ $variationId }}')">
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="flex-1 min-w-0">
                                            <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 line-clamp-3 group-hover:text-gray-700 dark:group-hover:text-gray-200 transition-colors">
                                                {{ $hiddenItem['product_name'] }}
                                            </h4>
                                            <div class="flex items-center gap-2 mt-2">
                                                <span class="inline-flex items-center px-2 py-1 text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/20 dark:text-amber-300 rounded-full">
                                                    <x-heroicon-s-clock class="w-3 h-3 mr-1" />
                                                    Hidden {{ \Carbon\Carbon::parse($hiddenItem['hidden_at'])->diffForHumans() }}
                                                </span>
                                            </div>
                                        </div>
                                        <button 
                                            wire:click="showHiddenItem('{{ $variationId }}')"
                                            class="shrink-0 inline-flex items-center px-3 py-2 text-sm font-medium text-blue-700 bg-blue-50 hover:bg-blue-100 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900 border border-blue-200 rounded-lg transition-all duration-200 dark:text-blue-400 dark:bg-blue-900/20 dark:border-blue-800 dark:hover:bg-blue-900/30 dark:focus:ring-blue-400"
                                            aria-label="Restore {{ $hiddenItem['product_name'] }}"
                                        >
                                            <x-heroicon-s-eye class="w-4 h-4 mr-2" />
                                            <span wire:loading.remove wire:target="showHiddenItem('{{ $variationId }}')">Restore</span>
                                            <span wire:loading wire:target="showHiddenItem('{{ $variationId }}')">
                                                <x-heroicon-s-arrow-path class="w-4 h-4 animate-spin" />
                                            </span>
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    
                    <!-- Panel Footer -->
                    <div class="p-4 sm:p-6 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                        <button 
                            wire:click="showHiddenRows"
                            class="w-full inline-flex items-center justify-center px-4 py-3 text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900 rounded-lg transition-all duration-200 shadow-sm disabled:opacity-50 disabled:cursor-not-allowed"
                            wire:loading.attr="disabled"
                            wire:target="showHiddenRows"
                        >
                            <span wire:loading.remove wire:target="showHiddenRows" class="flex items-center">
                                <x-heroicon-s-eye class="w-5 h-5 mr-2" />
                                Restore All Items ({{ count($this->hiddenRows) }})
                            </span>
                            <span wire:loading wire:target="showHiddenRows" class="flex items-center">
                                <x-heroicon-s-arrow-path class="w-5 h-5 mr-2 animate-spin" />
                                Restoring...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <script>
        // Define the print function globally so it's available immediately
        window.openPrintWindow = function(data) {
            console.log('Opening print window with data:', data);
            
            if (!data || !data.results) {
                console.error('No data provided to print function');
                alert('No data available to print');
                return;
            }
            
            const printWindow = window.open('', '_blank', 'width=800,height=600,scrollbars=yes');
            
            if (!printWindow) {
                console.error('Could not open print window - popup blocked?');
                alert('Print window was blocked. Please allow popups for this site.');
                return;
            }
            
            // Build the HTML content using DOM methods to avoid template literal issues
            const html = document.createElement('html');
            const head = document.createElement('head');
            const title = document.createElement('title');
            title.textContent = 'Order Simulator Results';
            
            const style = document.createElement('style');
            style.textContent = `
                @page { margin: 0.5in; size: letter; }
                @media print { body { -webkit-print-color-adjust: exact; } .no-print { display: none !important; } }
                body { font-family: Arial, sans-serif; font-size: 12px; line-height: 1.4; color: #333; margin: 20px; padding: 0; }
                .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #e5e7eb; padding-bottom: 20px; }
                .header h1 { color: #1f2937; font-size: 24px; margin: 0 0 10px 0; font-weight: bold; }
                .header .subtitle { color: #6b7280; font-size: 14px; margin: 5px 0; }
                .summary-stats { display: flex; justify-content: space-between; margin-bottom: 30px; padding: 15px; background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 4px; }
                .stat-item { text-align: center; flex: 1; }
                .stat-item .label { font-size: 11px; color: #6b7280; text-transform: uppercase; font-weight: bold; margin-bottom: 5px; }
                .stat-item .value { font-size: 18px; font-weight: bold; color: #1f2937; }
                .section { margin-bottom: 30px; break-inside: avoid; }
                .section-title { font-size: 18px; font-weight: bold; color: #1f2937; margin-bottom: 15px; padding-bottom: 8px; border-bottom: 1px solid #e5e7eb; }
                table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                th, td { padding: 8px 12px; text-align: left; border-bottom: 1px solid #e5e7eb; }
                th { background-color: #f9fafb; font-weight: bold; color: #374151; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; }
                .number { text-align: right; font-family: "Courier New", monospace; }
                .total-row { border-top: 2px solid #374151; font-weight: bold; background-color: #f3f4f6; }
                .print-controls { margin: 20px 0; text-align: center; }
                .print-btn { background: #3b82f6; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-size: 14px; margin: 0 10px; }
                .print-btn:hover { background: #2563eb; }
                .item-card { margin-bottom: 20px; border: 1px solid #e5e7eb; border-radius: 4px; padding: 15px; break-inside: avoid; }
                .item-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px; }
                .item-title { font-weight: bold; font-size: 14px; color: #1f2937; }
                .item-details { font-size: 12px; color: #6b7280; margin-top: 2px; }
                .item-total { font-weight: bold; font-size: 14px; color: #1f2937; text-align: right; }
                .mix-components { margin-top: 12px; padding-top: 10px; border-top: 1px solid #f3f4f6; }
                .mix-label { font-size: 11px; color: #6b7280; text-transform: uppercase; font-weight: bold; margin-bottom: 8px; }
                .mix-table { font-size: 12px; }
                .mix-row { display: flex; justify-content: space-between; padding: 3px 0; }
                .variety-name { color: #374151; }
                .variety-grams { color: #1f2937; font-weight: 500; font-family: "Courier New", monospace; }
            `;
            
            head.appendChild(title);
            head.appendChild(style);
            
            const body = document.createElement('body');
            body.innerHTML = `
                <div class="print-controls no-print">
                    <button class="print-btn" onclick="window.print()">🖨️ Print</button>
                    <button class="print-btn" onclick="window.close()">✖️ Close</button>
                </div>
                <div class="header">
                    <h1>Order Simulator Results</h1>
                    <div class="subtitle">Generated on ` + new Date(data.generated_at).toLocaleString() + `</div>
                </div>
                <div class="summary-stats">
                    <div class="stat-item">
                        <div class="label">Total Items</div>
                        <div class="value">` + data.total_items + `</div>
                    </div>
                    <div class="stat-item">
                        <div class="label">Total Varieties</div>
                        <div class="value">` + data.total_varieties + `</div>
                    </div>
                    <div class="stat-item">
                        <div class="label">Total Weight</div>
                        <div class="value">` + parseFloat(data.total_grams).toFixed(2) + `g</div>
                    </div>
                </div>
                <div class="section">
                    <h2 class="section-title">Variety Requirements Summary</h2>
                    ` + (data.results.variety_totals && data.results.variety_totals.length > 0 ? 
                        '<table><thead><tr><th>Variety</th><th class="number">Total Grams Required</th></tr></thead><tbody>' +
                        data.results.variety_totals.map(variety => 
                            '<tr><td>' + variety.variety_name + '</td><td class="number">' + parseFloat(variety.total_grams).toFixed(2) + 'g</td></tr>'
                        ).join('') +
                        '</tbody><tfoot><tr class="total-row"><td><strong>Total</strong></td><td class="number"><strong>' + parseFloat(data.results.summary.total_grams).toFixed(2) + 'g</strong></td></tr></tfoot></table>'
                        : '<p>No variety totals available.</p>'
                    ) + `
                </div>
                <div class="section">
                    <h2 class="section-title">Detailed Item Breakdown</h2>
                    ` + (data.results.item_breakdown && data.results.item_breakdown.length > 0 ?
                        data.results.item_breakdown.map(item => {
                            let html = '<div class="item-card">';
                            html += '<div class="item-header">';
                            html += '<div class="item-title"><strong>' + item.product_name + '</strong></div>';
                            html += '<div class="item-details">' + item.package_size + ' - Qty: ' + item.quantity + '</div>';
                            html += '<div class="item-total">' + parseFloat(item.total_grams).toFixed(2) + 'g total</div>';
                            html += '</div>';
                            
                            if (item.type === 'mix' && item.varieties && item.varieties.length > 0) {
                                html += '<div class="mix-components">';
                                html += '<div class="mix-label">Mix Components:</div>';
                                html += '<div class="mix-table">';
                                item.varieties.forEach(component => {
                                    html += '<div class="mix-row">';
                                    html += '<span class="variety-name">' + component.name + ' (' + component.percentage + '%)</span>';
                                    html += '<span class="variety-grams">' + parseFloat(component.grams).toFixed(2) + 'g</span>';
                                    html += '</div>';
                                });
                                html += '</div>';
                                html += '</div>';
                            }
                            
                            html += '</div>';
                            return html;
                        }).join('')
                        : '<p>No item breakdown available.</p>'
                    ) + `
                </div>
            `;
            
            html.appendChild(head);
            html.appendChild(body);
            
            printWindow.document.write('<!DOCTYPE html>' + html.outerHTML);
            printWindow.document.close();
            
            // Auto-focus the print window
            printWindow.focus();
            
            // Optional: Auto-print after a brief delay
            setTimeout(function() {
                printWindow.print();
            }, 500);
        };

        // Handle keyboard navigation for accessibility
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                // Close panel on Escape key if it's open
                const panelElement = document.querySelector('#hidden-items-panel');
                if (panelElement && panelElement.offsetParent !== null) {
                    @this.set('showHiddenPanel', false);
                }
            }
        });
    </script>

    <style>
            /* Line clamping for product names */
            .line-clamp-3 {
                display: -webkit-box;
                -webkit-line-clamp: 3;
                -webkit-box-orient: vertical;
                overflow: hidden;
                line-height: 1.4;
                max-height: 4.2em;
            }

            /* Panel backdrop blur for better performance */
            .backdrop-blur-sm {
                backdrop-filter: blur(4px);
                -webkit-backdrop-filter: blur(4px);
            }

            /* Enhanced animations for slideout */
            .transition-transform {
                transition-property: transform;
                transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
            }

            /* Custom scrollbar for panel content */
            #hidden-items-panel .overflow-y-auto {
                scrollbar-width: thin;
                scrollbar-color: rgb(156 163 175) transparent;
            }

            #hidden-items-panel .overflow-y-auto::-webkit-scrollbar {
                width: 8px;
            }

            #hidden-items-panel .overflow-y-auto::-webkit-scrollbar-track {
                background: transparent;
            }

            #hidden-items-panel .overflow-y-auto::-webkit-scrollbar-thumb {
                background-color: rgb(156 163 175);
                border-radius: 4px;
            }

            .dark #hidden-items-panel .overflow-y-auto {
                scrollbar-color: rgb(75 85 99) transparent;
            }

            .dark #hidden-items-panel .overflow-y-auto::-webkit-scrollbar-thumb {
                background-color: rgb(75 85 99);
            }

            /* Focus management and accessibility */
            .focus\:ring-2:focus {
                outline: none;
                box-shadow: 0 0 0 2px var(--tw-ring-color);
            }

            /* Improved focus visibility */
            .focus-visible\:ring-2:focus-visible {
                outline: none;
                box-shadow: 0 0 0 2px var(--tw-ring-color);
            }

            /* Smooth hover transitions */
            .group:hover .group-hover\:text-gray-700 {
                transition-property: color;
                transition-duration: 200ms;
                transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
            }
        </style>
</x-filament-panels::page>