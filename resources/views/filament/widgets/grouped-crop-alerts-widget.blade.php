<x-filament::section>
    <div class="space-y-6">
        <div class="text-xl font-bold flex items-center justify-between">
            <div>Crop Alerts</div>
            <x-filament::link
                color="primary"
                href="{{ route('filament.admin.pages.manage-crop-tasks') }}"
            >
                Manage Tasks
            </x-filament::link>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
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
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                        <div class="flex items-center gap-2 font-medium text-lg mb-3">
                            <span>{{ $stage['title'] }}</span>
                            <span class="ml-auto bg-{{ $stage['color'] }}-100 text-{{ $stage['color'] }}-800 dark:bg-{{ $stage['color'] }}-900 dark:text-{{ $stage['color'] }}-300 rounded-full px-2 py-0.5 text-xs">
                                {{ count($stage['crops']) }}
                            </span>
                        </div>

                        <div class="space-y-3">
                            @foreach($stage['crops'] as $crop)
                                <div class="p-2 rounded-lg {{ $crop['overdue'] ? 'bg-danger-50 dark:bg-danger-950' : 'bg-gray-50 dark:bg-gray-900' }}">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <div class="font-medium">{{ $crop['variety'] }}</div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">Tray: {{ $crop['tray'] }}</div>
                                        </div>
                                        <div class="text-right">
                                            <div class="font-medium {{ $crop['overdue'] ? 'text-danger-600 dark:text-danger-400' : '' }}">
                                                {{ $crop['days_in_stage'] }} days
                                            </div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                (Rec: {{ $crop['recommended_days'] }} days)
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-2 flex justify-end">
                                        <x-filament::button
                                            size="xs"
                                            href="{{ route('filament.admin.resources.crops.edit', ['record' => $crop['id']]) }}"
                                        >
                                            Manage
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