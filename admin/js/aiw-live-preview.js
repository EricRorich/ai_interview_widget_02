/**
 * AI Interview Widget - Live Preview Script
 * 
 * Real-time preview system for Enhanced Widget Customizer
 * Features canvas animations, CSS variable updates, and accessibility support
 * 
 * @version 1.0.0
 * @author Eric Rorich
 * @since 1.9.5
 */

(function() {
    'use strict';

    // Debug logging
    console.log('🎨 AIW Live Preview Script Loading...');

    // Check dependencies
    const $ = window.jQuery;
    const hasJQuery = typeof $ !== 'undefined';
    const customizerData = window.aiwCustomizerData || {};
    const defaults = customizerData.defaults || {};
    const debugMode = customizerData.debug || false;

    // Check if preview handler already created the object
    const handlerExists = window.aiwLivePreview && window.aiwLivePreview.getConfig && 
                         window.aiwLivePreview.getConfig().handlerReady;
    
    if (handlerExists) {
        console.log('🔧 AIW Live Preview: Using existing object from preview handler');
        // Store reference to the handler-created object
        var handlerObject = window.aiwLivePreview;
    } else {
        console.log('🔧 AIW Live Preview: Creating new object (handler not present)');
        // Create the public API object IMMEDIATELY to prevent timing issues
        // This ensures window.aiwLivePreview is available as soon as the script starts loading
        window.aiwLivePreview = {
            // Placeholder methods - will be replaced with actual implementations below
            initialize: function() {
                console.log('🔄 aiwLivePreview.initialize() called before script fully loaded, deferring...');
            },
            updatePreview: function() {},
            updateSetting: function() {},
            updateVariable: function() {},
            resizeCanvas: function() {},
            showFallbackMessage: function() {},
            getConfig: function() { return { initialized: false }; },
            test: {},
            debug: {}
        };
    }
    
    // Logging functions
    function debugLog(...args) {
        if (debugMode) {
            console.log('[AIW Live Preview]', ...args);
        }
    }
    
    function errorLog(...args) {
        console.error('[AIW Live Preview Error]', ...args);
    }
    
    // Source map error handler - suppress 404 errors for missing source maps and handle ai-media-library issues
    const originalConsoleError = console.error;
    const originalConsoleWarn = console.warn;
    
    console.error = function(...args) {
        const message = args.join(' ');
        
        // Suppress non-critical errors that don't affect functionality
        if (message.includes('Source map error') || 
            message.includes('sourceMappingURL') || 
            message.includes('.js.map') ||
            message.includes('ai-media-library.js.map') ||
            message.includes('unreachable code after return statement') ||
            message.includes('ai-media-library.js')) {
            // Log to debug only if debug mode is enabled
            if (debugMode) {
                originalConsoleError('[AIW Debug] Non-critical error suppressed:', ...args);
            }
            return;
        }
        
        // Pass through all other errors normally
        originalConsoleError.apply(console, args);
    };
    
    // Also handle console warnings for deprecated jQuery usage
    console.warn = function(...args) {
        const message = args.join(' ');
        
        // Suppress jQuery migration warnings that don't affect functionality
        if (message.includes('JQMIGRATE') || 
            message.includes('jQuery.fn.click() event shorthand is deprecated')) {
            if (debugMode) {
                originalConsoleWarn('[AIW Debug] jQuery deprecation warning suppressed:', ...args);
            }
            return;
        }
        
        // Pass through all other warnings normally
        originalConsoleWarn.apply(console, args);
    };
    
    console.log('✅ AIW Live Preview Script Loaded', {
        version: '1.0.0',
        debugMode: debugMode,
        hasJQuery: hasJQuery,
        customizerDataAvailable: !!customizerData,
        aiwLivePreviewCreated: !!window.aiwLivePreview
    });
    
    // Ensure aiwTranslationDebug object is available for debugging
    if (typeof window.aiwTranslationDebug === 'undefined') {
        window.aiwTranslationDebug = {
            logs: [],
            pushLog: function(level, message) {
                this.logs.push({
                    timestamp: new Date().toISOString(),
                    level: level,
                    message: message
                });
                if (debugMode) {
                    console.log(`[AIW Translation Debug] ${level}: ${message}`);
                }
            },
            clear: function() {
                this.logs = [];
                if (debugMode) {
                    console.log('[AIW Translation Debug] Log cleared');
                }
            },
            getLogs: function() {
                return this.logs;
            }
        };
        debugLog('✅ aiwTranslationDebug object initialized');
    }
    
    debugLog('🎨 Initializing Live Preview System...');

    // Configuration
    const PREVIEW_CONFIG = {
        initialized: false,
        canvas: null,
        ctx: null,
        updateTimeout: null,
        debounceDelay: 50, // Fast updates for real-time feel
        animationFrameId: null,
        reducedMotion: window.matchMedia('(prefers-reduced-motion: reduce)').matches,
        particles: [],
        vizBars: []
    };

    // Settings mapping for CSS variables
    const SETTINGS_MAP = {
        // Colors
        'ai_primary_color': '--aiw-color-primary',
        'ai_accent_color': '--aiw-color-accent', 
        'ai_background_color': '--aiw-color-background',
        'ai_text_color': '--aiw-color-text',
        
        // Shapes
        'ai_border_radius': '--aiw-radius',
        'ai_border_width': '--aiw-border-width',
        'ai_shadow_intensity': '--aiw-shadow',
        
        // Play button
        'ai_play_button_size': '--aiw-play-size',
        'ai_play_button_color': '--aiw-play-color',
        'ai_play_button_icon_color': '--aiw-play-icon-color',
        'ai_play_hover_scale': '--aiw-play-hover-scale',
        
        // Audio visualization
        'ai_viz_bar_count': '--aiw-visual-bars',
        'ai_viz_bar_gap': '--aiw-visual-gap',
        'ai_viz_color': '--aiw-visual-color',
        'ai_viz_line_width': '--aiw-visual-line-width',
        'ai_viz_glow': '--aiw-visual-glow',
        'ai_viz_speed': '--aiw-visual-speed',
        
        // Chatbox
        'ai_chat_bg': '--aiw-chat-bg',
        'ai_chat_bubble_radius': '--aiw-chat-bubble-radius',
        'ai_chat_avatar_size': '--aiw-chat-avatar-size',
        'ai_chat_spacing': '--aiw-chat-spacing'
    };

    /**
     * Initialize the live preview system
     */
    function initializePreviewSystem() {
        if (PREVIEW_CONFIG.initialized) {
            debugLog('Preview already initialized');
            return;
        }
        
        debugLog('🖼️ Initializing Live Preview System...');
        
        // Validate requirements
        if (!validateRequirements()) {
            errorLog('❌ Preview requirements not met');
            showFallbackMessage('Preview container not found. Please refresh the page.');
            return;
        }
        
        let initializationSuccess = true;
        const failedComponents = [];
        
        try {
            // Initialize components one by one, continue even if some fail
            try {
                initializeCanvas();
                debugLog('✅ Canvas initialized');
            } catch (error) {
                errorLog('⚠️ Canvas initialization failed:', error);
                failedComponents.push('Canvas');
                // Continue without canvas
            }
            
            try {
                initializeVisualizationBars();
                debugLog('✅ Visualization bars initialized');
            } catch (error) {
                errorLog('⚠️ Visualization bars initialization failed:', error);
                failedComponents.push('Visualization');
                // Continue without visualization
            }
            
            try {
                setupControlListeners();
                debugLog('✅ Control listeners setup');
            } catch (error) {
                errorLog('⚠️ Control listeners setup failed:', error);
                failedComponents.push('Controls');
                // Continue without some controls
            }
            
            try {
                setupResizeObserver();
                debugLog('✅ Resize observer setup');
            } catch (error) {
                errorLog('⚠️ Resize observer setup failed:', error);
                // Not critical, continue
            }
            
            try {
                loadInitialSettings();
                debugLog('✅ Initial settings loaded');
            } catch (error) {
                errorLog('⚠️ Initial settings loading failed:', error);
                // Continue with defaults
            }
            
            try {
                if (!PREVIEW_CONFIG.reducedMotion) {
                    startAnimationLoop();
                    debugLog('✅ Animation loop started');
                } else {
                    debugLog('ℹ️ Animations disabled (reduced motion preference)');
                }
            } catch (error) {
                errorLog('⚠️ Animation loop failed:', error);
                // Continue without animations
            }
            
            PREVIEW_CONFIG.initialized = true;
            
            // Setup retry button for preview loading
            setupRetryButton();
            
            // Load the actual widget preview via AJAX
            loadPreview();
            
            // Update UI state
            hideLoading();
            showPreview();
            
            // Show status based on success level
            if (failedComponents.length === 0) {
                updatePreviewStatus('Live preview initialized successfully');
                debugLog('✅ Live Preview System fully initialized');
            } else {
                updatePreviewStatus(`Live preview initialized with limited features (${failedComponents.join(', ')} unavailable)`);
                debugLog(`⚠️ Live Preview System partially initialized. Failed components: ${failedComponents.join(', ')}`);
            }
            
        } catch (error) {
            errorLog('❌ Critical failure during preview initialization:', error);
            showFallbackMessage('Preview initialization failed. Your settings are still being saved.');
        }
    }

    /**
     * Validate system requirements
     */
    function validateRequirements() {
        debugLog('🔍 Validating requirements...');
        
        const issues = [];
        const warnings = [];
        
        // Check for preview container (critical)
        const container = document.getElementById('aiw-live-preview');
        if (!container) {
            issues.push('Missing preview container #aiw-live-preview');
            return false; // This is critical, fail immediately
        }
        
        // Check for preview sections (non-critical, can work without some sections)
        const sections = [
            '.aiw-preview-section.aiw-preview-playbutton',
            '.aiw-preview-section.aiw-preview-audiovis', 
            '.aiw-preview-section.aiw-preview-chatbox'
        ];
        
        sections.forEach(selector => {
            if (!document.querySelector(selector)) {
                warnings.push(`Optional section missing: ${selector}`);
            }
        });
        
        // Check for canvas (non-critical, preview can work without canvas)
        const canvas = document.querySelector('.aiw-preview-canvas');
        if (!canvas) {
            warnings.push('Canvas element not found - animations will be disabled');
        }
        
        // Check for WordPress media dependencies (optional)
        if (typeof wp === 'undefined' || typeof wp.media === 'undefined') {
            warnings.push('WordPress media scripts not loaded - some features may be limited');
        }
        
        // Log warnings but don't fail
        if (warnings.length > 0) {
            debugLog('⚠️ Non-critical issues detected:', warnings);
        }
        
        // Only fail on critical issues
        if (issues.length > 0) {
            errorLog('❌ Critical validation failed:', issues);
            return false;
        }
        
        debugLog('✅ Core requirements met, proceeding with initialization');
        return true;
    }

    /**
     * Initialize canvas background
     */
    function initializeCanvas() {
        const canvas = document.querySelector('.aiw-preview-canvas');
        if (!canvas) {
            debugLog('⚠️ Canvas not found, skipping canvas initialization');
            return;
        }
        
        try {
            PREVIEW_CONFIG.canvas = canvas;
            PREVIEW_CONFIG.ctx = canvas.getContext('2d');
            
            if (!PREVIEW_CONFIG.ctx) {
                throw new Error('Failed to get 2D context');
            }
            
            // Set canvas size
            resizeCanvas();
            
            // Initialize particles
            if (!PREVIEW_CONFIG.reducedMotion) {
                initializeParticles();
            }
            
            debugLog('✅ Canvas initialized');
            
        } catch (error) {
            errorLog('❌ Canvas initialization failed:', error);
        }
    }

    /**
     * Resize canvas to match container
     */
    function resizeCanvas() {
        if (!PREVIEW_CONFIG.canvas) return;
        
        const container = PREVIEW_CONFIG.canvas.parentElement;
        const rect = container.getBoundingClientRect();
        const devicePixelRatio = window.devicePixelRatio || 1;
        
        // Set display size
        PREVIEW_CONFIG.canvas.style.width = rect.width + 'px';
        PREVIEW_CONFIG.canvas.style.height = rect.height + 'px';
        
        // Set actual size in memory (account for pixel ratio)
        PREVIEW_CONFIG.canvas.width = rect.width * devicePixelRatio;
        PREVIEW_CONFIG.canvas.height = rect.height * devicePixelRatio;
        
        // Scale context to match device pixel ratio
        PREVIEW_CONFIG.ctx.scale(devicePixelRatio, devicePixelRatio);
        
        // Reinitialize particles on resize
        if (!PREVIEW_CONFIG.reducedMotion) {
            initializeParticles();
        }
    }

    /**
     * Initialize background particles
     */
    function initializeParticles() {
        if (!PREVIEW_CONFIG.canvas || PREVIEW_CONFIG.reducedMotion) return;
        
        PREVIEW_CONFIG.particles = [];
        const particleCount = Math.min(15, Math.floor(PREVIEW_CONFIG.canvas.width / 50));
        
        for (let i = 0; i < particleCount; i++) {
            PREVIEW_CONFIG.particles.push({
                x: Math.random() * PREVIEW_CONFIG.canvas.width,
                y: Math.random() * PREVIEW_CONFIG.canvas.height,
                vx: (Math.random() - 0.5) * 0.3,
                vy: (Math.random() - 0.5) * 0.3,
                size: Math.random() * 1.5 + 0.5,
                opacity: Math.random() * 0.3 + 0.1
            });
        }
    }

    /**
     * Initialize visualization bars
     */
    function initializeVisualizationBars() {
        const vizContainer = document.querySelector('.aiw-preview-visualization');
        if (!vizContainer) {
            debugLog('⚠️ Visualization container not found');
            return;
        }
        
        // Clear existing bars
        vizContainer.innerHTML = '';
        
        // Create default number of bars
        const barCount = parseInt(getComputedStyle(document.documentElement)
            .getPropertyValue('--aiw-visual-bars')) || 12;
        
        for (let i = 0; i < barCount; i++) {
            const bar = document.createElement('div');
            bar.className = 'aiw-preview-viz-bar';
            bar.setAttribute('aria-hidden', 'true');
            vizContainer.appendChild(bar);
        }
        
        // Store reference
        PREVIEW_CONFIG.vizBars = Array.from(vizContainer.children);
        
        // Add screen reader text
        const srText = document.createElement('span');
        srText.className = 'screen-reader-text';
        srText.textContent = 'Animated frequency bars showing current visualization style';
        vizContainer.appendChild(srText);
        
        debugLog('✅ Visualization bars initialized:', barCount);
    }

    /**
     * Setup control listeners for real-time updates
     */
    function setupControlListeners() {
        debugLog('Setting up control listeners...');
        
        try {
            // Use event delegation for better performance
            document.addEventListener('input', handleInputChange);
            document.addEventListener('change', handleInputChange);
            
            // Also setup jQuery delegation if available
            if (hasJQuery) {
                $(document).on('input change', handleInputChangeJQuery);
            }
            
            debugLog('✅ Control listeners setup complete');
            
        } catch (error) {
            errorLog('❌ Failed to setup control listeners:', error);
        }
    }

    /**
     * Handle input changes (vanilla JS)
     */
    function handleInputChange(event) {
        const input = event.target;
        
        // Check if this is a preview-related input
        if (!isPreviewInput(input)) return;
        
        const settingName = getSettingName(input);
        const value = getInputValue(input);
        
        if (settingName && value !== null) {
            debugLog(`Input change: ${settingName} = ${value}`);
            debouncedUpdate(settingName, value);
        }
    }

    /**
     * Handle input changes (jQuery)
     */
    function handleInputChangeJQuery(event) {
        // Use same logic as vanilla handler
        handleInputChange(event);
    }

    /**
     * Check if input is preview-related
     */
    function isPreviewInput(input) {
        const name = input.getAttribute('name') || input.getAttribute('id') || '';
        return name.includes('style') || name.includes('ai_') || 
               input.classList.contains('wp-color-picker');
    }

    /**
     * Get setting name from input
     */
    function getSettingName(input) {
        let name = input.getAttribute('name') || input.getAttribute('id');
        if (!name) return null;
        
        // Extract setting name from WordPress array notation
        const match = name.match(/\[([^\]]+)\]$/);
        if (match) {
            return match[1];
        }
        
        return name;
    }

    /**
     * Get processed input value
     */
    function getInputValue(input) {
        switch (input.type) {
            case 'checkbox':
                return input.checked;
            case 'range':
                const unit = input.getAttribute('data-unit') || '';
                return input.value + unit;
            default:
                return input.value;
        }
    }

    /**
     * Debounced update function
     */
    function debouncedUpdate(settingName, value) {
        // Update CSS variable immediately for responsive feel
        const cssVariable = SETTINGS_MAP[settingName];
        if (cssVariable) {
            updateCSSVariable(cssVariable, value);
        }
        
        // Handle special cases
        handleSpecialUpdates(settingName, value);
        
        // Clear existing timeout
        clearTimeout(PREVIEW_CONFIG.updateTimeout);
        
        // Debounce more complex updates
        PREVIEW_CONFIG.updateTimeout = setTimeout(() => {
            updatePreview();
        }, PREVIEW_CONFIG.debounceDelay);
    }

    /**
     * Update CSS variable
     */
    function updateCSSVariable(variable, value) {
        document.documentElement.style.setProperty(variable, value);
        debugLog(`CSS variable updated: ${variable} = ${value}`);
    }

    /**
     * Handle special update cases that need custom logic
     */
    function handleSpecialUpdates(settingName, value) {
        switch (settingName) {
            case 'ai_viz_bar_count':
                updateVisualizationBarCount(parseInt(value));
                break;
                
            case 'ai_play_button_pulse_enabled':
                togglePlayButtonPulse(value);
                break;
                
            case 'ai_viz_style':
                updateVisualizationStyle(value);
                break;
                
            case 'ai_background_color':
            case 'ai_primary_color':
                // Canvas needs redraw when colors change
                if (PREVIEW_CONFIG.canvas) {
                    requestAnimationFrame(() => updateCanvas());
                }
                break;
        }
    }

    /**
     * Update visualization bar count
     */
    function updateVisualizationBarCount(count) {
        if (count < 1 || count > 24) return; // Reasonable limits
        
        updateCSSVariable('--aiw-visual-bars', count);
        
        // Rebuild bars
        setTimeout(() => {
            initializeVisualizationBars();
        }, 50);
    }

    /**
     * Toggle play button pulse animation
     */
    function togglePlayButtonPulse(enabled) {
        const playButton = document.querySelector('.aiw-preview-play-button');
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
        const vizContainer = document.querySelector('.aiw-preview-visualization');
        if (!vizContainer) return;
        
        // Remove existing style classes
        vizContainer.classList.remove('bars', 'waveform', 'circular');
        
        // Add new style class
        if (style && style !== 'default') {
            vizContainer.classList.add(style);
        }
    }

    /**
     * Load initial settings
     */
    function loadInitialSettings() {
        debugLog('Loading initial settings...');
        
        let loaded = 0;
        let fromDefaults = 0;
        
        Object.keys(SETTINGS_MAP).forEach(settingName => {
            const input = document.querySelector(`[name*="${settingName}"], #${settingName}`);
            let value = null;
            
            if (input && input.value !== '' && input.value !== null) {
                value = getInputValue(input);
                loaded++;
            } else if (defaults[settingName] !== undefined) {
                value = defaults[settingName];
                fromDefaults++;
            }
            
            if (value !== null) {
                const cssVar = SETTINGS_MAP[settingName];
                if (cssVar) {
                    updateCSSVariable(cssVar, value);
                    handleSpecialUpdates(settingName, value);
                }
            }
        });
        
        debugLog(`Settings loaded: ${loaded} from form, ${fromDefaults} from defaults`);
    }

    /**
     * Update preview (main update function)
     */
    function updatePreview() {
        debugLog('Updating preview...');
        
        try {
            // Update canvas if needed
            if (PREVIEW_CONFIG.canvas && !document.hidden) {
                updateCanvas();
            }
            
            // Reload widget preview with new settings
            if (PREVIEW_CONFIG.initialized) {
                loadPreview();
            }
            
            debugLog('✅ Preview updated');
            
        } catch (error) {
            errorLog('❌ Preview update failed:', error);
        }
    }

    /**
     * Start animation loop
     */
    function startAnimationLoop() {
        if (PREVIEW_CONFIG.reducedMotion) {
            debugLog('Animation loop skipped - reduced motion enabled');
            return;
        }
        
        if (document.hidden) {
            debugLog('Animation loop skipped - tab hidden');
            return;
        }
        
        // Cancel existing animation
        if (PREVIEW_CONFIG.animationFrameId) {
            cancelAnimationFrame(PREVIEW_CONFIG.animationFrameId);
        }
        
        debugLog('Starting animation loop');
        
        function animate() {
            if (PREVIEW_CONFIG.reducedMotion || document.hidden) {
                PREVIEW_CONFIG.animationFrameId = null;
                return;
            }
            
            try {
                updateCanvas();
                PREVIEW_CONFIG.animationFrameId = requestAnimationFrame(animate);
            } catch (error) {
                errorLog('Animation error:', error);
                PREVIEW_CONFIG.animationFrameId = null;
            }
        }
        
        animate();
    }

    /**
     * Update canvas background and particles
     */
    function updateCanvas() {
        if (!PREVIEW_CONFIG.ctx || !PREVIEW_CONFIG.canvas) return;
        
        const ctx = PREVIEW_CONFIG.ctx;
        const canvas = PREVIEW_CONFIG.canvas;
        
        // Clear canvas
        ctx.clearRect(0, 0, canvas.width / (window.devicePixelRatio || 1), 
                     canvas.height / (window.devicePixelRatio || 1));
        
        // Draw background gradient
        drawBackground(ctx, canvas);
        
        // Draw particles
        if (!PREVIEW_CONFIG.reducedMotion && PREVIEW_CONFIG.particles.length > 0) {
            drawParticles(ctx, canvas);
        }
    }

    /**
     * Draw canvas background
     */
    function drawBackground(ctx, canvas) {
        const bgColor = getComputedStyle(document.documentElement)
            .getPropertyValue('--aiw-color-background').trim() || '#0a0a1a';
        const primaryColor = getComputedStyle(document.documentElement)
            .getPropertyValue('--aiw-color-primary').trim() || '#00cfff';
        
        const width = canvas.width / (window.devicePixelRatio || 1);
        const height = canvas.height / (window.devicePixelRatio || 1);
        
        // Create gradient
        const gradient = ctx.createLinearGradient(0, 0, width, height);
        gradient.addColorStop(0, bgColor);
        gradient.addColorStop(1, adjustColorOpacity(primaryColor, 0.05));
        
        ctx.fillStyle = gradient;
        ctx.fillRect(0, 0, width, height);
    }

    /**
     * Draw animated particles
     */
    function drawParticles(ctx, canvas) {
        const primaryColor = getComputedStyle(document.documentElement)
            .getPropertyValue('--aiw-color-primary').trim() || '#00cfff';
        
        const width = canvas.width / (window.devicePixelRatio || 1);
        const height = canvas.height / (window.devicePixelRatio || 1);
        
        PREVIEW_CONFIG.particles.forEach(particle => {
            // Update position
            particle.x += particle.vx;
            particle.y += particle.vy;
            
            // Wrap around edges
            if (particle.x < 0) particle.x = width;
            if (particle.x > width) particle.x = 0;
            if (particle.y < 0) particle.y = height;
            if (particle.y > height) particle.y = 0;
            
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
     * Setup resize observer
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
     * Show/hide UI states
     */
    function hideLoading() {
        const loading = document.getElementById('preview-loading');
        if (loading) loading.style.display = 'none';
    }

    function showPreview() {
        const container = document.getElementById('aiw-live-preview');
        if (container) container.style.display = 'flex';
        
        hideLoading();
        
        const error = document.getElementById('preview-error');
        if (error) error.style.display = 'none';
        
        const fallback = document.getElementById('preview-fallback');
        if (fallback) fallback.style.display = 'none';
    }

    function showFallbackMessage(message) {
        hideLoading();
        
        const fallback = document.getElementById('preview-fallback');
        if (fallback) {
            const messageElement = fallback.querySelector('p');
            if (messageElement) {
                messageElement.textContent = message || 
                    'Preview temporarily unavailable. Your settings are being saved.';
            }
            fallback.style.display = 'block';
        }
    }

    function updatePreviewStatus(message) {
        const status = document.getElementById('aiw-preview-status');
        if (status) {
            status.textContent = message;
        }
    }

    /**
     * Utility functions
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

    function adjustColorOpacity(color, opacity) {
        color = color.trim();
        
        if (color.startsWith('#')) {
            const hex = color.slice(1);
            const r = parseInt(hex.slice(0, 2), 16);
            const g = parseInt(hex.slice(2, 4), 16);
            const b = parseInt(hex.slice(4, 6), 16);
            return `rgba(${r}, ${g}, ${b}, ${opacity})`;
        }
        
        if (color.startsWith('rgb(')) {
            return color.replace('rgb(', 'rgba(').replace(')', `, ${opacity})`);
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
     * Handle reduced motion changes
     */
    function handleReducedMotionChange(mediaQuery) {
        PREVIEW_CONFIG.reducedMotion = mediaQuery.matches;
        debugLog('Reduced motion changed:', mediaQuery.matches);
        
        if (mediaQuery.matches) {
            cleanup();
            // Remove animation classes
            document.querySelectorAll('.pulse').forEach(el => 
                el.classList.remove('pulse'));
        } else if (!document.hidden) {
            startAnimationLoop();
        }
    }

    /**
     * Handle visibility changes
     */
    function handleVisibilityChange() {
        if (document.hidden) {
            debugLog('Tab hidden, pausing animations');
            if (PREVIEW_CONFIG.animationFrameId) {
                cancelAnimationFrame(PREVIEW_CONFIG.animationFrameId);
                PREVIEW_CONFIG.animationFrameId = null;
            }
        } else {
            debugLog('Tab visible, resuming animations');
            if (PREVIEW_CONFIG.initialized && !PREVIEW_CONFIG.reducedMotion) {
                startAnimationLoop();
            }
        }
    }

    /**
     * Setup retry button functionality
     */
    function setupRetryButton() {
        const retryButton = document.getElementById('retry-preview');
        if (retryButton) {
            retryButton.addEventListener('click', function() {
                debugLog('🔄 Manual retry requested via button');
                loadPreview();
            });
            debugLog('✅ Retry button event listener attached');
        } else {
            debugLog('⚠️ Retry button #retry-preview not found');
        }
    }

    /**
     * Collect current settings from form controls
     */
    function collectCurrentSettings() {
        const style = {};
        const content = {};
        
        // Collect style settings from form controls
        const styleInputs = document.querySelectorAll('input[name*="style"], select[name*="style"]');
        styleInputs.forEach(input => {
            const name = input.name.replace(/^.*\[(.+)\]$/, '$1');
            if (input.type === 'checkbox') {
                style[name] = input.checked;
            } else {
                style[name] = input.value;
            }
        });
        
        // Collect content settings from form controls
        const contentInputs = document.querySelectorAll('input[name*="content"], textarea[name*="content"]');
        contentInputs.forEach(input => {
            const name = input.name.replace(/^.*\[(.+)\]$/, '$1');
            content[name] = input.value;
        });
        
        // If no form controls found, use defaults
        if (Object.keys(style).length === 0 && customizerData?.defaults) {
            debugLog('No form controls found, using defaults');
            style.primary_color = customizerData.defaults.ai_primary_color || '#00cfff';
            style.accent_color = customizerData.defaults.ai_accent_color || '#ff6b35';
            style.background_color = customizerData.defaults.ai_background_color || '#0a0a1a';
        }
        
        return { style, content };
    }

    /**
     * Load widget preview via AJAX
     * Renders the actual widget content in the preview area
     */
    function loadPreview() {
        debugLog('🔄 Loading widget preview via AJAX...');
        
        // Validate requirements
        if (!customizerData) {
            errorLog('❌ customizerData not available');
            showFallbackMessage('Configuration error: Data not loaded');
            return;
        }
        
        if (!customizerData.ajaxurl) {
            errorLog('❌ AJAX URL not available');
            showFallbackMessage('Configuration error: AJAX URL missing');
            return;
        }
        
        if (!customizerData.nonce) {
            errorLog('❌ Security nonce not available');
            showFallbackMessage('Configuration error: Security nonce missing');
            return;
        }
        
        // Show loading state
        const loadingEl = document.getElementById('preview-loading');
        if (loadingEl) loadingEl.style.display = 'block';
        
        // Hide other states
        const errorEl = document.getElementById('preview-error');
        const fallbackEl = document.getElementById('preview-fallback');
        if (errorEl) errorEl.style.display = 'none';
        if (fallbackEl) fallbackEl.style.display = 'none';
        
        // Collect current settings from form controls
        const settings = collectCurrentSettings();
        debugLog('📊 Settings collected:', settings);
        
        // Make AJAX request to render preview
        const formData = new FormData();
        formData.append('action', 'ai_interview_render_preview');
        formData.append('nonce', customizerData.nonce);
        formData.append('style_settings', JSON.stringify(settings.style));
        formData.append('content_settings', JSON.stringify(settings.content));
        
        debugLog('🌐 Making AJAX request to:', customizerData.ajaxurl);
        
        fetch(customizerData.ajaxurl, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            debugLog('📡 AJAX response received, status:', response.status);
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            debugLog('📋 AJAX response data:', data);
            if (data.success && data.data && data.data.html) {
                // Successfully loaded widget HTML
                debugLog('✅ Widget preview loaded successfully');
                
                // Hide loading state
                if (loadingEl) loadingEl.style.display = 'none';
                
                // Show the preview container with widget content
                const previewContainer = document.getElementById('aiw-preview-canvas-container');
                if (previewContainer) {
                    // Insert the widget HTML into a dedicated preview area
                    let widgetContainer = document.getElementById('aiw-widget-preview');
                    if (!widgetContainer) {
                        widgetContainer = document.createElement('div');
                        widgetContainer.id = 'aiw-widget-preview';
                        widgetContainer.style.cssText = `
                            position: relative;
                            z-index: 3;
                            padding: 20px;
                            background: rgba(255,255,255,0.05);
                            border-radius: 8px;
                            margin-top: 20px;
                        `;
                        previewContainer.appendChild(widgetContainer);
                    }
                    
                    // Insert the widget HTML
                    widgetContainer.innerHTML = data.data.html;
                    previewContainer.style.display = 'block';
                    
                    updatePreviewStatus('Widget preview loaded successfully');
                } else {
                    debugLog('⚠️ Preview container not found');
                }
                
            } else {
                throw new Error(data.data?.message || 'Unknown error occurred');
            }
        })
        .catch(error => {
            errorLog('❌ AJAX request failed:', error);
            
            // Hide loading
            if (loadingEl) loadingEl.style.display = 'none';
            
            // Show error state
            if (errorEl) {
                const messageEl = document.getElementById('preview-error-message');
                if (messageEl) {
                    messageEl.textContent = `Preview loading failed: ${error.message}`;
                }
                errorEl.style.display = 'block';
            } else {
                showFallbackMessage(`Preview loading failed: ${error.message}`);
            }
        });
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
     * Initialize wrapper that handles DOM ready timing
     * Made idempotent to safely handle multiple calls
     */
    function initializeWhenReady() {
        // Prevent multiple simultaneous initialization attempts
        if (initializeWhenReady._initializing) {
            debugLog('Initialize already in progress, ignoring duplicate call');
            return;
        }
        
        if (PREVIEW_CONFIG.initialized) {
            debugLog('Preview system already initialized');
            return;
        }
        
        initializeWhenReady._initializing = true;
        
        onDOMReady(function() {
            debugLog('🚀 DOM ready, initializing...');
            
            try {
                // Initialize preview system
                initializePreviewSystem();
                
                // Setup event listeners
                window.addEventListener('beforeunload', cleanup);
                document.addEventListener('visibilitychange', handleVisibilityChange);
                
                // Handle reduced motion preference changes
                const mediaQuery = window.matchMedia('(prefers-reduced-motion: reduce)');
                if (mediaQuery.addListener) {
                    mediaQuery.addListener(handleReducedMotionChange);
                } else {
                    mediaQuery.addEventListener('change', handleReducedMotionChange);
                }
                
            } catch (error) {
                errorLog('❌ Initialization failed:', error);
                showFallbackMessage('Preview initialization failed');
            } finally {
                initializeWhenReady._initializing = false;
            }
        });
    }

    /**
     * Replace placeholder methods with actual implementations
     * Use preview handler's replacement system if available
     */
    const fullSystemObject = {
        initialize: initializeWhenReady,
        updatePreview: updatePreview,
        loadPreview: loadPreview,
        updateSetting: function(settingName, value) {
            debouncedUpdate(settingName, value);
        },
        updateVariable: updateCSSVariable,
        resizeCanvas: resizeCanvas,
        showFallbackMessage: showFallbackMessage,
        getConfig: function() {
            return {
                initialized: PREVIEW_CONFIG.initialized,
                reducedMotion: PREVIEW_CONFIG.reducedMotion,
                hasCanvas: !!PREVIEW_CONFIG.canvas,
                version: '1.0.0'
            };
        },
        test: {
            validateRequirements: validateRequirements,
            initializeCanvas: initializeCanvas,
            initializeBars: initializeVisualizationBars,
            startAnimation: startAnimationLoop
        },
        debug: {
            log: debugLog,
            error: errorLog
        }
    };
    
    // Use preview handler replacement system if available
    if (handlerExists && window.aiwPreviewHandler && window.aiwPreviewHandler.replaceWithFullSystem) {
        console.log('🔧 AIW Live Preview: Using handler replacement system');
        window.aiwPreviewHandler.replaceWithFullSystem(fullSystemObject);
    } else {
        console.log('🔧 AIW Live Preview: Direct assignment (no handler)');
        // Direct assignment for backward compatibility
        Object.assign(window.aiwLivePreview, fullSystemObject);
    }

    console.log('✅ aiwLivePreview API methods assigned');

    // Auto-initialize when script loads (but wait for DOM ready internally)
    initializeWhenReady();

})();