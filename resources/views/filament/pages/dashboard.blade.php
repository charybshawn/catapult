<x-filament-panels::page>
    <x-filament-widgets::widgets
        :columns="$this->getColumns()"
        :widgets="$this->getHeaderWidgets()"
        class="mb-6"
    />

    <div class="grid grid-cols-1 gap-6">
        @foreach ($this->getFooterWidgets() as $widget)
            @if ($widget::canView())
                <div class="col-span-1">
                    @livewire(
                        $widget,
                        key($widget),
                    )
                </div>
            @endif
        @endforeach
    </div>
</x-filament-panels::page> 