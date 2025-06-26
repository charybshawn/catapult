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
        <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Available Backups
                    </h3>
                    <div class="flex items-center space-x-2">
                        <x-filament::icon 
                            icon="heroicon-o-clock" 
                            class="h-4 w-4 text-gray-400"
                        />
                        <span class="text-sm text-gray-500 dark:text-gray-400">
                            Auto-refreshes after operations
                        </span>
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