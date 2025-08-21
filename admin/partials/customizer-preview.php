<?php
/**
 * AI Interview Widget - Customizer Preview Partial
 * 
 * DOM-based live preview for Enhanced Widget Customizer
 * Uses canvas animations and real-time CSS variable updates
 * 
 * @version 2.0.0
 * @author Eric Rorich
 * @since 1.9.5
 */

// Security check
defined('ABSPATH') or die('No script kiddies please!');
?>

<div class="aiw-preview-container" id="aiw-live-preview" role="region" aria-label="Live Widget Preview">
    
    <!-- Loading State -->
    <div class="aiw-preview-loading" id="preview-loading" style="display: flex; align-items: center; justify-content: center; padding: 40px; background: #f9f9f9; border-radius: 8px; margin-bottom: 20px;">
        <div style="text-align: center;">
            <div style="margin-bottom: 15px;">
                <div class="spinner" style="width: 40px; height: 40px; border: 3px solid #f3f3f3; border-top: 3px solid #007cba; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto;"></div>
            </div>
            <h3 style="margin: 0 0 10px 0; color: #333;">Loading Live Preview...</h3>
            <p style="margin: 0; color: #666; font-size: 14px;">Initializing widget preview with your custom settings</p>
        </div>
    </div>
    
    <!-- Error State -->
    <div class="aiw-preview-error" id="preview-error" style="display: none; padding: 20px; background: #fff2f2; border: 1px solid #ff6b6b; border-radius: 8px; margin-bottom: 20px;">
        <h3 style="margin: 0 0 10px 0; color: #d63031;">Preview Error</h3>
        <p id="preview-error-message" style="margin: 0 0 15px 0; color: #636e72;">Unable to load preview. Please try again.</p>
        <button type="button" id="retry-preview" class="button button-secondary" style="margin-top: 10px;">
            🔄 Retry
        </button>
    </div>
    
    <!-- Fallback Message (shown when preview fails to initialize) -->
    <div class="aiw-preview-fallback" id="preview-fallback" style="display: none; padding: 20px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 8px; margin-bottom: 20px; text-align: center;">
        <h3 style="margin: 0 0 10px 0; color: #856404;">Preview Temporarily Unavailable</h3>
        <p style="margin: 0; color: #856404; font-size: 14px;">Your customizations are being saved, but the live preview cannot be displayed right now.</p>
    </div>
    
    <!-- Canvas-based Preview System -->
    <div class="aiw-canvas-preview-container" id="aiw-preview-canvas-container" style="position: relative; width: 100%; min-height: 400px; background: #f0f0f1; border-radius: 8px; overflow: hidden;">
        
        <!-- Canvas Background Layer -->
        <canvas class="aiw-preview-canvas" id="aiw-preview-canvas" 
                width="800" height="400"
                style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 1;"
                aria-hidden="true">
        </canvas>
        
        <!-- Preview Sections Container -->
        <div class="aiw-preview-sections" style="position: relative; z-index: 2; padding: 20px; height: 100%;">
            
            <!-- Play Button Preview Section -->
            <div class="aiw-preview-section aiw-preview-playbutton" data-label="Play Button" data-section="play-button">
                <canvas class="aiw-preview-canvas" aria-hidden="true"></canvas>
                <div class="aiw-preview-content">
                    <button class="aiw-preview-play-button" 
                            id="aiw-preview-play-btn" 
                            type="button"
                            aria-label="Preview play button design"
                            tabindex="0">
                        <span class="screen-reader-text">Play button preview - shows current design settings</span>
                    </button>
                </div>
            </div>
            
            <!-- Audio Visualization Preview Section -->
            <div class="aiw-preview-section aiw-preview-audiovis" data-label="Audio Visualization" data-section="visualization">
                <canvas class="aiw-preview-canvas" aria-hidden="true"></canvas>
                <div class="aiw-preview-content">
                    <div class="aiw-preview-visualization" 
                         id="aiw-preview-viz" 
                         role="img" 
                         aria-label="Audio visualization preview with animated frequency bars">
                        <!-- Dynamic visualization bars (generated via JavaScript) -->
                        <span class="screen-reader-text">Animated frequency bars showing current visualization style</span>
                    </div>
                </div>
            </div>
            
            <!-- Chatbox Preview Section -->
            <div class="aiw-preview-section aiw-preview-chatbox" data-label="Chatbox" data-section="chatbox">
                <canvas class="aiw-preview-canvas" aria-hidden="true"></canvas>
                <div class="aiw-preview-content">
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

@keyframes typing {
    0%, 60%, 100% { 
        transform: translateY(0);
        opacity: 0.5;
    }
    30% { 
        transform: translateY(-10px);
        opacity: 1;
    }
}

/* Default CSS variables for preview */
:root {
    --aiw-color-primary: #00cfff;
    --aiw-color-accent: #ff6b35;
    --aiw-color-background: #0a0a1a;
    --aiw-color-text: #ffffff;
    --aiw-radius: 8px;
    --aiw-shadow: 0 4px 15px rgba(0, 207, 255, 0.3);
}

.aiw-preview-container {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
}

.aiw-canvas-preview-container {
    position: relative;
    background: var(--aiw-color-background);
    border: 1px solid rgba(255,255,255,0.1);
    box-shadow: var(--aiw-shadow);
}

.aiw-preview-play-button:hover {
    transform: scale(1.05);
    box-shadow: 0 0 20px rgba(0, 207, 255, 0.5);
}

.aiw-preview-play-button:focus {
    outline: 2px solid var(--aiw-color-primary);
    outline-offset: 2px;
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
    .aiw-preview-play-button,
    .aiw-preview-viz-bar,
    .aiw-preview-typing-dot {
        animation: none !important;
        transition: none !important;
    }
    
    .spinner {
        animation: none !important;
    }
}

/* Screen reader only text */
.screen-reader-text {
    position: absolute !important;
    width: 1px !important;
    height: 1px !important;
    padding: 0 !important;
    margin: -1px !important;
    overflow: hidden !important;
    clip: rect(0, 0, 0, 0) !important;
    white-space: nowrap !important;
    border: 0 !important;
}
</style>

<!--
Preview initialization logic has been moved to customizer-partial-fix.js
This provides better error handling, retry logic, and avoids timing issues.
-->