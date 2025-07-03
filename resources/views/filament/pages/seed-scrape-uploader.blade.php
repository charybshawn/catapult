<x-filament-panels::page>
    <div class="space-y-6" x-data="{ refreshTrigger: 0 }" @refresh-uploads.window="refreshTrigger++">
        <!-- Status Overview Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
                <div class="flex items-center space-x-3">
                    <x-filament::icon 
                        icon="heroicon-o-arrow-up-tray" 
                        class="h-8 w-8 text-green-600 dark:text-green-400"
                    />
                    <div>
                        <h3 class="font-semibold text-green-900 dark:text-green-100">Upload Seed Data</h3>
                        <p class="text-sm text-green-700 dark:text-green-300">
                            JSON files from supplier websites
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                <div class="flex items-center space-x-3">
                    <x-filament::icon 
                        icon="heroicon-o-cog-8-tooth" 
                        class="h-8 w-8 text-blue-600 dark:text-blue-400"
                    />
                    <div>
                        <h3 class="font-semibold text-blue-900 dark:text-blue-100">Auto Processing</h3>
                        <p class="text-sm text-blue-700 dark:text-blue-300">
                            Intelligent supplier matching and import
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded-lg p-4">
                <div class="flex items-center space-x-3">
                    <x-filament::icon 
                        icon="heroicon-o-chart-bar" 
                        class="h-8 w-8 text-purple-600 dark:text-purple-400"
                    />
                    <div>
                        <h3 class="font-semibold text-purple-900 dark:text-purple-100">View Results</h3>
                        <p class="text-sm text-purple-700 dark:text-purple-300">
                            Browse imported seed inventory
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Upload Form Section -->
        <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        <x-filament::icon icon="heroicon-o-arrow-up-tray" class="h-5 w-5 inline mr-2"/>
                        Upload Seed Data Files
                    </h3>
                </div>
                
                <div class="mb-4">
                    <p class="text-gray-600 dark:text-gray-400 text-sm">
                        Upload JSON files containing seed data scraped from supplier websites. 
                        The system will intelligently match suppliers and process the data automatically.
                    </p>
                </div>
                
                <!-- Upload form -->
                {{ $this->form }}
            </div>
        </div>

        <!-- Workflow Process Guide -->
        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                <x-filament::icon icon="heroicon-o-arrow-path" class="h-5 w-5 inline mr-2"/>
                Import Workflow
                <span class="text-xs bg-emerald-100 dark:bg-emerald-800 text-emerald-800 dark:text-emerald-200 px-2 py-1 rounded-full ml-2">
                    ✅ Automated
                </span>
            </h3>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-sm">
                <div class="bg-white dark:bg-gray-900 rounded p-3 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center space-x-2 mb-2">
                        <span class="flex items-center justify-center w-5 h-5 bg-green-600 text-white rounded-full text-xs font-bold">1</span>
                        <span class="font-medium text-gray-900 dark:text-white">Upload JSON Files</span>
                    </div>
                    <span class="text-gray-600 dark:text-gray-400 text-xs">Upload scraped seed data from supplier websites</span>
                </div>
                
                <div class="bg-white dark:bg-gray-900 rounded p-3 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center space-x-2 mb-2">
                        <span class="flex items-center justify-center w-5 h-5 bg-blue-600 text-white rounded-full text-xs font-bold">2</span>
                        <span class="font-medium text-gray-900 dark:text-white">Intelligent Processing</span>
                    </div>
                    <span class="text-gray-600 dark:text-gray-400 text-xs">Auto-match suppliers and validate data structure</span>
                </div>
                
                <div class="bg-white dark:bg-gray-900 rounded p-3 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center space-x-2 mb-2">
                        <span class="flex items-center justify-center w-5 h-5 bg-purple-600 text-white rounded-full text-xs font-bold">3</span>
                        <span class="font-medium text-gray-900 dark:text-white">Import to Inventory</span>
                    </div>
                    <span class="text-gray-600 dark:text-gray-400 text-xs">Create seed entries and variations in the system</span>
                </div>
            </div>
            
            <!-- Expandable JSON format section -->
            <div class="mt-4 bg-white dark:bg-gray-900 rounded p-3 border border-gray-200 dark:border-gray-700" x-data="{ showFormat: false }">
                <button 
                    @click="showFormat = !showFormat"
                    class="flex items-center justify-between w-full text-left"
                >
                    <div class="flex items-start space-x-2">
                        <x-filament::icon icon="heroicon-o-document-text" class="h-4 w-4 text-blue-600 dark:text-blue-400 mt-0.5 flex-shrink-0"/>
                        <div class="text-xs text-gray-600 dark:text-gray-400">
                            <span class="font-medium">Required JSON Format:</span> Click to view the expected structure for uploaded files
                        </div>
                    </div>
                    <x-filament::icon 
                        icon="heroicon-o-chevron-down" 
                        class="h-4 w-4 text-gray-400 transition-transform"
                        x-bind:class="showFormat ? 'rotate-180' : ''"
                    />
                </button>
                
                <div x-show="showFormat" x-transition class="mt-3 overflow-auto max-h-80">
                    <pre class="text-xs bg-gray-100 dark:bg-gray-800 p-3 rounded overflow-x-auto text-gray-700 dark:text-gray-300"><code>{
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
        }
      ]
    }
  ]
}</code></pre>
                </div>
            </div>
        </div>

        <!-- Processing Tips -->
        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800 p-6">
            <h3 class="text-lg font-semibold text-blue-900 dark:text-blue-100 mb-4">
                <x-filament::icon icon="heroicon-o-light-bulb" class="h-5 w-5 inline mr-2"/>
                Import Tips & Best Practices
            </h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div class="bg-white dark:bg-gray-900 rounded p-3 border border-blue-200 dark:border-blue-700">
                    <h4 class="font-medium text-blue-900 dark:text-blue-100 mb-2">File Requirements</h4>
                    <ul class="space-y-1 text-blue-700 dark:text-blue-300">
                        <li class="flex items-start space-x-1">
                            <span class="text-blue-500 mt-1">•</span>
                            <span>JSON format with valid structure</span>
                        </li>
                        <li class="flex items-start space-x-1">
                            <span class="text-blue-500 mt-1">•</span>
                            <span>Maximum file size: 10MB</span>
                        </li>
                        <li class="flex items-start space-x-1">
                            <span class="text-blue-500 mt-1">•</span>
                            <span>Must include 'source_site' field</span>
                        </li>
                    </ul>
                </div>
                
                <div class="bg-white dark:bg-gray-900 rounded p-3 border border-blue-200 dark:border-blue-700">
                    <h4 class="font-medium text-blue-900 dark:text-blue-100 mb-2">Processing Behavior</h4>
                    <ul class="space-y-1 text-blue-700 dark:text-blue-300">
                        <li class="flex items-start space-x-1">
                            <span class="text-blue-500 mt-1">•</span>
                            <span>Background processing for large files</span>
                        </li>
                        <li class="flex items-start space-x-1">
                            <span class="text-blue-500 mt-1">•</span>
                            <span>Real-time status updates in table below</span>
                        </li>
                        <li class="flex items-start space-x-1">
                            <span class="text-blue-500 mt-1">•</span>
                            <span>Automatic supplier matching and mapping</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <!-- Upload History & Status -->
        <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        <x-filament::icon icon="heroicon-o-clock" class="h-5 w-5 inline mr-2"/>
                        Upload History & Status
                    </h3>
                    <div class="flex items-center space-x-4">
                        <div class="flex items-center space-x-2">
                            <span id="refresh-indicator" class="h-2 w-2 bg-primary-500 rounded-full opacity-0 transition-opacity duration-300"></span>
                            <span class="text-sm text-gray-500 dark:text-gray-400">
                                Auto-refreshes every {{ $refreshInterval }} seconds
                            </span>
                        </div>
                    </div>
                </div>
                
                <div :key="refreshTrigger">
                    {{ $this->table }}
                </div>
                
                <!-- Quick Access Actions -->
                <div class="mt-6 flex flex-wrap justify-end gap-3">
                    <a href="{{ route('filament.admin.resources.seed-entries.index') }}" 
                       class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        <x-filament::icon icon="heroicon-o-list-bullet" class="h-4 w-4 mr-2"/>
                        View Seed Entries
                    </a>
                    <a href="{{ route('filament.admin.resources.seed-variations.index') }}" 
                       class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg text-sm font-medium transition-colors">
                        <x-filament::icon icon="heroicon-o-squares-plus" class="h-4 w-4 mr-2"/>
                        View Seed Variations
                    </a>
                    <a href="{{ route('filament.admin.pages.manage-failed-seed-entries') }}" 
                       class="inline-flex items-center px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-sm font-medium transition-colors">
                        <x-filament::icon icon="heroicon-o-exclamation-triangle" class="h-4 w-4 mr-2"/>
                        Manage Failed Entries
                    </a>
                </div>
            </div>
        </div>

        <!-- Upload Processing Modal -->
        <div x-data="{ show: @entangle('showUploadModal') }" 
             x-show="show" 
             x-transition.opacity
             class="fixed inset-0 z-50 overflow-y-auto"
             style="display: none;">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="show" 
                     x-transition.opacity
                     class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75"
                     @click="$wire.closeUploadModal()"></div>

                <div x-show="show" 
                     x-transition:enter="ease-out duration-300"
                     x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave="ease-in duration-200"
                     x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     class="inline-block w-full max-w-4xl p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white dark:bg-gray-800 shadow-xl rounded-lg">
                    
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                            <x-filament::icon icon="heroicon-o-arrow-up-tray" class="h-5 w-5 inline mr-2"/>
                            Upload Processing
                        </h3>
                        <button @click="$wire.closeUploadModal()" 
                                class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                            <x-filament::icon icon="heroicon-o-x-mark" class="h-6 w-6"/>
                        </button>
                    </div>

                    <div class="mb-4">
                        <div class="flex items-center space-x-2">
                            <div wire:loading.remove wire:target="processFileWithSupplierModal,processFilesWithSupplierModal" class="flex items-center space-x-2">
                                @if($uploadRunning)
                                    <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600"></div>
                                    <span class="text-sm text-blue-600 dark:text-blue-400">Processing upload...</span>
                                @elseif($uploadSuccess)
                                    <x-filament::icon icon="heroicon-o-check-circle" class="h-4 w-4 text-green-600"/>
                                    <span class="text-sm text-green-600 dark:text-green-400">Upload completed successfully</span>
                                @else
                                    <x-filament::icon icon="heroicon-o-x-circle" class="h-4 w-4 text-red-600"/>
                                    <span class="text-sm text-red-600 dark:text-red-400">Upload failed or completed with errors</span>
                                @endif
                            </div>
                            <div wire:loading wire:target="processFileWithSupplierModal,processFilesWithSupplierModal" class="flex items-center space-x-2">
                                <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600"></div>
                                <span class="text-sm text-blue-600 dark:text-blue-400">Starting upload processing...</span>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-900 rounded-lg p-4 font-mono text-sm overflow-y-auto max-h-96">
                        <pre class="text-green-400 whitespace-pre-wrap" x-text="$wire.uploadOutput || 'Waiting for output...'"></pre>
                    </div>

                    <div class="mt-6 flex justify-end space-x-3">
                        <button @click="$wire.closeUploadModal()" 
                                class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded text-sm font-medium">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
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
                    
                    // Trigger refresh for any listeners
                    window.dispatchEvent(new CustomEvent('refresh-uploads'));
                });
            });
        });
    </script>
</x-filament-panels::page> 