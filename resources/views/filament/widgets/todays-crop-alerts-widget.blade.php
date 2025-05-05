<x-filament::section>
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Today's Crop Alerts</h2>
        <x-filament::link
            color="primary"
            tag="a"
            :href="route('filament.admin.resources.task-schedules.index', ['tableFilters[next_run_at][from]' => now()->startOfDay()->format('Y-m-d'), 'tableFilters[next_run_at][to]' => now()->endOfDay()->format('Y-m-d')])"
            size="sm"
        >
            View All Today's Alerts
        </x-filament::link>
    </div>
    
    @php
        $todaysAlerts = $this->getTodaysAlerts();
    @endphp
    
    @if($todaysAlerts->count() > 0)
        <div class="overflow-x-auto">
            <table class="w-full text-left rtl:text-right divide-y divide-gray-200 dark:divide-gray-700">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-800">
                        <th class="px-4 py-2 text-xs font-medium text-gray-500 dark:text-gray-400">Time</th>
                        <th class="px-4 py-2 text-xs font-medium text-gray-500 dark:text-gray-400">Alert</th>
                        <th class="px-4 py-2 text-xs font-medium text-gray-500 dark:text-gray-400">Crop</th>
                        <th class="px-4 py-2 text-xs font-medium text-gray-500 dark:text-gray-400">Trays</th>
                        <th class="px-4 py-2 text-xs font-medium text-gray-500 dark:text-gray-400">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($todaysAlerts as $alert)
                        @php
                            $isOverdue = $alert->next_run_at->isPast();
                            $trayCount = $alert->conditions['tray_count'] ?? 1;
                            $trayList = $alert->conditions['tray_list'] ?? ($alert->conditions['tray_number'] ?? 'N/A');
                            // Ensure we don't show too long lists in the UI
                            if (strlen($trayList) > 30) {
                                $trayList = substr($trayList, 0, 30) . '...';
                            }
                        @endphp
                        <tr class="{{ $isOverdue ? 'bg-danger-50 dark:bg-danger-900/30 hover:bg-danger-100 dark:hover:bg-danger-800/40' : 'hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                            <td class="px-4 py-2 whitespace-nowrap">
                                <div class="text-sm font-medium {{ $isOverdue ? 'text-danger-700 dark:text-danger-400' : 'text-gray-900 dark:text-white' }}">
                                    {{ $alert->next_run_at->format('g:i A') }}
                                </div>
                                <div class="text-xs {{ $isOverdue ? 'text-danger-600 dark:text-danger-400' : 'text-gray-500 dark:text-gray-400' }}">
                                    {{ $isOverdue ? 'Overdue: ' : '' }}{{ $alert->next_run_at->diffForHumans(['parts' => 1]) }}
                                </div>
                            </td>
                            <td class="px-4 py-2">
                                <div class="text-sm font-medium {{ $isOverdue ? 'text-danger-700 dark:text-danger-400' : 'text-gray-900 dark:text-white' }}">
                                    {{ $alert->task_name }}
                                </div>
                                <div class="text-xs {{ $isOverdue ? 'text-danger-600 dark:text-danger-400' : 'text-gray-500 dark:text-gray-400' }}">
                                    {{ \Illuminate\Support\Str::limit(is_array($alert->conditions) ? json_encode($alert->conditions) : ($alert->conditions ?? 'N/A'), 40) }}
                                </div>
                            </td>
                            <td class="px-4 py-2">
                                <div class="text-sm font-medium {{ $isOverdue ? 'text-danger-700 dark:text-danger-400' : 'text-gray-900 dark:text-white' }}">
                                    {{ $alert->conditions['variety'] ?? 'N/A' }}
                                </div>
                            </td>
                            <td class="px-4 py-2">
                                <div class="text-sm font-medium {{ $isOverdue ? 'text-danger-700 dark:text-danger-400' : 'text-gray-900 dark:text-white' }}">
                                    @if($trayCount > 1)
                                        <span class="font-semibold">{{ $trayCount }} trays:</span> {{ $trayList }}
                                    @else
                                        Tray #{{ $trayList }}
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-2">
                                <div class="flex gap-2">
                                    <x-filament::icon-button
                                        icon="heroicon-o-check-circle"
                                        color="{{ $isOverdue ? 'danger' : 'success' }}"
                                        tag="a"
                                        :href="route('filament.admin.resources.task-schedules.edit', $alert)"
                                        tooltip="Complete Alert"
                                        size="sm"
                                    />
                                    <x-filament::icon-button
                                        icon="heroicon-o-eye"
                                        tag="a"
                                        :href="route('filament.admin.resources.task-schedules.edit', $alert)"
                                        tooltip="View Alert"
                                        size="sm"
                                    />
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="p-4 text-center text-gray-500 dark:text-gray-400">
            No alerts scheduled for today.
        </div>
    @endif
</x-filament::section> 