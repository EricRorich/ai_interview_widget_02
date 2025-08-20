<?php
/**
 * AI Interview Widget - Customizer Preview Partial
 * 
 * Markup for live preview sections in Enhanced Widget Customizer
 * Contains iframe-based preview system with fallback to canvas-based system
 * 
 * @version 1.0.0
 * @author Eric Rorich
 * @since 1.9.5
 */

// Security check
defined('ABSPATH') or die('No script kiddies please!');
?>

<div class="aiw-preview-container" id="aiw-live-preview" role="region" aria-label="Live Widget Preview">
    
    <!-- Primary Iframe-based Preview System -->
    <div id="widget_preview_container" class="aiw-iframe-preview-container" style="position: relative; width: 100%; min-height: 400px;">
        
        <!-- Loading State -->
        <div class="aiw-preview-loading" id="preview-loading" style="display: flex; align-items: center; justify-content: center; padding: 40px; background: #f9f9f9; border-radius: 8px; margin-bottom: 20px;">
            <div style="text-align: center;">
                <div class="spinner" style="width: 20px; height: 20px; border: 2px solid #f3f3f3; border-top: 2px solid #007cba; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 10px;"></div>
                <p style="margin: 0; color: #666; font-size: 14px;">Loading preview...</p>
            </div>
        </div>
        
        <!-- Error State -->
        <div class="aiw-preview-error" id="preview-error" style="display: none; padding: 20px; background: #ffeaa7; border: 1px solid #fdcb6e; border-radius: 8px; margin-bottom: 20px;">
            <p style="margin: 0; color: #856404;">
                <strong>Preview Error:</strong> <span id="error-message">Unable to load preview</span>
            </p>
            <button type="button" id="retry-preview" class="button button-secondary" style="margin-top: 10px;">
                🔄 Retry
            </button>
        </div>
        
        <!-- Iframe Preview -->
        <iframe id="preview-iframe" 
                src="about:blank" 
                style="width: 100%; height: 400px; border: 1px solid #ddd; border-radius: 8px; background: #fff; display: none;"
                sandbox="allow-scripts allow-same-origin"
                title="Live Widget Preview">
        </iframe>
        
    </div>
    
    <!-- Fallback Canvas-based Preview System (backward compatibility) -->
    <div class="aiw-canvas-preview-fallback" style="display: none;">
        <!-- Canvas Background Layer -->
        <canvas class="aiw-preview-canvas" id="aiw-preview-canvas" aria-hidden="true"></canvas>
        
        <!-- Preview Sections Container -->
        <div class="aiw-preview-sections">
            
            <!-- Play Button Preview Section -->
            <div class="aiw-preview-section" data-label="Play Button" data-section="play-button">
                <button class="aiw-preview-play-button" 
                        id="aiw-preview-play-btn" 
                        type="button"
                        aria-label="Preview play button design"
                        tabindex="0">
                    <span class="screen-reader-text">Play button preview - shows current design settings</span>
                </button>
            </div>
            
            <!-- Audio Visualization Preview Section -->
            <div class="aiw-preview-section" data-label="Audio Visualization" data-section="visualization">
                <div class="aiw-preview-visualization" 
                     id="aiw-preview-viz" 
                     role="img" 
                     aria-label="Audio visualization preview with animated frequency bars">
                    
                    <!-- Dynamic visualization bars (generated via JavaScript) -->
                    <div class="aiw-preview-viz-bar" aria-hidden="true"></div>
                    <div class="aiw-preview-viz-bar" aria-hidden="true"></div>
                    <div class="aiw-preview-viz-bar" aria-hidden="true"></div>
                    <div class="aiw-preview-viz-bar" aria-hidden="true"></div>
                    <div class="aiw-preview-viz-bar" aria-hidden="true"></div>
                    <div class="aiw-preview-viz-bar" aria-hidden="true"></div>
                    <div class="aiw-preview-viz-bar" aria-hidden="true"></div>
                    <div class="aiw-preview-viz-bar" aria-hidden="true"></div>
                    <div class="aiw-preview-viz-bar" aria-hidden="true"></div>
                    <div class="aiw-preview-viz-bar" aria-hidden="true"></div>
                    <div class="aiw-preview-viz-bar" aria-hidden="true"></div>
                    <div class="aiw-preview-viz-bar" aria-hidden="true"></div>
                    
                    <span class="screen-reader-text">Animated frequency bars showing current visualization style</span>
                </div>
            </div>
            
            <!-- Chatbox Preview Section -->
            <div class="aiw-preview-section" data-label="Chatbox" data-section="chatbox">
                <div class="aiw-preview-chatbox" 
                     id="aiw-preview-chat"
                     role="log"
                     aria-label="Chat interface preview">
                    
                    <!-- Incoming Message -->
                    <div class="aiw-preview-chat-message incoming">
                        <div class="aiw-preview-chat-avatar" aria-hidden="true">AI</div>
                        <div class="aiw-preview-chat-bubble">
                            Hello! I'm Eric's AI assistant. How can I help you today?
                        </div>
                    </div>
                    
                    <!-- Outgoing Message -->
                    <div class="aiw-preview-chat-message outgoing">
                        <div class="aiw-preview-chat-avatar" aria-hidden="true">You</div>
                        <div class="aiw-preview-chat-bubble">
                            Tell me about Eric's experience.
                        </div>
                    </div>
                    
                    <!-- Typing Indicator -->
                    <div class="aiw-preview-chat-message incoming">
                        <div class="aiw-preview-chat-avatar" aria-hidden="true">AI</div>
                        <div class="aiw-preview-typing" aria-label="AI is typing">
                            <div class="aiw-preview-typing-dot" aria-hidden="true"></div>
                            <div class="aiw-preview-typing-dot" aria-hidden="true"></div>
                            <div class="aiw-preview-typing-dot" aria-hidden="true"></div>
                            <span class="screen-reader-text">AI is typing a response</span>
                        </div>
                    </div>
                    
                    <span class="screen-reader-text">Chat preview showing current theme and bubble styles</span>
                </div>
            </div>
            
        </div>
    </div>
    
    <!-- Screen Reader Status Updates -->
    <div class="screen-reader-text" 
         id="aiw-preview-status" 
         aria-live="polite" 
         aria-atomic="true">
        Live preview loaded successfully
    </div>
