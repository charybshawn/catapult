/**
 * Development debugging utilities for Ajax, Livewire, and DOM issues
 */

class DevDebugger {
    constructor() {
        this.isDebugMode = this.checkDebugMode();
        this.initializeDebugTools();
    }

    checkDebugMode() {
        // Check if we're in development mode
        return window.location.hostname === 'localhost' || 
               window.location.hostname.includes('.test') ||
               window.location.hostname.includes('127.0.0.1') ||
               localStorage.getItem('debug_mode') === 'true';
    }

    initializeDebugTools() {
        if (!this.isDebugMode) return;

        this.logMessage('🔧 Development debug tools initialized');
        this.interceptAjaxRequests();
        this.monitorLivewireEvents();
        this.addGlobalErrorHandling();
        this.createDebugPanel();
    }

    logMessage(message, data = null) {
        if (!this.isDebugMode) return;
        
        const timestamp = new Date().toLocaleTimeString();
        console.log(`[${timestamp}] ${message}`, data || '');
    }

    interceptAjaxRequests() {
        // Intercept fetch requests
        const originalFetch = window.fetch;
        window.fetch = (...args) => {
            this.logMessage('🌐 AJAX Request:', args[0]);
            return originalFetch(...args)
                .then(response => {
                    this.logMessage('✅ AJAX Response:', { 
                        url: args[0], 
                        status: response.status 
                    });
                    return response;
                })
                .catch(error => {
                    this.logMessage('❌ AJAX Error:', { 
                        url: args[0], 
                        error: error.message 
                    });
                    throw error;
                });
        };

        // Intercept XMLHttpRequest
        const originalXHR = window.XMLHttpRequest;
        window.XMLHttpRequest = function() {
            const xhr = new originalXHR();
            const originalOpen = xhr.open;
            const originalSend = xhr.send;

            xhr.open = function(method, url, ...args) {
                this._method = method;
                this._url = url;
                return originalOpen.apply(this, [method, url, ...args]);
            };

            xhr.send = function(data) {
                window.devDebugger?.logMessage('🌐 XHR Request:', { 
                    method: this._method, 
                    url: this._url,
                    data: data 
                });

                this.addEventListener('load', () => {
                    window.devDebugger?.logMessage('✅ XHR Response:', { 
                        url: this._url, 
                        status: this.status,
                        response: this.responseText?.substring(0, 100) + '...'
                    });
                });

                this.addEventListener('error', () => {
                    window.devDebugger?.logMessage('❌ XHR Error:', { 
                        url: this._url, 
                        status: this.status 
                    });
                });

                return originalSend.apply(this, arguments);
            };

            return xhr;
        };
    }

    monitorLivewireEvents() {
        // Monitor Livewire events if Livewire is present
        document.addEventListener('livewire:init', () => {
            this.logMessage('🔄 Livewire initialized');
            
            // Hook into Livewire events
            Livewire.hook('request', ({ uri, options, payload, respond, succeed, fail }) => {
                this.logMessage('🔄 Livewire Request:', { uri, payload });
                
                succeed(({ status, response }) => {
                    this.logMessage('✅ Livewire Success:', { uri, status });
                });
                
                fail(({ status, content, preventDefault }) => {
                    this.logMessage('❌ Livewire Fail:', { uri, status, content });
                });
            });

            Livewire.hook('component.init', ({ component }) => {
                this.logMessage('🧩 Livewire Component Init:', component.name);
            });

            Livewire.hook('element.updated', ({ el, component }) => {
                this.logMessage('🔄 Livewire Element Updated:', {
                    component: component.name,
                    element: el.tagName
                });
            });
        });

        // Also listen for Livewire events on document
        document.addEventListener('livewire:navigating', (event) => {
            this.logMessage('🧭 Livewire Navigating:', event.detail);
        });

        document.addEventListener('livewire:navigated', (event) => {
            this.logMessage('🎯 Livewire Navigated:', event.detail);
        });
    }

    addGlobalErrorHandling() {
        window.addEventListener('error', (event) => {
            this.logMessage('🔥 JavaScript Error:', {
                message: event.message,
                filename: event.filename,
                lineno: event.lineno,
                colno: event.colno,
                stack: event.error?.stack
            });
        });

        window.addEventListener('unhandledrejection', (event) => {
            this.logMessage('🔥 Unhandled Promise Rejection:', {
                reason: event.reason,
                promise: event.promise
            });
        });
    }

    createDebugPanel() {
        // Create a simple debug panel
        const panel = document.createElement('div');
        panel.id = 'dev-debug-panel';
        panel.style.cssText = `
            position: fixed;
            top: 10px;
            right: 10px;
            background: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 10px;
            border-radius: 5px;
            font-family: monospace;
            font-size: 12px;
            z-index: 10000;
            max-width: 300px;
            max-height: 200px;
            overflow-y: auto;
            display: none;
        `;

        // Add toggle button
        const toggleBtn = document.createElement('button');
        toggleBtn.textContent = '🔧';
        toggleBtn.style.cssText = `
            position: fixed;
            top: 10px;
            right: 10px;
            background: #007cba;
            color: white;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            font-size: 16px;
            cursor: pointer;
            z-index: 10001;
        `;

        toggleBtn.onclick = () => {
            panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
        };

        document.body.appendChild(panel);
        document.body.appendChild(toggleBtn);

        // Store reference for logging
        this.debugPanel = panel;
    }

    addToDebugPanel(message) {
        if (!this.debugPanel) return;
        
        const timestamp = new Date().toLocaleTimeString();
        const logEntry = document.createElement('div');
        logEntry.style.marginBottom = '5px';
        logEntry.textContent = `${timestamp}: ${message}`;
        
        this.debugPanel.appendChild(logEntry);
        this.debugPanel.scrollTop = this.debugPanel.scrollHeight;
        
        // Keep only last 50 entries
        while (this.debugPanel.children.length > 50) {
            this.debugPanel.removeChild(this.debugPanel.firstChild);
        }
    }

    // Utility methods for manual debugging
    inspectElement(selector) {
        const el = document.querySelector(selector);
        if (el) {
            this.logMessage(`🔍 Element Inspection: ${selector}`, {
                element: el,
                attributes: Array.from(el.attributes).map(attr => `${attr.name}="${attr.value}"`),
                computedStyle: window.getComputedStyle(el),
                boundingRect: el.getBoundingClientRect()
            });
        } else {
            this.logMessage(`❌ Element not found: ${selector}`);
        }
    }

    logLivewireComponent(componentName) {
        if (window.Livewire) {
            const components = Livewire.all().filter(c => c.name === componentName);
            this.logMessage(`🧩 Livewire Component Data: ${componentName}`, {
                count: components.length,
                components: components.map(c => ({ 
                    id: c.id, 
                    data: c.data,
                    el: c.el 
                }))
            });
        }
    }
}

// Initialize debug tools
window.devDebugger = new DevDebugger();

// Expose utilities globally for manual use
window.debugUtils = {
    inspect: (selector) => window.devDebugger.inspectElement(selector),
    livewire: (componentName) => window.devDebugger.logLivewireComponent(componentName),
    log: (message, data) => window.devDebugger.logMessage(message, data)
};

// Enable debug mode toggle
window.toggleDebugMode = () => {
    const current = localStorage.getItem('debug_mode') === 'true';
    localStorage.setItem('debug_mode', (!current).toString());
    location.reload();
};

console.log('🔧 Debug utilities loaded. Use debugUtils.inspect(), debugUtils.livewire(), or toggleDebugMode()');