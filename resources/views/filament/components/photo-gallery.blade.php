@php
    // Check if we're on the edit page and can retrieve the record
    $recordId = request()->route('record');
    $item = null;
    
    if ($recordId) {
        $item = \App\Models\Item::find($recordId);
        $photos = $item ? $item->photos()->orderBy('order')->get() : collect();
    }
@endphp

@if(!$recordId)
    <div class="text-center p-4 text-gray-500 dark:text-gray-400">
        Save the product first to enable photo management.
    </div>
@elseif($item && $photos->count() > 0)
    <div class="mb-6">
        <h3 class="text-lg font-medium mb-3">Product Photos</h3>
        
        <div class="overflow-x-auto pb-2">
            <div class="flex space-x-4 min-w-max">
                @foreach($photos as $photo)
                    <div class="relative flex-shrink-0 w-48 group">
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden border border-gray-200 dark:border-gray-700 transition hover:shadow-md">
                            <div class="relative aspect-square">
                                <img 
                                    src="{{ Storage::disk('public')->url($photo->photo) }}" 
                                    alt="Product Photo" 
                                    class="w-full h-full object-cover {{ $photo->is_default ? 'ring-2 ring-primary-500' : '' }}"
                                >
                                @if($photo->is_default)
                                    <div class="absolute top-2 left-2 bg-primary-500 text-white text-xs font-semibold px-2 py-1 rounded-full">
                                        Default
                                    </div>
                                @endif
                            </div>
                            
                            <div class="p-2 space-y-2">
                                <div class="text-xs text-gray-500 dark:text-gray-400">Order: {{ $photo->order }}</div>
                                
                                <div class="flex flex-col space-y-1">
                                    @if(!$photo->is_default)
                                        <button
                                            type="button"
                                            x-data="{}"
                                            x-on:click="
                                                $wire.setAsDefault({ 'record': {{ $photo->id }} });
                                            "
                                            class="text-xs px-2 py-1 bg-primary-500 text-white rounded hover:bg-primary-600 transition text-center"
                                        >
                                            Set Default
                                        </button>
                                    @endif
                                    
                                    <button
                                        type="button"
                                        x-data="{}"
                                        x-on:click="
                                            if (confirm('Are you sure you want to delete this photo?')) {
                                                $wire.deletePhoto({ 'record': {{ $photo->id }} });
                                            }
                                        "
                                        class="text-xs px-2 py-1 bg-danger-500 text-white rounded hover:bg-danger-600 transition text-center"
                                    >
                                        Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@else
    <div class="text-center p-4 text-gray-500 dark:text-gray-400">
        No photos have been added yet. Use the uploader above to add product photos.
    </div>
@endif 