<style>
    /* Navigation collapse/expand styles */
    .fi-sidebar-nav-groups .fi-sidebar-nav-group[data-collapsed="true"] .fi-sidebar-nav-group-items {
        display: none !important;
    }
    
    .fi-sidebar-nav-groups .fi-sidebar-nav-group[data-collapsed="true"] .fi-sidebar-nav-group-label {
        opacity: 0.7;
    }
    
    .fi-sidebar-nav-groups .fi-sidebar-nav-group[data-collapsed="true"] .fi-sidebar-nav-group-label::after {
        content: " ▶";
        font-size: 0.75rem;
        margin-left: 0.25rem;
    }
    
    .fi-sidebar-nav-groups .fi-sidebar-nav-group[data-collapsed="false"] .fi-sidebar-nav-group-label::after {
        content: " ▼";
        font-size: 0.75rem;
        margin-left: 0.25rem;
    }
    
    .fi-sidebar-nav-group-label {
        cursor: pointer;
        user-select: none;
        transition: opacity 0.2s ease;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    
    .fi-sidebar-nav-group-label:hover {
        opacity: 0.8;
    }
    
    /* Collapse all button */
    .nav-collapse-toggle {
        position: relative;
        width: 100%;
        padding: 0.5rem 1rem;
        margin-bottom: 0.5rem;
        background: rgba(255, 255, 255, 0.05);
        border: none;
        border-radius: 0.375rem;
        color: rgba(255, 255, 255, 0.7);
        font-size: 0.75rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    
    .nav-collapse-toggle:hover {
        background: rgba(255, 255, 255, 0.1);
        color: rgba(255, 255, 255, 0.9);
    }
    
    .nav-collapse-toggle-icon {
        transition: transform 0.2s ease;
    }
    
    .nav-collapse-toggle[data-all-collapsed="true"] .nav-collapse-toggle-icon {
        transform: rotate(180deg);
    }
    
    .nav-toggle-loading {
        opacity: 0.5;
        pointer-events: none;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const NavigationState = {
        // API endpoints
        endpoints: {
            get: '/api/navigation-preferences',
            toggleGroup: '/api/navigation-preferences/toggle-group',
            toggleAll: '/api/navigation-preferences/toggle-all'
        },
        
        // Initialize navigation state management
        init() {
            console.log('NavigationState.init() called');
            
            // Debug: Check what elements are available
            console.log('Available elements:');
            console.log('- .fi-sidebar-nav:', !!document.querySelector('.fi-sidebar-nav'));
            console.log('- .fi-sidebar-nav-groups:', !!document.querySelector('.fi-sidebar-nav-groups'));
            console.log('- .fi-sidebar-nav-group:', document.querySelectorAll('.fi-sidebar-nav-group').length);
            
            // Small delay to ensure DOM is fully loaded
            setTimeout(() => {
                console.log('After timeout - Available elements:');
                console.log('- .fi-sidebar-nav:', !!document.querySelector('.fi-sidebar-nav'));
                console.log('- .fi-sidebar-nav-groups:', !!document.querySelector('.fi-sidebar-nav-groups'));
                console.log('- .fi-sidebar-nav-group:', document.querySelectorAll('.fi-sidebar-nav-group').length);
                
                // Debug: Show the actual navigation structure
                const sidebar = document.querySelector('.fi-sidebar, nav, .sidebar');
                if (sidebar) {
                    console.log('Navigation structure preview:');
                    console.log(sidebar.innerHTML.substring(0, 1000));
                    
                    // Look for any elements with "group" in their class or attributes
                    const groupElements = sidebar.querySelectorAll('*[class*="group"], *[data*="group"], *[role*="group"]');
                    console.log('Elements with "group" in attributes:', groupElements.length);
                    groupElements.forEach((el, i) => {
                        if (i < 5) { // Show first 5
                            console.log(`  ${i}: ${el.tagName}.${el.className} - ${el.textContent?.trim().substring(0, 30)}`);
                        }
                    });
                }
                
                this.loadPreferences();
                this.setupEventListeners();
                this.addCollapseToggle();
            }, 500); // Increased delay
        },
        
        // Load user preferences from server
        async loadPreferences() {
            try {
                const response = await fetch(this.endpoints.get, {
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                
                if (response.ok) {
                    const data = await response.json();
                    this.applyPreferences(data.navigation);
                }
            } catch (error) {
                console.warn('Could not load navigation preferences:', error);
            }
        },
        
        // Apply preferences to the navigation
        applyPreferences(prefs) {
            console.log('Applying preferences:', prefs);
            const collapsedGroups = prefs.collapsed_groups || {};
            const allCollapsed = prefs.all_collapsed || false;
            
            console.log('Collapsed groups:', collapsedGroups);
            console.log('All collapsed:', allCollapsed);
            
            // Apply individual group states
            Object.entries(collapsedGroups).forEach(([groupName, isCollapsed]) => {
                console.log('Setting group state:', groupName, isCollapsed);
                this.setGroupState(groupName, isCollapsed, false);
            });
            
            // Update toggle button state
            this.updateToggleButton(allCollapsed);
        },
        
        // Set up event listeners for group labels
        setupEventListeners() {
            // Use event delegation for dynamically added elements
            document.addEventListener('click', (e) => {
                const groupLabel = e.target.closest('.fi-sidebar-nav-group-label');
                if (groupLabel && !groupLabel.closest('.nav-collapse-toggle')) {
                    e.preventDefault();
                    this.handleGroupClick(groupLabel);
                }
            });
        },
        
        // Handle clicking on a group label
        handleGroupClick(groupLabel) {
            const group = groupLabel.closest('.fi-sidebar-nav-group');
            if (!group) return;
            
            const groupName = groupLabel.textContent.trim().replace(/[▶▼]/g, '').trim();
            const isCurrentlyCollapsed = group.getAttribute('data-collapsed') === 'true';
            const newState = !isCurrentlyCollapsed;
            
            this.setGroupState(groupName, newState, true);
            this.saveGroupState(groupName, newState);
        },
        
        // Set the visual state of a group
        setGroupState(groupName, collapsed, animate = true) {
            // Try multiple selectors for navigation groups
            const groupSelectors = [
                '.fi-sidebar-nav-group',
                '.fi-sidebar-nav .fi-nav-group',
                '.fi-nav-group',
                '[data-nav-group]',
                'li[role="group"]',
                '.filament-sidebar-nav-group',
                'nav ul li',
                '.fi-sidebar nav > ul > li'
            ];
            
            let groups = [];
            for (const selector of groupSelectors) {
                groups = document.querySelectorAll(selector);
                if (groups.length > 0) {
                    console.log('Found', groups.length, 'navigation groups with selector:', selector);
                    break;
                }
            }
            
            if (groups.length === 0) {
                console.warn('No navigation groups found with any selector');
                // Try to find any navigation structure
                const nav = document.querySelector('nav, .fi-sidebar, .sidebar');
                if (nav) {
                    console.log('Found navigation container:', nav.className);
                    console.log('Navigation HTML:', nav.innerHTML.substring(0, 500));
                }
                return;
            }
            
            let found = false;
            groups.forEach((group, index) => {
                // Try multiple selectors for group labels
                const labelSelectors = [
                    '.fi-sidebar-nav-group-label',
                    '.fi-nav-group-label', 
                    '.nav-group-label',
                    'button[role="button"]',
                    'summary',
                    'h3',
                    'h4',
                    '.font-medium',
                    'span:first-child'
                ];
                
                let label = null;
                for (const labelSelector of labelSelectors) {
                    label = group.querySelector(labelSelector);
                    if (label) break;
                }
                
                if (!label) {
                    // Try to get the first text node or any text content
                    label = group.firstElementChild || group;
                    console.log('Group', index, 'fallback text:', label.textContent?.trim().substring(0, 50));
                }
                
                if (label) {
                    const labelText = label.textContent.trim().replace(/[▶▼]/g, '').trim();
                    console.log('Group', index, 'label text:', labelText, 'comparing to:', groupName);
                    
                    if (labelText === groupName || labelText.includes(groupName) || groupName.includes(labelText)) {
                        found = true;
                        console.log('Setting group', groupName, 'to collapsed:', collapsed);
                        group.setAttribute('data-collapsed', collapsed);
                        
                        if (animate) {
                            // Try multiple selectors for group items
                            const itemSelectors = [
                                '.fi-sidebar-nav-group-items',
                                '.fi-nav-group-items',
                                'ul',
                                '.nav-items',
                                'div[role="group"]'
                            ];
                            
                            let items = null;
                            for (const itemSelector of itemSelectors) {
                                items = group.querySelector(itemSelector);
                                if (items) break;
                            }
                            
                            if (items) {
                                if (collapsed) {
                                    items.style.display = 'none';
                                } else {
                                    items.style.display = '';
                                }
                            }
                        }
                        return;
                    }
                }
            });
            
            if (!found) {
                console.warn('Could not find group:', groupName);
                console.log('Available groups:');
                groups.forEach((group, index) => {
                    const text = group.textContent?.trim().substring(0, 50);
                    console.log(`  ${index}: ${text}`);
                });
            }
        },
        
        // Save group state to server
        async saveGroupState(groupName, collapsed) {
            try {
                await fetch(this.endpoints.toggleGroup, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        group: groupName,
                        collapsed: collapsed
                    })
                });
            } catch (error) {
                console.warn('Could not save navigation state:', error);
            }
        },
        
        // Add collapse/expand all button
        addCollapseToggle() {
            // Try multiple selectors to find the sidebar navigation
            const selectors = [
                '.fi-sidebar-nav',
                '.fi-sidebar nav', 
                '[data-sidebar-navigation]',
                '.fi-sidebar .fi-sidebar-nav',
                '.fi-nav',
                'nav[role="navigation"]',
                '.fi-sidebar',
                '.sidebar',
                'nav',
                'aside nav',
                '.fi-sidebar > div'
            ];
            
            let sidebar = null;
            for (const selector of selectors) {
                sidebar = document.querySelector(selector);
                if (sidebar) {
                    console.log('Found sidebar with selector:', selector);
                    break;
                }
            }
            
            if (!sidebar) {
                console.warn('Could not find sidebar navigation element');
                // Try to find navigation groups directly
                const navGroups = document.querySelector('.fi-sidebar-nav-groups');
                if (navGroups) {
                    sidebar = navGroups.parentElement;
                    console.log('Found sidebar via navigation groups');
                } else {
                    console.warn('Could not find navigation groups either');
                    return;
                }
            }
            
            // Don't add if already exists
            if (document.querySelector('.nav-collapse-toggle')) {
                console.log('Toggle button already exists');
                return;
            }
            
            const toggleButton = document.createElement('button');
            toggleButton.className = 'nav-collapse-toggle';
            toggleButton.innerHTML = `
                <span>Collapse All Groups</span>
                <span class="nav-collapse-toggle-icon">▼</span>
            `;
            
            toggleButton.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                console.log('Toggle all button clicked');
                this.toggleAllGroups();
            });
            
            // Insert at the beginning of the sidebar nav
            if (sidebar.firstChild) {
                sidebar.insertBefore(toggleButton, sidebar.firstChild);
            } else {
                sidebar.appendChild(toggleButton);
            }
            
            console.log('Toggle button added successfully');
        },
        
        // Toggle all groups at once
        async toggleAllGroups() {
            console.log('toggleAllGroups called');
            const button = document.querySelector('.nav-collapse-toggle');
            if (!button) {
                console.error('Toggle button not found');
                return;
            }
            
            button.classList.add('nav-toggle-loading');
            
            const isCurrentlyAllCollapsed = button.getAttribute('data-all-collapsed') === 'true';
            const newState = !isCurrentlyAllCollapsed;
            
            console.log('Current state:', isCurrentlyAllCollapsed, 'New state:', newState);
            
            try {
                console.log('Making API request to:', this.endpoints.toggleAll);
                const response = await fetch(this.endpoints.toggleAll, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        collapsed: newState
                    })
                });
                
                console.log('API response status:', response.status);
                
                if (response.ok) {
                    const data = await response.json();
                    console.log('API response data:', data);
                    this.applyPreferences(data.navigation);
                } else {
                    const errorText = await response.text();
                    console.error('API request failed:', response.status, errorText);
                }
            } catch (error) {
                console.error('Could not toggle all groups:', error);
            } finally {
                button.classList.remove('nav-toggle-loading');
            }
        },
        
        // Update toggle button state
        updateToggleButton(allCollapsed) {
            const button = document.querySelector('.nav-collapse-toggle');
            if (!button) return;
            
            button.setAttribute('data-all-collapsed', allCollapsed);
            const text = button.querySelector('span:first-child');
            if (text) {
                text.textContent = allCollapsed ? 'Expand All Groups' : 'Collapse All Groups';
            }
        }
    };
    
    // Initialize the navigation state management
    NavigationState.init();
    
    // Reinitialize when Livewire navigates (for SPA-like navigation)
    document.addEventListener('livewire:navigated', function() {
        NavigationState.init();
    });
});
</script>