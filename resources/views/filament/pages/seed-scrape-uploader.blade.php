<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            <div class="space-y-4">
                <div class="text-xl font-semibold">
                    Upload Seed Data
                </div>
                
                <p class="text-gray-500 dark:text-gray-400">
                    Upload JSON files containing seed data scraped from supplier websites. The data will be processed
                    and integrated into the seed inventory system automatically.
                </p>
                
                <!-- Workflow diagram showing the process -->
                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 my-4">
                    <h3 class="text-md font-medium mb-3">Workflow:</h3>
                    <div class="flex flex-wrap gap-2 items-center justify-center md:justify-start">
                        <div class="flex flex-col items-center p-3 bg-white dark:bg-gray-700 rounded-lg shadow-sm min-w-[120px]">
                            <span class="text-primary-500 font-bold text-xl mb-1">1</span>
                            <span class="text-sm text-center">Upload JSON</span>
                        </div>
                        <div class="hidden md:block text-gray-400">→</div>
                        <div class="flex flex-col items-center p-3 bg-white dark:bg-gray-700 rounded-lg shadow-sm min-w-[120px]">
                            <span class="text-primary-500 font-bold text-xl mb-1">2</span>
                            <span class="text-sm text-center">Background Processing</span>
                        </div>
                        <div class="hidden md:block text-gray-400">→</div>
                        <div class="flex flex-col items-center p-3 bg-white dark:bg-gray-700 rounded-lg shadow-sm min-w-[120px]">
                            <span class="text-primary-500 font-bold text-xl mb-1">3</span>
                            <span class="text-sm text-center">View Imported Data</span>
                        </div>
                    </div>
                </div>
                
                <!-- Upload form -->
                {{ $this->form }}
                
                <!-- Sample JSON Format (Collapsible) -->
                <div x-data="{ open: false }">
                    <button 
                        @click="open = !open" 
                        class="text-sm font-medium text-primary-600 hover:text-primary-500 flex items-center gap-1 mt-2"
                    >
                        <span x-show="!open">▶ Show sample JSON format</span>
                        <span x-show="open">▼ Hide sample JSON format</span>
                    </button>
                    
                    <div x-show="open" class="mt-2 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg overflow-auto max-h-80">
                        <h3 class="text-sm font-medium mb-2">Required JSON Format:</h3>
                        <pre class="text-xs bg-white dark:bg-gray-700 p-3 rounded overflow-x-auto"><code>{
  "timestamp": "2025-05-26T16:30:00Z",
  "source_site": "Example Seed Supplier",
  "data": [
    {
      "title": "Premium Basil Seeds",
      "url": "https://example.com/products/basil-seeds",
      "cultivar": "Genovese Basil",
      "description": "Organic basil seeds for microgreens and herbs.",
      "image_url": "https://example.com/images/basil.jpg",
      "tags": ["organic", "herb", "microgreen"],
      "variants": [
        {
          "variant_title": "1 oz Package",
          "sku": "BSL-001",
          "price": 4.99,
          "currency": "USD",
          "original_weight_value": "1",
          "original_weight_unit": "oz",
          "weight_kg": 0.0283,
          "is_variant_in_stock": true
        },
        {
          "variant_title": "4 oz Package",
          "sku": "BSL-004",
          "price": 14.99,
          "currency": "USD",
          "original_weight_value": "4",
          "original_weight_unit": "oz",
          "weight_kg": 0.1133,
          "is_variant_in_stock": true
        }
      ]
    }
  ]
}</code></pre>
                    </div>
                </div>
                
                <!-- Helpful tips -->
                <div class="mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                    <h3 class="text-sm font-semibold text-blue-800 dark:text-blue-300">Tips:</h3>
                    <ul class="mt-2 list-disc list-inside text-sm text-blue-700 dark:text-blue-400">
                        <li>The processing happens in the background and may take a few minutes.</li>
                        <li>Watch the "Recent Uploads" table below for status updates.</li>
                        <li>Once processing is complete, you can view your data in the seed inventory pages.</li>
                        <li>JSON files should follow the required structure shown in the sample format above.</li>
                    </ul>
                </div>
            </div>
        </x-filament::section>
        
        <x-filament::section>
            <div class="space-y-4">
                <div class="text-lg font-medium flex justify-between items-center">
                    <span>Recent Uploads</span>
                    <span class="text-xs text-gray-500 flex items-center gap-1">
                        <span id="refresh-indicator" class="h-2 w-2 bg-primary-500 rounded-full opacity-0 transition-opacity duration-300"></span>
                        Auto-refreshes every {{ $refreshInterval }} seconds
                    </span>
                </div>
                
                {{ $this->table }}
                
                <div class="flex justify-end mt-4 gap-4">
                    <a href="{{ route('filament.admin.resources.seed-cultivars.index') }}" class="filament-button filament-button-size-md inline-flex items-center justify-center py-1 gap-1 font-medium rounded-lg border transition-colors outline-none focus:ring-offset-2 focus:ring-2 focus:ring-inset min-h-[2.25rem] px-4 text-sm text-gray-800 bg-white border-gray-300 hover:bg-gray-50 focus:ring-primary-500 focus:ring-offset-white dark:bg-gray-800 dark:hover:bg-gray-700 dark:border-gray-600 dark:text-gray-200 dark:focus:ring-primary-500 dark:focus:ring-offset-gray-800">
                        <span>View Cultivars</span>
                    </a>
                    <a href="{{ route('filament.admin.resources.seed-variations.index') }}" class="filament-button filament-button-size-md inline-flex items-center justify-center py-1 gap-1 font-medium rounded-lg border transition-colors outline-none focus:ring-offset-2 focus:ring-2 focus:ring-inset min-h-[2.25rem] px-4 text-sm text-white bg-primary-600 border-primary-600 hover:bg-primary-500 hover:border-primary-500 focus:ring-primary-500 focus:ring-offset-white dark:bg-primary-500 dark:hover:bg-primary-400 dark:border-primary-500 dark:hover:border-primary-400 dark:focus:ring-primary-400 dark:focus:ring-offset-gray-800">
                        <span>View Seed Variations</span>
                    </a>
                </div>
            </div>
        </x-filament::section>
    </div>
    
    <script>
        document.addEventListener('livewire:initialized', () => {
            // Add a visual indicator for the refresh
            Livewire.hook('request', ({ component, commit, respond, succeed, fail }) => {
                // When a request starts
                succeed(({ snapshot, effect }) => {
                    // Flash the indicator when data is refreshed
                    const indicator = document.getElementById('refresh-indicator');
                    if (indicator) {
                        indicator.classList.add('opacity-100');
                        setTimeout(() => {
                            indicator.classList.remove('opacity-100');
                        }, 500);
                    }
                });
            });
        });
    </script>
</x-filament-panels::page> 