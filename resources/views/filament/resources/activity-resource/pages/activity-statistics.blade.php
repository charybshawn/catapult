<x-filament-panels::page>
    <div class="grid gap-6">
        <!-- Date Range Selector -->
        <x-filament::section>
            <form wire:submit.prevent="loadStatistics" class="flex gap-4 items-end">
                <div class="flex-1">
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">From Date</label>
                    <input type="date" wire:model="from" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                </div>
                <div class="flex-1">
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">To Date</label>
                    <input type="date" wire:model="to" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                </div>
                <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-md hover:bg-primary-700">
                    Update
                </button>
            </form>
        </x-filament::section>

        <!-- Summary Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <x-filament::section>
                <div class="text-2xl font-bold text-gray-900 dark:text-white">
                    {{ number_format($stats['total_activities'] ?? 0) }}
                </div>
                <div class="text-sm text-gray-500 dark:text-gray-400">Total Activities</div>
            </x-filament::section>

            <x-filament::section>
                <div class="text-2xl font-bold text-gray-900 dark:text-white">
                    {{ number_format($stats['unique_users'] ?? 0) }}
                </div>
                <div class="text-sm text-gray-500 dark:text-gray-400">Unique Users</div>
            </x-filament::section>

            <x-filament::section>
                <div class="text-2xl font-bold text-gray-900 dark:text-white">
                    {{ number_format($stats['activities_per_hour']['average'] ?? 0, 1) }}
                </div>
                <div class="text-sm text-gray-500 dark:text-gray-400">Avg Per Hour</div>
            </x-filament::section>

            <x-filament::section>
                <div class="text-2xl font-bold {{ ($stats['error_rate']['rate'] ?? 0) > 5 ? 'text-danger-600' : 'text-success-600' }}">
                    {{ number_format($stats['error_rate']['rate'] ?? 0, 2) }}%
                </div>
                <div class="text-sm text-gray-500 dark:text-gray-400">Error Rate</div>
            </x-filament::section>
        </div>

        <!-- Top Users -->
        <x-filament::section>
            <x-slot name="heading">Top Users</x-slot>
            <div class="space-y-2">
                @foreach($stats['top_users'] ?? [] as $userStat)
                    <div class="flex justify-between items-center py-2 border-b border-gray-200 dark:border-gray-700">
                        <div>
                            <div class="font-medium text-gray-900 dark:text-white">{{ $userStat['user']->name ?? 'Unknown' }}</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">{{ $userStat['user']->email ?? '' }}</div>
                        </div>
                        <div class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ number_format($userStat['count']) }}
                        </div>
                    </div>
                @endforeach
            </div>
        </x-filament::section>

        <!-- Top Actions -->
        <x-filament::section>
            <x-slot name="heading">Top Actions</x-slot>
            <div class="space-y-2">
                @foreach($stats['top_actions'] ?? [] as $action)
                    <div class="flex justify-between items-center py-2 border-b border-gray-200 dark:border-gray-700">
                        <div>
                            <span class="px-2 py-1 text-xs rounded-full bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                                {{ $action->event }}
                            </span>
                            <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">
                                {{ $action->description }}
                            </span>
                        </div>
                        <div class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ number_format($action->count) }}
                        </div>
                    </div>
                @endforeach
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>