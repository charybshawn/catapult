<x-filament-panels::page>
    <div class="space-y-6" x-data="{ refreshTrigger: 0 }" @refresh-backups.window="refreshTrigger++">
        <!-- Command Status Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                <div class="flex items-center space-x-3">
                    <x-filament::icon 
                        icon="heroicon-o-arrow-down-tray" 
                        class="h-8 w-8 text-blue-600 dark:text-blue-400"
                    />
                    <div>
                        <h3 class="font-semibold text-blue-900 dark:text-blue-100">Create Backup</h3>
                        <p class="text-sm text-blue-700 dark:text-blue-300">
                            Standard backup or safe backup with git integration
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                <div class="flex items-center space-x-3">
                    <x-filament::icon 
                        icon="heroicon-o-arrow-up-tray" 
                        class="h-8 w-8 text-red-600 dark:text-red-400"
                    />
                    <div>
                        <h3 class="font-semibold text-red-900 dark:text-red-100">Restore Database</h3>
                        <p class="text-sm text-red-700 dark:text-red-300">
                            Restore from any available backup file
                        </p>
                    </div>
                </div>
            </div>
        </div>


        <!-- Available Backups -->
        <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700" x-data="{ selectedBackups: [], selectAll: false }">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Available Backups
                    </h3>
                    <div class="flex items-center space-x-4">
                        <div class="flex items-center space-x-2">
                            <x-filament::icon 
                                icon="heroicon-o-clock" 
                                class="h-4 w-4 text-gray-400"
                            />
                            <span class="text-sm text-gray-500 dark:text-gray-400">
                                Auto-refreshes after operations
                            </span>
                        </div>
                        <div x-show="selectedBackups.length > 0" 
                             x-transition
                             class="flex items-center space-x-2">
                            <span class="text-sm text-gray-600 dark:text-gray-400" x-text="`${selectedBackups.length} selected`"></span>
                            <button 
                                @click="if(confirm('Are you sure you want to delete ' + selectedBackups.length + ' backup(s)? This action cannot be undone.')) { $wire.massDeleteBackups(selectedBackups); selectedBackups = []; selectAll = false; }"
                                class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-sm font-medium"
                            >
                                Delete Selected
                            </button>
                            <button 
                                @click="selectedBackups = []; selectAll = false;"
                                class="bg-gray-600 hover:bg-gray-700 text-white px-3 py-1 rounded text-sm font-medium"
                            >
                                Clear Selection
                            </button>
                        </div>
                    </div>
                </div>
                
                <div :key="refreshTrigger">
                    @php 
                        $backups = $this->getBackups(); 
                    @endphp
                    
                    @if(count($backups) > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-800">
                                    <tr>
                                        <th class="w-16 px-3 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            <input 
                                                type="checkbox" 
                                                x-model="selectAll"
                                                @change="selectAll ? selectedBackups = [@foreach($backups as $backup)'{{ $backup['name'] }}'@if(!$loop->last),@endif @endforeach] : selectedBackups = []"
                                                class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                                                title="Select All"
                                            />
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Backup File
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Size
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Created At
                                        </th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach($backups as $backup)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                                            <td class="w-16 px-3 py-4 whitespace-nowrap">
                                                <input 
                                                    type="checkbox" 
                                                    value="{{ $backup['name'] }}"
                                                    x-model="selectedBackups"
                                                    class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                                                />
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <x-filament::icon 
                                                        icon="heroicon-o-document-text" 
                                                        class="h-4 w-4 text-gray-400 mr-2"
                                                    />
                                                    <span class="text-sm font-medium text-gray-900 dark:text-white">
                                                        {{ $backup['name'] }}
                                                    </span>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                {{ $backup['size'] }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                <div class="flex items-center">
                                                    <x-filament::icon 
                                                        icon="heroicon-o-clock" 
                                                        class="h-3 w-3 mr-1"
                                                    />
                                                    {{ $backup['created_at']->format('M j, Y g:i A') }}
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-3">
                                                <button 
                                                    wire:click="downloadBackup('{{ $backup['name'] }}')"
                                                    class="text-primary-600 hover:text-primary-900 dark:text-primary-400 dark:hover:text-primary-300 inline-flex items-center"
                                                >
                                                    <x-filament::icon icon="heroicon-o-arrow-down-tray" class="h-3 w-3 mr-1"/>
                                                    Download
                                                </button>
                                                
                                                <button 
                                                    wire:click="deleteBackup('{{ $backup['name'] }}')"
                                                    wire:confirm="Are you sure you want to delete this backup? This action cannot be undone."
                                                    class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 inline-flex items-center"
                                                >
                                                    <x-filament::icon icon="heroicon-o-trash" class="h-3 w-3 mr-1"/>
                                                    Delete
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-12">
                            <x-filament::icon 
                                icon="heroicon-o-circle-stack" 
                                class="mx-auto h-12 w-12 text-gray-400"
                            />
                            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No Backups Found</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                Create your first database backup using the "Create Backup" button above.
                            </p>
                            <div class="mt-4">
                                <code class="text-xs bg-gray-100 dark:bg-gray-800 px-2 py-1 rounded text-gray-600 dark:text-gray-400">
                                    php artisan db:backup
                                </code>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Schema Change Workflow -->
        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                <x-filament::icon icon="heroicon-o-arrow-path" class="h-5 w-5 inline mr-2"/>
                Schema Change Workflow
                <span class="text-xs bg-emerald-100 dark:bg-emerald-800 text-emerald-800 dark:text-emerald-200 px-2 py-1 rounded-full ml-2">
                    ✅ Tested
                </span>
            </h3>
            
            <div class="mb-4">
                <p class="text-gray-600 dark:text-gray-400 text-sm">
                    When working on features that require <code class="bg-gray-200 dark:bg-gray-700 px-1 rounded text-xs">migration:fresh</code> 
                    but you want to preserve existing data, follow this tested workflow:
                </p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3 text-sm">
                <div class="bg-white dark:bg-gray-900 rounded p-3 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center space-x-2 mb-2">
                        <span class="flex items-center justify-center w-5 h-5 bg-blue-600 text-white rounded-full text-xs font-bold">1</span>
                        <span class="font-medium text-gray-900 dark:text-white">Backup Current Data</span>
                    </div>
                    <div class="mb-2">
                        <code class="text-blue-600 dark:text-blue-400">php artisan safe:backup --data-only</code>
                    </div>
                    <span class="text-gray-600 dark:text-gray-400 text-xs">Create DATA-ONLY backup (no schema)</span>
                </div>
                
                <div class="bg-white dark:bg-gray-900 rounded p-3 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center space-x-2 mb-2">
                        <span class="flex items-center justify-center w-5 h-5 bg-blue-600 text-white rounded-full text-xs font-bold">2</span>
                        <span class="font-medium text-gray-900 dark:text-white">Develop Feature</span>
                    </div>
                    <div class="mb-2">
                        <code class="text-blue-600 dark:text-blue-400">git checkout -b new-feature</code>
                    </div>
                    <span class="text-gray-600 dark:text-gray-400 text-xs">Create feature branch and make schema changes</span>
                </div>
                
                <div class="bg-white dark:bg-gray-900 rounded p-3 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center space-x-2 mb-2">
                        <span class="flex items-center justify-center w-5 h-5 bg-blue-600 text-white rounded-full text-xs font-bold">3</span>
                        <span class="font-medium text-gray-900 dark:text-white">Fresh Migration</span>
                    </div>
                    <div class="mb-2">
                        <code class="text-blue-600 dark:text-blue-400">php artisan migrate:fresh</code>
                    </div>
                    <span class="text-gray-600 dark:text-gray-400 text-xs">Apply new schema (drops all tables and rebuilds)</span>
                </div>
                
                <div class="bg-white dark:bg-gray-900 rounded p-3 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center space-x-2 mb-2">
                        <span class="flex items-center justify-center w-5 h-5 bg-blue-600 text-white rounded-full text-xs font-bold">4</span>
                        <span class="font-medium text-gray-900 dark:text-white">Restore Data</span>
                    </div>
                    <div class="mb-2">
                        <code class="text-blue-600 dark:text-blue-400">php artisan db:restore data_filename.sql --force</code>
                    </div>
                    <span class="text-gray-600 dark:text-gray-400 text-xs">Restore ONLY data to new schema</span>
                </div>
            </div>
            
            <div class="mt-4 bg-white dark:bg-gray-900 rounded p-3 border border-gray-200 dark:border-gray-700">
                <div class="flex items-start space-x-2">
                    <x-filament::icon icon="heroicon-o-light-bulb" class="h-4 w-4 text-blue-600 dark:text-blue-400 mt-0.5 flex-shrink-0"/>
                    <div class="text-xs text-gray-600 dark:text-gray-400">
                        <span class="font-medium">Why this works:</span> Data-only backup contains no schema - only INSERT statements. 
                        New schema remains intact during restoration. Compatible data inserts successfully, incompatible data is skipped gracefully.
                    </div>
                </div>
            </div>
        </div>

        <!-- CLI Command Reference -->
        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                <x-filament::icon icon="heroicon-o-command-line" class="h-5 w-5 inline mr-2"/>
                CLI Command Reference
            </h3>
            
            <div class="space-y-3 text-sm">
                <div class="bg-white dark:bg-gray-900 rounded p-3 border border-gray-200 dark:border-gray-700">
                    <code class="text-blue-600 dark:text-blue-400">php artisan db:backup</code>
                    <span class="text-gray-600 dark:text-gray-400 ml-2">- Create a database backup</span>
                </div>
                
                <div class="bg-white dark:bg-gray-900 rounded p-3 border border-gray-200 dark:border-gray-700">
                    <code class="text-blue-600 dark:text-blue-400">php artisan db:backup --list</code>
                    <span class="text-gray-600 dark:text-gray-400 ml-2">- List all available backups</span>
                </div>
                
                <div class="bg-white dark:bg-gray-900 rounded p-3 border border-gray-200 dark:border-gray-700">
                    <code class="text-blue-600 dark:text-blue-400">php artisan db:restore --latest</code>
                    <span class="text-gray-600 dark:text-gray-400 ml-2">- Restore from the most recent backup</span>
                </div>
                
                <div class="bg-white dark:bg-gray-900 rounded p-3 border border-gray-200 dark:border-gray-700">
                    <code class="text-blue-600 dark:text-blue-400">php artisan db:restore filename.sql</code>
                    <span class="text-gray-600 dark:text-gray-400 ml-2">- Restore from specific backup file</span>
                </div>
                
                <div class="bg-white dark:bg-gray-900 rounded p-3 border border-gray-200 dark:border-gray-700">
                    <code class="text-blue-600 dark:text-blue-400">php artisan safe:backup</code>
                    <span class="text-gray-600 dark:text-gray-400 ml-2">- Create backup + git commit + push</span>
                </div>
                
                <div class="bg-white dark:bg-gray-900 rounded p-3 border border-gray-200 dark:border-gray-700">
                    <code class="text-blue-600 dark:text-blue-400">php artisan safe:backup --data-only</code>
                    <span class="text-gray-600 dark:text-gray-400 ml-2">- Data-only backup for schema changes</span>
                </div>
            </div>
        </div>

        <!-- Safe Backup Modal -->
        <div x-data="{ show: @entangle('showSafeBackupModal') }" 
             x-show="show" 
             x-transition.opacity
             class="fixed inset-0 z-50 overflow-y-auto"
             style="display: none;">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="show" 
                     x-transition.opacity
                     class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75"
                     @click="$wire.closeSafeBackupModal()"></div>

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
                            <x-filament::icon icon="heroicon-o-arrow-down-tray" class="h-5 w-5 inline mr-2"/>
                            Backup Process
                        </h3>
                        <button @click="$wire.closeSafeBackupModal()" 
                                class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                            <x-filament::icon icon="heroicon-o-x-mark" class="h-6 w-6"/>
                        </button>
                    </div>

                    <div class="mb-4">
                        <div class="flex items-center space-x-2">
                            <div wire:loading.remove wire:target="safeBackup" class="flex items-center space-x-2">
                                @if($safeBackupRunning)
                                    <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600"></div>
                                    <span class="text-sm text-blue-600 dark:text-blue-400">Process running...</span>
                                @elseif($safeBackupSuccess)
                                    <x-filament::icon icon="heroicon-o-check-circle" class="h-4 w-4 text-green-600"/>
                                    <span class="text-sm text-green-600 dark:text-green-400">Process completed successfully</span>
                                @else
                                    <x-filament::icon icon="heroicon-o-x-circle" class="h-4 w-4 text-red-600"/>
                                    <span class="text-sm text-red-600 dark:text-red-400">Process failed</span>
                                @endif
                            </div>
                            <div wire:loading wire:target="safeBackup" class="flex items-center space-x-2">
                                <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600"></div>
                                <span class="text-sm text-blue-600 dark:text-blue-400">Starting process...</span>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-900 rounded-lg p-4 font-mono text-sm overflow-y-auto max-h-96">
                        <pre class="text-green-400 whitespace-pre-wrap" x-text="$wire.safeBackupOutput || 'Waiting for output...'"></pre>
                    </div>

                    <div class="mt-6 flex justify-end space-x-3">
                        <button @click="$wire.closeSafeBackupModal()" 
                                class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded text-sm font-medium">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Restore Modal -->
        <div x-data="{ show: @entangle('showRestoreModal') }" 
             x-show="show" 
             x-transition.opacity
             class="fixed inset-0 z-50 overflow-y-auto"
             style="display: none;">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="show" 
                     x-transition.opacity
                     class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75"
                     @click="$wire.closeRestoreModal()"></div>

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
                            Database Restore Process
                        </h3>
                        <button @click="$wire.closeRestoreModal()" 
                                class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                            <x-filament::icon icon="heroicon-o-x-mark" class="h-6 w-6"/>
                        </button>
                    </div>

                    <div class="mb-4">
                        <div class="flex items-center space-x-2">
                            <div wire:loading.remove wire:target="restoreBackup" class="flex items-center space-x-2">
                                @if($restoreRunning)
                                    <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600"></div>
                                    <span class="text-sm text-blue-600 dark:text-blue-400">Restore in progress...</span>
                                @elseif($restoreSuccess)
                                    <x-filament::icon icon="heroicon-o-check-circle" class="h-4 w-4 text-green-600"/>
                                    <span class="text-sm text-green-600 dark:text-green-400">Restore completed successfully</span>
                                @else
                                    <x-filament::icon icon="heroicon-o-x-circle" class="h-4 w-4 text-red-600"/>
                                    <span class="text-sm text-red-600 dark:text-red-400">Restore failed</span>
                                @endif
                            </div>
                            <div wire:loading wire:target="restoreBackup" class="flex items-center space-x-2">
                                <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600"></div>
                                <span class="text-sm text-blue-600 dark:text-blue-400">Starting restore...</span>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-900 rounded-lg p-4 font-mono text-sm overflow-y-auto max-h-96">
                        <pre class="text-green-400 whitespace-pre-wrap" x-text="$wire.restoreOutput || 'Waiting for output...'"></pre>
                    </div>

                    <div class="mt-6 flex justify-end space-x-3">
                        <button @click="$wire.closeRestoreModal()" 
                                class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded text-sm font-medium">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>