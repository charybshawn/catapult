<x-filament-panels::page>
    <div class="space-y-6" x-data="{ refreshTrigger: 0 }" @refresh-backups.window="refreshTrigger++">
        <!-- Command Status Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                <div class="flex items-center space-x-3">
                    <x-filament::icon 
                        icon="heroicon-o-arrow-down-tray" 
                        class="h-8 w-8 text-blue-600 dark:text-blue-400"
                    />
                    <div>
                        <h3 class="font-semibold text-blue-900 dark:text-blue-100">Create Backup</h3>
                        <p class="text-sm text-blue-700 dark:text-blue-300">
                            Run: <code class="bg-blue-100 dark:bg-blue-800 px-1 rounded">php artisan db:backup</code>
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-4">
                <div class="flex items-center space-x-3">
                    <x-filament::icon 
                        icon="heroicon-o-shield-check" 
                        class="h-8 w-8 text-amber-600 dark:text-amber-400"
                    />
                    <div>
                        <h3 class="font-semibold text-amber-900 dark:text-amber-100">Safe Backup</h3>
                        <p class="text-sm text-amber-700 dark:text-amber-300">
                            Run: <code class="bg-amber-100 dark:bg-amber-800 px-1 rounded">php artisan safe:backup</code>
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
                            Run: <code class="bg-red-100 dark:bg-red-800 px-1 rounded">php artisan db:restore</code>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Information Card -->
        <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center space-x-3 mb-4">
                <x-filament::icon 
                    icon="heroicon-o-information-circle" 
                    class="h-6 w-6 text-primary-600 dark:text-primary-400"
                />
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    Database Console Commands
                </h3>
            </div>
            
            <div class="prose dark:prose-invert max-w-none">
                <p class="text-gray-600 dark:text-gray-400 mb-4">
                    Execute database backup and restore operations using the command-line interface through this web interface.
                    All operations run the actual CLI commands with real-time output.
                </p>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 not-prose">
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-2">
                            <x-filament::icon icon="heroicon-o-arrow-down-tray" class="h-4 w-4 inline mr-1"/>
                            db:backup
                        </h4>
                        <p class="text-gray-600 dark:text-gray-400 text-sm">
                            Creates a database backup using mysqldump-php. Supports custom output paths and lists existing backups.
                        </p>
                    </div>
                    
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-2">
                            <x-filament::icon icon="heroicon-o-shield-check" class="h-4 w-4 inline mr-1"/>
                            safe:backup
                        </h4>
                        <p class="text-gray-600 dark:text-gray-400 text-sm">
                            Creates backup, commits all changes to git, and pushes to remote. Perfect for checkpoint saves.
                        </p>
                    </div>
                    
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-2">
                            <x-filament::icon icon="heroicon-o-arrow-up-tray" class="h-4 w-4 inline mr-1"/>
                            db:restore
                        </h4>
                        <p class="text-gray-600 dark:text-gray-400 text-sm">
                            Restores database from backup file. Can select specific backup or use latest. ⚠️ Irreversible operation.
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
        <div class="bg-gradient-to-r from-emerald-50 to-teal-50 dark:from-emerald-900/20 dark:to-teal-900/20 rounded-lg border border-emerald-200 dark:border-emerald-800 p-6">
            <div class="flex items-center space-x-3 mb-4">
                <x-filament::icon 
                    icon="heroicon-o-arrow-path" 
                    class="h-6 w-6 text-emerald-600 dark:text-emerald-400"
                />
                <h3 class="text-lg font-semibold text-emerald-900 dark:text-emerald-100">
                    Schema Change Workflow
                </h3>
                <span class="text-xs bg-emerald-100 dark:bg-emerald-800 text-emerald-800 dark:text-emerald-200 px-2 py-1 rounded-full">
                    ✅ Tested
                </span>
            </div>
            
            <div class="prose dark:prose-invert max-w-none">
                <p class="text-emerald-700 dark:text-emerald-300 mb-6">
                    When working on features that require <code class="bg-emerald-100 dark:bg-emerald-800 px-1 rounded">migration:fresh</code> 
                    but you want to preserve existing data, follow this tested workflow:
                </p>
                
                <div class="grid grid-cols-1 lg:grid-cols-4 gap-4 not-prose">
                    <div class="bg-white dark:bg-gray-900 rounded-lg border border-emerald-200 dark:border-emerald-700 p-4">
                        <div class="flex items-center space-x-2 mb-3">
                            <span class="flex items-center justify-center w-6 h-6 bg-emerald-500 text-white rounded-full text-xs font-bold">1</span>
                            <h4 class="font-semibold text-gray-900 dark:text-white">Backup Current Data</h4>
                        </div>
                        <div class="space-y-2 text-sm">
                            <div class="bg-gray-50 dark:bg-gray-800 rounded p-2">
                                <code class="text-emerald-600 dark:text-emerald-400 text-xs">php artisan db:backup</code>
                            </div>
                            <p class="text-gray-600 dark:text-gray-400">
                                Create backup of current data on your main branch before starting development.
                            </p>
                        </div>
                    </div>
                    
                    <div class="bg-white dark:bg-gray-900 rounded-lg border border-emerald-200 dark:border-emerald-700 p-4">
                        <div class="flex items-center space-x-2 mb-3">
                            <span class="flex items-center justify-center w-6 h-6 bg-emerald-500 text-white rounded-full text-xs font-bold">2</span>
                            <h4 class="font-semibold text-gray-900 dark:text-white">Develop Feature</h4>
                        </div>
                        <div class="space-y-2 text-sm">
                            <div class="bg-gray-50 dark:bg-gray-800 rounded p-2">
                                <code class="text-emerald-600 dark:text-emerald-400 text-xs">git checkout -b new-feature</code>
                            </div>
                            <p class="text-gray-600 dark:text-gray-400">
                                Create feature branch and make your schema changes/migrations.
                            </p>
                        </div>
                    </div>
                    
                    <div class="bg-white dark:bg-gray-900 rounded-lg border border-emerald-200 dark:border-emerald-700 p-4">
                        <div class="flex items-center space-x-2 mb-3">
                            <span class="flex items-center justify-center w-6 h-6 bg-emerald-500 text-white rounded-full text-xs font-bold">3</span>
                            <h4 class="font-semibold text-gray-900 dark:text-white">Fresh Migration</h4>
                        </div>
                        <div class="space-y-2 text-sm">
                            <div class="bg-gray-50 dark:bg-gray-800 rounded p-2">
                                <code class="text-emerald-600 dark:text-emerald-400 text-xs">php artisan migrate:fresh</code>
                            </div>
                            <p class="text-gray-600 dark:text-gray-400">
                                Apply new schema. This drops all tables and rebuilds them.
                            </p>
                        </div>
                    </div>
                    
                    <div class="bg-white dark:bg-gray-900 rounded-lg border border-emerald-200 dark:border-emerald-700 p-4">
                        <div class="flex items-center space-x-2 mb-3">
                            <span class="flex items-center justify-center w-6 h-6 bg-emerald-500 text-white rounded-full text-xs font-bold">4</span>
                            <h4 class="font-semibold text-gray-900 dark:text-white">Restore Data</h4>
                        </div>
                        <div class="space-y-2 text-sm">
                            <div class="bg-gray-50 dark:bg-gray-800 rounded p-2">
                                <code class="text-emerald-600 dark:text-emerald-400 text-xs">php artisan db:restore filename.sql --force</code>
                            </div>
                            <p class="text-gray-600 dark:text-gray-400">
                                Restore old data to new schema. System handles mismatches gracefully.
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="mt-6 bg-emerald-100 dark:bg-emerald-800/30 border border-emerald-200 dark:border-emerald-700 rounded-lg p-4">
                    <div class="flex items-start space-x-3">
                        <x-filament::icon 
                            icon="heroicon-o-light-bulb" 
                            class="h-5 w-5 text-emerald-600 dark:text-emerald-400 mt-0.5 flex-shrink-0"
                        />
                        <div class="text-sm">
                            <h5 class="font-semibold text-emerald-900 dark:text-emerald-100 mb-2">Why This Works:</h5>
                            <ul class="space-y-1 text-emerald-800 dark:text-emerald-200">
                                <li>• The restore process disables foreign key checks for compatibility</li>
                                <li>• Failed SQL statements are skipped gracefully while preserving what works</li>
                                <li>• New schema tables/columns are preserved alongside restored data</li>
                                <li>• Transactions ensure data integrity during the restore process</li>
                            </ul>
                        </div>
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
            </div>
        </div>
    </div>
</x-filament-panels::page>