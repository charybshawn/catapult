<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    @php
        $containers = $getChildComponentContainers();
        $addAction = $getAction('add');
        $cloneAction = $getAction('clone');
        $deleteAction = $getAction('delete');
        $moveDownAction = $getAction('moveDown');
        $moveUpAction = $getAction('moveUp');
        $reorderAction = $getAction('reorder');
        $isReorderable = $isReorderable();
        $isReorderableWithButtons = $isReorderableWithButtons();
        $isReorderableWithDragAndDrop = $isReorderableWithDragAndDrop();
        $statePath = $getStatePath();
        $columnWidths = $getColumnWidths();
        $isCompact = $isCompact();
    @endphp

    <div
        x-data="{}"
        {{
            $attributes
                ->merge($getExtraAttributes(), escape: false)
                ->class(['fi-fo-repeater'])
        }}
    >
        @if ($isCompact && count($containers) > 0)
            {{-- Compact Table View --}}
            <div class="overflow-x-auto rounded-lg border border-gray-300 dark:border-gray-700">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            @if ($isReorderableWithDragAndDrop || $isReorderableWithButtons)
                                <th class="w-10 px-2 py-2"></th>
                            @endif
                            
                            @foreach ($containers[array_key_first($containers)]?->getComponents() ?? [] as $field)
                                @php
                                    $width = $columnWidths[$field->getName()] ?? 'auto';
                                    $label = $field->getLabel();
                                @endphp
                                <th 
                                    class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"
                                    @if ($width !== 'auto')
                                        style="width: {{ $width }}"
                                    @endif
                                >
                                    {{ $label }}
                                    @if ($field->isRequired())
                                        <span class="text-danger-600 dark:text-danger-400">*</span>
                                    @endif
                                </th>
                            @endforeach
                            
                            <th class="w-10 px-2 py-2"></th>
                        </tr>
                    </thead>
                    <tbody 
                        @if ($isReorderableWithDragAndDrop)
                            x-sortable
                            x-on:end="$wire.reorderFormComponent(@js($statePath), $event.target.sortable.toArray())"
                        @endif
                        class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700"
                    >
                        @foreach ($containers as $uuid => $container)
                            <tr 
                                wire:key="{{ $container->getStatePath() }}"
                                x-sortable-item="{{ $uuid }}"
                                class="hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
                            >
                                @if ($isReorderableWithDragAndDrop || $isReorderableWithButtons)
                                    <td class="px-2 py-1 text-center">
                                        @if ($isReorderableWithDragAndDrop)
                                            <div x-sortable-handle class="cursor-move">
                                                <x-filament::icon-button
                                                    icon="heroicon-m-bars-3"
                                                    color="gray"
                                                />
                                            </div>
                                        @endif
                                        
                                        @if ($isReorderableWithButtons)
                                            <div class="flex flex-col gap-1">
                                                {{ $moveUpAction(['item' => $uuid])->disabled($loop->first) }}
                                                {{ $moveDownAction(['item' => $uuid])->disabled($loop->last) }}
                                            </div>
                                        @endif
                                    </td>
                                @endif
                                
                                @foreach ($container->getComponents() as $field)
                                    <td class="px-3 py-1">
                                        {{ $field }}
                                    </td>
                                @endforeach
                                
                                <td class="px-2 py-1 text-center">
                                    {{ $deleteAction(['item' => $uuid]) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            {{-- Regular repeater items or empty state --}}
            <div
                @if ($isReorderableWithDragAndDrop)
                    x-sortable
                    x-on:end="$wire.reorderFormComponent(@js($statePath), $event.target.sortable.toArray())"
                @endif
                class="fi-fo-repeater-items divide-y divide-gray-100 rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:divide-white/10 dark:bg-white/5 dark:ring-white/10"
            >
                @forelse ($containers as $uuid => $container)
                    <div
                        wire:key="{{ $container->getStatePath() }}"
                        x-sortable-item="{{ $uuid }}"
                        class="fi-fo-repeater-item relative rounded-xl bg-white p-4 shadow-sm dark:bg-white/5"
                    >
                        @if ($isReorderableWithDragAndDrop || $isReorderableWithButtons || $cloneAction->isVisible() || $deleteAction->isVisible())
                            <div class="absolute -top-2 right-0 flex items-center gap-x-1">
                                @if ($reorderAction)
                                    {{ $reorderAction }}
                                @endif

                                @if ($isReorderableWithButtons)
                                    <div class="flex items-center">
                                        {{ $moveUpAction(['item' => $uuid])->disabled($loop->first) }}
                                        {{ $moveDownAction(['item' => $uuid])->disabled($loop->last) }}
                                    </div>
                                @endif

                                @if ($cloneAction->isVisible())
                                    {{ $cloneAction(['item' => $uuid]) }}
                                @endif

                                @if ($deleteAction->isVisible())
                                    {{ $deleteAction(['item' => $uuid]) }}
                                @endif
                            </div>
                        @endif

                        {{ $container }}
                    </div>
                @empty
                    <div class="text-center p-6">
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ (is_callable($getEmptyStateMessage) ? $getEmptyStateMessage() : null) ?? 'No items' }}
                        </p>
                    </div>
                @endforelse
            </div>
        @endif

        @if ($addAction->isVisible())
            <div class="mt-3">
                {{ $addAction }}
            </div>
        @endif
    </div>
</x-dynamic-component>