<x-filament-panels::page>
    <div 
        x-data="{ 
            showCropDetails: false, 
            cropData: null,
            async loadCropDetails(cropId) {
                console.log('Loading crop details for ID:', cropId);
                try {
                    const response = await fetch(`/admin/crops/${cropId}/details`);
                    if (!response.ok) {
                        throw new Error('Failed to fetch crop details');
                    }
                    this.cropData = await response.json();
                    this.showCropDetails = true;
                    console.log('Crop data loaded:', this.cropData);
                } catch (error) {
                    console.error('Error loading crop details:', error);
                    alert('Failed to load crop details: ' + error.message);
                }
            }
        }"
        data-csrf="{{ csrf_token() }}">
        
        {{ $this->table }}
        
        @include('filament.components.crop-details-modal')
    </div>
    
    <script>
        // Make loadCropDetails available globally for the table actions
        window.loadCropDetails = function(cropId) {
            console.log('loadCropDetails called with ID:', cropId);
            // Find the Alpine component and call its method
            const alpineComponent = document.querySelector('[x-data*="loadCropDetails"]')?._x_dataStack?.[0];
            if (alpineComponent && alpineComponent.loadCropDetails) {
                alpineComponent.loadCropDetails(cropId);
            } else {
                console.error('Alpine component not found');
                // Fallback: try to find the component via Alpine
                const element = document.querySelector('[x-data*="loadCropDetails"]');
                if (element && element._x_dataStack && element._x_dataStack[0]) {
                    element._x_dataStack[0].loadCropDetails(cropId);
                } else {
                    console.error('Could not find Alpine component with loadCropDetails method');
                }
            }
        };

        // Add click handlers to table rows after the table loads
        document.addEventListener('DOMContentLoaded', function() {
            // Use a MutationObserver to detect when the table is loaded/updated
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'childList') {
                        addRowClickHandlers();
                    }
                });
            });

            // Start observing
            const tableContainer = document.querySelector('.fi-ta-table');
            if (tableContainer) {
                observer.observe(tableContainer, { childList: true, subtree: true });
                addRowClickHandlers(); // Add handlers to existing rows
            }

            function addRowClickHandlers() {
                // Find all table rows that have data-row-key (Filament's row identifier)
                const rows = document.querySelectorAll('[data-row-key]');
                rows.forEach(function(row) {
                    // Skip if already has click handler
                    if (row.hasAttribute('data-click-handler-added')) {
                        return;
                    }
                    
                    row.setAttribute('data-click-handler-added', 'true');
                    row.style.cursor = 'pointer';
                    
                    row.addEventListener('click', function(e) {
                        // Don't trigger if clicking on an action button
                        if (e.target.closest('.fi-ta-actions') || e.target.closest('button') || e.target.closest('a')) {
                            return;
                        }
                        
                        // Extract the record ID from the row's data attribute or URL
                        const rowKey = this.getAttribute('data-row-key');
                        if (rowKey) {
                            console.log('Row clicked, ID:', rowKey);
                            loadCropDetails(rowKey);
                        }
                    });
                });
            }
        });
    </script>
</x-filament-panels::page>