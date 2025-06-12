@php
    $cultivars = is_array($getState()) ? $getState() : [];
    $count = count($cultivars);
@endphp

<div class="flex flex-wrap gap-1">
    @if(empty($cultivars))
        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200">
            None
        </span>
    @elseif($count <= 3)
        @foreach($cultivars as $cultivar)
            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                {{ $cultivar }}
            </span>
        @endforeach
    @else
        @foreach(array_slice($cultivars, 0, 3) as $cultivar)
            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                {{ $cultivar }}
            </span>
        @endforeach
        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200" 
              title="{{ implode(', ', $cultivars) }}">
            +{{ $count - 3 }} more
        </span>
    @endif
</div>