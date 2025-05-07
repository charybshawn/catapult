@php
    $record = $getRecord();
    $variations = $record->priceVariations;
@endphp

<div class="space-y-4 pb-4">
    @if($variations->isEmpty())
        <div class="text-gray-500 p-4 text-center bg-gray-100 rounded-lg">
            No price variations found. Add price variations in the tab below.
        </div>
    @else
        <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 sm:rounded-lg">
            <table class="min-w-full divide-y divide-gray-300">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900">Name</th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Unit</th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">SKU</th>
                        <th scope="col" class="px-3 py-3.5 text-right text-sm font-semibold text-gray-900">Price</th>
                        <th scope="col" class="px-3 py-3.5 text-center text-sm font-semibold text-gray-900">Default</th>
                        <th scope="col" class="px-3 py-3.5 text-center text-sm font-semibold text-gray-900">Active</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    @foreach($variations as $variation)
                    <tr class="{{ $variation->is_default ? 'bg-primary-50' : '' }}">
                        <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900">{{ $variation->name }}</td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ ucfirst($variation->unit) }}</td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ $variation->sku ?? '-' }}</td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 text-right">${{ number_format($variation->price, 2) }}</td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-center">
                            @if($variation->is_default)
                                <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800">
                                    <svg class="-ml-0.5 mr-1.5 h-2 w-2 text-green-400" fill="currentColor" viewBox="0 0 8 8">
                                        <circle cx="4" cy="4" r="3" />
                                    </svg>
                                    Default
                                </span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-center">
                            @if($variation->is_active)
                                <span class="h-2 w-2 rounded-full bg-green-400 inline-block"></span>
                            @else
                                <span class="h-2 w-2 rounded-full bg-gray-300 inline-block"></span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4 text-right">
            <a href="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc() }}" class="text-primary-600 hover:text-primary-800 text-sm">
                Manage all price variations
            </a>
        </div>
    @endif
</div> 