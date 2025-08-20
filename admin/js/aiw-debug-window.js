/**
 * AI Interview Widget - Debug Window Script
 * 
 * Real-time debug window for Enhanced Widget Customizer
 * Provides logging, state monitoring, and troubleshooting tools
 * 
 * @version 1.0.0
 * @author Eric Rorich
 * @since 1.9.5
 */

(function() {
    'use strict';

    // Immediate loading confirmation
    console.log('🐛 AIW Debug Window Script Loading...');

    // Check dependencies
    const $ = window.jQuery;
    const hasJQuery = typeof $ !== 'undefined';
    const customizerData = window.aiwCustomizerData || {};
    const debugMode = customizerData.debug || false;

    // Debug Window State
    const DEBUG_STATE = {
        isOpen: false,
        logs: [],
        maxLogs: 100,
        visualStyleState: {},
        initialized: false
    };

    // Log levels
    const LOG_LEVELS = {
        ERROR: 'error',
        WARNING: 'warning', 
        INFO: 'info',
        DEBUG: 'debug'
    };

    /**
     * Initialize the debug window
     */
    function initializeDebugWindow() {
        if (DEBUG_STATE.initialized) {
            return;
        }

        console.log('🐛 Initializing Debug Window...');

        // Check if we're on the customizer page
        if (!isCustomizerPage()) {
            console.log('🐛 Not on customizer page, skipping debug window initialization');
            return;
        }

        // Create debug window HTML
        createDebugWindowHTML();
        
        // Setup event listeners
        setupEventListeners();
        
        // Hook into existing logging functions
        hookIntoExistingLogging();
        
        // Start monitoring visual style state
        startVisualStyleMonitoring();
        
        DEBUG_STATE.initialized = true;
        
        // Log initialization
        addLog(LOG_LEVELS.INFO, 'Debug Window initialized successfully');
        
        console.log('✅ Debug Window initialized');
    }

    /**
     * Check if we're on the customizer page
     */
    function isCustomizerPage() {
        // Check for customizer-specific elements
        return document.getElementById('aiw-live-preview') !== null;
    }

    /**
     * Create the debug window HTML structure
     */
    function createDebugWindowHTML() {
        const debugHTML = `
            <div id="aiw-debug-window" class="aiw-debug-window" role="dialog" aria-labelledby="aiw-debug-title" aria-hidden="true">
                <div class="aiw-debug-header" id="aiw-debug-header">
                    <h3 class="aiw-debug-title" id="aiw-debug-title">AIW Debug Window</h3>
                    <button class="aiw-debug-toggle" id="aiw-debug-close" type="button" aria-label="Close debug window">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="aiw-debug-content" id="aiw-debug-content">
                    <div class="aiw-debug-section">
                        <div class="aiw-debug-section-header">
                            Logs
                            <button type="button" id="aiw-debug-clear-logs" style="float: right; background: none; border: 1px solid #666; color: #ccc; padding: 2px 6px; font-size: 10px; border-radius: 2px; cursor: pointer;">Clear</button>
                        </div>
                        <div class="aiw-debug-section-content" id="aiw-debug-logs-content">
                            <ul class="aiw-debug-logs" id="aiw-debug-logs" role="log" aria-live="polite" aria-label="Debug logs"></ul>
                        </div>
                    </div>
                    <div class="aiw-debug-section">
                        <div class="aiw-debug-section-header">
                            Visual Style State
                            <button type="button" id="aiw-debug-refresh-state" style="float: right; background: none; border: 1px solid #666; color: #ccc; padding: 2px 6px; font-size: 10px; border-radius: 2px; cursor: pointer;">Refresh</button>
                        </div>
                        <div class="aiw-debug-section-content">
                            <div class="aiw-debug-state" id="aiw-debug-state">
                                <pre id="aiw-debug-state-content">Loading state...</pre>
                            </div>
                        </div>
                    </div>
                    <div class="aiw-debug-section">
                        <div class="aiw-debug-section-header">Tools</div>
                        <div class="aiw-debug-section-content">
                            <div class="aiw-debug-tools">
                                <button type="button" id="aiw-debug-reset-preview" class="aiw-debug-tool-button danger">
                                    Reset Preview
                                </button>
                                <button type="button" id="aiw-debug-reload-styles" class="aiw-debug-tool-button primary">
                                    Reload Styles
                                </button>
                                <button type="button" id="aiw-debug-export-logs" class="aiw-debug-tool-button">
                                    Export Logs
                                </button>
                                <button type="button" id="aiw-debug-test-logging" class="aiw-debug-tool-button">
                                    Test Logging
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <button id="aiw-debug-toggle-button" class="aiw-debug-toggle-button" type="button" aria-label="Show debug window">
                Show Debug
            </button>
        `;

        // Append to body
        document.body.insertAdjacentHTML('beforeend', debugHTML);
    }

    /**
     * Setup event listeners for debug window interactions
     */
    function setupEventListeners() {
        const toggleButton = document.getElementById('aiw-debug-toggle-button');
        const closeButton = document.getElementById('aiw-debug-close');
        const debugWindow = document.getElementById('aiw-debug-window');
        const clearLogsButton = document.getElementById('aiw-debug-clear-logs');
        const refreshStateButton = document.getElementById('aiw-debug-refresh-state');
        const resetPreviewButton = document.getElementById('aiw-debug-reset-preview');
        const reloadStylesButton = document.getElementById('aiw-debug-reload-styles');
        const exportLogsButton = document.getElementById('aiw-debug-export-logs');
        const testLoggingButton = document.getElementById('aiw-debug-test-logging');

        // Toggle debug window
        if (toggleButton) {
            toggleButton.addEventListener('click', openDebugWindow);
        }

        // Close debug window
        if (closeButton) {
            closeButton.addEventListener('click', closeDebugWindow);
        }

        // Header click to toggle
        const header = document.getElementById('aiw-debug-header');
        if (header) {
            header.addEventListener('click', function(e) {
                if (e.target === closeButton || e.target.parentNode === closeButton) {
                    return; // Don't toggle if clicking close button
                }
                closeDebugWindow();
            });
        }

        // Clear logs
        if (clearLogsButton) {
            clearLogsButton.addEventListener('click', clearLogs);
        }

        // Refresh state
        if (refreshStateButton) {
            refreshStateButton.addEventListener('click', updateVisualStyleState);
        }

        // Reset preview
        if (resetPreviewButton) {
            resetPreviewButton.addEventListener('click', resetPreview);
        }

        // Reload styles
        if (reloadStylesButton) {
            reloadStylesButton.addEventListener('click', reloadStyles);
        }

        // Export logs
        if (exportLogsButton) {
            exportLogsButton.addEventListener('click', exportLogs);
        }

        // Test logging
        if (testLoggingButton) {
            testLoggingButton.addEventListener('click', testLogging);
        }

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl/Cmd + Shift + D to toggle debug window
            if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'D') {
                e.preventDefault();
                if (DEBUG_STATE.isOpen) {
                    closeDebugWindow();
                } else {
                    openDebugWindow();
                }
            }
            
            // Escape to close debug window
            if (e.key === 'Escape' && DEBUG_STATE.isOpen) {
                closeDebugWindow();
            }
        });
    }

    /**
     * Open the debug window
     */
    function openDebugWindow() {
        const debugWindow = document.getElementById('aiw-debug-window');
        const toggleButton = document.getElementById('aiw-debug-toggle-button');
        
        if (debugWindow && toggleButton) {
            debugWindow.classList.add('aiw-debug-open');
            debugWindow.setAttribute('aria-hidden', 'false');
            toggleButton.classList.add('aiw-debug-open');
            DEBUG_STATE.isOpen = true;
            
            // Update visual style state when opening
            updateVisualStyleState();
            
            addLog(LOG_LEVELS.INFO, 'Debug window opened');
        }
    }

    /**
     * Close the debug window
     */
    function closeDebugWindow() {
        const debugWindow = document.getElementById('aiw-debug-window');
        const toggleButton = document.getElementById('aiw-debug-toggle-button');
        
        if (debugWindow && toggleButton) {
            debugWindow.classList.remove('aiw-debug-open');
            debugWindow.setAttribute('aria-hidden', 'true');
            toggleButton.classList.remove('aiw-debug-open');
            DEBUG_STATE.isOpen = false;
            
            addLog(LOG_LEVELS.INFO, 'Debug window closed');
        }
    }

    /**
     * Add a log entry to the debug window
     */
    function addLog(level, message, data = null) {
        const timestamp = new Date().toLocaleTimeString();
        const logEntry = {
            timestamp: timestamp,
            level: level,
            message: message,
            data: data
        };

        // Add to logs array
        DEBUG_STATE.logs.push(logEntry);
        
        // Trim logs if exceeding max
        if (DEBUG_STATE.logs.length > DEBUG_STATE.maxLogs) {
            DEBUG_STATE.logs = DEBUG_STATE.logs.slice(-DEBUG_STATE.maxLogs);
        }

        // Update UI
        updateLogsDisplay();
    }

    /**
     * Update the logs display in the UI
     */
    function updateLogsDisplay() {
        const logsContainer = document.getElementById('aiw-debug-logs');
        if (!logsContainer) return;

        // Clear existing logs
        logsContainer.innerHTML = '';

        // Add new logs (show most recent first)
        const recentLogs = DEBUG_STATE.logs.slice(-20).reverse();
        
        recentLogs.forEach(log => {
            const logItem = document.createElement('li');
            logItem.className = `aiw-debug-log-item ${log.level}`;
            
            const logContent = `
                <span class="aiw-debug-log-timestamp">${log.timestamp}</span>
                <span class="aiw-debug-log-level">${log.level.toUpperCase()}</span>
                <span class="aiw-debug-log-message">${escapeHtml(log.message)}</span>
                ${log.data ? `<br><small style="color: #9ca3af; margin-left: 24px;">${escapeHtml(JSON.stringify(log.data))}</small>` : ''}
            `;
            
            logItem.innerHTML = logContent;
            logsContainer.appendChild(logItem);
        });

        // Auto-scroll to top (since we're showing most recent first)
        const logsContent = document.getElementById('aiw-debug-logs-content');
        if (logsContent) {
            logsContent.scrollTop = 0;
        }
    }

    /**
     * Clear all logs
     */
    function clearLogs() {
        DEBUG_STATE.logs = [];
        updateLogsDisplay();
        addLog(LOG_LEVELS.INFO, 'Logs cleared');
    }

    /**
     * Hook into existing logging functions
     */
    function hookIntoExistingLogging() {
        // Store original console methods
        const originalConsoleLog = console.log;
        const originalConsoleWarn = console.warn;
        const originalConsoleError = console.error;

        // Override console methods to also log to debug window
        console.log = function(...args) {
            originalConsoleLog.apply(console, args);
            
            // Only capture AIW-related logs
            const message = args.join(' ');
            if (message.includes('AIW') || message.includes('ai-interview') || message.includes('🎨') || message.includes('✅') || message.includes('❌')) {
                addLog(LOG_LEVELS.DEBUG, message);
            }
        };

        console.warn = function(...args) {
            originalConsoleWarn.apply(console, args);
            
            const message = args.join(' ');
            if (message.includes('AIW') || message.includes('ai-interview') || message.includes('⚠️')) {
                addLog(LOG_LEVELS.WARNING, message);
            }
        };

        console.error = function(...args) {
            originalConsoleError.apply(console, args);
            
            const message = args.join(' ');
            if (message.includes('AIW') || message.includes('ai-interview') || message.includes('❌')) {
                addLog(LOG_LEVELS.ERROR, message);
            }
        };

        // Try to hook into existing debugLog and errorLog functions if available
        setTimeout(() => {
            if (window.aiwLivePreview && window.aiwLivePreview.debug) {
                const originalDebugLog = window.aiwLivePreview.debug.log;
                const originalErrorLog = window.aiwLivePreview.debug.error;
                
                // Override the debug functions to also log to debug window
                window.aiwLivePreview.debug.log = function(...args) {
                    originalDebugLog.apply(this, args);
                    addLog(LOG_LEVELS.DEBUG, args.join(' '));
                };
                
                window.aiwLivePreview.debug.error = function(...args) {
                    originalErrorLog.apply(this, args);
                    addLog(LOG_LEVELS.ERROR, args.join(' '));
                };
                
                addLog(LOG_LEVELS.INFO, 'Hooked into aiwLivePreview debug functions');
            } else {
                addLog(LOG_LEVELS.WARNING, 'aiwLivePreview debug functions not available, using console hook only');
            }
        }, 1000);
    }

    /**
     * Start monitoring visual style state
     */
    function startVisualStyleMonitoring() {
        // Update state immediately
        updateVisualStyleState();
        
        // Set up periodic updates
        setInterval(updateVisualStyleState, 2000);
        
        // Monitor form changes
        if (hasJQuery) {
            $(document).on('input change', 'input[name*="style"], select[name*="style"]', function() {
                setTimeout(updateVisualStyleState, 100);
            });
        } else {
            // Vanilla JS fallback
            document.addEventListener('input', function(e) {
                if (e.target.name && e.target.name.includes('style')) {
                    setTimeout(updateVisualStyleState, 100);
                }
            });
            
            document.addEventListener('change', function(e) {
                if (e.target.name && e.target.name.includes('style')) {
                    setTimeout(updateVisualStyleState, 100);
                }
            });
        }
    }

    /**
     * Update the visual style state display
     */
    function updateVisualStyleState() {
        const stateContainer = document.getElementById('aiw-debug-state-content');
        if (!stateContainer) return;

        try {
            // Collect current state from various sources
            const state = {
                timestamp: new Date().toISOString(),
                formValues: collectFormValues(),
                cssVariables: collectCSSVariables(),
                customizerData: customizerData,
                previewConfig: window.PREVIEW_CONFIG || null,
                livePreviewState: window.aiwLivePreview || null
            };

            DEBUG_STATE.visualStyleState = state;

            // Format and display as JSON
            const formattedJSON = formatJSON(state);
            stateContainer.innerHTML = formattedJSON;
            
        } catch (error) {
            stateContainer.innerHTML = `<span style="color: #f87171;">Error collecting state: ${error.message}</span>`;
            addLog(LOG_LEVELS.ERROR, 'Failed to update visual style state', error.message);
        }
    }

    /**
     * Collect form values related to styling
     */
    function collectFormValues() {
        const formValues = {};
        
        // Find all style-related inputs
        const styleInputs = document.querySelectorAll('input[name*="style"], select[name*="style"], input[name*="ai_"], select[name*="ai_"]');
        
        styleInputs.forEach(input => {
            const name = input.name.replace(/^.*\[(.+)\]$/, '$1') || input.name;
            
            if (input.type === 'checkbox') {
                formValues[name] = input.checked;
            } else if (input.type === 'radio') {
                if (input.checked) {
                    formValues[name] = input.value;
                }
            } else {
                formValues[name] = input.value;
            }
        });
        
        return formValues;
    }

    /**
     * Collect current CSS variables
     */
    function collectCSSVariables() {
        const cssVariables = {};
        
        try {
            const computedStyle = getComputedStyle(document.documentElement);
            const allProps = Array.from(document.styleSheets)
                .flatMap(styleSheet => {
                    try {
                        return Array.from(styleSheet.cssRules);
                    } catch {
                        return [];
                    }
                })
                .flatMap(rule => {
                    try {
                        return Array.from(rule.style);
                    } catch {
                        return [];
                    }
                })
                .filter(prop => prop.startsWith('--aiw') || prop.startsWith('--ai-'));

            // Get unique CSS variables
            const uniqueProps = [...new Set(allProps)];
            
            uniqueProps.forEach(prop => {
                const value = computedStyle.getPropertyValue(prop);
                if (value) {
                    cssVariables[prop] = value.trim();
                }
            });
            
        } catch (error) {
            cssVariables.error = error.message;
        }
        
        return cssVariables;
    }

    /**
     * Format JSON for display with syntax highlighting
     */
    function formatJSON(obj) {
        try {
            const jsonString = JSON.stringify(obj, null, 2);
            
            // Simple syntax highlighting
            return jsonString
                .replace(/("([^"\\]|\\.)*")\s*:/g, '<span class="json-key">$1</span>:')
                .replace(/:\s*("([^"\\]|\\.)*")/g, ': <span class="json-string">$1</span>')
                .replace(/:\s*([+-]?\d+\.?\d*)/g, ': <span class="json-number">$1</span>')
                .replace(/:\s*(true|false)/g, ': <span class="json-boolean">$1</span>')
                .replace(/:\s*(null)/g, ': <span class="json-null">$1</span>');
                
        } catch (error) {
            return `Error formatting JSON: ${error.message}`;
        }
    }

    /**
     * Reset the preview
     */
    function resetPreview() {
        addLog(LOG_LEVELS.INFO, 'Resetting preview...');
        
        try {
            // Try multiple reset approaches
            
            // 1. Reset via live preview system
            if (window.aiwLivePreview && typeof window.aiwLivePreview.initialize === 'function') {
                window.aiwLivePreview.initialize();
                addLog(LOG_LEVELS.INFO, 'Preview reset via aiwLivePreview.initialize()');
            }
            
            // 2. Reload preview container
            const previewContainer = document.getElementById('aiw-live-preview');
            if (previewContainer) {
                // Show loading state
                const loadingElement = document.getElementById('preview-loading');
                if (loadingElement) {
                    loadingElement.style.display = 'flex';
                }
                
                // Hide error state
                const errorElement = document.getElementById('preview-error');
                if (errorElement) {
                    errorElement.style.display = 'none';
                }
                
                addLog(LOG_LEVELS.INFO, 'Preview container state reset');
            }
            
            // 3. Trigger a form change to refresh preview
            const firstStyleInput = document.querySelector('input[name*="style"]');
            if (firstStyleInput) {
                const event = new Event('input', { bubbles: true });
                firstStyleInput.dispatchEvent(event);
                addLog(LOG_LEVELS.INFO, 'Triggered form change event for preview refresh');
            }
            
            updateVisualStyleState();
            addLog(LOG_LEVELS.INFO, 'Preview reset completed');
            
        } catch (error) {
            addLog(LOG_LEVELS.ERROR, 'Failed to reset preview', error.message);
        }
    }

    /**
     * Reload styles
     */
    function reloadStyles() {
        addLog(LOG_LEVELS.INFO, 'Reloading styles...');
        
        try {
            // 1. Update CSS variables from current form values
            const formValues = collectFormValues();
            
            Object.keys(formValues).forEach(key => {
                const cssVar = `--aiw-${key.replace(/_/g, '-')}`;
                document.documentElement.style.setProperty(cssVar, formValues[key]);
            });
            
            addLog(LOG_LEVELS.INFO, `Updated ${Object.keys(formValues).length} CSS variables`);
            
            // 2. Trigger canvas update if available
            if (window.PREVIEW_CONFIG && window.PREVIEW_CONFIG.canvas) {
                if (typeof window.updateCanvas === 'function') {
                    window.updateCanvas();
                    addLog(LOG_LEVELS.INFO, 'Canvas updated');
                }
            }
            
            // 3. Update visualization if available
            if (typeof window.updateVisualization === 'function') {
                window.updateVisualization();
                addLog(LOG_LEVELS.INFO, 'Visualization updated');
            }
            
            updateVisualStyleState();
            addLog(LOG_LEVELS.INFO, 'Styles reload completed');
            
        } catch (error) {
            addLog(LOG_LEVELS.ERROR, 'Failed to reload styles', error.message);
        }
    }

    /**
     * Export logs as downloadable file
     */
    function exportLogs() {
        try {
            const exportData = {
                timestamp: new Date().toISOString(),
                debugWindowVersion: '1.0.0',
                logs: DEBUG_STATE.logs,
                visualStyleState: DEBUG_STATE.visualStyleState,
                userAgent: navigator.userAgent,
                url: window.location.href
            };
            
            const jsonData = JSON.stringify(exportData, null, 2);
            const blob = new Blob([jsonData], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            
            const a = document.createElement('a');
            a.href = url;
            a.download = `aiw-debug-logs-${new Date().toISOString().slice(0, 19).replace(/:/g, '-')}.json`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
            
            addLog(LOG_LEVELS.INFO, 'Debug logs exported successfully');
            
        } catch (error) {
            addLog(LOG_LEVELS.ERROR, 'Failed to export logs', error.message);
        }
    }

    /**
     * Test logging functionality
     */
    function testLogging() {
        addLog(LOG_LEVELS.DEBUG, 'This is a debug message');
        addLog(LOG_LEVELS.INFO, 'This is an info message');
        addLog(LOG_LEVELS.WARNING, 'This is a warning message');
        addLog(LOG_LEVELS.ERROR, 'This is an error message');
        addLog(LOG_LEVELS.INFO, 'Logging test completed', { testData: 'sample' });
    }

    /**
     * Escape HTML to prevent XSS
     */
    function escapeHtml(unsafe) {
        return unsafe
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeDebugWindow);
    } else {
        initializeDebugWindow();
    }

    // Expose public API
    window.aiwDebugWindow = {
        addLog: addLog,
        LOG_LEVELS: LOG_LEVELS,
        updateVisualStyleState: updateVisualStyleState,
        resetPreview: resetPreview,
        reloadStyles: reloadStyles,
        clearLogs: clearLogs,
        exportLogs: exportLogs
    };

    console.log('✅ AIW Debug Window Script Loaded');

})();