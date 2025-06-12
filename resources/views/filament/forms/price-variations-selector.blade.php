@php
    $templates = \App\Models\PriceVariation::where('is_global', true)
        ->where('is_active', true)
        ->with('packagingType')
        ->get();
    
    // Get the current state (selected templates and custom variations)
    $currentState = $getState() ?? [];
    $selectedTemplates = $currentState['selected_templates'] ?? [];
    $customVariations = $currentState['custom_variations'] ?? [];
    
    // Create lookup for selected template IDs
    $selectedTemplateIds = collect($selectedTemplates)->pluck('id')->toArray();
    $selectedTemplateData = collect($selectedTemplates)->keyBy('id');
@endphp

<div x-data="priceVariationSelector(@js($selectedTemplates), @js($customVariations))" class="space-y-6">
    <!-- Global Templates Section -->
    <div class="space-y-4">
        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Available Templates</h3>
        <p class="text-sm text-gray-600 dark:text-gray-400">Select global templates to apply to this product. You can customize both the price and fill weight/quantity for each template.</p>
        <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-3">
            <div class="flex">
                <svg class="w-5 h-5 text-amber-400 mt-0.5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                </svg>
                <div>
                    <h4 class="text-sm font-medium text-amber-800 dark:text-amber-200">Custom Pricing Available</h4>
                    <p class="text-sm text-amber-700 dark:text-amber-300 mt-1">
                        Use the "Custom Price" column to override template pricing for this specific product. Leave blank to use the template's default price.
                    </p>
                </div>
            </div>
        </div>
        
        @if($templates->count() > 0)
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th scope="col" class="w-12 px-6 py-3">
                                <input type="checkbox" @click="toggleAll" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Template Name
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Packaging
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Price
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Custom Price
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Weight/Quantity
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($templates as $template)
                            @php
                                $isSelected = in_array($template->id, $selectedTemplateIds);
                                $selectedData = $selectedTemplateData->get($template->id, []);
                                $currentPrice = $selectedData['price'] ?? $template->price;
                                $currentFillWeight = $selectedData['fill_weight_grams'] ?? $template->fill_weight_grams ?? 0;
                            @endphp
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700" x-data="{ fillWeight: {{ $currentFillWeight }}, customPrice: {{ $currentPrice }} }">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <input 
                                        type="checkbox" 
                                        value="{{ $template->id }}"
                                        {{ $isSelected ? 'checked' : '' }}
                                        @change="toggleTemplate({{ $template->id }}, '{{ addslashes($template->name) }}', {{ $template->price }}, {{ $template->packaging_type_id ?? 'null' }}, fillWeight, customPrice)"
                                        class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                    {{ $template->name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ $template->packagingType?->display_name ?? 'No packaging' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                    ${{ number_format($template->price, 2) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <input 
                                        type="number" 
                                        x-model="customPrice"
                                        step="0.01"
                                        min="0"
                                        placeholder="${{ number_format($template->price, 2) }}"
                                        @if($isSelected && $currentPrice != $template->price)
                                        value="{{ $currentPrice }}"
                                        @endif
                                        class="block w-20 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm focus:border-primary-500 focus:ring-primary-500">
                                    <span class="text-xs text-gray-500 mt-1 block">optional override</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($template->packagingType && in_array($template->packagingType->name, ['Bulk', 'Live Tray']))
                                        @if($template->packagingType->name === 'Bulk')
                                            <input 
                                                type="number" 
                                                x-model="fillWeight"
                                                step="0.01"
                                                min="0"
                                                placeholder="Weight (g)"
                                                @if($isSelected && $currentFillWeight > 0)
                                                value="{{ $currentFillWeight }}"
                                                @endif
                                                class="block w-24 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm focus:border-primary-500 focus:ring-primary-500">
                                            <span class="text-xs text-gray-500 mt-1 block">grams</span>
                                        @elseif($template->packagingType->name === 'Live Tray')
                                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                                1 tray
                                            </div>
                                            <input type="hidden" x-model="fillWeight" value="1">
                                        @endif
                                    @else
                                        <input 
                                            type="number" 
                                            x-model="fillWeight"
                                            step="0.01"
                                            min="0"
                                            placeholder="Fill weight (g)"
                                            @if($isSelected && $currentFillWeight > 0)
                                            value="{{ $currentFillWeight }}"
                                            @endif
                                            class="block w-24 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm focus:border-primary-500 focus:ring-primary-500">
                                        <span class="text-xs text-gray-500 mt-1 block">grams</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-8 text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-800 rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-600">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                </svg>
                <p class="mt-2 font-medium">No global templates available</p>
                <p class="text-sm mt-1">Create global templates in the Price Variations section to use them here.</p>
                <a href="{{ \App\Filament\Resources\PriceVariationResource::getUrl('create') }}" 
                   class="mt-3 inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-primary-700 bg-primary-100 hover:bg-primary-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 dark:text-primary-400 dark:bg-primary-900 dark:hover:bg-primary-800">
                    Create Global Template
                </a>
            </div>
        @endif
    </div>

    <!-- Selected Templates Preview -->
    <div x-show="selectedTemplates.length > 0" class="space-y-4">
        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Selected Variations</h3>
        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 space-y-2">
            <template x-for="template in selectedTemplates" :key="template.id">
                <div class="flex justify-between items-center p-3 bg-white dark:bg-gray-800 rounded border">
                    <div class="flex-1">
                        <span class="font-medium" x-text="template.name"></span>
                        <span class="text-gray-500 ml-2">
                            $<span x-text="template.price.toFixed(2)"></span>
                            <span x-show="template.original_price && template.price !== template.original_price" 
                                  class="text-xs text-amber-600 ml-1">
                                (was $<span x-text="template.original_price.toFixed(2)"></span>)
                            </span>
                        </span>
                        <span class="text-gray-500 ml-2" x-show="template.fill_weight_grams" x-text="template.fill_weight_grams + 'g'"></span>
                        <span x-show="template.original_price && template.price !== template.original_price" 
                              class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200 ml-2">
                            Custom Price
                        </span>
                    </div>
                    <button type="button" @click="removeTemplate(template.id)" class="text-red-600 hover:text-red-800">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                        </svg>
                    </button>
                </div>
            </template>
        </div>
    </div>

    <!-- Custom Variation Button -->
    <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
        <button 
            type="button" 
            @click="addCustomVariation()"
            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            Add Custom Variation
        </button>
    </div>

    <!-- Hidden inputs to store the data -->
    <input type="hidden" name="selected_templates" x-model="JSON.stringify(selectedTemplates)">
    <input type="hidden" name="custom_variations" x-model="JSON.stringify(customVariations)">
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('priceVariationSelector', (initialSelectedTemplates = [], initialCustomVariations = []) => ({
        selectedTemplates: initialSelectedTemplates,
        customVariations: initialCustomVariations,
        
        toggleAll() {
            const checkboxes = document.querySelectorAll('input[type="checkbox"][value]');
            const allChecked = Array.from(checkboxes).every(cb => cb.checked);
            
            checkboxes.forEach(cb => {
                cb.checked = !allChecked;
                if (!allChecked) {
                    // Find the template data from the row
                    const row = cb.closest('tr');
                    const fillWeightInput = row.querySelector('input[type="number"]');
                    this.toggleTemplate(
                        parseInt(cb.value),
                        row.cells[1].textContent.trim(),
                        parseFloat(row.cells[3].textContent.replace('$', '')),
                        null, // packaging will be handled separately
                        parseFloat(fillWeightInput.value) || 0
                    );
                } else {
                    this.removeTemplate(parseInt(cb.value));
                }
            });
        },
        
        toggleTemplate(id, name, defaultPrice, packagingTypeId, fillWeight, customPrice) {
            const existingIndex = this.selectedTemplates.findIndex(t => t.id === id);
            
            if (existingIndex >= 0) {
                this.selectedTemplates.splice(existingIndex, 1);
            } else {
                // Use custom price if provided, otherwise use default template price
                const finalPrice = customPrice && customPrice !== defaultPrice ? customPrice : defaultPrice;
                
                this.selectedTemplates.push({
                    id: id,
                    name: name,
                    price: finalPrice,
                    original_price: defaultPrice, // Keep track of original price
                    packaging_type_id: packagingTypeId,
                    fill_weight_grams: fillWeight || null,
                    is_global: false, // Will be product-specific
                    is_active: true,
                    is_default: this.selectedTemplates.length === 0
                });
            }
        },
        
        removeTemplate(id) {
            this.selectedTemplates = this.selectedTemplates.filter(t => t.id !== id);
            
            // Uncheck the corresponding checkbox
            const checkbox = document.querySelector(`input[type="checkbox"][value="${id}"]`);
            if (checkbox) {
                checkbox.checked = false;
            }
            
            // Ensure we have a default if there are still templates
            if (this.selectedTemplates.length > 0) {
                const hasDefault = this.selectedTemplates.some(t => t.is_default);
                if (!hasDefault) {
                    this.selectedTemplates[0].is_default = true;
                }
            }
        },
        
        addCustomVariation() {
            // For now, just show an alert - this could be expanded to show a modal
            alert('Custom variation creation will be implemented in the next phase. For now, please use the Price Variations relation manager after creating the product.');
        }
    }));
});
</script>