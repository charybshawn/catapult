<x-filament-panels::page>
    <div class="space-y-6">
        <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center space-x-3 mb-4">
                <x-filament::icon 
                    icon="heroicon-o-information-circle" 
                    class="h-6 w-6 text-primary-600 dark:text-primary-400"
                />
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    Database Backup & Restore
                </h3>
            </div>
            
            <div class="prose dark:prose-invert max-w-none">
                <p class="text-gray-600 dark:text-gray-400 mb-4">
                    Manage your database backups and restore operations. Always create a backup before making significant changes.
                </p>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 not-prose">
                    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                        <h4 class="font-semibold text-blue-900 dark:text-blue-100 mb-2">Create Backup</h4>
                        <p class="text-blue-700 dark:text-blue-300 text-sm">
                            Creates a complete database backup as an SQL file that can be downloaded and stored safely.
                        </p>
                    </div>
                    
                    <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-4">
                        <h4 class="font-semibold text-amber-900 dark:text-amber-100 mb-2">Restore Database</h4>
                        <p class="text-amber-700 dark:text-amber-300 text-sm">
                            ⚠️ Completely replaces your current database. This action cannot be undone.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    Available Backups
                </h3>
                
                @php 
                    $backupService = new \App\Services\SimpleBackupService();
                    $backups = $backupService->listBackups()->toArray(); 
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
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $backup['name'] }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            {{ $backup['size'] }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            {{ $backup['created_at']->format('M j, Y g:i A') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-3">
                                            <button 
                                                wire:click="restoreBackup('{{ $backup['name'] }}')"
                                                wire:confirm="Are you sure you want to restore this backup? This will completely replace your current database and cannot be undone."
                                                class="text-amber-600 hover:text-amber-900 dark:text-amber-400 dark:hover:text-amber-300"
                                            >
                                                Restore
                                            </button>
                                            
                                            <a 
                                                href="{{ route('database.backup.download', ['filename' => $backup['name']]) }}"
                                                class="text-primary-600 hover:text-primary-900 dark:text-primary-400 dark:hover:text-primary-300"
                                            >
                                                Download
                                            </a>
                                            
                                            <button 
                                                wire:click="deleteBackup('{{ $backup['name'] }}')"
                                                wire:confirm="Are you sure you want to delete this backup? This action cannot be undone."
                                                class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                                            >
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
                    </div>
                @endif
            </div>
        </div>
    </div>

</x-filament-panels::page>