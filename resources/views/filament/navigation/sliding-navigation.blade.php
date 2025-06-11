<div 
    x-data="{
        currentView: localStorage.getItem('slidingNavView') || 'main',
        previousView: null,
        isTransitioning: false,
        
        init() {
            // Check if we're on a route that belongs to a submenu
            this.checkCurrentRoute();
            
            // Listen for navigation changes (including back/forward browser buttons)
            window.addEventListener('popstate', () => this.checkCurrentRoute());
            
            // Watch for Livewire navigation
            if (window.Livewire) {
                Livewire.hook('commit.prepared', () => {
                    // Small delay to ensure URL has updated
                    setTimeout(() => this.checkCurrentRoute(), 50);
                });
            }
        },
        
        checkCurrentRoute() {
            // Get the current path
            const currentPath = window.location.pathname;
            let matched = false;
            
            // Check each submenu to see if current route belongs to it
            @foreach($submenus as $groupKey => $submenu)
                @foreach($submenu['items'] as $item)
                    // Check if current path starts with the item URL
                    if (currentPath.startsWith('{{ $item['url'] }}')) {
                        if (this.currentView !== '{{ $groupKey }}') {
                            this.currentView = '{{ $groupKey }}';
                            this.saveView();
                        }
                        matched = true;
                        return;
                    }
                @endforeach
            @endforeach
            
            // If no direct match, check by resource patterns
            if (!matched) {
                const resourcePatterns = {
                    'dashboard': ['/dashboard', '/daily-operations', '/weekly-planning', '/analytics'],
                    'production': ['/crops', '/recipes', '/crop-plans', '/crop-alerts', '/tasks', '/activities'],
                    'orders': ['/orders', '/recurring-orders', '/invoices', '/users'],
                    'inventory': ['/consumables', '/packaging-types', '/product-inventories'],
                    'products': ['/products', '/product-mixes', '/price-variations'],
                    'procurement': ['/suppliers', '/seed-entries', '/seed-scrapes', '/seed-price-trends', '/seed-reorder-advisor'],
                    'system': ['/settings', '/categories', '/scheduled-tasks']
                };
                
                for (const [group, patterns] of Object.entries(resourcePatterns)) {
                    if (patterns.some(pattern => currentPath.includes(pattern))) {
                        if (this.currentView !== group) {
                            this.currentView = group;
                            this.saveView();
                        }
                        return;
                    }
                }
            }
            
            // If no match found and we're at the root admin, go to main
            if (!matched && (currentPath === '/admin' || currentPath === '/admin/')) {
                this.currentView = 'main';
                this.saveView();
            }
        },
        
        showSubmenu(group) {
            if (this.isTransitioning) return;
            
            this.isTransitioning = true;
            this.previousView = this.currentView;
            
            // Add slight delay for smooth animation
            setTimeout(() => {
                this.currentView = group;
                this.saveView();
                this.isTransitioning = false;
            }, 150);
        },
        
        showMain() {
            if (this.isTransitioning) return;
            
            this.isTransitioning = true;
            this.previousView = this.currentView;
            
            setTimeout(() => {
                this.currentView = 'main';
                this.saveView();
                this.isTransitioning = false;
            }, 150);
        },
        
        saveView() {
            localStorage.setItem('slidingNavView', this.currentView);
        }
    }"
    class="fi-sidebar-nav flex-1 overflow-hidden"
