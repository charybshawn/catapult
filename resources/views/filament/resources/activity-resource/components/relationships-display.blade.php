<div class="space-y-4">
    @php
        $relationships = $properties['relationships'] ?? [];
    @endphp
    
    @if(!empty($relationships))
        @foreach($relationships as $relationName => $relationData)
            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-3">
                    {{ str_replace('_', ' ', ucwords($relationName)) }}
                </h4>
                
                @if(is_null($relationData))
                    <p class="text-sm text-gray-500 dark:text-gray-400 italic">No related {{ str_replace('_', ' ', $relationName) }}</p>
                @elseif(isset($relationData['error']))
                    <p class="text-sm text-red-600 dark:text-red-400">{{ $relationData['error'] }}</p>
                @elseif(isset($relationData['_model']))
                    {{-- Single relationship (BelongsTo, HasOne) --}}
                    <div class="bg-gray-50 dark:bg-gray-800 rounded p-3">
                        <div class="grid grid-cols-2 gap-2 text-sm">
                            <div class="font-medium text-gray-600 dark:text-gray-400">Type:</div>
                            <div class="text-gray-900 dark:text-gray-100">{{ $relationData['_model'] }}</div>
                            
                            <div class="font-medium text-gray-600 dark:text-gray-400">ID:</div>
                            <div class="text-gray-900 dark:text-gray-100">{{ $relationData['_id'] }}</div>
                            
                            @foreach($relationData as $key => $value)
                                @if(!str_starts_with($key, '_'))
                                    <div class="font-medium text-gray-600 dark:text-gray-400">{{ str_replace('_', ' ', ucfirst($key)) }}:</div>
                                    <div class="text-gray-900 dark:text-gray-100">
                                        @if(is_array($value) || is_object($value))
                                            <pre class="text-xs bg-gray-100 dark:bg-gray-900 p-1 rounded overflow-x-auto">{{ json_encode($value, JSON_PRETTY_PRINT) }}</pre>
                                        @elseif(is_bool($value))
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $value ? 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100' : 'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100' }}">
                                                {{ $value ? 'Yes' : 'No' }}
                                            </span>
                                        @else
                                            {{ $value }}
                                        @endif
                                    </div>
                                @endif
                            @endforeach
                            
                            @if(isset($relationData['_created_at']))
                                <div class="font-medium text-gray-600 dark:text-gray-400">Created:</div>
                                <div class="text-gray-900 dark:text-gray-100">{{ \Carbon\Carbon::parse($relationData['_created_at'])->format('Y-m-d H:i:s') }}</div>
                            @endif
                        </div>
                        
                        @if(isset($relationData['_relations']))
                            <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                                <h5 class="text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">Nested Relations</h5>
                                <div class="ml-4">
                                    @include('filament.resources.activity-resource.components.relationships-display', ['properties' => ['relationships' => $relationData['_relations']]])
                                </div>
                            </div>
                        @endif
                    </div>
                @else
                    {{-- Collection relationship (HasMany, BelongsToMany) --}}
                    <div class="space-y-2">
                        @foreach($relationData as $index => $item)
                            <div class="bg-gray-50 dark:bg-gray-800 rounded p-3">
                                <div class="text-xs font-semibold text-gray-600 dark:text-gray-400 mb-2">
                                    {{ $item['_model'] ?? 'Item' }} #{{ $item['_id'] ?? ($index + 1) }}
                                </div>
                                <div class="grid grid-cols-2 gap-2 text-sm">
                                    @foreach($item as $key => $value)
                                        @if(!str_starts_with($key, '_'))
                                            <div class="font-medium text-gray-600 dark:text-gray-400">{{ str_replace('_', ' ', ucfirst($key)) }}:</div>
                                            <div class="text-gray-900 dark:text-gray-100">
                                                @if(is_array($value) || is_object($value))
                                                    <pre class="text-xs bg-gray-100 dark:bg-gray-900 p-1 rounded overflow-x-auto">{{ json_encode($value, JSON_PRETTY_PRINT) }}</pre>
                                                @elseif(is_bool($value))
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $value ? 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100' : 'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100' }}">
                                                        {{ $value ? 'Yes' : 'No' }}
                                                    </span>
                                                @else
                                                    {{ $value }}
                                                @endif
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endforeach
    @else
        <p class="text-sm text-gray-500 dark:text-gray-400 italic">No relationship data recorded</p>
    @endif
</div>