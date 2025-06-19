@php
    $templates = \App\Models\PriceVariation::where('is_global', true)
        ->where('is_active', true)
        ->with('packagingType')
        ->orderBy('name')
        ->get();
@endphp

<div x-data="{
    selectedTemplates: [],
    selectAll: false,
    templateNames: @js($templates->mapWithKeys(function($t) { return [$t->id => $t->name]; })),
    
    toggleTemplate(templateId) {
        templateId = parseInt(templateId);
        if (this.selectedTemplates.includes(templateId)) {
            this.removeTemplate(templateId);
        } else {
            this.selectedTemplates.push(templateId);
        }
        this.updateSelectAllState();
        this.updateHiddenInput();
    },
    
    removeTemplate(templateId) {
        this.selectedTemplates = this.selectedTemplates.filter(id => id !== templateId);
        this.updateSelectAllState();
        this.updateHiddenInput();
    },
    
    toggleSelectAll() {
        const allTemplateIds = @js($templates->pluck('id')->toArray());
        
        if (this.selectedTemplates.length === allTemplateIds.length) {
            this.selectedTemplates = [];
            this.selectAll = false;
        } else {
            this.selectedTemplates = [...allTemplateIds];
            this.selectAll = true;
        }
        
        this.updateHiddenInput();
        console.log('Toggle select all:', this.selectAll, 'Selected:', this.selectedTemplates);
    },
    
    updateHiddenInput() {
        // Update the hidden input field directly
        const hiddenInput = document.querySelector('input[wire\\:model\*=\"selected_template_ids\"]');
        if (hiddenInput) {
            hiddenInput.value = JSON.stringify(this.selectedTemplates);
            // Trigger change event to notify Livewire
            hiddenInput.dispatchEvent(new Event('input', { bubbles: true }));
            console.log('Updated hidden input with:', JSON.stringify(this.selectedTemplates));
        } else {
            console.error('Hidden input field not found');
        }
        
        // Also try to update Livewire directly as fallback
        try {
            // Try to find the correct path for modal action data
            if (typeof $wire !== 'undefined') {
                $wire.set('mountedActionsData.0.selected_template_ids', JSON.stringify(this.selectedTemplates));
                console.log('Updated Livewire mountedActionsData with:', JSON.stringify(this.selectedTemplates));
            }
        } catch (e) {
            console.log('Livewire update failed (this is expected):', e);
        }
    },
    
    updateSelectAllState() {
        const totalTemplates = {{ $templates->count() }};
        this.selectAll = this.selectedTemplates.length === totalTemplates;
        console.log('Update select all state:', this.selectAll, 'Count:', this.selectedTemplates.length, 'Total:', totalTemplates);
    },
    
    getTemplateName(templateId) {
        return this.templateNames[templateId] || 'Unknown';
    }
}" class="space-y-4">
    @if($templates->isNotEmpty())
        <!-- Select All Controls -->
        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="flex items-center space-x-3">
                <input 
                    type="checkbox" 
                    :checked="selectAll"
                    @click="toggleSelectAll()"
                    class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                >
                <label class="text-sm font-medium text-gray-900 dark:text-gray-100 cursor-pointer" @click="toggleSelectAll()">
                    Select All Templates
                </label>
            </div>
            <div class="text-sm text-gray-500 dark:text-gray-400">
                <span x-text="selectedTemplates.length"></span> of {{ $templates->count() }} selected
            </div>
        </div>

        <!-- Templates Grid -->
        <div class="grid gap-3 max-h-96 overflow-y-auto">
            @foreach($templates as $template)
                @php
                    $packagingName = $template->packagingType?->display_name ?? 'No packaging';
                    $price = '$' . number_format($template->price, 2);
                    $weight = $template->fill_weight_grams ? $template->fill_weight_grams . 'g' : 'No weight';
                @endphp
                
                <div 
                    class="border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer transition-colors"
                    @click="toggleTemplate({{ $template->id }})"
                    :class="selectedTemplates.includes({{ $template->id }}) ? 'bg-primary-50 dark:bg-primary-900/20 border-primary-200 dark:border-primary-800' : ''"
                >
                    <div class="flex items-start p-4 space-x-3">
                        <input 
                            type="checkbox" 
                            value="{{ $template->id }}"
                            x-model="selectedTemplates"
                            @change="updateSelectAllState()"
                            @click.stop
                            class="mt-1 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                        >
                        <div class="flex-1 flex justify-between items-start">
                            <div class="flex-1">
                                <h4 class="font-medium text-gray-900 dark:text-gray-100">
                                    {{ $template->name }}
                                </h4>
                                <div class="mt-1 space-y-1">
                                    <div class="flex items-center space-x-4 text-sm text-gray-600 dark:text-gray-400">
                                        <span class="flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10"></path>
                                            </svg>
                                            {{ $packagingName }}
                                        </span>
                                        <span class="flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16l3-1m-3 1l-3-1"></path>
                                            </svg>
                                            {{ $weight }}
                                        </span>
                                    </div>
                                    @if($template->description)
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $template->description }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                            <div class="text-right ml-4">
                                <div class="text-lg font-bold text-gray-900 dark:text-gray-100">
                                    {{ $price }}
                                </div>
                                @if($template->sku)
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        SKU: {{ $template->sku }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Selected Templates Summary -->
        <div x-show="selectedTemplates.length > 0" class="mt-4 p-3 bg-primary-50 dark:bg-primary-900/20 rounded-lg border border-primary-200 dark:border-primary-800">
            <h4 class="text-sm font-medium text-primary-900 dark:text-primary-100 mb-2">
                Selected Templates (<span x-text="selectedTemplates.length"></span>)
            </h4>
            <div class="flex flex-wrap gap-2">
                <template x-for="templateId in selectedTemplates" :key="templateId">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-100 text-primary-800 dark:bg-primary-900 dark:text-primary-200">
                        <span x-text="getTemplateName(templateId)"></span>
                        <button 
                            type="button" 
                            @click="removeTemplate(templateId)"
                            class="ml-1.5 inline-flex items-center justify-center w-4 h-4 rounded-full text-primary-600 hover:bg-primary-200 hover:text-primary-800 focus:outline-none"
                        >
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                            </svg>
                        </button>
                    </span>
                </template>
            </div>
        </div>
    @else
        <div class="text-center py-8 text-gray-500 dark:text-gray-400">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
            </svg>
            <p class="mt-2 font-medium">No global templates available</p>
            <p class="text-sm mt-1">Create global templates in the Price Variations section to use them here.</p>
        </div>
    @endif

    <!-- Data will be handled by the proper Hidden form component -->
</div>