>
    @php
        $navigation = \App\Filament\Support\SlidingNavigationBuilder::build();
        $mainMenu = $navigation['main'];
        $submenus = $navigation['submenus'];
    @endphp
    
    <!-- Navigation Container with Sliding Animation -->
    <div class="relative h-full overflow-hidden">
        
        <!-- Main Menu -->
        <div 
            x-show="currentView === 'main'"
            x-transition:enter="transition-transform duration-300 ease-out"
            x-transition:enter-start="transform translate-x-full"
            x-transition:enter-end="transform translate-x-0"
            x-transition:leave="transition-transform duration-300 ease-in"
            x-transition:leave-start="transform translate-x-0"
            x-transition:leave-end="transform -translate-x-full"
            class="absolute inset-0 flex flex-col py-4 space-y-2"
        >
            <div class="px-4 mb-4">
                <h2 class="text-sm font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">
                    Menu
                </h2>
            </div>
            
            @foreach($mainMenu as $groupKey => $group)
                <button
                    @click="showSubmenu('{{ $groupKey }}')"
                    class="mx-2 flex items-center justify-between px-4 py-3 text-left rounded-lg border border-transparent hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors group"
                >
                    <div class="flex items-center space-x-3">
                        <x-filament::icon 
                            :icon="$group['icon']" 
                            class="h-5 w-5 text-gray-500 dark:text-gray-400 group-hover:text-gray-700 dark:group-hover:text-gray-300"
                        />
                        <div>
                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ $group['label'] }}
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $group['description'] }}
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex items-center space-x-2">
                        @if($group['badge'])
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                @if($group['badge']['color'] === 'danger') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                @elseif($group['badge']['color'] === 'warning') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                @elseif($group['badge']['color'] === 'primary') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                @else bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200
                                @endif
                            ">
                                {{ $group['badge']['count'] }}
                            </span>
                        @endif
                        
                        <x-filament::icon 
                            icon="heroicon-m-chevron-right" 
                            class="h-4 w-4 text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-300"
                        />
                    </div>
                </button>
            @endforeach
        </div>
        
        <!-- Submenus -->
        @foreach($submenus as $groupKey => $submenu)
            <div 
                x-show="currentView === '{{ $groupKey }}'"
                x-transition:enter="transition-transform duration-300 ease-out"
                x-transition:enter-start="transform translate-x-full"
                x-transition:enter-end="transform translate-x-0"
                x-transition:leave="transition-transform duration-300 ease-in"
                x-transition:leave-start="transform translate-x-0"
                x-transition:leave-end="transform translate-x-full"
                class="absolute inset-0 flex flex-col py-4 space-y-1"
            >
                <!-- Back Button Header -->
                <div class="px-2 mb-4">
                    <button
                        @click="showMain()"
                        class="flex items-center space-x-2 px-2 py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors"
                    >
                        <x-filament::icon 
                            icon="heroicon-m-arrow-left" 
                            class="h-4 w-4"
                        />
                        <span>Back to Menu</span>
                    </button>
                    
                    <h2 class="mt-2 px-2 text-sm font-semibold text-gray-900 dark:text-white">
                        {{ $mainMenu[$groupKey]['label'] }}
                    </h2>
                </div>
                
                <!-- Submenu Items -->
                <div class="space-y-1">
                    @foreach($submenu['items'] as $item)
                        <a
                            href="{{ $item['url'] }}"
                            @class([
                                'mx-2 flex items-center space-x-3 px-3 py-2 text-sm rounded-lg transition-colors',
                                'bg-primary-100 dark:bg-primary-900 text-primary-700 dark:text-primary-300 font-medium' => $item['active'],
                                'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' => !$item['active'],
                            ])
                        >
                            <x-filament::icon 
                                :icon="$item['icon']" 
                                @class([
                                    'h-4 w-4',
                                    'text-primary-600 dark:text-primary-400' => $item['active'],
                                    'text-gray-500 dark:text-gray-400' => !$item['active'],
                                ])
                            />
                            
                            <span class="flex-1">{{ $item['label'] }}</span>
                            
                            @if(isset($item['badge']))
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                    @if($item['badge']['color'] === 'danger') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                    @elseif($item['badge']['color'] === 'warning') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                    @elseif($item['badge']['color'] === 'primary') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                    @else bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200
                                    @endif
                                ">
                                    {{ $item['badge']['count'] }}
                                </span>
                            @endif
                        </a>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</div>

<style>
/* Ensure transitions work smoothly */
.fi-sidebar-nav [x-cloak] { 
    display: none !important; 
}

/* Optional: Add subtle shadow during transitions */
.fi-sidebar-nav .absolute {
    box-shadow: 0 0 0 1px rgba(0, 0, 0, 0.05);
}
</style>