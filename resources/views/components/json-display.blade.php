@props(['data', 'inline' => false])

@if(is_array($data) && !empty($data))
    @if($inline)
        <div class="flex flex-wrap gap-1">
            @foreach($data as $item)
                <span class="fi-badge flex items-center justify-center gap-x-1 rounded-md text-xs font-medium ring-1 ring-inset px-2 min-h-6 fi-color-gray fi-badge-color-gray bg-gray-50 text-gray-600 ring-gray-600/10 dark:bg-gray-400/10 dark:text-gray-400 dark:ring-gray-400/30">
                    {{ ucfirst($item) }}
                </span>
            @endforeach
        </div>
    @else
        <div class="space-y-1">
            @foreach($data as $item)
                <div class="flex items-center px-3 py-2 text-sm bg-gray-50 rounded border">
                    {{ ucfirst($item) }}
                </div>
            @endforeach
        </div>
    @endif
@else
    <span class="text-gray-400 text-sm italic">No items</span>
@endif