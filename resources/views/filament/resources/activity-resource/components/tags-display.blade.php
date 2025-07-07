<div class="space-y-2">
    @if(is_array($tags) || is_object($tags))
        @php
            $tags = is_string($tags) ? json_decode($tags, true) : (array) $tags;
        @endphp
        
        @if(!empty($tags))
            <div class="flex flex-wrap gap-2">
                @foreach($tags as $tag)
                    @php
                        $tagColor = match(true) {
                            str_contains(strtolower($tag), 'error') => 'red',
                            str_contains(strtolower($tag), 'warning') => 'yellow',
                            str_contains(strtolower($tag), 'success') => 'green',
                            str_contains(strtolower($tag), 'info') => 'blue',
                            str_contains(strtolower($tag), 'debug') => 'gray',
                            str_contains(strtolower($tag), 'critical') => 'red',
                            str_contains(strtolower($tag), 'slow') => 'orange',
                            str_contains(strtolower($tag), 'api') => 'purple',
                            str_contains(strtolower($tag), 'user') => 'indigo',
                            str_contains(strtolower($tag), 'system') => 'gray',
                            default => 'gray'
                        };
                    @endphp
                    <span class="inline-flex items-center px-3 py-0.5 rounded-full text-sm font-medium bg-{{ $tagColor }}-100 text-{{ $tagColor }}-800 dark:bg-{{ $tagColor }}-800 dark:text-{{ $tagColor }}-100">
                        {{ $tag }}
                    </span>
                @endforeach
            </div>
        @else
            <p class="text-sm text-gray-500 dark:text-gray-400 italic">No tags assigned</p>
        @endif
    @else
        <p class="text-sm text-gray-500 dark:text-gray-400 italic">No tags assigned</p>
    @endif
</div>