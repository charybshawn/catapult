<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            {{ static::$heading }}
        </x-slot>

        <div class="space-y-3">
            @foreach($this->getViewData()['users'] as $index => $user)
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center text-xs font-semibold text-gray-600 dark:text-gray-300">
                            {{ $index + 1 }}
                        </div>
                        <div>
                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ $user['name'] }}
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $user['email'] }}
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="text-sm font-semibold text-gray-900 dark:text-white">
                            {{ number_format($user['count']) }}
                        </div>
                        <div class="w-24">
                            <div class="bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                <div class="bg-primary-600 h-2 rounded-full" style="width: {{ $user['percentage'] }}%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach

            @if(empty($this->getViewData()['users']))
                <div class="text-center py-4 text-sm text-gray-500 dark:text-gray-400">
                    No user activity in the last 7 days
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>