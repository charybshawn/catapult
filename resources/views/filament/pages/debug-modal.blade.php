@php
    $taskData = $data['taskData'] ?? [];
    $cropData = $data['cropData'] ?? null;
@endphp

<div class="space-y-4">
    <div>
        <h3 class="text-lg font-medium">Task Data</h3>
        <div class="mt-2 space-y-1">
            @foreach($taskData as $key => $value)
                <div class="flex">
                    <span class="font-medium w-32">{{ $key }}:</span>
                    <span class="text-gray-600">
                        @if(is_array($value))
                            <pre class="text-xs">{{ json_encode($value, JSON_PRETTY_PRINT) }}</pre>
                        @else
                            {{ $value ?? 'null' }}
                        @endif
                    </span>
                </div>
            @endforeach
        </div>
    </div>

    @if($cropData)
        <div>
            <h3 class="text-lg font-medium">Crop Data</h3>
            <div class="mt-2 space-y-1">
                @foreach($cropData as $key => $value)
                    <div class="flex">
                        <span class="font-medium w-32">{{ $key }}:</span>
                        <span class="text-gray-600">{{ $value ?? 'null' }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @else
        <div class="text-gray-500">
            Crop not found
        </div>
    @endif
</div> 