</div>

<style>
/* Preview container styles */
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.aiw-iframe-preview-container {
    position: relative;
    z-index: 10;
}

.aiw-canvas-preview-fallback {
    position: relative;
    z-index: 5;
}

/* Responsive iframe */
#preview-iframe {
    transition: opacity 0.3s ease;
}

#preview-iframe.loading {
    opacity: 0.5;
}

/* Error state styling */
.aiw-preview-error {
    animation: fadeInError 0.3s ease;
}

@keyframes fadeInError {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Loading state animation */
.aiw-preview-loading {
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}
</style>

<?php
/**
 * Dynamic bar count generation for different visualization styles
 * This can be extended based on customizer settings
 */
?>
<script type="text/javascript">
document.addEventListener('DOMContentLoaded', function() {
    // Initialize visualization bars based on settings (fallback canvas system)
    const initializeBars = () => {
        const vizContainer = document.getElementById('aiw-preview-viz');
        if (!vizContainer) return;
        
        // Get current bar count from CSS variable or default
        const barCount = parseInt(getComputedStyle(document.documentElement)
            .getPropertyValue('--aiw-preview-viz-bars') || '12');
        
        // Clear existing bars
        vizContainer.innerHTML = '';
        
        // Create new bars
        for (let i = 0; i < barCount; i++) {
            const bar = document.createElement('div');
            bar.className = 'aiw-preview-viz-bar';
            bar.setAttribute('aria-hidden', 'true');
            bar.style.animationDelay = `${(i * 100) % 800}ms`;
            vizContainer.appendChild(bar);
        }
        
        // Add screen reader text
        const srText = document.createElement('span');
        srText.className = 'screen-reader-text';
        srText.textContent = `Animated frequency bars showing current visualization style with ${barCount} bars`;
        vizContainer.appendChild(srText);
    };
    
    // Initialize on load
    initializeBars();
    
    // Re-initialize when bar count changes
    window.aiwPreviewUpdateBars = initializeBars;
    
    // Initialize iframe-based preview system with enhanced error handling
    function initializePreviewWithFallback() {
        // Check if script is loaded
        if (typeof window.aiwCustomizerPreview === 'undefined') {
            console.warn('⚠️ aiwCustomizerPreview not loaded yet, retrying in 500ms...');
            setTimeout(initializePreviewWithFallback, 500);
            return;
        }
        
        // Check if customizerData is available
        if (typeof window.aiwCustomizerData === 'undefined') {
            console.warn('⚠️ aiwCustomizerData not available yet, retrying in 500ms...');
            setTimeout(initializePreviewWithFallback, 500);
            return;
        }
        
        console.log('✅ Preview dependencies ready, initializing...');
        if (window.aiwCustomizerPreview.initializePreviewSystem) {
            window.aiwCustomizerPreview.initializePreviewSystem();
        } else {
            console.error('❌ initializePreviewSystem method not found');
        }
    }
    
    // Start initialization with retry mechanism
    initializePreviewWithFallback();
});
</script>