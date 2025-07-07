<div class="space-y-2">
    @if(is_array($context) || is_object($context))
        @php
            $context = is_string($context) ? json_decode($context, true) : (array) $context;
        @endphp
        
        @if(!empty($context))
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead>
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Context Key
                            </th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Value
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($context as $key => $value)
                            <tr>
                                <td class="px-3 py-2 text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ str_replace('_', ' ', ucfirst($key)) }}
                                </td>
                                <td class="px-3 py-2 text-sm text-gray-700 dark:text-gray-300">
                                    @if(is_array($value) || is_object($value))
                                        <pre class="text-xs bg-gray-100 dark:bg-gray-800 p-2 rounded overflow-x-auto">{{ json_encode($value, JSON_PRETTY_PRINT) }}</pre>
                                    @elseif(is_bool($value))
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $value ? 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100' : 'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100' }}">
                                            {{ $value ? 'Yes' : 'No' }}
                                        </span>
                                    @elseif(is_null($value))
                                        <span class="text-gray-400 dark:text-gray-600 italic">null</span>
                                    @else
                                        {{ $value }}
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-sm text-gray-500 dark:text-gray-400 italic">No context data recorded</p>
        @endif
    @else
        <p class="text-sm text-gray-500 dark:text-gray-400 italic">No context data recorded</p>
    @endif
</div>