/**
 * AI Interview Widget - Customizer Partial Fix
 * 
 * Improved retry logic and error handling for the customizer preview partial
 * Replaces the inline JavaScript with better structured code
 * 
 * @version 1.0.0
 * @author Eric Rorich
 * @since 1.9.5
 */

(function($) {
    'use strict';
    
    console.log('🔧 Customizer Partial Fix: Loading...');
    
    // Configuration
    const CONFIG = {
        maxRetries: 15,
        baseDelay: 50,
        maxDelay: 1000,
        timeoutAfter: 10000, // 10 seconds maximum wait
        debug: (window.aiwCustomizerData && window.aiwCustomizerData.debug) || false
    };
    
    let retryCount = 0;
    let startTime = Date.now();
    let timeoutId = null;
    
    // Enhanced logging
    function debugLog(...args) {
        if (CONFIG.debug) {
            console.log('🔧 Partial Fix:', ...args);
        }
    }
    
    function errorLog(...args) {
        console.error('❌ Partial Fix:', ...args);
    }
    
    // Smart retry delay calculation
    function calculateDelay(attempt) {
        if (attempt <= 3) return CONFIG.baseDelay;           // First 3: 50ms
        if (attempt <= 8) return CONFIG.baseDelay * 2;      // Next 5: 100ms  
        if (attempt <= 12) return CONFIG.baseDelay * 4;     // Next 4: 200ms
        return Math.min(CONFIG.baseDelay * 8, CONFIG.maxDelay); // Final: 400ms max
    }
    
    // Check if we've exceeded time limit
    function hasTimedOut() {
        return (Date.now() - startTime) > CONFIG.timeoutAfter;
    }
    
    // Enhanced fallback with specific messaging
    function showEnhancedFallback(reason, technical) {
        debugLog('Showing enhanced fallback:', reason);
        
        const $loading = $('#preview-loading');
        const $fallback = $('#preview-fallback');
        const $error = $('#preview-error');
        
        // Hide loading state
        $loading.hide();
        
        // Show appropriate error state
        if (technical) {
            $error.show();
            $error.find('#preview-error-message').text(reason);
        } else {
            $fallback.show();
            $fallback.find('p').text(reason);
        }
        
        // Clear any timeout
        if (timeoutId) {
            clearTimeout(timeoutId);
            timeoutId = null;
        }
    }
    
    // Enhanced dependency checking
    function checkDependencies() {
        const checks = {
            aiwLivePreview: typeof window.aiwLivePreview !== 'undefined',
            customizerData: typeof window.aiwCustomizerData !== 'undefined',
            previewHandler: typeof window.aiwPreviewHandler !== 'undefined',
            jquery: typeof $ !== 'undefined'
        };
        
        debugLog('Dependency check:', checks);
        return checks;
    }
    
    // Check if the full preview system is ready
    function isFullSystemReady() {
        const deps = checkDependencies();
        
        if (!deps.aiwLivePreview) {
            return { ready: false, reason: 'aiwLivePreview object not found' };
        }
        
        if (!deps.customizerData) {
            return { ready: false, reason: 'aiwCustomizerData not available' };
        }
        
        // Check if this is the placeholder from preview-handler.js
        const config = window.aiwLivePreview.getConfig();
        if (config.handlerReady && config.loading) {
            return { ready: false, reason: 'Full preview system still loading' };
        }
        
        // Check if initialize method is functional (not just a placeholder)
        if (typeof window.aiwLivePreview.initialize !== 'function') {
            return { ready: false, reason: 'Initialize method not available' };
        }
        
        return { ready: true, reason: 'All systems ready' };
    }
    
    // Main initialization function with enhanced retry logic
    function initializeWithSmartRetry() {
        retryCount++;
        
        debugLog(`Initialization attempt ${retryCount}/${CONFIG.maxRetries}`);
        
        // Check timeout first
        if (hasTimedOut()) {
            errorLog('Initialization timed out after', CONFIG.timeoutAfter, 'ms');
            showEnhancedFallback(
                'Preview initialization timed out. Your settings are still being saved.',
                false
            );
            return;
        }
        
        // Check retry limit
        if (retryCount > CONFIG.maxRetries) {
            errorLog('Maximum retry attempts exceeded');
            showEnhancedFallback(
                'Preview system failed to load after multiple attempts. Please refresh the page.',
                true
            );
            return;
        }
        
        // Check if system is ready
        const systemCheck = isFullSystemReady();
        
        if (systemCheck.ready) {
            debugLog('System ready, attempting initialization...');
            
            try {
                // Initialize the preview system
                const result = window.aiwLivePreview.initialize();
                
                if (result !== false) {
                    console.log('✅ Preview system successfully initialized');
                    
                    // Hide loading and show preview
                    $('#preview-loading').hide();
                    $('#aiw-preview-canvas-container').show();
                    
                    // Clear timeout
                    if (timeoutId) {
                        clearTimeout(timeoutId);
                        timeoutId = null;
                    }
                    
                    return;
                }
            } catch (error) {
                errorLog('Error during initialization:', error);
                showEnhancedFallback(
                    `Preview initialization error: ${error.message}`,
                    true
                );
                return;
            }
        }
        
        // Schedule next retry
        const delay = calculateDelay(retryCount);
        debugLog(`System not ready (${systemCheck.reason}), retrying in ${delay}ms...`);
        
        timeoutId = setTimeout(initializeWithSmartRetry, delay);
    }
    
    // Setup retry button functionality
    function setupRetryButton() {
        $(document).on('click', '#retry-preview', function() {
            console.log('🔄 Manual retry requested...');
            
            // Reset state
            retryCount = 0;
            startTime = Date.now();
            
            // Clear any existing timeout
            if (timeoutId) {
                clearTimeout(timeoutId);
                timeoutId = null;
            }
            
            // Hide error states and show loading
            $('#preview-error').hide();
            $('#preview-fallback').hide();
            $('#preview-loading').show();
            
            // Start initialization again
            initializeWithSmartRetry();
        });
    }
    
    // Initialize when DOM is ready
    $(document).ready(function() {
        debugLog('DOM ready, starting enhanced preview initialization...');
        
        // Setup retry button
        setupRetryButton();
        
        // Start the initialization process
        initializeWithSmartRetry();
        
        // Set up global timeout as a safety net
        setTimeout(function() {
            if ($('#preview-loading').is(':visible')) {
                errorLog('Global timeout reached, forcing fallback');
                showEnhancedFallback(
                    'Preview system failed to load within reasonable time. Your settings are being saved.',
                    false
                );
            }
        }, CONFIG.timeoutAfter + 2000);
    });
    
    // Export for debugging
    window.aiwPartialFix = {
        version: '1.0.0',
        config: CONFIG,
        retry: initializeWithSmartRetry,
        checkDependencies: checkDependencies,
        isFullSystemReady: isFullSystemReady,
        debugLog: debugLog
    };
    
    console.log('✅ Customizer Partial Fix: Loaded and ready');
    
})(jQuery);