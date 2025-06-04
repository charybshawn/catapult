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
            this.loadPreferences();
            this.setupEventListeners();
            this.addCollapseToggle();
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
            const collapsedGroups = prefs.collapsed_groups || {};
            const allCollapsed = prefs.all_collapsed || false;
            
            // Apply individual group states
            Object.entries(collapsedGroups).forEach(([groupName, isCollapsed]) => {
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
            
            groups.forEach(group => {
                const label = group.querySelector('.fi-sidebar-nav-group-label');
                if (!label) return;
                
                const labelText = label.textContent.trim().replace(/[▶▼]/g, '').trim();
                if (labelText === groupName) {
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
            const sidebar = document.querySelector('.fi-sidebar-nav');
            if (!sidebar) return;
            
            // Don't add if already exists
            if (sidebar.querySelector('.nav-collapse-toggle')) return;
            
            const toggleButton = document.createElement('button');
            toggleButton.className = 'nav-collapse-toggle';
            toggleButton.innerHTML = `
                <span>Collapse All Groups</span>
                <span class="nav-collapse-toggle-icon">▼</span>
            `;
            
            toggleButton.addEventListener('click', () => this.toggleAllGroups());
            
            // Insert at the beginning of the sidebar nav
            sidebar.insertBefore(toggleButton, sidebar.firstChild);
        },
        
        // Toggle all groups at once
        async toggleAllGroups() {
            const button = document.querySelector('.nav-collapse-toggle');
            if (!button) return;
            
            button.classList.add('nav-toggle-loading');
            
            const isCurrentlyAllCollapsed = button.getAttribute('data-all-collapsed') === 'true';
            const newState = !isCurrentlyAllCollapsed;
            
            try {
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
                
                if (response.ok) {
                    const data = await response.json();
                    this.applyPreferences(data.navigation);
                }
            } catch (error) {
                console.warn('Could not toggle all groups:', error);
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