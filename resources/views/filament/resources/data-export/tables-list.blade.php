<div>
    @if($getRecord() && $getRecord()->manifest)
        <div class="space-y-2">
            @foreach($getRecord()->manifest['tables'] ?? [] as $table)
                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                            {{ $table['name'] }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $table['file'] }}
                        </p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                            {{ number_format($table['records'] ?? 0) }} records
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ formatBytes($table['size'] ?? 0) }}
                        </p>
                    </div>
                </div>
            @endforeach
            
            @if(isset($getRecord()->manifest['statistics']) && count($getRecord()->manifest['statistics']) > 0)
                <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">
                        Total Records Exported
                    </p>
                    <div class="grid grid-cols-2 gap-2">
                        @foreach($getRecord()->manifest['statistics'] as $table => $count)
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600 dark:text-gray-400">{{ ucfirst(str_replace('_', ' ', $table)) }}:</span>
                                <span class="font-medium text-gray-900 dark:text-gray-100">{{ number_format($count) }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @else
        <p class="text-sm text-gray-500 dark:text-gray-400">
            No export data available.
        </p>
    @endif
</div>

@php
    function formatBytes($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
@endphp