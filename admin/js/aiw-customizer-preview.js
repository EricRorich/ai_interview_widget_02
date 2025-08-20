/**
 * AI Interview Widget - Customizer Preview Script
 * 
 * Real-time data binding, canvas background, visualization animation,
 * and live CSS variable updates for Enhanced Widget Customizer preview
 * 
 * @version 1.0.0
 * @author Eric Rorich
 * @since 1.9.5
 */

(function() {
    'use strict';

    // Check if jQuery is available
    const $ = window.jQuery;
    const hasJQuery = typeof $ !== 'undefined';

    // Configuration object
    const PREVIEW_CONFIG = {
        initialized: false,
        debounceDelay: 50,
        updateTimeout: null,
        animationFrameId: null,
        canvas: null,
        ctx: null,
        particles: [],
        reducedMotion: window.matchMedia('(prefers-reduced-motion: reduce)').matches
    };

    // Settings map for real-time updates
    const SETTINGS_MAP = {
        // Color settings
        'ai_primary_color': '--aiw-preview-primary',
        'ai_accent_color': '--aiw-preview-accent', 
        'ai_background_color': '--aiw-preview-background',
        'ai_text_color': '--aiw-preview-text',
        
        // Shape settings
        'ai_border_radius': '--aiw-preview-border-radius',
        'ai_border_width': '--aiw-preview-border-width',
        'ai_shadow_intensity': '--aiw-preview-shadow-intensity',
        
        // Play button settings
        'ai_play_button_size': '--aiw-preview-play-size',
        'ai_play_button_color': '--aiw-preview-play-color',
        'ai_play_button_icon_color': '--aiw-preview-play-icon-color',
        
        // Audio visualization settings
        'ai_viz_bar_count': '--aiw-preview-viz-bars',
        'ai_viz_bar_gap': '--aiw-preview-viz-gap',
        'ai_viz_color': '--aiw-preview-viz-color',
        'ai_viz_glow': '--aiw-preview-viz-glow',
        'ai_viz_speed': '--aiw-preview-viz-speed',
        
        // Chatbox settings
        'ai_chat_bubble_color': '--aiw-preview-chat-bubble-color',
        'ai_chat_bubble_radius': '--aiw-preview-chat-bubble-radius',
        'ai_chat_avatar_size': '--aiw-preview-chat-avatar-size'
    };

    /**
     * Cross-browser event listener helper
     */
    function addEventListeners(selector, events, handler) {
        const elements = typeof selector === 'string' ? 
            document.querySelectorAll(selector) : [selector];
        
        elements.forEach(element => {
            if (!element) return;
            events.split(' ').forEach(event => {
                element.addEventListener(event, handler);
            });
        });
    }

    /**
     * Cross-browser element selector helper
     */
    function getElements(selector) {
        return document.querySelectorAll(selector);
    }

    /**
     * Initialize the preview system
     */
    function initializePreview() {
        if (PREVIEW_CONFIG.initialized) return;
        
        // Check if we're on the customizer page
        if (!document.getElementById('aiw-live-preview')) return;
        
        console.log('AIW Customizer Preview: Initializing live preview system');
        
        // Initialize canvas
        initializeCanvas();
        
        // Setup control listeners
        setupControlListeners();
        
        // Setup resize observer
        setupResizeObserver();
        
        // Load initial settings
        loadInitialSettings();
        
        // Start animation loop
        startAnimationLoop();
        
        PREVIEW_CONFIG.initialized = true;
        
        // Update status for screen readers
        updatePreviewStatus('Live preview initialized successfully');
        
        console.log('AIW Customizer Preview: Initialization complete');
    }

    /**
     * Initialize canvas background
     */
    function initializeCanvas() {
        const canvas = document.getElementById('aiw-preview-canvas');
        if (!canvas) return;
        
        PREVIEW_CONFIG.canvas = canvas;
        PREVIEW_CONFIG.ctx = canvas.getContext('2d');
        
        // Set initial canvas size
        resizeCanvas();
        
        // Initialize particles for animation
        initializeParticles();
    }

    /**
     * Resize canvas to match container
     */
    function resizeCanvas() {
        if (!PREVIEW_CONFIG.canvas) return;
        
        const container = PREVIEW_CONFIG.canvas.parentElement;
        const rect = container.getBoundingClientRect();
        
        PREVIEW_CONFIG.canvas.width = rect.width;
        PREVIEW_CONFIG.canvas.height = rect.height;
        
        // Reinitialize particles on resize
        initializeParticles();
    }

    /**
     * Initialize background particles
     */
    function initializeParticles() {
        if (PREVIEW_CONFIG.reducedMotion) return;
        
        PREVIEW_CONFIG.particles = [];
        const particleCount = 20;
        
        for (let i = 0; i < particleCount; i++) {
            PREVIEW_CONFIG.particles.push({
                x: Math.random() * PREVIEW_CONFIG.canvas.width,
                y: Math.random() * PREVIEW_CONFIG.canvas.height,
                vx: (Math.random() - 0.5) * 0.5,
                vy: (Math.random() - 0.5) * 0.5,
                size: Math.random() * 2 + 1,
                opacity: Math.random() * 0.5 + 0.1
            });
        }
    }

    /**
     * Setup resize observer for responsive behavior
     */
    function setupResizeObserver() {
        if (!window.ResizeObserver) return;
        
        const observer = new ResizeObserver(debounce(() => {
            resizeCanvas();
        }, 100));
        
        const container = document.getElementById('aiw-live-preview');
        if (container) {
            observer.observe(container);
        }
    }

    /**
     * Setup control listeners for real-time updates
     */
    function setupControlListeners() {
        // Handle different types of inputs
        addEventListeners('input[type="color"].wp-color-picker', 'change input', handleColorChange);
        addEventListeners('input[type="range"]', 'input change', handleRangeChange);
        addEventListeners('select', 'change', handleSelectChange);
        addEventListeners('input[type="checkbox"]', 'change', handleCheckboxChange);
        addEventListeners('input[type="text"]:not(.wp-color-picker), input[type="number"]', 'input', handleTextChange);
        
        // Fallback for any color inputs that aren't WP color pickers
        addEventListeners('input[type="color"]:not(.wp-color-picker)', 'change input', handleColorChange);
        
        // If jQuery is available, also use jQuery delegation
        if (hasJQuery) {
            $(document).on('change', 'input[type="text"].wp-color-picker', handleColorChange);
            $(document).on('input change', 'input[type="range"]', handleRangeChange);
            $(document).on('change', 'select', handleSelectChange);
            $(document).on('change', 'input[type="checkbox"]', handleCheckboxChange);
            $(document).on('input', 'input[type="text"]:not(.wp-color-picker), input[type="number"]', handleTextChange);
        }
    }

    /**
     * Handle color picker changes
     */
    function handleColorChange(event) {
        const input = event.target;
        const settingName = input.getAttribute('name') || input.getAttribute('id');
        const value = input.value;
        
        debouncedUpdate(settingName, value);
    }

    /**
     * Handle range slider changes
     */
    function handleRangeChange(event) {
        const input = event.target;
        const settingName = input.getAttribute('name') || input.getAttribute('id');
        const value = input.value;
        const unit = input.getAttribute('data-unit') || '';
        
        debouncedUpdate(settingName, value + unit);
    }

    /**
     * Handle select dropdown changes
     */
    function handleSelectChange(event) {
        const select = event.target;
        const settingName = select.getAttribute('name') || select.getAttribute('id');
        const value = select.value;
        
        debouncedUpdate(settingName, value);
    }

    /**
     * Handle checkbox changes
     */
    function handleCheckboxChange(event) {
        const checkbox = event.target;
        const settingName = checkbox.getAttribute('name') || checkbox.getAttribute('id');
        const value = checkbox.checked;
        
        debouncedUpdate(settingName, value);
    }

    /**
     * Handle text input changes
     */
    function handleTextChange(event) {
        const input = event.target;
        const settingName = input.getAttribute('name') || input.getAttribute('id');
        const value = input.value;
        
        debouncedUpdate(settingName, value);
    }

    /**
     * Debounced update function
     */
    function debouncedUpdate(settingName, value) {
        clearTimeout(PREVIEW_CONFIG.updateTimeout);
        
        PREVIEW_CONFIG.updateTimeout = setTimeout(() => {
            updatePreviewSetting(settingName, value);
        }, PREVIEW_CONFIG.debounceDelay);
    }

    /**
     * Update preview setting
     */
    function updatePreviewSetting(settingName, value) {
        const cssVariable = SETTINGS_MAP[settingName];
        
        if (cssVariable) {
            // Update CSS variable
            updateCSSVariable(cssVariable, value);
            
            // Handle special cases
            handleSpecialUpdates(settingName, value);
            
            // Log for debugging
            console.log(`AIW Preview: Updated ${settingName} = ${value} (${cssVariable})`);
        }
    }

    /**
     * Update CSS variable
     */
    function updateCSSVariable(variable, value) {
        document.documentElement.style.setProperty(variable, value);
    }

    /**
     * Handle special update cases
     */
    function handleSpecialUpdates(settingName, value) {
        switch (settingName) {
            case 'ai_viz_bar_count':
                updateVisualizationBars(parseInt(value));
                break;
                
            case 'ai_background_color':
                updateCanvasBackground();
                break;
                
            case 'ai_play_button_pulse_enabled':
                togglePlayButtonPulse(value);
                break;
                
            case 'ai_viz_style':
                updateVisualizationStyle(value);
                break;
        }
    }

    /**
     * Update visualization bars
     */
    function updateVisualizationBars(count) {
        if (window.aiwPreviewUpdateBars) {
            updateCSSVariable('--aiw-preview-viz-bars', count);
            window.aiwPreviewUpdateBars();
        }
    }

    /**
     * Toggle play button pulse animation
     */
    function togglePlayButtonPulse(enabled) {
        const playButton = document.getElementById('aiw-preview-play-btn');
        if (!playButton) return;
        
        if (enabled && !PREVIEW_CONFIG.reducedMotion) {
            playButton.classList.add('pulse');
        } else {
            playButton.classList.remove('pulse');
        }
    }

    /**
     * Update visualization style
     */
    function updateVisualizationStyle(style) {
        const vizContainer = document.getElementById('aiw-preview-viz');
        if (!vizContainer) return;
        
        // Remove existing style classes
        vizContainer.classList.remove('bars', 'waveform', 'smiley');
        
        // Add new style class
        if (style) {
            vizContainer.classList.add(style);
        }
    }

    /**
     * Load initial settings from form inputs
     */
    function loadInitialSettings() {
        // Collect all current form values
        Object.keys(SETTINGS_MAP).forEach(settingName => {
            const input = document.querySelector(`[name="${settingName}"], #${settingName}`);
            if (input) {
                let value;
                
                if (input.type === 'checkbox') {
                    value = input.checked;
                } else {
                    value = input.value;
                    const unit = input.getAttribute('data-unit') || '';
                    if (unit) value += unit;
                }
                
                updatePreviewSetting(settingName, value);
            }
        });
    }

    /**
     * Start animation loop for canvas and visualizations
     */
    function startAnimationLoop() {
        if (PREVIEW_CONFIG.reducedMotion) return;
        
        function animate() {
            // Update canvas background
            updateCanvas();
            
            // Continue animation
            PREVIEW_CONFIG.animationFrameId = requestAnimationFrame(animate);
        }
        
        animate();
    }

    /**
     * Update canvas background
     */
    function updateCanvas() {
        if (!PREVIEW_CONFIG.ctx || !PREVIEW_CONFIG.canvas) return;
        
        const ctx = PREVIEW_CONFIG.ctx;
        const canvas = PREVIEW_CONFIG.canvas;
        
        // Clear canvas
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        
        // Draw background gradient
        drawCanvasBackground(ctx, canvas);
        
        // Draw particles
        if (!PREVIEW_CONFIG.reducedMotion) {
            drawParticles(ctx, canvas);
        }
    }

    /**
     * Draw canvas background
     */
    function drawCanvasBackground(ctx, canvas) {
        const bgColor = getComputedStyle(document.documentElement)
            .getPropertyValue('--aiw-preview-background') || '#0a0a1a';
        const primaryColor = getComputedStyle(document.documentElement)
            .getPropertyValue('--aiw-preview-primary') || '#00cfff';
        
        // Create gradient
        const gradient = ctx.createLinearGradient(0, 0, canvas.width, canvas.height);
        gradient.addColorStop(0, bgColor);
        gradient.addColorStop(1, adjustColorOpacity(primaryColor, 0.1));
        
        ctx.fillStyle = gradient;
        ctx.fillRect(0, 0, canvas.width, canvas.height);
    }

    /**
     * Draw animated particles
     */
    function drawParticles(ctx, canvas) {
        const primaryColor = getComputedStyle(document.documentElement)
            .getPropertyValue('--aiw-preview-primary') || '#00cfff';
        
        PREVIEW_CONFIG.particles.forEach(particle => {
            // Update position
            particle.x += particle.vx;
            particle.y += particle.vy;
            
            // Wrap around edges
            if (particle.x < 0) particle.x = canvas.width;
            if (particle.x > canvas.width) particle.x = 0;
            if (particle.y < 0) particle.y = canvas.height;
            if (particle.y > canvas.height) particle.y = 0;
            
            // Draw particle
            ctx.save();
            ctx.globalAlpha = particle.opacity;
            ctx.fillStyle = primaryColor;
            ctx.beginPath();
            ctx.arc(particle.x, particle.y, particle.size, 0, Math.PI * 2);
            ctx.fill();
            ctx.restore();
        });
    }

    /**
     * Update canvas background when colors change
     */
    function updateCanvasBackground() {
        // Trigger a redraw on next frame
        if (PREVIEW_CONFIG.animationFrameId) {
            cancelAnimationFrame(PREVIEW_CONFIG.animationFrameId);
            startAnimationLoop();
        }
    }

    /**
     * Update preview status for screen readers
     */
    function updatePreviewStatus(message) {
        const statusElement = document.getElementById('aiw-preview-status');
        if (statusElement) {
            statusElement.textContent = message;
        }
    }

    /**
     * Utility: Debounce function
     */
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    /**
     * Utility: Adjust color opacity
     */
    function adjustColorOpacity(color, opacity) {
        // Simple hex to rgba conversion
        if (color.startsWith('#')) {
            const hex = color.slice(1);
            const r = parseInt(hex.slice(0, 2), 16);
            const g = parseInt(hex.slice(2, 4), 16);
            const b = parseInt(hex.slice(4, 6), 16);
            return `rgba(${r}, ${g}, ${b}, ${opacity})`;
        }
        return color;
    }

    /**
     * Cleanup function
     */
    function cleanup() {
        if (PREVIEW_CONFIG.animationFrameId) {
            cancelAnimationFrame(PREVIEW_CONFIG.animationFrameId);
        }
        
        clearTimeout(PREVIEW_CONFIG.updateTimeout);
    }

    /**
     * DOM ready handler
     */
    function onDOMReady(callback) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback);
        } else {
            callback();
        }
    }

    /**
     * Initialize when DOM is ready
     */
    onDOMReady(function() {
        // Initialize preview system
        initializePreview();
        
        // Setup cleanup on page unload
        window.addEventListener('beforeunload', cleanup);
        
        // Handle reduced motion preference changes
        const mediaQuery = window.matchMedia('(prefers-reduced-motion: reduce)');
        const handleMotionChange = function(e) {
            PREVIEW_CONFIG.reducedMotion = e.matches;
            
            if (e.matches) {
                // Stop animations
                cleanup();
                // Remove pulse classes
                const playButtons = document.querySelectorAll('.aiw-preview-play-button');
                playButtons.forEach(btn => btn.classList.remove('pulse'));
            } else {
                // Restart animations
                startAnimationLoop();
            }
        };
        
        if (mediaQuery.addListener) {
            mediaQuery.addListener(handleMotionChange);
        } else {
            mediaQuery.addEventListener('change', handleMotionChange);
        }
    });

    /**
     * Public API for external integration
     */
    window.aiwCustomizerPreview = {
        updateSetting: updatePreviewSetting,
        updateVariable: updateCSSVariable,
        refresh: function() {
            loadInitialSettings();
            updateCanvasBackground();
        }
    };

})();