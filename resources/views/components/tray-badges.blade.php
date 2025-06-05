@if($getState())
    @php
        $trayNumbers = explode(', ', $getState());
    @endphp
    
    <div class="flex flex-wrap gap-1">
        @foreach($trayNumbers as $trayNumber)
            @php
                $trayNumber = trim($trayNumber);
            @endphp
            
            <x-filament::badge color="info" size="sm">
                {{ $trayNumber }}
            </x-filament::badge>
        @endforeach
    </div>
@else
    <span class="text-gray-400">-</span>
@endif