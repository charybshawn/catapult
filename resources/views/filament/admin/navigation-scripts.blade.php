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
    console.log('Navigation script loaded');
    
    // Simple approach: Just observe the DOM and work with what we find
    setTimeout(() => {
        console.log('Starting navigation enhancement...');
        
        // First, let's see what we're actually working with
        const body = document.body;
        console.log('Page HTML preview:', body.innerHTML.substring(0, 500));
        
        // Look for any navigation elements
        const allNavElements = document.querySelectorAll('nav, aside, .sidebar, [class*="sidebar"], [class*="nav"]');
        console.log('Found navigation-like elements:', allNavElements.length);
        
        allNavElements.forEach((el, i) => {
            console.log(`Nav element ${i}:`, el.tagName, el.className);
        });
        
        // Look for any list items that might be navigation groups
        const allLists = document.querySelectorAll('ul, ol');
        console.log('Found list elements:', allLists.length);
        
        allLists.forEach((list, i) => {
            if (i < 3) { // Check first 3 lists
                console.log(`List ${i}:`, list.className, 'items:', list.children.length);
                if (list.children.length > 0) {
                    console.log('  First item:', list.children[0].textContent?.trim().substring(0, 50));
                }
            }
        });
        
        // Now try the actual implementation
        NavigationCollapse.init();
        
    }, 1000);
});

const NavigationCollapse = {
    init() {
        console.log('NavigationCollapse.init() starting...');
        
        // Try to add collapse toggle button
        this.addToggleButton();
        
        // Set up click listeners for existing navigation
        this.setupClickListeners();
        
        // Load saved preferences
        this.loadPreferences();
    },
    
    addToggleButton() {
        console.log('Adding toggle button...');
        
        // Find a good place to put the button
        const targets = [
            '.fi-sidebar',
            'aside', 
            'nav',
            '[class*="sidebar"]'
        ];
        
        let container = null;
        for (const selector of targets) {
            container = document.querySelector(selector);
            if (container) {
                console.log('Found container for button:', selector);
                break;
            }
        }
        
        if (!container) {
            console.log('No suitable container found for toggle button');
            return;
        }
        
        // Don't add if already exists
        if (container.querySelector('.nav-toggle-all')) {
            console.log('Toggle button already exists');
            return;
        }
        
        const button = document.createElement('button');
        button.className = 'nav-toggle-all';
        button.style.cssText = `
            width: 100%;
            padding: 8px 16px;
            margin: 8px 0;
            background: rgba(0,0,0,0.1);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 6px;
            color: rgba(255,255,255,0.8);
            font-size: 12px;
            cursor: pointer;
            transition: all 0.2s;
        `;
        button.innerHTML = 'Toggle All Navigation Groups';
        
        button.addEventListener('click', () => {
            console.log('Toggle all button clicked!');
            this.toggleAllGroups();
        });
        
        // Insert at the top
        if (container.firstChild) {
            container.insertBefore(button, container.firstChild);
        } else {
            container.appendChild(button);
        }
        
        console.log('Toggle button added successfully');
    },
    
    setupClickListeners() {
        console.log('Setting up click listeners...');
        
        // Find text that looks like our navigation groups
        const groupNames = [
            'Dashboard & Overview',
            'Production Management', 
            'Seed Management',
            'Inventory & Materials',
            'Sales & Products',
            'Order Management',
            'Analytics & Reports',
            'System & Settings'
        ];
        
        // Look for elements containing these group names
        groupNames.forEach(groupName => {
            const elements = Array.from(document.querySelectorAll('*')).filter(el => {
                const text = el.textContent?.trim();
                return text === groupName || text?.includes(groupName);
            });
            
            console.log(`Found ${elements.length} elements for group "${groupName}"`);
            
            elements.forEach(el => {
                // Make it clickable if it's not already
                if (!el.hasAttribute('data-nav-toggle')) {
                    el.setAttribute('data-nav-toggle', groupName);
                    el.style.cursor = 'pointer';
                    
                    el.addEventListener('click', (e) => {
                        console.log('Group clicked:', groupName);
                        this.toggleGroup(groupName, el);
                    });
                }
            });
        });
    },
    
    toggleGroup(groupName, element) {
        console.log('Toggling group:', groupName);
        
        // Find items to collapse - look for siblings or children that are lists
        const parent = element.closest('li, div, section');
        if (!parent) return;
        
        const items = parent.querySelector('ul, ol, .items, [class*="items"]');
        if (items) {
            const isHidden = items.style.display === 'none';
            items.style.display = isHidden ? '' : 'none';
            
            // Update visual indicator
            if (!isHidden) {
                element.setAttribute('data-collapsed', 'true');
                if (!element.textContent.includes('▶')) {
                    element.textContent = element.textContent + ' ▶';
                }
            } else {
                element.setAttribute('data-collapsed', 'false');
                element.textContent = element.textContent.replace(' ▶', '').replace(' ▼', '');
                if (!element.textContent.includes('▼')) {
                    element.textContent = element.textContent + ' ▼';
                }
            }
            
            // Save to backend
            this.saveGroupState(groupName, !isHidden);
        }
    },
    
    async toggleAllGroups() {
        console.log('Toggle all groups called');
        
        try {
            const response = await fetch('/api/navigation-preferences/toggle-all', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                },
                body: JSON.stringify({
                    collapsed: true // Always collapse for now
                })
            });
            
            if (response.ok) {
                const data = await response.json();
                console.log('Toggle all response:', data);
                
                // Apply the changes to all groups
                const collapsedGroups = data.navigation?.collapsed_groups || {};
                Object.entries(collapsedGroups).forEach(([groupName, isCollapsed]) => {
                    this.applyGroupState(groupName, isCollapsed);
                });
            } else {
                console.error('Toggle all failed:', response.status);
            }
        } catch (error) {
            console.error('Toggle all error:', error);
        }
    },
    
    applyGroupState(groupName, collapsed) {
        console.log('Applying state:', groupName, collapsed);
        
        // Find all elements with this group name
        const elements = document.querySelectorAll(`[data-nav-toggle="${groupName}"]`);
        elements.forEach(el => {
            const parent = el.closest('li, div, section');
            if (parent) {
                const items = parent.querySelector('ul, ol, .items, [class*="items"]');
                if (items) {
                    items.style.display = collapsed ? 'none' : '';
                    el.setAttribute('data-collapsed', collapsed);
                    
                    // Update visual
                    el.textContent = el.textContent.replace(' ▶', '').replace(' ▼', '');
                    el.textContent = el.textContent + (collapsed ? ' ▶' : ' ▼');
                }
            }
        });
    },
    
    async saveGroupState(groupName, collapsed) {
        try {
            await fetch('/api/navigation-preferences/toggle-group', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                },
                body: JSON.stringify({
                    group: groupName,
                    collapsed: collapsed
                })
            });
        } catch (error) {
            console.warn('Could not save group state:', error);
        }
    },
    
    async loadPreferences() {
        try {
            const response = await fetch('/api/navigation-preferences', {
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                }
            });
            
            if (response.ok) {
                const data = await response.json();
                console.log('Loaded preferences:', data);
                
                const collapsedGroups = data.navigation?.collapsed_groups || {};
                Object.entries(collapsedGroups).forEach(([groupName, isCollapsed]) => {
                    this.applyGroupState(groupName, isCollapsed);
                });
            }
        } catch (error) {
            console.warn('Could not load preferences:', error);
        }
    }
};
</script>