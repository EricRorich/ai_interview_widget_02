/**
 * AI Interview Widget - Customizer Preview Script
 * 
 * Real-time data binding, canvas background, visualization animation,
 * and live CSS variable updates for Enhanced Widget Customizer preview
 * 
 * @version 1.0.1
 * @author Eric Rorich
 * @since 1.9.5
 */

(function() {
    'use strict';

    // Immediate loading confirmation
    console.log('🎨 AIW Customizer Preview Script Loading...');

    // Check if jQuery is available
    const $ = window.jQuery;
    const hasJQuery = typeof $ !== 'undefined';
    
    // Check for localized data
    const customizerData = window.aiwCustomizerData || {};
    const defaults = customizerData.defaults || {};
    const debugMode = customizerData.debug || false;
    
    // Debug logging function
    function debugLog(...args) {
        if (debugMode) {
            console.log('[AIW Customizer Preview]', ...args);
        }
    }
    
    // Error logging function  
    function errorLog(...args) {
        console.error('[AIW Customizer Preview Error]', ...args);
    }
    
    // Always log script loading (even without debug mode)
    console.log('✅ AIW Customizer Preview Script Loaded Successfully');
    console.log('📋 Script Info:', {
        version: '1.0.1',
        debugMode: debugMode,
        hasJQuery: hasJQuery,
        customizerDataAvailable: !!customizerData,
        defaults: defaults
    });
    
    debugLog('🎨 Initializing Enhanced Widget Customizer Preview System...');
    debugLog('Debug mode:', debugMode);
    debugLog('Available defaults:', defaults);
    debugLog('jQuery available:', hasJQuery);

    // Configuration object
    const PREVIEW_CONFIG = {
        initialized: false,
        canvas: null,
        ctx: null,
        updateTimeout: null,
        debounceDelay: 500,
        retryCount: 0,
        maxRetries: 3,
        particles: [],
        animationFrameId: null,
        reducedMotion: window.matchMedia('(prefers-reduced-motion: reduce)').matches,
        fallbackMessageHidden: false,
        
        // Preview mode: now always 'canvas' (DOM-based approach)
        mode: 'canvas'
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
     * Validate preview system requirements
     */
    function validatePreviewRequirements() {
        debugLog('🔍 Validating preview system requirements...');
        
        const issues = [];
        
        // Check for required global data with detailed logging
        if (!customizerData) {
            issues.push('Missing customizerData object');
            errorLog('❌ customizerData is undefined - script loading issue?');
        } else {
            debugLog('✅ customizerData exists');
            if (!customizerData.ajaxurl) {
                issues.push('Missing AJAX URL');
                errorLog('❌ customizerData.ajaxurl is missing');
            } else {
                debugLog('✅ AJAX URL:', customizerData.ajaxurl);
            }
            if (!customizerData.nonce) {
                issues.push('Missing security nonce');
                errorLog('❌ customizerData.nonce is missing');
            } else {
                debugLog('✅ Security nonce available');
            }
            if (!customizerData.defaults) {
                issues.push('Missing defaults data');
                errorLog('❌ customizerData.defaults is missing');
            } else {
                debugLog('✅ Defaults available:', Object.keys(customizerData.defaults).length, 'keys');
            }
        }
        
        // Check for required DOM elements with detailed logging
        const requiredElements = [
            'aiw-live-preview',
            'aiw-preview-canvas-container',
            'aiw-preview-canvas',
            'preview-loading',
            'preview-error'
        ];
        
        debugLog('🔍 Checking DOM elements...');
        requiredElements.forEach(id => {
            const element = document.getElementById(id);
            if (!element) {
                issues.push(`Missing DOM element: #${id}`);
                errorLog(`❌ DOM element not found: #${id}`);
            } else {
                debugLog(`✅ DOM element found: #${id}`);
            }
        });
        
        if (issues.length > 0) {
            errorLog('❌ Preview system validation failed:', issues);
            errorLog('💡 Possible fixes:');
            errorLog('  - Ensure customizerData is localized before script execution');
            errorLog('  - Verify customizer-preview.php partial is included');
            errorLog('  - Check for JavaScript errors preventing DOM rendering');
            return false;
        }
        
        debugLog('✅ All preview system requirements met');
        return true;
    }

    /**
     * Cross-browser element selector helper
     */
    function getElements(selector) {
        return document.querySelectorAll(selector);
    }

    /**
     * Initialize the preview system (DOM/Canvas based)
     */
    function initializePreview() {
        if (PREVIEW_CONFIG.initialized) {
            debugLog('Preview already initialized, skipping');
            return;
        }
        
        debugLog('🖼️ Initializing Live Widget Preview (DOM/Canvas mode)...');
        
        // Validate system requirements first
        if (!validatePreviewRequirements()) {
            errorLog('❌ Preview system requirements not met, aborting initialization');
            showPreviewError('Preview system validation failed. Please check console for details.');
            return;
        }
        
        // Check if we're on the customizer page
        const previewContainer = document.getElementById('aiw-live-preview');
        if (!previewContainer) {
            errorLog('❌ Preview container #aiw-live-preview not found, aborting initialization');
            showPreviewError('Preview container not found. Please refresh the page.');
            return;
        }
        
        debugLog('✅ Preview container found: #aiw-live-preview');
        
        try {
            // Initialize DOM/Canvas-based preview system
            PREVIEW_CONFIG.mode = 'canvas';
            initializeCanvasPreview();
            debugLog('✅ Preview canvas initialized');
            
            // Setup control listeners
            debugLog('Setting up control listeners...');
            setupControlListeners();
            
            PREVIEW_CONFIG.initialized = true;
            
            // Hide loading, show preview
            hidePreviewLoading();
            showPreviewCanvas();
            
            // Update status for screen readers
            updatePreviewStatus('Live preview initialized successfully (DOM/Canvas mode)');
            
            debugLog('✅ Live Widget Preview fully initialized');
            
        } catch (error) {
            errorLog('❌ Failed to initialize preview:', error);
            showPreviewError('Preview initialization failed. Please refresh the page.');
        }
    }
    
    /**
     * Hide preview loading state
     */
    function hidePreviewLoading() {
        const loadingElement = document.getElementById('preview-loading');
        if (loadingElement) {
            loadingElement.style.display = 'none';
        }
    }
    
    /**
     * Show preview canvas (hide loading/error states)
     */
    function showPreviewCanvas() {
        document.getElementById('preview-loading').style.display = 'none';
        document.getElementById('preview-error').style.display = 'none';
        
        const canvasContainer = document.getElementById('aiw-preview-canvas-container');
        if (canvasContainer) {
            canvasContainer.style.display = 'block';
            debugLog('✅ Canvas preview shown');
        }
    }
    
    /**
     * Show preview error
     */
    function showPreviewError(message) {
        hidePreviewLoading();
        
        const errorElement = document.getElementById('preview-error');
        const errorMessage = document.getElementById('preview-error-message');
        
        if (errorElement && errorMessage) {
            errorMessage.textContent = message || 'An error occurred while loading the preview.';
            errorElement.style.display = 'block';
            
            // Hide canvas container
            const canvasContainer = document.getElementById('aiw-preview-canvas-container');
            if (canvasContainer) {
                canvasContainer.style.display = 'none';
            }
            
            debugLog('❌ Error shown:', message);
        }
    }
    
    /**
     * Initialize iframe-based preview system
     */
    function initializeIframePreview() {
        try {
            debugLog('🖼️ Initializing iframe-based preview system...');
            
            const iframeContainer = document.getElementById('widget_preview_container');
            if (!iframeContainer) {
                debugLog('❌ Iframe container #widget_preview_container not found');
                return false;
            }
            debugLog('✅ Iframe container found: #widget_preview_container');
            
            const iframe = document.getElementById('preview-iframe');
            const loadingElement = document.getElementById('preview-loading');
            const errorElement = document.getElementById('preview-error');
            
            if (!iframe) {
                debugLog('❌ Preview iframe #preview-iframe not found');
                return false;
            }
            debugLog('✅ Preview iframe found: #preview-iframe');
            
            if (!loadingElement) {
                debugLog('❌ Loading element #preview-loading not found');
                return false;
            }
            debugLog('✅ Loading element found: #preview-loading');
            
            if (!errorElement) {
                debugLog('❌ Error element #preview-error not found');
                return false;
            }
            debugLog('✅ Error element found: #preview-error');
            
            PREVIEW_CONFIG.iframe = iframe;
            
            // Setup retry button
            const retryButton = document.getElementById('retry-preview');
            if (retryButton) {
                retryButton.addEventListener('click', function() {
                    // Reset and reinitialize the preview system
                    PREVIEW_CONFIG.initialized = false;
                    initializePreview();
                });
                debugLog('✅ Retry button event listener attached');
            } else {
                debugLog('⚠️ Retry button #retry-preview not found');
            }
            
            // Load initial settings and start animation
            loadInitialSettings();
            startAnimationLoop();
            
            return true;
            
        } catch (error) {
            errorLog('❌ Error initializing iframe preview:', error);
            return false;
        }
    }
    
    /**
     * Initialize canvas-based preview system
     */
    function initializeCanvasPreview() {
        debugLog('🎨 Initializing canvas-based preview system...');
        
        try {
            // Ensure canvas container is visible
            const canvasContainer = document.getElementById('aiw-preview-canvas-container');
            if (!canvasContainer) {
                throw new Error('Canvas container not found');
            }
            
            // Initialize canvas element
            const canvas = document.getElementById('aiw-preview-canvas');
            if (canvas) {
                PREVIEW_CONFIG.canvas = canvas;
                PREVIEW_CONFIG.ctx = canvas.getContext('2d');
                
                // Set up canvas responsiveness
                resizeCanvas();
                window.addEventListener('resize', resizeCanvas);
            }
            
            // Setup retry button
            const retryButton = document.getElementById('retry-preview');
            if (retryButton) {
                retryButton.addEventListener('click', function() {
                    initializePreview();
                });
                debugLog('✅ Retry button event listener attached');
            }
            
            // Load initial settings and start animation
            loadInitialSettings();
            startAnimationLoop();
            
            debugLog('✅ Canvas preview system initialized successfully');
            return true;
            
        } catch (error) {
            errorLog('❌ Failed to initialize canvas preview:', error);
            return false;
        }
    }
    
    /**
     * Load preview content into iframe
     */
    function loadPreview() {
        if (PREVIEW_CONFIG.mode !== 'iframe' || !PREVIEW_CONFIG.iframe) {
            debugLog('❌ Not in iframe mode or iframe not available, falling back to canvas');
            debugLog('Mode:', PREVIEW_CONFIG.mode, 'Iframe:', !!PREVIEW_CONFIG.iframe);
            return;
        }
        
        debugLog('🔄 Loading preview content...');
        showPreviewLoading();
        
        // Enhanced validation with detailed logging
        if (typeof customizerData === 'undefined') {
            errorLog('❌ customizerData object not available');
            showPreviewError('Configuration error: customizerData not loaded');
            return;
        }
        
        debugLog('✅ customizerData available:', {
            hasAjaxurl: !!customizerData.ajaxurl,
            hasNonce: !!customizerData.nonce,
            hasDefaults: !!customizerData.defaults,
            version: customizerData.version
        });
        
        if (!customizerData.ajaxurl) {
            errorLog('❌ AJAX URL not available in customizerData');
            showPreviewError('Configuration error: AJAX URL missing');
            return;
        }
        
        if (!customizerData.nonce) {
            errorLog('❌ Security nonce not available in customizerData');
            showPreviewError('Configuration error: Security nonce missing');
            return;
        }
        
        // Collect current settings
        debugLog('📊 Collecting current settings...');
        const settings = collectCurrentSettings();
        debugLog('✅ Settings collected:', settings);
        
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
            if (data.success) {
                // Validate response data
                if (!data.data || !data.data.html) {
                    throw new Error('Invalid response: missing HTML content');
                }
                
                // Load HTML into iframe
                const iframeDoc = PREVIEW_CONFIG.iframe.contentDocument || PREVIEW_CONFIG.iframe.contentWindow.document;
                if (!iframeDoc) {
                    throw new Error('Cannot access iframe document');
                }
                
                iframeDoc.open();
                iframeDoc.write(data.data.html);
                iframeDoc.close();
                
                showPreviewIframe();
                PREVIEW_CONFIG.retryCount = 0;
                debugLog('✅ Preview loaded successfully');
                debugLog('✅ Preview animation started');
                
            } else {
                const errorMessage = data.data && data.data.message ? data.data.message : 'Unknown error occurred';
                const errorCode = data.data && data.data.code ? data.data.code : 'unknown';
                debugLog('❌ AJAX request failed:', { message: errorMessage, code: errorCode });
                throw new Error(`${errorMessage} (${errorCode})`);
            }
        })
        .catch(error => {
            errorLog('❌ Error loading preview:', error);
            
            // Enhanced error reporting based on error type
            let userMessage = error.message;
            if (error.message.includes('nonce')) {
                userMessage = 'Security error: Please refresh the page and try again.';
            } else if (error.message.includes('HTTP')) {
                userMessage = 'Server error: Please check your connection and try again.';
            } else if (error.message.includes('JSON')) {
                userMessage = 'Response error: Server returned invalid data.';
            }
            
            showPreviewError(userMessage);
            
            // Retry logic
            if (PREVIEW_CONFIG.retryCount < PREVIEW_CONFIG.maxRetries) {
                PREVIEW_CONFIG.retryCount++;
                debugLog(`🔄 Retrying preview load (attempt ${PREVIEW_CONFIG.retryCount}/${PREVIEW_CONFIG.maxRetries})`);
                setTimeout(() => loadPreview(), 2000 * PREVIEW_CONFIG.retryCount);
            } else {
                errorLog('❌ Max retries reached, preview load failed permanently');
            }
        });
    }
    
    /**
     * Update preview with current settings (debounced)
     */
    function updatePreview() {
        // Clear existing timeout
        if (PREVIEW_CONFIG.updateTimeout) {
            clearTimeout(PREVIEW_CONFIG.updateTimeout);
        }
        
        // Debounce updates
        PREVIEW_CONFIG.updateTimeout = setTimeout(() => {
            debugLog('Updating preview...');
            
            // Collect current settings
            const settings = collectCurrentSettings();
            
            // Apply CSS variables for real-time updates
            applyCSSVariables(settings.style);
            
            // Update content if needed
            updatePreviewContent(settings.content);
            
            debugLog('✅ Preview updated successfully');
            
        }, PREVIEW_CONFIG.debounceDelay);
    }
    
    /**
     * Apply CSS variables to preview elements
     */
    function applyCSSVariables(styleSettings) {
        if (!styleSettings) return;
        
        const root = document.documentElement;
        
        // Map style settings to CSS variables
        Object.entries(styleSettings).forEach(([key, value]) => {
            const cssVar = SETTINGS_MAP[key];
            if (cssVar && value) {
                root.style.setProperty(cssVar, value);
                debugLog(`Applied CSS variable: ${cssVar} = ${value}`);
            }
        });
    }
    
    /**
     * Update preview content (headlines, messages, etc.)
     */
    function updatePreviewContent(contentSettings) {
        if (!contentSettings) return;
        
        // Update headline if present
        if (contentSettings.headline_text) {
            const headlines = document.querySelectorAll('.aiw-preview-headline');
            headlines.forEach(h => {
                h.textContent = contentSettings.headline_text;
            });
        }
        
        // Update welcome messages
        Object.keys(contentSettings).forEach(key => {
            if (key.startsWith('welcome_message_')) {
                const lang = key.replace('welcome_message_', '');
                const elements = document.querySelectorAll(`[data-lang="${lang}"] .welcome-text`);
                elements.forEach(el => {
                    el.textContent = contentSettings[key];
                });
            }
        });
    }
    
    /**
     * Show preview loading state
     */
    function showPreviewLoading() {
        document.getElementById('preview-loading').style.display = 'flex';
        document.getElementById('preview-error').style.display = 'none';
        document.getElementById('preview-iframe').style.display = 'none';
    }
    
    /**
     * Show preview iframe
     */
    function showPreviewIframe() {
        document.getElementById('preview-loading').style.display = 'none';
        document.getElementById('preview-error').style.display = 'none';
        document.getElementById('preview-iframe').style.display = 'block';
    }
    
    /**
     * Show preview error
     */
    function showPreviewError(message) {
        document.getElementById('preview-loading').style.display = 'none';
        document.getElementById('preview-iframe').style.display = 'none';
        
        const errorElement = document.getElementById('preview-error');
        const errorMessage = document.getElementById('error-message');
        
        if (errorMessage) {
            errorMessage.textContent = message;
        }
        
        errorElement.style.display = 'block';
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
        
        return { style, content };
    }
    
    /**
     * Generate CSS from current settings
     */
    function generatePreviewCSS(styleSettings) {
        let css = ':root {\n';
        
        // Map settings to CSS variables
        Object.keys(styleSettings).forEach(key => {
            const cssVar = SETTINGS_MAP[key];
            if (cssVar && styleSettings[key]) {
                css += `    ${cssVar}: ${styleSettings[key]};\n`;
            }
        });
        
        css += '}\n';
        return css;
    }

    /**
     * Hide the fallback message if it exists
     */
    function hideFallbackMessage() {
        const fallbackMessage = document.querySelector('.aiw-preview-error');
        if (fallbackMessage && !PREVIEW_CONFIG.fallbackMessageHidden) {
            fallbackMessage.style.display = 'none';
            PREVIEW_CONFIG.fallbackMessageHidden = true;
            debugLog('Fallback message hidden');
        }
    }
    
    /**
     * Show fallback message if preview fails to initialize
     */
    function showFallbackMessage(message) {
        const fallbackMessage = document.querySelector('.aiw-preview-error');
        if (fallbackMessage) {
            const messageElement = fallbackMessage.querySelector('p');
            if (messageElement) {
                messageElement.textContent = message || 'Preview temporarily unavailable. Please refresh the page.';
            }
            fallbackMessage.style.display = 'block';
            PREVIEW_CONFIG.fallbackMessageHidden = false;
            debugLog('Fallback message shown:', message);
        }
    }

    /**
     * Initialize canvas background
     */
    function initializeCanvas() {
        const canvas = document.getElementById('aiw-preview-canvas');
        if (!canvas) {
            debugLog('Canvas element #aiw-preview-canvas not found, skipping canvas initialization');
            return;
        }
        
        try {
            PREVIEW_CONFIG.canvas = canvas;
            PREVIEW_CONFIG.ctx = canvas.getContext('2d');
            
            if (!PREVIEW_CONFIG.ctx) {
                errorLog('Failed to get 2D canvas context');
                return;
            }
            
            debugLog('Canvas initialized successfully');
            
            // Set initial canvas size
            resizeCanvas();
            
            // Initialize particles for animation
            initializeParticles();
            
        } catch (error) {
            errorLog('Failed to initialize canvas:', error);
        }
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
        try {
            debugLog('Setting up control listeners...');
            
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
                debugLog('Setting up jQuery event delegation...');
                $(document).on('change', 'input[type="text"].wp-color-picker', handleColorChange);
                $(document).on('input change', 'input[type="range"]', handleRangeChange);
                $(document).on('change', 'select', handleSelectChange);
                $(document).on('change', 'input[type="checkbox"]', handleCheckboxChange);
                $(document).on('input', 'input[type="text"]:not(.wp-color-picker), input[type="number"]', handleTextChange);
            }
            
            debugLog('Control listeners setup complete');
            
        } catch (error) {
            errorLog('Failed to setup control listeners:', error);
        }
    }

    /**
     * Handle color picker changes
     */
    function handleColorChange(event) {
        try {
            const input = event.target;
            const settingName = input.getAttribute('name') || input.getAttribute('id');
            const value = input.value;
            
            if (!settingName) {
                debugLog('Color input has no name or id attribute, skipping');
                return;
            }
            
            debugLog(`Color change: ${settingName} = ${value}`);
            debouncedUpdate(settingName, value);
        } catch (error) {
            errorLog('Error handling color change:', error);
        }
    }

    /**
     * Handle range slider changes
     */
    function handleRangeChange(event) {
        try {
            const input = event.target;
            const settingName = input.getAttribute('name') || input.getAttribute('id');
            const value = input.value;
            const unit = input.getAttribute('data-unit') || '';
            
            if (!settingName) {
                debugLog('Range input has no name or id attribute, skipping');
                return;
            }
            
            const finalValue = value + unit;
            debugLog(`Range change: ${settingName} = ${finalValue}`);
            debouncedUpdate(settingName, finalValue);
        } catch (error) {
            errorLog('Error handling range change:', error);
        }
    }

    /**
     * Handle select dropdown changes
     */
    function handleSelectChange(event) {
        try {
            const select = event.target;
            const settingName = select.getAttribute('name') || select.getAttribute('id');
            const value = select.value;
            
            if (!settingName) {
                debugLog('Select has no name or id attribute, skipping');
                return;
            }
            
            debugLog(`Select change: ${settingName} = ${value}`);
            debouncedUpdate(settingName, value);
        } catch (error) {
            errorLog('Error handling select change:', error);
        }
    }

    /**
     * Handle checkbox changes
     */
    function handleCheckboxChange(event) {
        try {
            const checkbox = event.target;
            const settingName = checkbox.getAttribute('name') || checkbox.getAttribute('id');
            const value = checkbox.checked;
            
            if (!settingName) {
                debugLog('Checkbox has no name or id attribute, skipping');
                return;
            }
            
            debugLog(`Checkbox change: ${settingName} = ${value}`);
            debouncedUpdate(settingName, value);
        } catch (error) {
            errorLog('Error handling checkbox change:', error);
        }
    }

    /**
     * Handle text input changes
     */
    function handleTextChange(event) {
        try {
            const input = event.target;
            const settingName = input.getAttribute('name') || input.getAttribute('id');
            const value = input.value;
            
            if (!settingName) {
                debugLog('Text input has no name or id attribute, skipping');
                return;
            }
            
            debugLog(`Text change: ${settingName} = ${value}`);
            debouncedUpdate(settingName, value);
        } catch (error) {
            errorLog('Error handling text change:', error);
        }
    }

    /**
     * Debounced update function
     */
    function debouncedUpdate(settingName, value) {
        // Update CSS variable for immediate feedback (in case iframe fails)
        const cssVariable = SETTINGS_MAP[settingName];
        if (cssVariable) {
            updateCSSVariable(cssVariable, value);
        }
        
        // Call main update method
        updatePreview();
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
     * Load initial settings from form inputs with fallback to defaults
     */
    function loadInitialSettings() {
        debugLog('Loading initial settings...');
        let settingsLoaded = 0;
        let settingsFromDefaults = 0;
        
        // Collect all current form values
        Object.keys(SETTINGS_MAP).forEach(settingName => {
            const input = document.querySelector(`[name="${settingName}"], #${settingName}`);
            let value = null;
            
            if (input) {
                if (input.type === 'checkbox') {
                    value = input.checked;
                } else {
                    value = input.value;
                    const unit = input.getAttribute('data-unit') || '';
                    if (unit && value) value += unit;
                }
                
                // Only use form value if it's not empty
                if (value !== null && value !== '' && value !== false) {
                    updatePreviewSetting(settingName, value);
                    settingsLoaded++;
                    debugLog(`Loaded from form - ${settingName}:`, value);
                } else {
                    // Fall back to default
                    const defaultValue = defaults[settingName];
                    if (defaultValue !== undefined) {
                        updatePreviewSetting(settingName, defaultValue);
                        settingsFromDefaults++;
                        debugLog(`Using default - ${settingName}:`, defaultValue);
                    }
                }
            } else {
                // No form input found, use default
                const defaultValue = defaults[settingName];
                if (defaultValue !== undefined) {
                    updatePreviewSetting(settingName, defaultValue);
                    settingsFromDefaults++;
                    debugLog(`No input found, using default - ${settingName}:`, defaultValue);
                }
            }
        });
        
        debugLog(`Settings loaded: ${settingsLoaded} from form, ${settingsFromDefaults} from defaults`);
    }

    /**
     * Start animation loop for canvas and visualizations
     */
    function startAnimationLoop() {
        if (PREVIEW_CONFIG.reducedMotion) {
            debugLog('Animation loop skipped - reduced motion enabled');
            return;
        }
        
        if (document.hidden) {
            debugLog('Animation loop skipped - tab is hidden');
            return;
        }
        
        // Cancel any existing animation frame
        if (PREVIEW_CONFIG.animationFrameId) {
            cancelAnimationFrame(PREVIEW_CONFIG.animationFrameId);
        }
        
        debugLog('Starting animation loop');
        
        function animate() {
            // Check if we should continue animating
            if (PREVIEW_CONFIG.reducedMotion || document.hidden) {
                debugLog('Stopping animation loop - conditions changed');
                PREVIEW_CONFIG.animationFrameId = null;
                return;
            }
            
            try {
                // Update canvas background
                updateCanvas();
                
                // Continue animation
                PREVIEW_CONFIG.animationFrameId = requestAnimationFrame(animate);
            } catch (error) {
                errorLog('Error in animation loop:', error);
                PREVIEW_CONFIG.animationFrameId = null;
            }
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
        debugLog('🚀 DOM ready, starting preview system initialization...');
        
        // Initialize preview system
        initializePreview();
        
        // Setup cleanup on page unload
        window.addEventListener('beforeunload', cleanup);
        
        // Handle tab visibility changes to pause/resume animations
        document.addEventListener('visibilitychange', function() {
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
        });
        
        // Handle reduced motion preference changes
        const mediaQuery = window.matchMedia('(prefers-reduced-motion: reduce)');
        const handleMotionChange = function(e) {
            PREVIEW_CONFIG.reducedMotion = e.matches;
            debugLog('Reduced motion preference changed:', e.matches);
            
            if (e.matches) {
                // Stop animations
                cleanup();
                // Remove pulse classes
                const playButtons = document.querySelectorAll('.aiw-preview-play-button');
                playButtons.forEach(btn => btn.classList.remove('pulse'));
            } else {
                // Restart animations if tab is visible
                if (!document.hidden) {
                    startAnimationLoop();
                }
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
        // Canvas-based preview system
        initializePreviewSystem: initializePreview,
        updatePreview: updatePreview,
        
        // Legacy canvas-based system
        updateSetting: updatePreviewSetting,
        updateVariable: updateCSSVariable,
        refresh: function() {
            updateCanvasBackground();
            updatePreview();
        },
        
        // Manual testing functions
        manualInit: function() {
            console.log('🧪 Manual initialization triggered...');
            initializePreview();
        },
        
        validateElements: function() {
            console.log('🔍 Manual element validation...');
            return validatePreviewRequirements();
        },
        
        // Configuration access
        getConfig: function() {
            return {
                mode: PREVIEW_CONFIG.mode,
                initialized: PREVIEW_CONFIG.initialized,
                canvas: PREVIEW_CONFIG.canvas !== null
            };
        }
    };

})();