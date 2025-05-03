<div class="space-y-4">
    <div class="text-lg font-medium">Sort Debug Information</div>
    
    <div class="overflow-hidden overflow-x-auto border border-gray-300 rounded-lg">
        <table class="min-w-full divide-y divide-gray-300">
            <thead>
                <tr class="bg-gray-100">
                    <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Field</th>
                    <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Value</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach ($sortData as $key => $value)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2 text-sm font-medium text-gray-900">{{ $key }}</td>
                        <td class="px-4 py-2 text-sm text-gray-700">
                            @if ($value === null)
                                <span class="text-gray-400">NULL</span>
                            @elseif ($value === '')
                                <span class="text-gray-400">Empty String</span>
                            @elseif (is_numeric($value) && $value == PHP_INT_MAX)
                                <span class="text-amber-600">PHP_INT_MAX</span>
                            @elseif (is_numeric($value) && $value == 0)
                                <span class="text-green-600">0</span>
                            @else
                                {{ $value }}
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    
    <div class="text-sm text-gray-600">
        <p>This debug information shows the computed and stored values used for column sorting.</p>
        <ul class="list-disc pl-5 pt-2">
            <li>For time-based columns, we store both a human-readable status and a minutes value for sorting</li>
            <li>Lower minute values will appear first when sorting ascending</li>
            <li>Special values: 0 = highest priority, PHP_INT_MAX = lowest priority</li>
        </ul>
    </div>
</div> 