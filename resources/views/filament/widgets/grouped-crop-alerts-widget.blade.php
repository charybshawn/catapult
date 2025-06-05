<x-filament::section>
    <div class="space-y-6">
        <div class="text-xl font-bold flex items-center justify-between">
            <div>Crop Alerts</div>
            <x-filament::link
                color="primary"
                href="{{ route('filament.admin.resources.crop-alerts.index') }}"
            >
                Manage Alerts
            </x-filament::link>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            @php
                $stages = $this->getCropsNeedingAction();
                $totalAlerts = collect($stages)->sum(fn ($stage) => count($stage['crops']));
            @endphp

            @if($totalAlerts === 0)
                <div class="col-span-full bg-gray-50 dark:bg-gray-800 p-6 rounded-lg text-center">
                    <div class="text-lg font-medium text-gray-500 dark:text-gray-400">
                        No crops need attention right now
                    </div>
                    <div class="mt-2 text-sm text-gray-400 dark:text-gray-500">
                        All crops are growing according to their schedules
                    </div>
                </div>
            @endif

            @foreach($stages as $stageKey => $stage)
                @if(count($stage['crops']) > 0)
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold">{{ $stage['title'] }}</h3>
                            <span class="bg-{{ $stage['color'] }}-100 text-{{ $stage['color'] }}-800 dark:bg-{{ $stage['color'] }}-900 dark:text-{{ $stage['color'] }}-300 rounded-full px-3 py-1 text-sm font-medium">
                                {{ count($stage['crops']) }} {{ count($stage['crops']) === 1 ? 'crop' : 'crops' }}
                            </span>
                        </div>

                        <div class="space-y-4">
                            @foreach($stage['crops'] as $crop)
                                <div class="p-4 rounded-lg border {{ $crop['overdue'] ? 'bg-danger-50 border-danger-200 dark:bg-danger-950 dark:border-danger-800' : 'bg-gray-50 border-gray-200 dark:bg-gray-900 dark:border-gray-700' }}">
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="flex-1">
                                            <div class="font-semibold text-base">{{ $crop['variety'] }}</div>
                                            <div class="mt-1 space-y-1">
                                                <div class="text-sm text-gray-600 dark:text-gray-400">
                                                    <span class="font-medium">Tray:</span> {{ $crop['tray'] }}
                                                </div>
                                                <div class="text-sm text-gray-600 dark:text-gray-400">
                                                    <span class="font-medium">Next Stage:</span> {{ $crop['target_stage'] }}
                                                </div>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-lg font-semibold {{ $crop['overdue'] ? 'text-danger-600 dark:text-danger-400' : 'text-gray-900 dark:text-gray-100' }}">
                                                {{ $crop['time_in_stage'] }}
                                            </div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                                Recommended: {{ $crop['recommended_days'] }}d
                                            </div>
                                            @if($crop['overdue'])
                                                <div class="text-xs text-danger-600 dark:text-danger-400 font-medium mt-1">
                                                    Overdue
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="mt-3 flex justify-end">
                                        <x-filament::button
                                            size="sm"
                                            href="{{ route('filament.admin.resources.crops.edit', ['record' => $crop['id']]) }}"
                                        >
                                            View Details
                                        </x-filament::button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    </div>
</x-filament::section> 