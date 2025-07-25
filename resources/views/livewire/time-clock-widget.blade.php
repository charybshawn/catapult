<div class="flex items-center space-x-2" 
     x-data="{}" 
     x-on:refresh-time-widgets.window="$wire.refreshWidget()">
    @if($isActive)
        <div class="flex items-center space-x-2 text-sm">
            @if($isFlagged)
                <svg class="w-4 h-4 text-danger-500 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.732 15.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
            @else
                <svg class="w-4 h-4 text-warning-500 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            @endif
            <span class="font-mono font-semibold {{ $isFlagged ? 'text-danger-600 dark:text-danger-400' : 'text-gray-700 dark:text-gray-200' }}" wire:poll.1s="updateElapsedTime">
                {{ $elapsedTime }}
            </span>
            @if($isFlagged)
                <span class="px-2 py-1 text-xs font-medium text-danger-800 bg-danger-100 dark:text-danger-200 dark:bg-danger-900 rounded-md">
                    REVIEW NEEDED
                </span>
            @endif
            <button 
                wire:click="clockOut"
                class="ml-2 px-3 py-1 text-xs font-medium text-white bg-red-600 hover:bg-red-700 rounded-md transition-all duration-200 shadow-sm hover:shadow-md transform hover:scale-105"
            >
                Clock Out
            </button>
        </div>
    @else
        <div class="flex items-center space-x-2 text-sm">
            <div class="text-gray-500 dark:text-gray-400">
                Not clocked in
            </div>
            <button 
                wire:click="clockIn"
                class="px-3 py-1 text-xs font-medium text-white bg-green-600 hover:bg-green-700 rounded-md transition-all duration-200 shadow-sm hover:shadow-md transform hover:scale-105 ring-2 ring-green-500 ring-opacity-50"
            >
                Clock In
            </button>
        </div>
    @endif
    
    <!-- Include the logout task modal -->
    <livewire:logout-task-modal />
</div>