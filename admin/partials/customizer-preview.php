<?php
/**
 * AI Interview Widget - Customizer Preview Partial
 * 
 * Markup for live preview sections in Enhanced Widget Customizer
 * Contains three vertically stacked sections: Play Button, Audio Visualization, Chatbox
 * 
 * @version 1.0.0
 * @author Eric Rorich
 * @since 1.9.5
 */

// Security check
defined('ABSPATH') or die('No script kiddies please!');
?>

<div class="aiw-preview-container" id="aiw-live-preview" role="region" aria-label="Live Widget Preview">
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
    
    <!-- Screen Reader Status Updates -->
    <div class="screen-reader-text" 
         id="aiw-preview-status" 
         aria-live="polite" 
         aria-atomic="true">
        Live preview loaded successfully
    </div>
</div>

<?php
/**
 * Dynamic bar count generation for different visualization styles
 * This can be extended based on customizer settings
 */
?>
<script type="text/javascript">
document.addEventListener('DOMContentLoaded', function() {
    // Initialize visualization bars based on settings
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
});
</script>