<x-filament::page>
    <x-filament::section>
        <x-slot name="heading">
            {{ $monthLabel }} Planting Calendar
        </x-slot>
        
        <x-slot name="headerEnd">
            {{ $this->form }}
        </x-slot>
        
        <div class="space-y-4">
            <!-- Calendar Container -->
            <div id="calendar" class="overflow-hidden rounded-xl border border-gray-200 shadow-sm dark:border-gray-700"></div>
            
            <!-- Legend -->
            <div class="flex flex-wrap gap-4 pt-4">
                <div class="flex items-center">
                    <span class="mr-2 inline-block h-4 w-4 rounded-full bg-green-500"></span>
                    <span class="text-sm font-medium">Planting Days</span>
                </div>
                <div class="flex items-center">
                    <span class="mr-2 inline-block h-4 w-4 rounded-full bg-red-500"></span>
                    <span class="text-sm font-medium">Harvest Days</span>
                </div>
            </div>
        </div>
    </x-filament::section>
    
    @pushonce('scripts')
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const calendarEl = document.getElementById('calendar');
            
            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                initialDate: '{{ $selectedMonth }}-01',
                height: 'auto',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek'
                },
                events: @json($events),
                eventClick: function(info) {
                    if (info.event.url) {
                        window.open(info.event.url);
                        info.jsEvent.preventDefault(); // prevents browser from navigating by default
                    }
                },
                eventContent: function(arg) {
                    const event = arg.event;
                    const type = event.extendedProps.type;
                    const status = event.extendedProps.status;
                    const trays = event.extendedProps.trays;
                    
                    // Create the custom content
                    const content = document.createElement('div');
                    content.classList.add('px-2', 'py-1', 'text-sm', 'leading-tight');
                    
                    // Title with icon
                    const title = document.createElement('div');
                    title.classList.add('font-semibold');
                    
                    // Add appropriate icon
                    if (type === 'planting') {
                        title.innerHTML = `<i class="fas fa-seedling mr-1"></i> ${event.title}`;
                    } else if (type === 'harvesting') {
                        title.innerHTML = `<i class="fas fa-cut mr-1"></i> ${event.title}`;
                    } else {
                        title.textContent = event.title;
                    }
                    
                    content.appendChild(title);
                    
                    // Add trays info
                    const traysElement = document.createElement('div');
                    traysElement.classList.add('text-xs', 'mt-1');
                    traysElement.textContent = `${trays} tray${trays !== 1 ? 's' : ''}`;
                    content.appendChild(traysElement);
                    
                    // Status badge
                    if (status) {
                        const statusElement = document.createElement('div');
                        statusElement.classList.add('text-xs', 'mt-1', 'inline-block', 'px-1.5', 'py-0.5', 'rounded');
                        
                        // Style based on status
                        switch (status) {
                            case 'pending':
                                statusElement.classList.add('bg-gray-200', 'text-gray-800');
                                break;
                            case 'partially_planted':
                                statusElement.classList.add('bg-yellow-200', 'text-yellow-800');
                                break;
                            case 'fully_planted':
                                statusElement.classList.add('bg-green-200', 'text-green-800');
                                break;
                            case 'completed':
                                statusElement.classList.add('bg-blue-200', 'text-blue-800');
                                break;
                            case 'cancelled':
                                statusElement.classList.add('bg-red-200', 'text-red-800');
                                break;
                        }
                        
                        statusElement.textContent = status.replace('_', ' ');
                        content.appendChild(statusElement);
                    }
                    
                    const arrayOfDomNodes = [ content ];
                    return { domNodes: arrayOfDomNodes };
                }
            });
            
            calendar.render();
            
            // Re-render calendar if Livewire reloads the component
            document.addEventListener('livewire:navigated', function() {
                calendar.render();
            });
        });
    </script>
    @endpushonce
    
    @push('styles')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        .fc-event {
            cursor: pointer;
        }
        .fc-event-time {
            display: none;
        }
        .fc-day-today {
            background-color: rgba(var(--primary-50), 0.3) !important;
        }
        .fc-header-toolbar {
            margin-bottom: 0.5em !important;
        }
    </style>
    @endpush
</x-filament::page> 