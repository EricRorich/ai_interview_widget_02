/**
 * AI Interview Widget - Preview Handler
 * 
 * Ensures aiwLivePreview object is available immediately to prevent timing issues
 * This script loads first and creates the basic object structure
 * 
 * @version 1.0.0
 * @author Eric Rorich
 * @since 1.9.5
 */

(function() {
    'use strict';
    
    // Immediately create the aiwLivePreview object to prevent timing issues
    if (typeof window.aiwLivePreview === 'undefined') {
        console.log('🔧 Preview Handler: Creating aiwLivePreview object...');
        
        window.aiwLivePreview = {
            // State tracking
            _initialized: false,
            _loading: true,
            _version: '1.0.0',
            
            // Basic methods that are always available
            initialize: function() {
                console.log('🔄 Preview Handler: initialize() called, waiting for full system...');
                return false;
            },
            
            updatePreview: function() {
                console.log('🔄 Preview Handler: updatePreview() called, waiting for full system...');
                return false;
            },
            
            updateSetting: function(settingName, value) {
                console.log('🔄 Preview Handler: updateSetting() called, waiting for full system...', settingName, value);
                return false;
            },
            
            updateVariable: function(variable, value) {
                console.log('🔄 Preview Handler: updateVariable() called, waiting for full system...', variable, value);
                return false;
            },
            
            resizeCanvas: function() {
                console.log('🔄 Preview Handler: resizeCanvas() called, waiting for full system...');
                return false;
            },
            
            showFallbackMessage: function(message) {
                console.log('🔄 Preview Handler: showFallbackMessage() called:', message);
                // This one we can implement immediately as it's just DOM manipulation
                const fallbackElement = document.getElementById('preview-fallback');
                const loadingElement = document.getElementById('preview-loading');
                
                if (loadingElement) {
                    loadingElement.style.display = 'none';
                }
                
                if (fallbackElement) {
                    fallbackElement.style.display = 'block';
                    const messageElement = fallbackElement.querySelector('p');
                    if (messageElement && message) {
                        messageElement.textContent = message;
                    }
                }
                return true;
            },
            
            getConfig: function() {
                return {
                    initialized: this._initialized,
                    loading: this._loading,
                    version: this._version,
                    handlerReady: true
                };
            },
            
            // Placeholder objects that will be replaced by the full system
            test: {},
            debug: {
                log: function(...args) {
                    console.log('🔧 Preview Handler Debug:', ...args);
                },
                error: function(...args) {
                    console.error('🔧 Preview Handler Error:', ...args);
                }
            }
        };
        
        console.log('✅ Preview Handler: aiwLivePreview object created and ready');
    } else {
        console.log('ℹ️ Preview Handler: aiwLivePreview already exists, skipping creation');
    }
    
    // Set up a ready state checker for when the full system loads
    window.aiwPreviewHandler = {
        version: '1.0.0',
        ready: true,
        
        // Method to replace the placeholder object with the real implementation
        replaceWithFullSystem: function(fullSystemObject) {
            if (fullSystemObject && typeof fullSystemObject === 'object') {
                console.log('🔧 Preview Handler: Replacing placeholder with full system...');
                
                // Preserve any state that might be important
                const wasInitialized = window.aiwLivePreview._initialized;
                
                // Replace the object entirely
                for (const key in fullSystemObject) {
                    window.aiwLivePreview[key] = fullSystemObject[key];
                }
                
                // Update state
                window.aiwLivePreview._initialized = wasInitialized;
                window.aiwLivePreview._loading = false;
                
                console.log('✅ Preview Handler: Full system replacement complete');
                return true;
            }
            
            console.error('❌ Preview Handler: Invalid full system object provided');
            return false;
        },
        
        // Check if the full system is loaded
        isFullSystemLoaded: function() {
            return window.aiwLivePreview && 
                   !window.aiwLivePreview._loading && 
                   typeof window.aiwLivePreview.initialize === 'function' &&
                   window.aiwLivePreview.getConfig().initialized !== false;
        }
    };
    
    console.log('✅ Preview Handler: Handler system ready');
    
})();