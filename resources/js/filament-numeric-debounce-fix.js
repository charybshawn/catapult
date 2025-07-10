// Fix for Livewire numeric input debouncing issues
document.addEventListener('DOMContentLoaded', function() {
    // Function to fix numeric input debouncing
    function fixNumericInputDebouncing() {
        const numericInputs = document.querySelectorAll('input[type="number"], input[inputmode="numeric"], input[inputmode="decimal"]');
        
        numericInputs.forEach(input => {
            // Skip if already processed
            if (input.hasAttribute('data-debounce-fixed')) {
                return;
            }
            
            // Mark as processed
            input.setAttribute('data-debounce-fixed', 'true');
            
            let timeout;
            let lastValue = input.value;
            
            // Store the original Livewire event handlers
            const originalInput = input.oninput;
            const originalChange = input.onchange;
            
            // Override the input handler with debouncing
            input.oninput = function(e) {
                clearTimeout(timeout);
                
                // Only trigger Livewire updates after user stops typing
                timeout = setTimeout(() => {
                    if (originalInput && typeof originalInput === 'function') {
                        originalInput.call(this, e);
                    }
                    
                    // Trigger Livewire update if value changed
                    if (this.value !== lastValue) {
                        this.dispatchEvent(new Event('change', { bubbles: true }));
                        lastValue = this.value;
                    }
                }, 800); // 800ms delay
            };
            
            // Ensure change events still work
            input.onchange = function(e) {
                clearTimeout(timeout);
                lastValue = this.value;
                
                if (originalChange && typeof originalChange === 'function') {
                    originalChange.call(this, e);
                }
            };
        });
    }
    
    // Fix existing inputs
    fixNumericInputDebouncing();
    
    // Fix inputs that are added dynamically (e.g., repeater fields)
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'childList') {
                mutation.addedNodes.forEach(function(node) {
                    if (node.nodeType === Node.ELEMENT_NODE) {
                        // Check if the added node contains numeric inputs
                        const newInputs = node.querySelectorAll ? 
                            node.querySelectorAll('input[type="number"], input[inputmode="numeric"], input[inputmode="decimal"]') : 
                            [];
                        
                        if (newInputs.length > 0) {
                            fixNumericInputDebouncing();
                        }
                    }
                });
            }
        });
    });
    
    // Start observing
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
});

// Also handle Livewire page updates
document.addEventListener('livewire:load', function () {
    setTimeout(() => {
        const script = document.querySelector('script[data-debounce-fix]');
        if (!script) {
            const newScript = document.createElement('script');
            newScript.setAttribute('data-debounce-fix', 'true');
            newScript.textContent = 'document.dispatchEvent(new Event("DOMContentLoaded"));';
            document.head.appendChild(newScript);
        }
    }, 100);
});