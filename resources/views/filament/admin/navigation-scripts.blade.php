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
            const groups = document.querySelectorAll('.fi-sidebar-nav-group');
            console.log('Found', groups.length, 'navigation groups');
            
            let found = false;
            groups.forEach(group => {
                const label = group.querySelector('.fi-sidebar-nav-group-label');
                if (!label) {
                    console.log('No label found in group');
                    return;
                }
                
                const labelText = label.textContent.trim().replace(/[▶▼]/g, '').trim();
                console.log('Checking group label:', labelText, 'against', groupName);
                
                if (labelText === groupName) {
                    found = true;
                    console.log('Setting group', groupName, 'to collapsed:', collapsed);
                    group.setAttribute('data-collapsed', collapsed);
                    
                    if (animate) {
                        const items = group.querySelector('.fi-sidebar-nav-group-items');
                        if (items) {
                            if (collapsed) {
                                items.style.display = 'none';
                            } else {
                                items.style.display = '';
                            }
                        }
                    }
                }
            });
            
            if (!found) {
                console.warn('Could not find group:', groupName);
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
                'nav[role="navigation"]'
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