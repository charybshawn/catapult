<div 
    x-data="{
        currentView: localStorage.getItem('slidingNavView') || 'main',
        previousView: null,
        isTransitioning: false,
        expandedGroups: {},
        
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
                @foreach($submenu['items'] as $index => $item)
                    @if(isset($item['type']) && $item['type'] === 'group')
                        // Check group children
                        @foreach($item['children'] as $child)
                            if (currentPath.startsWith('{{ $child['url'] }}')) {
                                if (this.currentView !== '{{ $groupKey }}') {
                                    this.currentView = '{{ $groupKey }}';
                                    this.saveView();
                                }
                                // Auto-expand the group if we're on one of its children
                                this.expandedGroups['{{ $groupKey }}_{{ $index }}'] = true;
                                matched = true;
                                return;
                            }
                        @endforeach
                    @else
                        // Check regular items
                        if (currentPath.startsWith('{{ $item['url'] }}')) {
                            if (this.currentView !== '{{ $groupKey }}') {
                                this.currentView = '{{ $groupKey }}';
                                this.saveView();
                            }
                            matched = true;
                            return;
                        }
                    @endif
                @endforeach
            @endforeach
            
            // If no direct match, use simple path-based detection
            if (!matched) {
                const groupMapping = {
                    '/dashboard': 'dashboard',
                    '/daily-operations': 'dashboard',
                    '/weekly-planning': 'dashboard',
                    '/analytics': 'dashboard',
                    '/crops': 'production',
                    '/recipes': 'production',
                    '/crop-plans': 'production',
                    '/crop-alerts': 'production',
                    '/tasks': 'production',
                    '/orders': 'orders',
                    '/invoices': 'orders',
                    '/users': 'customers',
                    '/consumables': 'inventory',
                    '/packaging-types': 'inventory',
                    '/product-inventories': 'products',
                    '/seed-entries': 'inventory',
                    '/seed-scrape-uploader': 'inventory',
                    '/seed-price-trends': 'inventory',
                    '/seed-reorder-advisor': 'inventory',
                    '/suppliers': 'inventory',
                    '/master-seed-catalogs': 'inventory',
                    '/products': 'products',
                    '/product-mixes': 'products',
                    '/categories': 'products',
                    '/database-management': 'system'
                };
                
                for (const [path, group] of Object.entries(groupMapping)) {
                    if (currentPath.includes(path)) {
                        if (this.currentView !== group) {
                            this.currentView = group;
                            this.saveView();
                        }
                        
                        // Auto-expand Online Seed Pricing group
                        if (group === 'inventory' && (currentPath.includes('/seed-entries') || currentPath.includes('/seed-scrape-uploader') || currentPath.includes('/seed-price-trends') || currentPath.includes('/seed-reorder-advisor'))) {
                            this.expandedGroups['inventory_1'] = true;
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
        },
        
        toggleGroup(groupKey) {
            this.expandedGroups[groupKey] = !this.expandedGroups[groupKey];
        },
        
        isGroupExpanded(groupKey) {
            return this.expandedGroups[groupKey] || false;
        }
    }"
    class="fi-sidebar-nav flex-1 overflow-hidden"
>
    @php
        $navigation = \App\Filament\Support\SimpleNavigationBuilder::build();
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
                    
                    <x-filament::icon 
                        icon="heroicon-m-chevron-right" 
                        class="h-4 w-4 text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-300"
                    />
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
                    @foreach($submenu['items'] as $index => $item)
                        @if(isset($item['type']) && $item['type'] === 'group')
                            {{-- Group with dropdown --}}
                            <div class="mx-2">
                                <button
                                    @click="toggleGroup('{{ $groupKey }}_{{ $index }}')"
                                    class="w-full flex items-center justify-between px-3 py-2 text-sm rounded-lg transition-colors text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800"
                                >
                                    <div class="flex items-center space-x-3">
                                        <x-filament::icon 
                                            :icon="$item['icon']" 
                                            class="h-4 w-4 text-gray-500 dark:text-gray-400"
                                        />
                                        <span>{{ $item['label'] }}</span>
                                    </div>
                                    
                                    <x-filament::icon 
                                        icon="heroicon-m-chevron-down" 
                                        class="h-4 w-4 text-gray-400 transition-transform duration-200"
                                        ::class="{ 'rotate-180': isGroupExpanded('{{ $groupKey }}_{{ $index }}') }"
                                    />
                                </button>
                                
                                {{-- Dropdown children --}}
                                <div 
                                    x-show="isGroupExpanded('{{ $groupKey }}_{{ $index }}')"
                                    x-transition:enter="transition ease-out duration-200"
                                    x-transition:enter-start="opacity-0 -translate-y-2"
                                    x-transition:enter-end="opacity-100 translate-y-0"
                                    x-transition:leave="transition ease-in duration-150"
                                    x-transition:leave-start="opacity-100 translate-y-0"
                                    x-transition:leave-end="opacity-0 -translate-y-2"
                                    class="mt-1 space-y-1"
                                >
                                    @foreach($item['children'] as $child)
                                        <a
                                            href="{{ $child['url'] }}"
                                            @class([
                                                'flex items-center space-x-3 px-3 py-2 ml-6 text-sm rounded-lg transition-colors',
                                                'bg-primary-100 dark:bg-primary-900 text-primary-700 dark:text-primary-300 font-medium' => $child['active'],
                                                'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800/50 hover:text-gray-900 dark:hover:text-gray-200' => !$child['active'],
                                            ])
                                        >
                                            <x-filament::icon 
                                                :icon="$child['icon']" 
                                                @class([
                                                    'h-3 w-3',
                                                    'text-primary-600 dark:text-primary-400' => $child['active'],
                                                    'text-gray-400 dark:text-gray-500' => !$child['active'],
                                                ])
                                            />
                                            
                                            <span class="flex-1">{{ $child['label'] }}</span>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            {{-- Regular menu item --}}
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
                            </a>
                        @endif
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