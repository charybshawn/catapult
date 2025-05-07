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
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Weight</th>
                        <th scope="col" class="px-3 py-3.5 text-right text-sm font-semibold text-gray-900">Price</th>
                        <th scope="col" class="px-3 py-3.5 text-center text-sm font-semibold text-gray-900">Default</th>
                        <th scope="col" class="px-3 py-3.5 text-center text-sm font-semibold text-gray-900">Global</th>
                        <th scope="col" class="px-3 py-3.5 text-center text-sm font-semibold text-gray-900">Active</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    @foreach($variations->sortByDesc('is_default')->sortBy('name') as $variation)
                    <tr class="{{ $variation->is_default ? 'bg-primary-50' : '' }}">
                        <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900">
                            {{ $variation->name }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                            {{ ucfirst($variation->unit) }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                            {{ $variation->sku ?? '-' }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                            @if($variation->unit !== 'item' && $variation->weight)
                                {{ $variation->weight }} {{ $variation->unit }}
                            @else
                                -
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-800 text-right font-medium">
                            ${{ number_format($variation->price, 2) }}
                        </td>
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
                            @if($variation->is_global)
                                <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800">
                                    <svg class="-ml-0.5 mr-1.5 h-2 w-2 text-blue-400" fill="currentColor" viewBox="0 0 8 8">
                                        <circle cx="4" cy="4" r="3" />
                                    </svg>
                                    Global
                                </span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-center">
                            @if($variation->is_active)
                                <span class="inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800">
                                    Active
                                </span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600">
                                    Inactive
                                </span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4 flex justify-between items-center">
            <div class="text-xs text-gray-500">
                <p>For global price variations that can be used across multiple products, mark a variation as "Global" when editing it.</p>
            </div>
            <a href="{{ route('filament.admin.resources.products.edit', ['record' => $record->id]) }}#relation-manager-{{ Str::kebab(class_basename(\App\Filament\Resources\ProductResource\RelationManagers\PriceVariationsRelationManager::class)) }}" class="text-primary-600 hover:text-primary-800 text-sm">
                Manage price variations
            </a>
        </div>
    @endif
</div> 