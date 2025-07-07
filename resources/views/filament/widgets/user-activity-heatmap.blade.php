<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            {{ static::$heading }}
        </x-slot>

        <div class="overflow-x-auto">
            @php
                $data = $this->getHeatmapData();
                $maxCount = collect($data['users'])->pluck('data')->flatten(1)->max('count') ?? 1;
            @endphp

            <table class="min-w-full">
                <thead>
                    <tr>
                        <th class="px-2 py-1 text-left text-xs font-medium text-gray-500 dark:text-gray-400">User</th>
                        @foreach($data['dates'] as $date)
                            <th class="px-1 py-1 text-center text-xs font-medium text-gray-500 dark:text-gray-400">
                                {{ \Carbon\Carbon::parse($date)->format('j') }}
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($data['users'] as $user)
                        <tr>
                            <td class="px-2 py-1 text-xs font-medium text-gray-900 dark:text-white whitespace-nowrap">
                                {{ $user['user'] }}
                            </td>
                            @foreach($user['data'] as $day)
                                <td class="px-1 py-1">
                                    <div class="w-6 h-6 rounded"
                                         style="background-color: {{ $day['count'] > 0 
                                            ? 'rgba(59, 130, 246, ' . ($day['count'] / $maxCount) . ')' 
                                            : 'rgba(229, 231, 235, 0.5)' }}"
                                         title="{{ $user['user'] }}: {{ $day['count'] }} activities on {{ $day['date'] }}">
                                    </div>
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="mt-4 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                <div>Last 30 days</div>
                <div class="flex items-center gap-2">
                    <span>Less</span>
                    <div class="flex gap-1">
                        <div class="w-4 h-4 rounded" style="background-color: rgba(229, 231, 235, 0.5)"></div>
                        <div class="w-4 h-4 rounded" style="background-color: rgba(59, 130, 246, 0.25)"></div>
                        <div class="w-4 h-4 rounded" style="background-color: rgba(59, 130, 246, 0.5)"></div>
                        <div class="w-4 h-4 rounded" style="background-color: rgba(59, 130, 246, 0.75)"></div>
                        <div class="w-4 h-4 rounded" style="background-color: rgba(59, 130, 246, 1)"></div>
                    </div>
                    <span>More</span>
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>