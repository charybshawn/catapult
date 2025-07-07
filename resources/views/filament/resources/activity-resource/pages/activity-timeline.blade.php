<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Filters -->
        <x-filament::section>
            <div class="flex gap-4 items-end">
                <div class="flex items-center gap-2">
                    <button wire:click="previousDay" class="p-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </button>
                    <input type="date" wire:model.live="filters.date" class="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                    <button wire:click="nextDay" class="p-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                    <button wire:click="today" class="px-3 py-1 text-sm bg-primary-600 text-white rounded-md hover:bg-primary-700">
                        Today
                    </button>
                </div>
                
                <select wire:model.live="filters.user_id" class="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                    <option value="">All Users</option>
                    @foreach(\App\Models\User::orderBy('name')->get() as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
                
                <select wire:model.live="filters.log_name" class="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                    <option value="">All Types</option>
                    @foreach(\App\Models\Activity::distinct()->pluck('log_name') as $logName)
                        <option value="{{ $logName }}">{{ ucfirst($logName ?? 'default') }}</option>
                    @endforeach
                </select>
            </div>
        </x-filament::section>

        <!-- Timeline -->
        <div class="relative">
            @if($activities->isEmpty())
                <x-filament::section>
                    <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                        No activities found for the selected filters.
                    </div>
                </x-filament::section>
            @else
                <div class="absolute left-8 top-0 bottom-0 w-0.5 bg-gray-200 dark:bg-gray-700"></div>
                
                @foreach($groupedActivities as $hour => $hourActivities)
                    <div class="mb-8">
                        <div class="flex items-center mb-4">
                            <div class="bg-white dark:bg-gray-800 pr-3">
                                <div class="text-sm font-semibold text-gray-900 dark:text-white">
                                    {{ $hour }}
                                </div>
                            </div>
                        </div>
                        
                        <div class="ml-16 space-y-4">
                            @foreach($hourActivities as $activity)
                                <div class="relative">
                                    <div class="absolute -left-[3.25rem] w-4 h-4 rounded-full {{ $activity->log_name === 'error' ? 'bg-danger-500' : 'bg-primary-500' }}"></div>
                                    
                                    <x-filament::section class="p-4">
                                        <div class="flex items-start justify-between">
                                            <div class="flex-1">
                                                <div class="flex items-center gap-2 mb-1">
                                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                                        {{ $activity->created_at->format('H:i:s') }}
                                                    </span>
                                                    <span class="px-2 py-0.5 text-xs rounded-full {{ $activity->log_name === 'error' ? 'bg-danger-100 text-danger-700 dark:bg-danger-900 dark:text-danger-300' : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300' }}">
                                                        {{ ucfirst($activity->log_name ?? 'default') }}
                                                    </span>
                                                    @if($activity->event)
                                                        <span class="px-2 py-0.5 text-xs rounded-full bg-primary-100 text-primary-700 dark:bg-primary-900 dark:text-primary-300">
                                                            {{ $activity->event }}
                                                        </span>
                                                    @endif
                                                </div>
                                                
                                                <div class="text-sm font-medium text-gray-900 dark:text-white mb-1">
                                                    {{ $activity->description }}
                                                </div>
                                                
                                                <div class="flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400">
                                                    @if($activity->causer)
                                                        <div class="flex items-center gap-1">
                                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                                                            </svg>
                                                            {{ $activity->causer->name }}
                                                        </div>
                                                    @endif
                                                    
                                                    @if($activity->subject_type)
                                                        <div class="flex items-center gap-1">
                                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v8a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2H4zm3 5a1 1 0 011-1h4a1 1 0 110 2H8a1 1 0 01-1-1zm1 3a1 1 0 100 2h4a1 1 0 100-2H8z" clip-rule="evenodd" />
                                                            </svg>
                                                            {{ class_basename($activity->subject_type) }}
                                                            @if($activity->subject_id)
                                                                #{{ $activity->subject_id }}
                                                            @endif
                                                        </div>
                                                    @endif
                                                </div>
                                                
                                                @if($activity->properties && count($activity->properties) > 0)
                                                    <details class="mt-2">
                                                        <summary class="text-xs text-gray-500 dark:text-gray-400 cursor-pointer hover:text-gray-700 dark:hover:text-gray-300">
                                                            View Properties
                                                        </summary>
                                                        <pre class="mt-2 p-2 bg-gray-100 dark:bg-gray-800 rounded text-xs overflow-x-auto">{{ json_encode($activity->properties, JSON_PRETTY_PRINT) }}</pre>
                                                    </details>
                                                @endif
                                            </div>
                                        </div>
                                    </x-filament::section>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    </div>
</x-filament-panels::page>