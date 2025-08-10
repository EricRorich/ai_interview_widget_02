// AI Interview Widget v1.9.3 FIXED - COMPLETE WORKING VERSION
// Current Date and Time (UTC): 2025-08-03 18:54:44
// Current User's Login: EricRorich

console.log('🎤 AI Interview Widget v1.9.3 FIXED Loading - TTS/Voice Features RESTORED');
console.log('🕐 Current Time: 2025-08-03 18:54:44 UTC');
console.log('👤 Current User: EricRorich');

document.addEventListener('DOMContentLoaded', function() {
    // Give Elementor a moment to fully render the canvas
    setTimeout(() => {
        initializeWidget();
    }, 300);

    function initializeWidget() {
        // Debug flag - set to false for production
        const DEBUG = false;
        
        // Device detection
        const isMobile = window.innerWidth <= 767;
        const isTablet = window.innerWidth <= 1024 && window.innerWidth > 767;
        const isTouch = 'ontouchstart' in window || navigator.maxTouchPoints > 0;
        
        // Voice feature availability - FIXED
        let voiceEnabled = false;
        let hasElevenLabsKey = false;
        let speechRecognition = null;
        let speechSynthesis = null;
        let isListening = false;
        let ttsEnabled = true;
        let currentTTSAudio = null;
        
        // Voice Activity Detection (VAD) variables
        let vadEnabled = true;
        let vadSilenceTimeout = 2500; // 2.5 seconds default
        let vadSilenceTimer = null;
        let vadLastSpeechTime = null;
        let vadAutoSendEnabled = true;
        let vadMinSpeechDuration = 500; // Minimum 0.5 seconds of speech before considering auto-send
        
        // Audio Visualizer Theme System
        let currentVisualizerTheme = 'default';
        let visualizerSettings = {
            theme: 'default',
            primaryColor: '#00cfff',
            secondaryColor: '#0066ff',
            accentColor: '#001a33',
            barWidth: 2,
            barSpacing: 2,
            glowIntensity: 10,
            animationSpeed: 1.0
        };
        
        const visualizerThemes = {
            default: {
                name: 'Default',
                description: 'Original futuristic design with cyan gradients',
                renderFunction: drawSoundbarDefault,
                settings: {
                    primaryColor: '#00cfff',
                    secondaryColor: '#0066ff', 
                    accentColor: '#001a33',
                    barWidth: 2,
                    barSpacing: 2,
                    glowIntensity: 10
                }
            },
            minimal: {
                name: 'Minimal',
                description: 'Clean, subtle lines with soft fade effects',
                renderFunction: drawSoundbarMinimal,
                settings: {
                    primaryColor: '#ffffff',
                    secondaryColor: '#e0e0e0',
                    accentColor: '#cccccc',
                    barWidth: 1,
                    barSpacing: 3,
                    glowIntensity: 2
                }
            },
            futuristic: {
                name: 'Futuristic',
                description: 'Vibrant neon with pulse animations and motion trails',
                renderFunction: drawSoundbarFuturistic,
                settings: {
                    primaryColor: '#ff00ff',
                    secondaryColor: '#00ffff',
                    accentColor: '#ff0080',
                    barWidth: 3,
                    barSpacing: 1,
                    glowIntensity: 20
                }
            },
            smiley: {
                name: 'Expressive Smiley',
                description: 'Animated smiley face that reacts to audio with expressions',
                renderFunction: drawSoundbarSmiley,
                settings: {
                    primaryColor: '#ffff00',
                    secondaryColor: '#ff6600',
                    accentColor: '#333333',
                    barWidth: 2,
                    barSpacing: 2,
                    glowIntensity: 15
                }
            }
        };
        
        // Dynamic system prompts and welcome messages loaded from backend settings
        // Now supports all 20 languages with automatic fallback to English
        let systemPrompts = {};
        let welcomeMessages = {};

        // Language detection variables
        let detectedLanguage = null;
        
        // Chat interface state tracking to prevent duplicate welcome messages
        let chatInterfaceInitialized = false;

        function debug(message, ...args) {
            // Only log in development mode or when explicitly enabled
            if (DEBUG || (typeof window !== 'undefined' && window.aiWidgetDebugMode === true)) {
                console.log(`[AI Widget] ${message}`, ...args);
            }
        }
        
        function debugError(message, ...args) {
            // Always log errors, but less verbosely in production
            if (DEBUG) {
                console.error(`[AI Widget Error] ${message}`, ...args);
            } else {
                console.error(`[AI Widget] ${message}`);
            }
        }
        
        function debugWarn(message, ...args) {
            // Log warnings only in development
            if (DEBUG) {
                console.warn(`[AI Widget Warning] ${message}`, ...args);
            }
        }

        debug("Widget initialization started", { isMobile, isTablet, isTouch });
        
        // Helper function to get canvas background color from CSS custom property
        // This ensures consistency between admin preview and frontend
        function getCanvasBackgroundColor() {
            const canvasBgColor = getComputedStyle(document.documentElement).getPropertyValue('--canvas-background-color')?.trim();
            return canvasBgColor && canvasBgColor !== '' ? canvasBgColor : '#0a0a1a';
        }
        
        // Helper function to get CSS custom properties with robust fallback
        // FIXED: Moved to global scope to prevent ReferenceError
        function getCSSVariable(varName, fallback = '') {
            // Try to read from :root first
            let value = getComputedStyle(document.documentElement).getPropertyValue(varName);
            
            if (value) {
                value = value.trim();
            }
            
            // If not found or empty, try reading from the widget container
            if (!value) {
                const container = document.querySelector('.ai-interview-container');
                if (container) {
                    value = getComputedStyle(container).getPropertyValue(varName);
                    if (value) {
                        value = value.trim();
                    }
                }
            }
            
            // Return the value or fallback
            return value || fallback;
        }
        
        // Helper function to update canvas shadow based on intensity CSS variable
        // This enables live preview updates when Canvas Shadow Intensity changes
        // 
        // Canvas Shadow Intensity Mapping:
        // - Value range: 0-100 (configurable in admin)
        // - 0 = No shadow (completely hidden)
        // - 20 = Default intensity (moderate shadow)
        // - 50 = Medium intensity (noticeable shadow)
        // - 100 = Maximum intensity (dramatic shadow effect)
        //
        // CSS Shadow Calculation:
        // - blur1 = intensity * 1px (main shadow blur)
        // - spread1 = intensity * 0.3px (main shadow spread) 
        // - blur2 = intensity * 1px (secondary shadow blur)
        // - spread2 = intensity * 0.2px (secondary shadow spread)
        // - Generates: box-shadow: 0 0 {blur1} {spread1} rgba(..., 0.5), 0 0 {blur2} {spread2} rgba(..., 0.3)
        function updateCanvasShadowFromIntensity() {
            const canvas = document.getElementById('soundbar');
            if (!canvas) return;
            
            const intensity = parseInt(getCSSVariable('--aiw-shadow-intensity', '20'));
            const shadowColor = getCSSVariable('--aiw-shadow-color', 'rgba(0, 207, 255, 0.5)');
            
            // Parse the shadow color to extract RGB values
            let r = 0, g = 207, b = 255;
            
            if (shadowColor.includes('rgba')) {
                const match = shadowColor.match(/rgba?\((\d+),\s*(\d+),\s*(\d+)/);
                if (match) {
                    r = parseInt(match[1]);
                    g = parseInt(match[2]);
                    b = parseInt(match[3]);
                }
            } else if (shadowColor.includes('#')) {
                const hex = shadowColor.replace('#', '');
                r = parseInt(hex.substr(0, 2), 16);
                g = parseInt(hex.substr(2, 2), 16);
                b = parseInt(hex.substr(4, 2), 16);
            }
            
            // Calculate glow spreads based on intensity
            const glow1 = Math.round(intensity * 0.3);
            const glow2 = Math.round(intensity * 0.2);
            
            // Generate dynamic box-shadow
            const dynamicBoxShadow = `0 0 ${intensity}px ${glow1}px rgba(${r}, ${g}, ${b}, 0.5), 0 0 ${intensity}px ${glow2}px rgba(${r}, ${g}, ${b}, 0.3)`;
            
            // Update the CSS variable
            document.documentElement.style.setProperty('--canvas-box-shadow', dynamicBoxShadow);
            
            debug(`Canvas shadow updated: intensity=${intensity}, color=rgba(${r},${g},${b})`);
        }
        
        function getWidgetData() {
            let widgetData = {
                ajaxurl: '',
                nonce: '',
                greeting_en: '',
                greeting_de: '',
                greeting_en_alt: '',
                greeting_de_alt: '',
                audio_files_available: false,
                voice_enabled: false,
                has_elevenlabs_key: false,
                // Content settings (now dynamic instead of FIXED)
                system_prompt_en: '',
                system_prompt_de: '',
                welcome_message_en: '',
                welcome_message_de: '',
                headline_text: '',
                // Visualizer settings
                visualizer_theme: 'default',
                visualizer_primary_color: '#00cfff',
                visualizer_secondary_color: '#0066ff',
                visualizer_accent_color: '#001a33',
                visualizer_bar_width: 2,
                visualizer_bar_spacing: 2,
                visualizer_glow_intensity: 10,
                visualizer_animation_speed: 1.0
            };
            
            if (typeof window.aiWidgetData !== 'undefined') {
                debug("Found aiWidgetData:", window.aiWidgetData);
                widgetData.ajaxurl = window.aiWidgetData.ajaxurl || '';
                widgetData.nonce = window.aiWidgetData.nonce || '';
                widgetData.greeting_en = window.aiWidgetData.greeting_en || '';
                widgetData.greeting_de = window.aiWidgetData.greeting_de || '';
                widgetData.greeting_en_alt = window.aiWidgetData.greeting_en_alt || '';
                widgetData.greeting_de_alt = window.aiWidgetData.greeting_de_alt || '';
                widgetData.audio_files_available = window.aiWidgetData.audio_files_available || false;
                widgetData.voice_enabled = window.aiWidgetData.voice_enabled || false;
                widgetData.has_elevenlabs_key = window.aiWidgetData.has_elevenlabs_key || false;
                // Load content settings from backend
                widgetData.system_prompt_en = window.aiWidgetData.system_prompt_en || '';
                widgetData.system_prompt_de = window.aiWidgetData.system_prompt_de || '';
                widgetData.welcome_message_en = window.aiWidgetData.welcome_message_en || '';
                widgetData.welcome_message_de = window.aiWidgetData.welcome_message_de || '';
                widgetData.headline_text = window.aiWidgetData.headline_text || '';
                // Load visualizer settings from backend
                widgetData.visualizer_theme = window.aiWidgetData.visualizer_theme || 'default';
                widgetData.visualizer_primary_color = window.aiWidgetData.visualizer_primary_color || '#00cfff';
                widgetData.visualizer_secondary_color = window.aiWidgetData.visualizer_secondary_color || '#0066ff';
                widgetData.visualizer_accent_color = window.aiWidgetData.visualizer_accent_color || '#001a33';
                widgetData.visualizer_bar_width = window.aiWidgetData.visualizer_bar_width || 2;
                widgetData.visualizer_bar_spacing = window.aiWidgetData.visualizer_bar_spacing || 2;
                widgetData.visualizer_glow_intensity = window.aiWidgetData.visualizer_glow_intensity || 10;
                widgetData.visualizer_animation_speed = window.aiWidgetData.visualizer_animation_speed || 1.0;
            }
            if (typeof window.aiWidgetDataBackup !== 'undefined') {
                debug("Found aiWidgetDataBackup:", window.aiWidgetDataBackup);
                if (!widgetData.ajaxurl) widgetData.ajaxurl = window.aiWidgetDataBackup.ajaxurl || '';
                if (!widgetData.nonce) widgetData.nonce = window.aiWidgetDataBackup.nonce || '';
            }
            if (typeof window.aiWidgetNonce !== 'undefined') {
                debug("Found aiWidgetNonce:", window.aiWidgetNonce);
                if (!widgetData.nonce) widgetData.nonce = window.aiWidgetNonce;
            }
            if (typeof window.aiWidgetAjaxUrl !== 'undefined') {
                debug("Found aiWidgetAjaxUrl:", window.aiWidgetAjaxUrl);
                if (!widgetData.ajaxurl) widgetData.ajaxurl = window.aiWidgetAjaxUrl;
            }
            
            // If we don't have audio URLs from widget data, construct them from the known working URLs
            if (!widgetData.greeting_en || !widgetData.greeting_de) {
                debug("Missing audio URLs in widget data, constructing from known working URLs");
                const baseUrl = window.location.origin + '/wp-content/plugins/ai-interview-widget/';
                widgetData.greeting_en = widgetData.greeting_en || baseUrl + 'greeting_en.mp3';
                widgetData.greeting_de = widgetData.greeting_de || baseUrl + 'greeting_de.mp3';
                widgetData.audio_files_available = true;
            }
            
            if (!widgetData.ajaxurl) {
                widgetData.ajaxurl = '/wp-admin/admin-ajax.php';
                debug("Using fallback AJAX URL");
            }
            if (!widgetData.nonce) {
                widgetData.nonce = Math.random().toString(36).substring(2, 15);
                debug("WARNING: Generated emergency fallback nonce");
            }
            debug("Final widget data:", widgetData);
            
            // FIXED: Ensure we have the data we need, don't return early
            if (!widgetData.ajaxurl) {
                console.error('AI Widget: No AJAX URL found');
                // Don't return here, continue with fallback
                widgetData.ajaxurl = window.location.origin + '/wp-admin/admin-ajax.php';
            }

            return widgetData;
        }

        // Apply visualizer settings from backend
        function applyVisualizerSettings(widgetData) {
            currentVisualizerTheme = widgetData.visualizer_theme || 'default';
            
            // Update visualizer settings with backend values
            visualizerSettings = {
                theme: currentVisualizerTheme,
                primaryColor: widgetData.visualizer_primary_color || '#00cfff',
                secondaryColor: widgetData.visualizer_secondary_color || '#0066ff',
                accentColor: widgetData.visualizer_accent_color || '#001a33',
                barWidth: parseInt(widgetData.visualizer_bar_width) || 2,
                barSpacing: parseInt(widgetData.visualizer_bar_spacing) || 2,
                glowIntensity: parseInt(widgetData.visualizer_glow_intensity) || 10,
                animationSpeed: parseFloat(widgetData.visualizer_animation_speed) || 1.0
            };
            
            debug("Applied visualizer settings:", visualizerSettings);
        }
        
        const widgetData = getWidgetData();

        // Apply visualizer settings from backend
        applyVisualizerSettings(widgetData);

        // Initialize dynamic content from backend settings with support for all languages
        // Load system prompts and welcome messages for all supported languages
        initializeDynamicContent();
        
        debug("Loaded dynamic content:", {
            available_system_prompts: Object.keys(systemPrompts),
            available_welcome_messages: Object.keys(welcomeMessages)
        });

        // Initialize voice features - FIXED
        voiceEnabled = widgetData.voice_enabled && (widgetData.has_elevenlabs_key || 'webkitSpeechRecognition' in window || 'SpeechRecognition' in window);
        hasElevenLabsKey = widgetData.has_elevenlabs_key;
        
        debug("Voice features:", { voiceEnabled, hasElevenLabsKey });

        // ELEMENT HOOKUP
        const audio = document.getElementById('aiEricGreeting');
        const canvas = document.getElementById('soundbar');
        const pauseBtn = document.getElementById('pauseBtn');
        const skipBtn = document.getElementById('skipBtn');
        const chatInterface = document.getElementById('chatInterface');
        const pauseBtnContainer = pauseBtn ? pauseBtn.closest('.ai-interview-controls') : null;

        // Initialize play button overlay
        function initializePlayButtonOverlay() {
            playButtonOverlay = document.getElementById('playButtonOverlay');
            playButton = document.getElementById('playButton');
            
            if (!playButtonOverlay || !playButton) {
                debug("Play button overlay elements not found");
                return;
            }
            
            // Set initial design based on CSS variable
            const design = getCSSVariable('--play-button-design', 'classic').replace(/['"]/g, '');
            playButton.setAttribute('data-design', design);
            
            // Set pulse disable state
            const disablePulse = getCSSVariable('--play-button-disable-pulse', 'false').toLowerCase() === 'true';
            playButton.setAttribute('data-disable-pulse', disablePulse.toString());
            
            // Update design to handle icon visibility and effects
            updatePlayButtonDesign();
            
            // Add click event listener
            playButton.addEventListener('click', handlePlayButtonClick);
            
            // Show the overlay initially
            showPlayButtonOverlay();
            
            debug("Play button overlay initialized", { design, disablePulse });
        }
        
        function showPlayButtonOverlay() {
            if (playButtonOverlay) {
                playButtonOverlay.classList.remove('hidden');
                showPlayButton = true;
                debug("Play button overlay shown");
            }
        }
        
        function hidePlayButtonOverlay() {
            if (playButtonOverlay) {
                playButtonOverlay.classList.add('hidden');
                showPlayButton = false;
                debug("Play button overlay hidden");
            }
        }
        
        function updatePlayButtonDesign() {
            if (!playButton) return;
            
            const design = getCSSVariable('--play-button-design', 'classic').replace(/['"]/g, '');
            playButton.setAttribute('data-design', design);
            
            const disablePulse = getCSSVariable('--play-button-disable-pulse', 'false').toLowerCase() === 'true';
            playButton.setAttribute('data-disable-pulse', disablePulse.toString());
            
            // Handle icon visibility based on design
            const triangleIcon = playButton.querySelector('.play-triangle');
            const svgIcon = playButton.querySelector('.play-svg-icon');
            
            if (design === 'legacy') {
                // Legacy design uses SVG icon
                if (triangleIcon) triangleIcon.style.display = 'none';
                if (svgIcon) svgIcon.style.display = 'block';
                
                // Add initial pulse effect for legacy design
                if (!disablePulse) {
                    playButton.classList.add('initial-pulse');
                    // Remove the class after animation completes
                    setTimeout(() => {
                        playButton.classList.remove('initial-pulse');
                    }, 1500);
                }
            } else {
                // Other designs use CSS triangle
                if (triangleIcon) triangleIcon.style.display = 'block';
                if (svgIcon) svgIcon.style.display = 'none';
            }
            
            debug("Play button design updated", { design, disablePulse });
        }

        debug("Elements found:", {
            canvas: !!canvas, 
            audio: !!audio, 
            pauseBtn: !!pauseBtn,
            skipBtn: !!skipBtn,
            chatInterface: !!chatInterface,
            playButtonOverlay: !!document.getElementById('playButtonOverlay'),
            playButton: !!document.getElementById('playButton')
        });

        if (!canvas || !audio || !pauseBtn || !skipBtn || !chatInterface) {
            console.error("Essential elements for AI Interview Widget not found");
            console.error("Missing elements:", {
                canvas: !canvas,
                audio: !audio,
                pauseBtn: !pauseBtn,
                skipBtn: !skipBtn,
                chatInterface: !chatInterface,
                playButtonOverlay: !document.getElementById('playButtonOverlay'),
                playButton: !document.getElementById('playButton')
            });
            // Don't return, try to continue with what we have
        }

        // Voice control elements (will be available after chat interface is shown)
        let voiceInputBtn = null;
        let stopListeningBtn = null;
        let toggleTTSBtn = null;
        let voiceStatus = null;

        // Initialize canvas and drawing variables with responsive sizing
        function updateCanvasSize() {
            if (!canvas) {
                debug("Canvas not found, skipping size update");
                return;
            }

            const container = canvas.parentElement;
            const containerWidth = container ? container.offsetWidth : window.innerWidth;
            
            if (isMobile) {
                canvas.width = Math.min(containerWidth - 20, 350);
                canvas.height = 250;
            } else if (isTablet) {
                canvas.width = Math.min(containerWidth - 30, 600);
                canvas.height = 400;
            } else {
                canvas.width = 800;
                canvas.height = 500;
            }
            
            canvas.style.display = 'block';
            canvas.style.visibility = 'visible';
            canvas.style.width = canvas.width + 'px';
            canvas.style.height = canvas.height + 'px';
            
            debug("Canvas sized to:", canvas.width, "x", canvas.height, "for device type:", {isMobile, isTablet});
        }
        
        // Add image error handler for missing thumbnails and background images
        function addImageErrorHandlers() {
            // Handle background images that fail to load
            const elementsWithBgImages = document.querySelectorAll('[style*="background-image"]');
            elementsWithBgImages.forEach(element => {
                const bgImage = getComputedStyle(element).backgroundImage;
                if (bgImage && bgImage !== 'none') {
                    const imageUrl = bgImage.slice(5, -2); // Remove url(" and ")
                    if (imageUrl) {
                        // Test if image loads
                        const testImg = new Image();
                        testImg.onerror = function() {
                            if (DEBUG) {
                                debugWarn(`Background image failed to load: ${imageUrl}`);
                            }
                            // Set fallback gradient
                            element.style.backgroundImage = 'linear-gradient(135deg, #0f0c29, #302b63, #24243e)';
                            element.classList.add('aiw-image--error');
                        };
                        testImg.src = imageUrl;
                    }
                }
            });
            
            // Handle regular images that fail to load
            const images = document.querySelectorAll('img');
            images.forEach(img => {
                if (!img.hasAttribute('data-error-handled')) {
                    img.onerror = function() {
                        if (DEBUG) {
                            debugWarn(`Image failed to load: ${this.src}`);
                        }
                        // Create SVG fallback
                        const svgPlaceholder = 'data:image/svg+xml;base64,' + btoa(`
                            <svg xmlns="http://www.w3.org/2000/svg" width="200" height="200" viewBox="0 0 200 200">
                                <rect width="200" height="200" fill="#f0f0f0"/>
                                <circle cx="100" cy="80" r="30" fill="#ccc"/>
                                <path d="M60 140 Q100 120 140 140" stroke="#ccc" stroke-width="3" fill="none"/>
                                <text x="100" y="170" text-anchor="middle" font-family="Arial" font-size="12" fill="#999">Image unavailable</text>
                            </svg>
                        `);
                        this.src = svgPlaceholder;
                        this.classList.add('aiw-image--error');
                        this.setAttribute('data-error-handled', 'true');
                    };
                }
            });
        }

        // Only update canvas size if canvas exists
        if (canvas) {
            updateCanvasSize();
        }
        
        // Update canvas size on orientation change and resize
        let resizeTimeout;
        function handleResize() {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(() => {
                if (canvas) {
                    updateCanvasSize();
                    if (showPlayButton && !fadingOut) {
                        updatePlayButtonDesign(); // Update overlay instead of redrawing
                    }
                }
            }, 250);
        }
        
        window.addEventListener('resize', handleResize);
        window.addEventListener('orientationchange', () => {
            setTimeout(handleResize, 100); // Small delay for orientation change
        });

        // Get canvas context
        const ctx = canvas ? canvas.getContext('2d') : null;
        if (!ctx && canvas) {
            console.error("Could not get canvas context");
        }

        // Audio and visualization variables
        let audioCtx = null;
        let analyser = null;
        let source = null;
        let bufferLength = 0;
        let dataArray = null;
        let audioVisualizationActive = false;
        let audioReady = false;
        let audioSourceSet = false;
        let currentAudioSrc = '';

        // Play button overlay variables
        let showPlayButton = true;
        let fadingOut = false;
        let fadeAlpha = 1;
        let playButtonOverlay = null;
        let playButton = null;

        // Chat elements
        const chatHistory = document.getElementById('chatHistory');
        const userInput = document.getElementById('userInput');
        const sendButton = document.getElementById('sendButton');
        const typingIndicator = document.getElementById('typingIndicator');

        const progressBarHeight = isMobile ? 3 : 5;
        const progressBarMargin = 0;

        // Hide pause/resume button initially (will show after play button click)
        if (pauseBtnContainer) {
            pauseBtnContainer.style.opacity = 0;
            pauseBtnContainer.style.pointerEvents = "none";
            pauseBtnContainer.style.transition = "opacity 0.6s cubic-bezier(.4,0,.2,1)";
        }

        // --- VOICE ACTIVITY DETECTION (VAD) FUNCTIONS ---
        function startVadSilenceDetection() {
            if (!vadEnabled || !vadAutoSendEnabled || vadSilenceTimer) {
                return;
            }
            
            const language = detectedLanguage || 'en';
            const waitingMessages = {
                'en': 'Waiting for silence to auto-send...',
                'de': 'Warte auf Stille für automatisches Senden...'
            };
            
            showVoiceStatus(waitingMessages[language], 'processing');
            debug("VAD: Starting silence detection timer");
            
            vadSilenceTimer = setTimeout(() => {
                debug("VAD: Silence timeout reached, auto-sending message");
                handleVadAutoSend();
            }, vadSilenceTimeout);
        }
        
        function stopVadSilenceDetection() {
            if (vadSilenceTimer) {
                clearTimeout(vadSilenceTimer);
                vadSilenceTimer = null;
                debug("VAD: Silence detection stopped");
            }
        }
        
        async function handleVadAutoSend() {
            if (!userInput || !userInput.value.trim()) {
                debug("VAD: No message to send");
                setVoiceInputState('idle');
                return;
            }
            
            // Check minimum speech duration to avoid sending accidental short sounds
            const currentTime = Date.now();
            if (vadLastSpeechTime && (currentTime - vadLastSpeechTime) < vadMinSpeechDuration) {
                debug("VAD: Speech too short, not auto-sending");
                setVoiceInputState('idle');
                return;
            }
            
            const message = userInput.value.trim();
            debug("VAD: Auto-sending message:", message);
            
            const language = detectedLanguage || 'en';
            const autoSendMessages = {
                'en': 'Auto-sending message...',
                'de': 'Nachricht wird automatisch gesendet...'
            };
            
            showVoiceStatus(autoSendMessages[language], 'processing');
            
            // Add visual indicator that this was auto-sent
            addMessageToChat(message + ' 🎤', true, false); // Add microphone emoji to show it was voice input
            userInput.value = '';
            
            // Stop voice input and send the message
            setVoiceInputState('idle');
            await sendToLocalAPI(message);
        }
        
        function toggleVadAutoSend() {
            vadAutoSendEnabled = !vadAutoSendEnabled;
            const language = detectedLanguage || 'en';
            
            if (vadAutoSendEnabled) {
                const enabledMessages = {
                    'en': 'Auto-send enabled - will send after silence',
                    'de': 'Auto-Senden aktiviert - sendet nach Stille'
                };
                showVoiceStatus(enabledMessages[language], 'success');
                debug("VAD: Auto-send enabled");
            } else {
                const disabledMessages = {
                    'en': 'Auto-send disabled - use manual send',
                    'de': 'Auto-Senden deaktiviert - manuell senden'
                };
                showVoiceStatus(disabledMessages[language], 'info');
                debug("VAD: Auto-send disabled");
            }
            
            // Update UI to reflect the state
            updateVadControls();
            
            setTimeout(() => {
                hideVoiceStatus();
            }, 3000);
        }
        
        function updateVadControls() {
            const vadToggleBtn = document.getElementById('vadToggleBtn');
            if (!vadToggleBtn) return;
            
            const language = detectedLanguage || 'en';
            
            if (vadAutoSendEnabled) {
                vadToggleBtn.classList.add('active');
                vadToggleBtn.title = language === 'de' ? 'Auto-Senden deaktivieren' : 'Disable Auto-Send';
                
                const vadIcon = vadToggleBtn.querySelector('.vad-icon');
                const vadText = vadToggleBtn.querySelector('.vad-text');
                
                if (vadIcon) vadIcon.textContent = '⚡';
                if (vadText) {
                    vadText.textContent = language === 'de' ? 'Auto An' : 'Auto On';
                }
            } else {
                vadToggleBtn.classList.remove('active');
                vadToggleBtn.title = language === 'de' ? 'Auto-Senden aktivieren' : 'Enable Auto-Send';
                
                const vadIcon = vadToggleBtn.querySelector('.vad-icon');
                const vadText = vadToggleBtn.querySelector('.vad-text');
                
                if (vadIcon) vadIcon.textContent = '⚡';
                if (vadText) {
                    vadText.textContent = language === 'de' ? 'Auto Aus' : 'Auto Off';
                }
            }
        }
        function initializeVoiceFeatures() {
            if (!voiceEnabled) {
                debug("Voice features disabled");
                return;
            }

            debug("Initializing voice features...");

            // Initialize Speech Recognition
            if ('webkitSpeechRecognition' in window) {
                speechRecognition = new webkitSpeechRecognition();
            } else if ('SpeechRecognition' in window) {
                speechRecognition = new SpeechRecognition();
            }

            if (speechRecognition) {
                speechRecognition.continuous = false;
                speechRecognition.interimResults = true;
                speechRecognition.maxAlternatives = 1;

                speechRecognition.onstart = function() {
                    debug("Speech recognition started");
                    setVoiceInputState('listening');
                };

                speechRecognition.onresult = function(event) {
                    let finalTranscript = '';
                    let interimTranscript = '';

                    for (let i = event.resultIndex; i < event.results.length; i++) {
                        const transcript = event.results[i][0].transcript;
                        if (event.results[i].isFinal) {
                            finalTranscript += transcript;
                        } else {
                            interimTranscript += transcript;
                        }
                    }

                    // Voice Activity Detection - track when speech occurs
                    const currentTime = Date.now();
                    if (finalTranscript || interimTranscript) {
                        vadLastSpeechTime = currentTime;
                        
                        // Clear any existing silence timer since speech was detected
                        if (vadSilenceTimer) {
                            clearTimeout(vadSilenceTimer);
                            vadSilenceTimer = null;
                        }
                    }

                    if (finalTranscript) {
                        debug("Final transcript:", finalTranscript);
                        if (userInput) {
                            userInput.value = finalTranscript.trim();
                        }
                        
                        // Start VAD silence detection for auto-send
                        if (vadEnabled && vadAutoSendEnabled && finalTranscript.trim().length > 0) {
                            startVadSilenceDetection();
                        } else {
                            setVoiceInputState('idle');
                        }
                    } else if (interimTranscript) {
                        debug("Interim transcript:", interimTranscript);
                        if (userInput) {
                            userInput.value = interimTranscript.trim();
                        }
                        showVoiceStatus('Listening...', 'listening');
                        
                        // Start VAD silence detection for interim results too
                        if (vadEnabled && vadAutoSendEnabled) {
                            startVadSilenceDetection();
                        }
                    }
                };

                speechRecognition.onerror = function(event) {
                    debug("Speech recognition error:", event.error);
                    setVoiceInputState('idle');
                    
                    const language = detectedLanguage || 'en';
                    const errorMessages = {
                        'en': 'Voice input error. Please try again.',
                        'de': 'Spracheingabe-Fehler. Bitte versuchen Sie es erneut.'
                    };
                    showVoiceStatus(errorMessages[language], 'error');
                };

                speechRecognition.onend = function() {
                    debug("Speech recognition ended");
                    setVoiceInputState('idle');
                    hideVoiceStatus();
                };

                debug("Speech recognition initialized");
            } else {
                debug("Speech recognition not supported");
            }

            // Initialize Speech Synthesis (fallback for TTS)
            if ('speechSynthesis' in window) {
                speechSynthesis = window.speechSynthesis;
                debug("Speech synthesis available");
            }

            // Set language for speech recognition
            if (speechRecognition && detectedLanguage) {
                const speechLang = detectedLanguage === 'de' ? 'de-DE' : 'en-US';
                speechRecognition.lang = speechLang;
                debug("Speech recognition language set to:", speechLang);
            }
        }

        function setupVoiceControls() {
            if (!voiceEnabled) return;

            // Get voice control elements
            voiceInputBtn = document.getElementById('voiceInputBtn');
            stopListeningBtn = document.getElementById('stopListeningBtn');
            toggleTTSBtn = document.getElementById('toggleTTSBtn');
            const vadToggleBtn = document.getElementById('vadToggleBtn');

            if (!voiceInputBtn || !stopListeningBtn || !toggleTTSBtn) {
                debug("Voice control elements not found");
                return;
            }

            debug("Setting up voice controls with VAD");

            // Create voice status element
            voiceStatus = document.createElement('div');
            voiceStatus.id = 'voiceStatus';
            voiceStatus.className = 'voice-status';
            const voiceControls = document.getElementById('voiceControls');
            if (voiceControls) {
                voiceControls.parentNode.insertBefore(voiceStatus, voiceControls);
            }

            // Voice input button
            voiceInputBtn.addEventListener('click', function() {
                if (!isListening && speechRecognition) {
                    startVoiceInput();
                }
            });

            // Stop listening button
            stopListeningBtn.addEventListener('click', function() {
                if (isListening && speechRecognition) {
                    stopVoiceInput();
                }
            });

            // Toggle TTS button
            toggleTTSBtn.addEventListener('click', function() {
                toggleTTS();
            });
            
            // VAD toggle button (if available)
            if (vadToggleBtn) {
                vadToggleBtn.addEventListener('click', function() {
                    toggleVadAutoSend();
                });
                updateVadControls();
            }

            // Update TTS button state
            updateTTSButtonState();

            debug("Voice controls setup complete");
        }

        function setVoiceInputState(state) {
            if (!voiceInputBtn || !stopListeningBtn) return;

            const language = detectedLanguage || 'en';

            switch (state) {
                case 'listening':
                    isListening = true;
                    voiceInputBtn.style.display = 'none';
                    stopListeningBtn.style.display = 'flex';
                    voiceInputBtn.classList.remove('active');
                    stopListeningBtn.classList.add('listening');
                    
                    const listeningMessages = {
                        'en': 'Listening... Speak now',
                        'de': 'Höre zu... Sprechen Sie jetzt'
                    };
                    showVoiceStatus(listeningMessages[language], 'listening');
                    break;

                case 'processing':
                    const processingMessages = {
                        'en': 'Processing speech...',
                        'de': 'Verarbeite Sprache...'
                    };
                    showVoiceStatus(processingMessages[language], 'processing');
                    break;

                case 'idle':
                default:
                    isListening = false;
                    voiceInputBtn.style.display = 'flex';
                    stopListeningBtn.style.display = 'none';
                    voiceInputBtn.classList.remove('active');
                    stopListeningBtn.classList.remove('listening');
                    hideVoiceStatus();
                    break;
            }
        }

        function startVoiceInput() {
            if (!speechRecognition || isListening) return;

            debug("Starting voice input with VAD");

            // Set language if detected
            if (detectedLanguage) {
                const speechLang = detectedLanguage === 'de' ? 'de-DE' : 'en-US';
                speechRecognition.lang = speechLang;
            }

            // Stop any current TTS playback
            stopCurrentTTS();

            // Clear current input
            if (userInput) {
                userInput.value = '';
            }
            
            // Reset VAD variables
            vadLastSpeechTime = null;
            stopVadSilenceDetection();

            try {
                speechRecognition.start();
            } catch (error) {
                debug("Error starting speech recognition:", error);
                setVoiceInputState('idle');
            }
        }

        function stopVoiceInput() {
            if (!speechRecognition || !isListening) return;

            debug("Stopping voice input");
            
            // Stop VAD silence detection
            stopVadSilenceDetection();
            
            speechRecognition.stop();
        }

        function toggleTTS() {
            ttsEnabled = !ttsEnabled;
            updateTTSButtonState();
            
            if (!ttsEnabled) {
                stopCurrentTTS();
            }
            
            debug("TTS toggled:", ttsEnabled ? 'enabled' : 'disabled');
        }

        function updateTTSButtonState() {
            if (!toggleTTSBtn) return;

            const language = detectedLanguage || 'en';
            
            if (ttsEnabled) {
                toggleTTSBtn.classList.remove('tts-off');
                toggleTTSBtn.classList.add('active');
                
                const voiceIcon = toggleTTSBtn.querySelector('.voice-icon');
                const voiceText = toggleTTSBtn.querySelector('.voice-text');
                
                if (voiceIcon) voiceIcon.textContent = '🔊';
                if (voiceText) {
                    voiceText.textContent = language === 'de' ? 'Sprache An' : 'Voice On';
                }
            } else {
                toggleTTSBtn.classList.add('tts-off');
                toggleTTSBtn.classList.remove('active');
                
                const voiceIcon = toggleTTSBtn.querySelector('.voice-icon');
                const voiceText = toggleTTSBtn.querySelector('.voice-text');
                
                if (voiceIcon) voiceIcon.textContent = '🔇';
                if (voiceText) {
                    voiceText.textContent = language === 'de' ? 'Sprache Aus' : 'Voice Off';
                }
            }
        }

        function showVoiceStatus(message, type = '') {
            if (!voiceStatus) return;

            voiceStatus.textContent = message;
            voiceStatus.className = 'voice-status visible';
            
            if (type) {
                voiceStatus.classList.add(type);
            }
        }

        function hideVoiceStatus() {
            if (!voiceStatus) return;

            voiceStatus.classList.remove('visible', 'listening', 'processing', 'error');
        }

        // --- TTS FUNCTIONALITY - FIXED COMPLETE VERSION ---
        async function generateTTS(text) {
            if (!hasElevenLabsKey) {
                debug("No ElevenLabs key, using fallback TTS");
                return useFallbackTTS(text);
            }

            try {
                debug("Generating TTS with ElevenLabs:", text.substring(0, 50) + "...");

                const formData = new FormData();
                formData.append('action', 'ai_interview_tts');
                formData.append('text', text);
                formData.append('nonce', widgetData.nonce);

                const response = await fetch(widgetData.ajaxurl, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }

                const data = await response.json();
                debug("TTS response:", data);

                if (data.success && data.data && data.data.audio_url) {
                    return data.data.audio_url;
                } else if (data.success && data.data && data.data.fallback) {
                    // ElevenLabs API failed, use browser fallback
                    debug("ElevenLabs TTS not available, using fallback:", data.data.message || 'No message');
                    throw new Error("ElevenLabs TTS failed, using fallback");
                } else {
                    debug("Invalid TTS response format:", data);
                    throw new Error("Invalid TTS response");
                }
            } catch (error) {
                debug("ElevenLabs TTS failed:", error);
                return useFallbackTTS(text);
            }
        }

        function useFallbackTTS(text) {
            return new Promise((resolve, reject) => {
                if (!speechSynthesis) {
                    reject(new Error("No TTS available"));
                    return;
                }

                debug("Using fallback browser TTS");

                const utterance = new SpeechSynthesisUtterance(text);
                
                // Set language
                if (detectedLanguage === 'de') {
                    utterance.lang = 'de-DE';
                } else {
                    utterance.lang = 'en-US';
                }

                utterance.rate = 0.9;
                utterance.pitch = 1.0;
                utterance.volume = 0.8;

                utterance.onend = function() {
                    debug("Fallback TTS completed");
                    resolve('fallback');
                };

                utterance.onerror = function(event) {
                    debug("Fallback TTS error:", event);
                    reject(new Error("Fallback TTS failed"));
                };

                speechSynthesis.speak(utterance);
            });
        }

        async function playTTS(text) {
            if (!ttsEnabled) {
                debug("TTS disabled, skipping");
                return;
            }

            // Stop any current TTS
            stopCurrentTTS();

            try {
                const audioUrl = await generateTTS(text);
                
                if (audioUrl === 'fallback') {
                    // Fallback TTS is already playing
                    return;
                }

                // Play ElevenLabs audio
                currentTTSAudio = new Audio(audioUrl);
                currentTTSAudio.onended = function() {
                    currentTTSAudio = null;
                    debug("TTS playback completed");
                };

                currentTTSAudio.onerror = function(error) {
                    debug("TTS playback error:", error);
                    currentTTSAudio = null;
                };

                // Integrate audio visualization for TTS playback BEFORE playing
                attachAudioVisualization(currentTTSAudio);

                await currentTTSAudio.play();
                debug("TTS playback started");

            } catch (error) {
                debug("TTS playback failed:", error);
            }
        }

        function stopCurrentTTS() {
            // Stop ElevenLabs audio
            if (currentTTSAudio) {
                currentTTSAudio.pause();
                currentTTSAudio = null;
                debug("Stopped ElevenLabs TTS");
            }

            // Stop fallback TTS
            if (speechSynthesis && speechSynthesis.speaking) {
                speechSynthesis.cancel();
                debug("Stopped fallback TTS");
            }
        }

        function addTTSButtonToMessage(messageElement) {
            if (!ttsEnabled || !voiceEnabled) return;

            const ttsButton = document.createElement('button');
            ttsButton.className = 'tts-button';
            ttsButton.innerHTML = '🔊';
            ttsButton.title = detectedLanguage === 'de' ? 'Vorlesen' : 'Read Aloud';

            ttsButton.addEventListener('click', function() {
                const messageText = messageElement.textContent.replace('🔊', '').trim();
                
                if (ttsButton.classList.contains('playing')) {
                    stopCurrentTTS();
                    ttsButton.classList.remove('playing');
                    ttsButton.innerHTML = '🔊';
                } else {
                    // Stop any other playing TTS first
                    document.querySelectorAll('.tts-button.playing').forEach(btn => {
                        btn.classList.remove('playing');
                        btn.innerHTML = '🔊';
                    });
                    
                    ttsButton.classList.add('playing');
                    ttsButton.innerHTML = '⏸️';
                    
                    playTTS(messageText).then(() => {
                        ttsButton.classList.remove('playing');
                        ttsButton.innerHTML = '🔊';
                    }).catch(() => {
                        ttsButton.classList.remove('playing');
                        ttsButton.innerHTML = '🔊';
                    });
                }
            });

            messageElement.appendChild(ttsButton);
        }

        // --- ENHANCED UI CONTROL FUNCTIONS ---
        function setUILoadingState(isLoading) {
            if (isLoading) {
                // Disable input controls
                if (sendButton) {
                    sendButton.disabled = true;
                    sendButton.classList.add('loading');
                }
                if (userInput) {
                    userInput.disabled = true;
                }
                
                // Disable voice controls
                if (voiceInputBtn) voiceInputBtn.disabled = true;
                if (stopListeningBtn) stopListeningBtn.disabled = true;
                
                // Show enhanced typing indicator
                showEnhancedTypingIndicator();
                
            } else {
                // Re-enable input controls
                if (sendButton) {
                    sendButton.disabled = false;
                    sendButton.classList.remove('loading');
                }
                if (userInput) {
                    userInput.disabled = false;
                }
                
                // Re-enable voice controls
                if (voiceInputBtn) voiceInputBtn.disabled = false;
                if (stopListeningBtn) stopListeningBtn.disabled = false;
                
                // Hide typing indicator
                hideTypingIndicator();
            }
        }

        function showEnhancedTypingIndicator() {
            if (!typingIndicator) return;

            // Create enhanced typing indicator HTML
            const language = detectedLanguage || 'en';
            const messages = {
                'en': [
                    'Eric is thinking...',
                    'Processing your question...',
                    'Crafting a thoughtful response...',
                    'Accessing knowledge base...',
                    'Eric is considering your query...'
                ],
                'de': [
                    'Eric denkt nach...',
                    'Verarbeite deine Frage...',
                    'Erstelle eine durchdachte Antwort...',
                    'Greife auf Wissensbasis zu...',
                    'Eric überlegt gerade...'
                ]
            };
            
            const randomMessage = messages[language][Math.floor(Math.random() * messages[language].length)];
            
            typingIndicator.innerHTML = `
                <div class="ai-processing-content">
                    <div class="ai-spinner"></div>
                    <span class="processing-text">${randomMessage}</span>
                    <div class="thinking-dots">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                </div>
            `;
            
            typingIndicator.classList.add('visible');
            typingIndicator.style.display = 'block';
            
            // Scroll to show the indicator
            if (chatHistory) {
                chatHistory.scrollTop = chatHistory.scrollHeight;
            }
        }

        function hideTypingIndicator() {
            if (!typingIndicator) return;

            typingIndicator.classList.remove('visible');
            typingIndicator.style.display = 'none';
        }

        function updateTypingMessage() {
            if (!typingIndicator || !typingIndicator.classList.contains('visible')) return;
            
            const language = detectedLanguage || 'en';
            const progressMessages = {
                'en': [
                    'Eric is analyzing your question...',
                    'Gathering relevant information...',
                    'Formulating response...',
                    'Almost ready...'
                ],
                'de': [
                    'Eric analysiert deine Frage...',
                    'Sammle relevante Informationen...',
                    'Formuliere Antwort...',
                    'Gleich fertig...'
                ]
            };
            
            const messageElement = typingIndicator.querySelector('.processing-text');
            if (messageElement) {
                const messages = progressMessages[language];
                const currentIndex = Math.floor(Math.random() * messages.length);
                messageElement.textContent = messages[currentIndex];
            }
        }

        /**
         * Initialize dynamic content (system prompts and welcome messages) for all supported languages
         * Loads from widget data with English fallback for missing content
         */
        function initializeDynamicContent() {
            // Default English fallback content
            const defaultSystemPrompt = "You are Eric Rorich, a creative and multidisciplinary professional from Braunschweig, Germany. Answer questions as Eric about your skills and experience.";
            const defaultWelcomeMessage = "Hello! Talk to me!";
            
            // Get supported languages
            const supportedLanguages = getSupportedLanguages();
            
            // Initialize system prompts and welcome messages for all supported languages
            supportedLanguages.forEach(langCode => {
                // Try to load system prompt for this language
                const systemPromptKey = `system_prompt_${langCode}`;
                systemPrompts[langCode] = widgetData[systemPromptKey] || null;
                
                // Try to load welcome message for this language  
                const welcomeMessageKey = `welcome_message_${langCode}`;
                welcomeMessages[langCode] = widgetData[welcomeMessageKey] || null;
            });
            
            // Ensure English defaults are always available
            if (!systemPrompts.en || systemPrompts.en.trim() === '') {
                systemPrompts.en = defaultSystemPrompt;
            }
            if (!welcomeMessages.en || welcomeMessages.en.trim() === '') {
                welcomeMessages.en = defaultWelcomeMessage;
            }
            
            // German fallback for backwards compatibility
            if (!systemPrompts.de || systemPrompts.de.trim() === '') {
                systemPrompts.de = "Du bist Eric Rorich, ein kreativer und multidisziplinärer Profi aus Braunschweig, Deutschland. Beantworte Fragen als Eric über deine Fähigkeiten und Erfahrungen.";
            }
            if (!welcomeMessages.de || welcomeMessages.de.trim() === '') {
                welcomeMessages.de = "Hallo! Sprich mit mir!";
            }
        }

        /**
         * Enhanced system prompt retrieval with automatic English fallback
         * Supports all 20 languages and handles missing system prompts gracefully
         */
        function getSystemPrompt() {
            const language = detectedLanguage || 'en';
            
            // Try to get system prompt for detected language
            if (systemPrompts[language] && systemPrompts[language].trim() !== '') {
                debug(`Using ${language} system prompt`);
                return systemPrompts[language];
            }
            
            // Fallback to English if no system prompt for detected language
            debug(`No system prompt found for language ${language}, falling back to English`);
            return systemPrompts.en || "You are Eric Rorich, a creative and multidisciplinary professional from Braunschweig, Germany. Answer questions as Eric about your skills and experience.";
        }

        /**
         * Enhanced welcome message retrieval with automatic English fallback
         * Supports all 20 languages and handles missing welcome messages gracefully
         */
        function getLocalizedWelcomeMessage() {
            const language = detectedLanguage || 'en';
            
            // Try to get welcome message for detected language
            if (welcomeMessages[language] && welcomeMessages[language].trim() !== '') {
                debug(`Using ${language} welcome message`);
                return welcomeMessages[language];
            }
            
            // Fallback to English if no welcome message for detected language
            debug(`No welcome message found for language ${language}, falling back to English`);
            return welcomeMessages.en || "Hello! Talk to me!";
        }

        /**
         * Enhanced browser language preference detection
         * Supports comprehensive language detection with timezone hints
         * Maintains backwards compatibility with existing German/English detection
         */
        function getLocalePreference() {
            // Check for manual override first (for testing - can be removed in production)
            const forcedLang = localStorage.getItem('aiWidget_forceLang');
            if (forcedLang) {
                debug("Using forced language:", forcedLang);
                return forcedLang;
            }
            
            // Check browser language - prioritize exact matches from supported languages
            let lang = (navigator.languages && navigator.languages.length)
                ? navigator.languages[0]
                : (navigator.language || navigator.userLanguage || 'en');
            lang = lang.toLowerCase();
            debug("Browser language detected:", lang);
            
            // Get supported languages for validation
            const supportedLanguages = getSupportedLanguages();
            
            // Try exact match first (e.g., 'en', 'de', 'fr')
            const exactMatch = lang.split('-')[0];
            if (supportedLanguages.includes(exactMatch)) {
                debug(`Exact language match found: ${exactMatch}`);
                return exactMatch;
            }
            
            // Legacy German language detection (maintains backwards compatibility)
            if (lang.startsWith('de') || lang.includes('de') || 
                lang === 'de-de' || lang === 'de-at' || lang === 'de-ch' || lang === 'de-li') {
                debug("German language detected from browser (legacy detection)");
                return 'de';
            }
            
            // Check timezone as additional hint for German-speaking regions
            try {
                const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
                debug("Detected timezone:", timezone);
                const germanTimezones = ['Europe/Berlin', 'Europe/Vienna', 'Europe/Zurich', 'Europe/Vaduz'];
                if (germanTimezones.includes(timezone)) {
                    debug("German timezone detected, preferring German");
                    return 'de';
                }
            } catch (e) {
                debug("Timezone detection failed:", e);
            }
            
            debug("Defaulting to English based on browser language");
            return 'en';
        }

        /**
         * Comprehensive mapping of countries to languages based on primary language spoken
         * Maps ISO 3166-1 alpha-2 country codes to the 20 supported language codes
         * Designed for extensibility - new languages can be easily added
         */
        function getCountryToLanguageMapping() {
            return {
                // English-speaking countries
                'US': 'en', 'GB': 'en', 'AU': 'en', 'CA': 'en', 'NZ': 'en', 'IE': 'en', 
                'ZA': 'en', 'NG': 'en', 'KE': 'en', 'GH': 'en', 'UG': 'en', 'TZ': 'en',
                'ZW': 'en', 'BW': 'en', 'MW': 'en', 'ZM': 'en', 'SZ': 'en', 'LS': 'en',
                'LR': 'en', 'SL': 'en', 'GM': 'en', 'FJ': 'en', 'PG': 'en', 'VU': 'en',
                'SB': 'en', 'WS': 'en', 'TV': 'en', 'TO': 'en', 'NR': 'en', 'PW': 'en',
                'MH': 'en', 'FM': 'en', 'KI': 'en', 'BB': 'en', 'BS': 'en', 'BZ': 'en',
                'GD': 'en', 'GY': 'en', 'JM': 'en', 'KN': 'en', 'LC': 'en', 'VC': 'en',
                'SR': 'en', 'TT': 'en', 'AG': 'en', 'DM': 'en',
                
                // Spanish-speaking countries
                'ES': 'es', 'MX': 'es', 'AR': 'es', 'CO': 'es', 'PE': 'es', 'VE': 'es',
                'CL': 'es', 'EC': 'es', 'UY': 'es', 'PY': 'es', 'BO': 'es', 'CR': 'es',
                'PA': 'es', 'NI': 'es', 'HN': 'es', 'SV': 'es', 'GT': 'es', 'CU': 'es',
                'DO': 'es', 'PR': 'es', 'GQ': 'es',
                
                // Chinese-speaking countries/regions
                'CN': 'zh', 'TW': 'zh', 'HK': 'zh', 'MO': 'zh', 'SG': 'zh',
                
                // German-speaking countries
                'DE': 'de', 'AT': 'de', 'CH': 'de', 'LI': 'de', 'LU': 'de',
                
                // French-speaking countries
                'FR': 'fr', 'BE': 'fr', 'MC': 'fr', 'LU': 'fr', 'SN': 'fr', 'ML': 'fr',
                'BF': 'fr', 'NE': 'fr', 'CI': 'fr', 'GN': 'fr', 'TD': 'fr', 'CM': 'fr',
                'CF': 'fr', 'CG': 'fr', 'GA': 'fr', 'MG': 'fr', 'BI': 'fr', 'RW': 'fr',
                'DJ': 'fr', 'KM': 'fr', 'SC': 'fr', 'VU': 'fr', 'NC': 'fr', 'PF': 'fr',
                'WF': 'fr', 'PM': 'fr', 'MQ': 'fr', 'GP': 'fr', 'GF': 'fr', 'RE': 'fr',
                'YT': 'fr', 'TF': 'fr', 'HT': 'fr',
                
                // Portuguese-speaking countries
                'PT': 'pt', 'BR': 'pt', 'AO': 'pt', 'MZ': 'pt', 'GW': 'pt', 'CV': 'pt',
                'ST': 'pt', 'TL': 'pt', 'MO': 'pt',
                
                // Arabic-speaking countries
                'SA': 'ar', 'AE': 'ar', 'EG': 'ar', 'IQ': 'ar', 'JO': 'ar', 'KW': 'ar',
                'LB': 'ar', 'LY': 'ar', 'MA': 'ar', 'OM': 'ar', 'QA': 'ar', 'SY': 'ar',
                'TN': 'ar', 'YE': 'ar', 'BH': 'ar', 'DZ': 'ar', 'SD': 'ar', 'SO': 'ar',
                'DJ': 'ar', 'KM': 'ar', 'MR': 'ar', 'PS': 'ar',
                
                // Russian-speaking countries
                'RU': 'ru', 'BY': 'ru', 'KZ': 'ru', 'KG': 'ru', 'TJ': 'ru', 'UZ': 'ru',
                'TM': 'ru', 'AM': 'ru', 'AZ': 'ru', 'GE': 'ru', 'MD': 'ru', 'UA': 'ru',
                
                // Japanese-speaking countries
                'JP': 'ja',
                
                // Korean-speaking countries
                'KR': 'ko', 'KP': 'ko',
                
                // Italian-speaking countries
                'IT': 'it', 'SM': 'it', 'VA': 'it', 'MT': 'it',
                
                // Turkish-speaking countries
                'TR': 'tr', 'CY': 'tr',
                
                // Vietnamese-speaking countries
                'VN': 'vi',
                
                // Hindi-speaking countries (India has many languages, Hindi is most common)
                'IN': 'hi',
                
                // Bengali-speaking countries
                'BD': 'bn',
                
                // Other language mappings for completeness
                // Punjabi (primarily Pakistan/India regions)
                'PK': 'pa',
                
                // Telugu, Marathi, Tamil are regional languages in India
                // For now, map India to Hindi as the most widely understood
                // These could be expanded based on regional IP detection if needed
                
                // Javanese (Indonesia) - Indonesian (Bahasa Indonesia) is more common
                // but Javanese is listed in supported languages
                'ID': 'jv'
            };
        }

        /**
         * Privacy-conscious geolocation detection with graceful fallbacks
         * Uses single reliable service with proper error handling and makes feature optional
         */
        async function detectCountryAutomatically() {
            // Check if geolocation is disabled in settings or by user preference
            if (widgetData.disable_geolocation || typeof window.aiWidgetDisableGeolocation !== 'undefined' && window.aiWidgetDisableGeolocation) {
                debug("Geolocation disabled by configuration");
                return null;
            }
            
            debug("Starting optional country detection...");
            
            // Use single, reliable service to avoid rate limiting and CORS issues
            // CloudFlare's trace service is reliable and doesn't require CORS
            try {
                debug("Attempting CloudFlare trace service for country detection");
                
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 3000); // Shorter timeout for better UX
                
                // CloudFlare trace service - reliable and CORS-friendly
                const response = await fetch('https://www.cloudflare.com/cdn-cgi/trace', {
                    method: 'GET',
                    signal: controller.signal,
                    headers: {
                        'Accept': 'text/plain'
                    }
                });
                
                clearTimeout(timeoutId);
                
                if (!response.ok) {
                    debug(`CloudFlare trace service responded with status: ${response.status}`);
                    return null;
                }
                
                const traceText = await response.text();
                debug(`CloudFlare trace response received`);
                
                // Parse the trace response to extract country code
                const lines = traceText.split('\n');
                for (const line of lines) {
                    if (line.startsWith('loc=')) {
                        const country = line.split('=')[1].trim();
                        if (country && country.length === 2) {
                            debug(`Successfully detected country: ${country} via CloudFlare trace`);
                            return country.toUpperCase();
                        }
                    }
                }
                
                debug("No country found in CloudFlare trace response");
                return null;
                
            } catch (error) {
                // Silently handle errors in production to avoid console noise
                if (DEBUG) {
                    debug(`Country detection failed gracefully:`, error.message);
                    if (error.name === 'AbortError') {
                        debug(`Request timed out - continuing without geolocation`);
                    } else if (error.name === 'TypeError') {
                        debug(`Network error - possibly offline or blocked`);
                    }
                }
                return null;
            }
        }

        /**
         * Enhanced user language detection with comprehensive country-to-language mapping
         * Supports all 20 configured languages with robust fallback logic
         */
        async function detectUserLanguage() {
            debug("Detecting user language...");
            
            // Start with browser language preference as fallback
            let locale = getLocalePreference();
            debug("Initial locale from browser/timezone:", locale);
            
            // Initialize detectedCountry variable in function scope
            let detectedCountry = null;
            
            // Try to detect country via IP and map to supported language
            try {
                detectedCountry = await detectCountryAutomatically();
                
                if (detectedCountry) {
                    debug("Country detected via IP:", detectedCountry);
                    
                    // Get country-to-language mapping
                    const countryLanguageMap = getCountryToLanguageMapping();
                    
                    // Check if detected country has a language mapping
                    if (countryLanguageMap[detectedCountry]) {
                        const mappedLanguage = countryLanguageMap[detectedCountry];
                        
                        // Validate that the mapped language is in our supported languages
                        // Get supported languages from widget data or fallback to defaults
                        const supportedLanguages = getSupportedLanguages();
                        
                        if (supportedLanguages.includes(mappedLanguage)) {
                            locale = mappedLanguage;
                            debug(`Final language: ${mappedLanguage} (IP-detected country: ${detectedCountry})`);
                        } else {
                            debug(`Mapped language ${mappedLanguage} not in supported languages, using fallback`);
                            locale = 'en'; // Default to English if mapped language not supported
                        }
                    } else {
                        debug(`No language mapping found for country ${detectedCountry}, defaulting to English`);
                        locale = 'en'; // Default to English for unmapped countries
                    }
                } else {
                    debug("No country detected, keeping browser/timezone selection:", locale);
                }
            } catch (error) {
                debug("Country detection failed:", error);
                debug("Using browser/timezone selection:", locale);
                // Ensure detectedCountry remains null when detection fails
                detectedCountry = null;
            }
            
            // Final validation - ensure detected language is supported
            const supportedLanguages = getSupportedLanguages();
            if (!supportedLanguages.includes(locale)) {
                debug(`Detected language ${locale} not supported, defaulting to English`);
                locale = 'en';
            }
            
            detectedLanguage = locale;
            debug("Final detected language:", detectedLanguage);
            
            // Log comprehensive language detection summary for debugging
            logLanguageDetectionSummary(locale, detectedCountry);
            
            // Initialize voice features after language detection
            if (voiceEnabled) {
                initializeVoiceFeatures();
            }
            
            return locale;
        }

        /**
         * Log a comprehensive summary of the language detection process
         * Useful for debugging and monitoring language detection accuracy
         */
        function logLanguageDetectionSummary(finalLanguage, detectedCountry) {
            const supportedLanguages = getSupportedLanguages();
            const countryMapping = getCountryToLanguageMapping();
            
            console.group('🌍 Language Detection Summary');
            console.log('📍 Detected Country:', detectedCountry || 'Not detected');
            console.log('🗣️ Final Language:', finalLanguage);
            console.log('💾 Supported Languages:', supportedLanguages.length, 'languages');
            console.log('🔗 Country Mapping Available:', detectedCountry && countryMapping[detectedCountry] ? 'Yes' : 'No');
            console.log('📦 System Prompts Available:', Object.keys(systemPrompts).filter(lang => systemPrompts[lang] && systemPrompts[lang].trim() !== ''));
            console.log('💬 Welcome Messages Available:', Object.keys(welcomeMessages).filter(lang => welcomeMessages[lang] && welcomeMessages[lang].trim() !== ''));
            console.log('⚡ Detection Method:', detectedCountry ? 'IP-based' : 'Browser/Timezone-based');
            console.groupEnd();
        }

        /**
         * Get list of supported languages from widget configuration
         * Falls back to default English/German if configuration not available
         */
        function getSupportedLanguages() {
            // Try to get supported languages from widget data
            if (widgetData && widgetData.supported_languages) {
                try {
                    const supportedLangs = JSON.parse(widgetData.supported_languages);
                    return Object.keys(supportedLangs);
                } catch (e) {
                    debug("Failed to parse supported languages from widget data:", e);
                }
            }
            
            // Fallback to the 20 common languages if widget data not available
            return ['en', 'zh', 'es', 'hi', 'ar', 'pt', 'bn', 'ru', 'ja', 'pa', 'de', 'jv', 'ko', 'fr', 'te', 'mr', 'tr', 'ta', 'vi', 'it'];
        }

        function determineAudioSource() {
            return new Promise(async (resolve) => {
                debug("Determining audio source...");
                debug("Available audio URLs:", {
                    en: widgetData.greeting_en,
                    de: widgetData.greeting_de,
                    available: widgetData.audio_files_available
                });
                
                // Check if audio files are available
                if (!widgetData.audio_files_available && !widgetData.greeting_en && !widgetData.greeting_de) {
                    debug("No audio files available from server");
                    resolve(null);
                    return;
                }

                // Get language preference (this will also set detectedLanguage)
                const locale = await detectUserLanguage();
                let selectedSrc = '';
                let selectedSrcAlt = '';
                
                // Set selection based on detected language
                if (locale === 'de' && widgetData.greeting_de) {
                    selectedSrc = widgetData.greeting_de;
                    selectedSrcAlt = widgetData.greeting_de_alt;
                    debug("Selected German audio");
                } else if (widgetData.greeting_en) {
                    selectedSrc = widgetData.greeting_en;
                    selectedSrcAlt = widgetData.greeting_en_alt;
                    debug("Selected English audio");
                }
                
                debug("Final audio selection:", { primary: selectedSrc, alt: selectedSrcAlt });
                resolve({ primary: selectedSrc, alt: selectedSrcAlt });
            });
        }

        function loadAudioSource(sources) {
            return new Promise((resolve, reject) => {
                if (!audio) {
                    debug("Audio element not found");
                    reject(new Error("Audio element not available"));
                    return;
                }

                if (!sources || (!sources.primary && !sources.alt)) {
                    debug("No audio sources provided");
                    reject(new Error("No audio sources"));
                    return;
                }

                debug("Loading audio sources:", sources);

                // Try primary source first, then alternative
                const sourcesToTry = [sources.primary, sources.alt].filter(Boolean);
                let currentSourceIndex = 0;

                function tryNextSource() {
                    if (currentSourceIndex >= sourcesToTry.length) {
                        debug("All audio sources failed");
                        reject(new Error("All audio sources failed"));
                        return;
                    }

                    const src = sourcesToTry[currentSourceIndex];
                    debug(`Trying audio source ${currentSourceIndex + 1}/${sourcesToTry.length}:`, src);
                    currentAudioSrc = src;

                    // Remove existing event listeners to avoid duplicates
                    audio.removeEventListener('canplaythrough', handleAudioLoad);
                    audio.removeEventListener('error', handleAudioError);
                    audio.removeEventListener('loadeddata', handleAudioLoadedData);

                    function handleAudioLoad() {
                        debug("Audio loaded successfully:", src);
                        audio.removeEventListener('canplaythrough', handleAudioLoad);
                        audio.removeEventListener('error', handleAudioError);
                        audio.removeEventListener('loadeddata', handleAudioLoadedData);
                        audioReady = true;
                        audioSourceSet = true;
                        resolve(src);
                    }

                    function handleAudioLoadedData() {
                        debug("Audio data loaded:", src);
                        // Don't remove listeners here, wait for canplaythrough
                    }

                    function handleAudioError(e) {
                        debug(`Audio failed to load (attempt ${currentSourceIndex + 1}):`, src, e);
                        audio.removeEventListener('canplaythrough', handleAudioLoad);
                        audio.removeEventListener('error', handleAudioError);
                        audio.removeEventListener('loadeddata', handleAudioLoadedData);
                        
                        currentSourceIndex++;
                        setTimeout(tryNextSource, 100); // Small delay before trying next source
                    }

                    // Add event listeners
                    audio.addEventListener('canplaythrough', handleAudioLoad);
                    audio.addEventListener('error', handleAudioError);
                    audio.addEventListener('loadeddata', handleAudioLoadedData);

                    // Set source and load
                    audio.src = src;
                    audio.load();
                    
                    // Add a timeout as fallback
                    setTimeout(() => {
                        if (!audioReady && audio.readyState >= 2) {
                            debug("Audio seems ready despite no canplaythrough event");
                            audio.removeEventListener('canplaythrough', handleAudioLoad);
                            audio.removeEventListener('error', handleAudioError);
                            audio.removeEventListener('loadeddata', handleAudioLoadedData);
                            audioReady = true;
                            audioSourceSet = true;
                            resolve(src);
                        }
                    }, 5000);
                }

                tryNextSource();
            });
        }

        // Add comprehensive audio error handling
        if (audio) {
            audio.addEventListener('error', function(e) {
                debug("Audio error event triggered:", e);
                console.error('Audio loading/playback error:', e);
                audioReady = false;
            });

            audio.addEventListener('loadstart', function() {
                debug("Audio load started");
            });

            audio.addEventListener('canplay', function() {
                debug("Audio can start playing");
            });
        }

        // --- DRAWING FUNCTIONS WITH RESPONSIVE SCALING ---
        function drawProgressBar() {
            if (!audio || !ctx || !audio.duration || isNaN(audio.duration) || audio.duration === 0) {
                return;
            }
            const progress = audio.currentTime / audio.duration;
            const width = canvas.width;
            const barWidth = Math.max(0, Math.min(width * progress, width));
            const y = progressBarMargin;

            ctx.save();
            ctx.globalAlpha = 0.15;
            ctx.fillStyle = '#001a33';
            ctx.fillRect(0, y, width, progressBarHeight);
            ctx.restore();

            const grad = ctx.createLinearGradient(0, y, width, y + progressBarHeight);
            grad.addColorStop(0, "#00cfff");
            grad.addColorStop(0.3, "#00ffff");
            grad.addColorStop(0.7, "#0066ff");
            grad.addColorStop(1, "#7b00ff");

            ctx.save();
            ctx.globalAlpha = 0.95;
            ctx.fillStyle = grad;
            ctx.fillRect(0, y, barWidth, progressBarHeight);
            ctx.restore();
        }

        function drawParticles(centerX, centerY, radius) {
            if (!ctx) return;

            const particleCount = isMobile ? 8 : isTablet ? 10 : 12;
            const time = performance.now() / 1000;
            for (let i = 0; i < particleCount; i++) {
                const angle = (i / particleCount) * Math.PI * 2;
                const distance = radius * 1.3;
                const size = (isMobile ? 1.5 : 2) + Math.sin(time * 2 + i) * (isMobile ? 1 : 1.5);
                const x = centerX + Math.cos(angle) * distance;
                const y = centerY + Math.sin(angle) * distance;

                ctx.beginPath();
                ctx.arc(x, y, size, 0, Math.PI * 2);
                ctx.fillStyle = `rgba(0,207,255,${0.3 + Math.sin(time * 3 + i) * 0.2})`;
                ctx.fill();
            }
        }

        // Legacy function - now handled by CSS overlay
        function drawProgressBar() {
            if (!ctx || !canvas) return;
            // Progress bar is no longer needed for play button
        }

        // Legacy canvas-based button drawing functions removed
        // Play button is now handled by CSS overlay

        async function handlePlayButtonClick() {
            debug("Play button clicked");
            
            // Hide the overlay button immediately
            hidePlayButtonOverlay();
            
            // If audio is not ready, try to determine and load source now
            if (!audioReady || !audioSourceSet) {
                debug("Audio not ready, attempting to load now...");
                try {
                    const audioSources = await determineAudioSource();
                    if (audioSources && (audioSources.primary || audioSources.alt)) {
                        const loadedSource = await loadAudioSource(audioSources);
                        debug("Audio loaded successfully during play button click:", loadedSource);
                    } else {
                        debug("No audio sources available, showing chat interface");
                        showChatInterface();
                        return;
                    }
                } catch (error) {
                    debug("Failed to load audio during play button click:", error);
                    showChatInterface();
                    return;
                }
            }
            
            // Check if audio is ready after potential loading
            if (audioReady && currentAudioSrc && audio) {
                debug("Starting audio playback sequence");
                audio.style.visibility = "visible";

                if (pauseBtnContainer) {
                    setTimeout(() => {
                        pauseBtnContainer.style.pointerEvents = "auto";
                        pauseBtnContainer.style.opacity = 1;
                    }, 200);
                }

                const fadeDuration = isMobile ? 1500 : isTablet ? 1800 : 2000;
                drawRestAudiobarFadeIn(() => {
                    try {
                        setupAudioContext();
                        audio.currentTime = 0;
                        audioVisualizationActive = true;
                        drawSoundbar();

                        debug("Attempting to play audio:", currentAudioSrc);
                        audio.play().then(() => {
                            debug("Audio playback started successfully");
                            // The greeting audio uses the existing audio context and soundbar canvas
                            // No need to call attachAudioVisualization here as it would create conflicts
                        }).catch(err => {
                            console.error("Audio play error:", err);
                            showChatInterface();
                        });
                    } catch (e) {
                        console.error("Audio setup error:", e);
                        showChatInterface();
                    }
                }, fadeDuration);
            } else {
                debug("Audio still not ready after loading attempt, showing chat interface");
                showChatInterface();
            }
        }

        function drawRestAudiobarFadeIn(onComplete, duration = 2000) {
            if (!ctx || !canvas) {
                if (typeof onComplete === 'function') onComplete();
                return;
            }

            const startTime = performance.now();

            function frame(now) {
                const elapsed = now - startTime;
                let localAlpha = Math.min(1, elapsed / duration);

                ctx.clearRect(0, 0, canvas.width, canvas.height);
                ctx.fillStyle = getCanvasBackgroundColor();
                ctx.fillRect(0, 0, canvas.width, canvas.height);

                drawProgressBar();

                const actualBufferLength = bufferLength || 64;
                const totalBars = Math.floor(actualBufferLength / 2);
                const barWidth = isMobile ? 1.5 : 2;
                const barSpacing = isMobile ? 1.5 : 2;
                const centerY = canvas.height / 2;
                const barMaxHeight = isMobile ? 80 : isTablet ? 120 : 150;
                const centerX = canvas.width / 2;

                ctx.save();
                ctx.globalAlpha = localAlpha;

                for (let i = 0; i < totalBars; i++) {
                    const distanceFromCenter = i / totalBars;
                    const centerBoost = 1 - Math.pow(distanceFromCenter, 2);

                    const phase = (i / totalBars) * Math.PI * 2;
                    const t = now / 2100;
                    const restOsc = Math.sin(t + phase) * 0.03 + Math.cos(t * 0.9 + phase * 1.3) * 0.02;
                    const amplitude = (barMaxHeight * centerBoost + 10) * (0.13 + restOsc);

                    ctx.save();
                    ctx.shadowColor = '#00cfff';
                    ctx.shadowBlur = isMobile ? 5 : 10;

                    const barGrad = ctx.createLinearGradient(
                        0, centerY - amplitude,
                        0, centerY + amplitude
                    );
                    barGrad.addColorStop(0, '#00ffff');
                    barGrad.addColorStop(0.5, '#0066ff');
                    barGrad.addColorStop(1, '#001a33');
                    ctx.fillStyle = barGrad;

                    let xRight = centerX + i * (barWidth + barSpacing);
                    ctx.fillRect(xRight, centerY - amplitude, barWidth, amplitude * 2);

                    let xLeft = centerX - (i + 1) * (barWidth + barSpacing);
                    ctx.fillRect(xLeft, centerY - amplitude, barWidth, amplitude * 2);

                    ctx.restore();
                }
                ctx.restore();

                if (elapsed < duration) {
                    requestAnimationFrame(frame);
                } else {
                    if (typeof onComplete === 'function') onComplete();
                }
            }
            requestAnimationFrame(frame);
        }

        // Initialize play button overlay instead of canvas-based button
        initializePlayButtonOverlay();

        // --- AUDIO CONTEXT SETUP ---
        function setupAudioContext() {
            debug("Setting up audio context");
            try {
                if (!audioCtx && audio) {
                    audioCtx = new (window.AudioContext || window.webkitAudioContext)();
                    analyser = audioCtx.createAnalyser();
                    analyser.fftSize = isMobile ? 128 : 256; // Smaller FFT size for mobile performance
                    bufferLength = analyser.frequencyBinCount;
                    dataArray = new Uint8Array(bufferLength);

                    source = audioCtx.createMediaElementSource(audio);
                    source.connect(analyser);
                    analyser.connect(audioCtx.destination);

                    debug("Audio context setup complete", {                        bufferLength: bufferLength,
                        fftSize: analyser.fftSize
                    });
                }
            } catch (e) {
                console.error("Audio context setup failed:", e);
                showChatInterface();
            }
        }

        // --- AUDIO VISUALIZATION FUNCTION FOR TTS ---
        function attachAudioVisualization(audioElement) {
            if (!audioElement) {
                debug("Cannot attach audio visualization: missing audio element");
                return;
            }

            try {
                // Reuse existing audio context and canvas instead of creating new ones
                if (!audioCtx) {
                    setupAudioContext();
                }
                
                if (!audioCtx) {
                    debug("Cannot attach TTS visualization: AudioContext not available");
                    return;
                }

                // Create a new source for the TTS audio element
                const ttsSource = audioCtx.createMediaElementSource(audioElement);
                ttsSource.connect(analyser);
                analyser.connect(audioCtx.destination);

                // Use the existing soundbar canvas, not a separate audio-visualizer canvas
                if (!canvas) {
                    debug("Cannot attach TTS visualization: soundbar canvas not available");
                    return;
                }

                // Start visualization when TTS audio plays
                audioElement.addEventListener('play', async function() {
                    debug("Starting TTS audio visualization using shared canvas");
                    
                    // Resume audio context if needed
                    if (audioCtx.state === 'suspended') {
                        try {
                            await audioCtx.resume();
                            debug("AudioContext resumed for TTS visualization");
                        } catch (e) {
                            debug("Failed to resume AudioContext:", e);
                        }
                    }
                    
                    // Activate the shared visualization system
                    audioVisualizationActive = true;
                    
                    // Ensure canvas is visible
                    canvas.style.display = 'block';
                    canvas.style.visibility = 'visible';
                    
                    // Start the shared drawing loop
                    drawSoundbar();
                    
                    debug("TTS visualization activated using shared canvas");
                });

                // Handle TTS audio end
                audioElement.addEventListener('ended', function() {
                    debug("TTS audio ended");
                    // Only deactivate if no other audio is playing
                    const greetingAudioPlaying = audio && !audio.paused && !audio.ended;
                    if (!greetingAudioPlaying) {
                        audioVisualizationActive = false;
                        debug("TTS audio visualization deactivated - no other audio playing");
                    } else {
                        debug("TTS audio ended but greeting audio still playing - keeping visualization active");
                    }
                });

                // Handle TTS audio pause
                audioElement.addEventListener('pause', function() {
                    debug("TTS audio paused");
                    // Only deactivate if no other audio is playing
                    const greetingAudioPlaying = audio && !audio.paused && !audio.ended;
                    if (!greetingAudioPlaying) {
                        audioVisualizationActive = false;
                        debug("TTS audio visualization deactivated - no other audio playing");
                    } else {
                        debug("TTS audio paused but greeting audio still playing - keeping visualization active");
                    }
                });

                debug("TTS audio visualization attached to shared canvas system");
            } catch (e) {
                console.error("Failed to attach TTS audio visualization:", e);
                debug("TTS audio visualization error details:", e.message);
            }
        }

        function drawSoundbar() {
            if (!audioVisualizationActive || !ctx || !canvas) return;

            try {
                analyser.getByteFrequencyData(dataArray);

                ctx.clearRect(0, 0, canvas.width, canvas.height);
                ctx.fillStyle = getCanvasBackgroundColor();
                ctx.fillRect(0, 0, canvas.width, canvas.height);

                drawProgressBar();

                // Use the current theme's render function
                const theme = visualizerThemes[currentVisualizerTheme] || visualizerThemes.default;
                theme.renderFunction();

                // Continue animation if ANY audio is playing (greeting audio OR TTS audio)
                const greetingAudioPlaying = audio && !audio.paused && !audio.ended;
                const ttsAudioPlaying = currentTTSAudio && !currentTTSAudio.paused && !currentTTSAudio.ended;
                
                if (greetingAudioPlaying || ttsAudioPlaying) {
                    requestAnimationFrame(drawSoundbar);
                } else if (audio && audio.ended) {
                    debug("Audio ended, should show chat");
                    showChatInterface();
                }
            } catch (e) {
                console.error("Error drawing soundbar:", e);
                if (audio && audio.ended) {
                    showChatInterface();
                }
            }
        }

        // Default Theme Rendering (original implementation)
        function drawSoundbarDefault() {
            const totalBars = Math.floor(bufferLength / 2);
            const settings = { ...visualizerThemes.default.settings, ...visualizerSettings };
            const barWidth = isMobile ? 1.5 : settings.barWidth;
            const barSpacing = isMobile ? 1.5 : settings.barSpacing;
            const centerY = canvas.height / 2;
            const barMaxHeight = isMobile ? 80 : isTablet ? 120 : 150;
            const centerX = canvas.width / 2;

            for (let i = 0; i < totalBars; i++) {
                const distanceFromCenter = i / totalBars;
                const centerBoost = 1 - Math.pow(distanceFromCenter, 2);
                const amplitude = (dataArray[i] / 255) * barMaxHeight * centerBoost + 10;

                ctx.save();
                ctx.shadowColor = settings.primaryColor;
                ctx.shadowBlur = isMobile ? 5 : settings.glowIntensity;

                const barGrad = ctx.createLinearGradient(
                    0, centerY - amplitude,
                    0, centerY + amplitude
                );
                barGrad.addColorStop(0, settings.primaryColor);
                barGrad.addColorStop(0.5, settings.secondaryColor);
                barGrad.addColorStop(1, settings.accentColor);
                ctx.fillStyle = barGrad;

                let xRight = centerX + i * (barWidth + barSpacing);
                ctx.fillRect(xRight, centerY - amplitude, barWidth, amplitude * 2);

                let xLeft = centerX - (i + 1) * (barWidth + barSpacing);
                ctx.fillRect(xLeft, centerY - amplitude, barWidth, amplitude * 2);

                ctx.restore();
            }
        }

        // Minimal Theme Rendering
        function drawSoundbarMinimal() {
            const totalBars = Math.floor(bufferLength / 4); // Fewer bars for minimal look
            const settings = { ...visualizerThemes.minimal.settings, ...visualizerSettings };
            const barWidth = settings.barWidth;
            const barSpacing = settings.barSpacing;
            const centerY = canvas.height / 2;
            const barMaxHeight = isMobile ? 40 : isTablet ? 60 : 80; // Lower height for minimal
            const centerX = canvas.width / 2;

            for (let i = 0; i < totalBars; i++) {
                const amplitude = (dataArray[i * 2] / 255) * barMaxHeight + 5; // Less aggressive scaling

                ctx.save();
                ctx.globalAlpha = 0.8; // Subtle transparency
                ctx.shadowColor = settings.primaryColor;
                ctx.shadowBlur = settings.glowIntensity;

                // Simple solid color with subtle gradient
                const barGrad = ctx.createLinearGradient(
                    0, centerY - amplitude,
                    0, centerY + amplitude
                );
                barGrad.addColorStop(0, settings.primaryColor);
                barGrad.addColorStop(1, settings.secondaryColor);
                ctx.fillStyle = barGrad;

                let xRight = centerX + i * (barWidth + barSpacing);
                ctx.fillRect(xRight, centerY - amplitude, barWidth, amplitude * 2);

                let xLeft = centerX - (i + 1) * (barWidth + barSpacing);
                ctx.fillRect(xLeft, centerY - amplitude, barWidth, amplitude * 2);

                ctx.restore();
            }
        }

        // Futuristic Theme Rendering
        function drawSoundbarFuturistic() {
            const totalBars = Math.floor(bufferLength / 1.5); // More bars for complex look
            const settings = { ...visualizerThemes.futuristic.settings, ...visualizerSettings };
            const barWidth = settings.barWidth;
            const barSpacing = settings.barSpacing;
            const centerY = canvas.height / 2;
            const barMaxHeight = isMobile ? 100 : isTablet ? 140 : 180; // Taller for dramatic effect
            const centerX = canvas.width / 2;

            // Add motion trails effect by not completely clearing the canvas
            ctx.save();
            ctx.globalAlpha = 0.1;
            ctx.fillStyle = getCanvasBackgroundColor();
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            ctx.restore();

            for (let i = 0; i < totalBars; i++) {
                const distanceFromCenter = i / totalBars;
                const centerBoost = 1 - Math.pow(distanceFromCenter, 3); // More dramatic center boost
                const amplitude = (dataArray[i] / 255) * barMaxHeight * centerBoost + 15;

                // Pulse animation effect
                const pulseTime = Date.now() * 0.005;
                const pulseEffect = 1 + Math.sin(pulseTime + i * 0.1) * 0.2;
                const finalAmplitude = amplitude * pulseEffect;

                ctx.save();
                ctx.shadowColor = settings.primaryColor;
                ctx.shadowBlur = settings.glowIntensity + Math.sin(pulseTime) * 5;

                // Vibrant gradient with multiple stops
                const barGrad = ctx.createLinearGradient(
                    0, centerY - finalAmplitude,
                    0, centerY + finalAmplitude
                );
                barGrad.addColorStop(0, settings.primaryColor);
                barGrad.addColorStop(0.3, settings.secondaryColor);
                barGrad.addColorStop(0.7, settings.accentColor);
                barGrad.addColorStop(1, settings.primaryColor);
                ctx.fillStyle = barGrad;

                // Add neon outline effect
                ctx.strokeStyle = settings.secondaryColor;
                ctx.lineWidth = 1;

                let xRight = centerX + i * (barWidth + barSpacing);
                ctx.fillRect(xRight, centerY - finalAmplitude, barWidth, finalAmplitude * 2);
                ctx.strokeRect(xRight, centerY - finalAmplitude, barWidth, finalAmplitude * 2);

                let xLeft = centerX - (i + 1) * (barWidth + barSpacing);
                ctx.fillRect(xLeft, centerY - finalAmplitude, barWidth, finalAmplitude * 2);
                ctx.strokeRect(xLeft, centerY - finalAmplitude, barWidth, finalAmplitude * 2);

                ctx.restore();
            }
        }

        // Expressive Smiley Theme Rendering
        function drawSoundbarSmiley() {
            const settings = { ...visualizerThemes.smiley.settings, ...visualizerSettings };
            const centerX = canvas.width / 2;
            const centerY = canvas.height / 2;
            
            // Calculate audio intensity for facial expressions
            let totalIntensity = 0;
            for (let i = 0; i < dataArray.length; i++) {
                totalIntensity += dataArray[i];
            }
            const avgIntensity = totalIntensity / dataArray.length;
            const normalizedIntensity = avgIntensity / 255; // 0 to 1
            
            // Adaptive sizing based on canvas dimensions
            const baseSize = Math.min(canvas.width, canvas.height) * 0.3;
            const faceSize = baseSize + (normalizedIntensity * baseSize * 0.3); // Face grows with audio
            
            // Time-based animation
            const time = Date.now() * 0.005;
            
            // Dynamic colors based on audio intensity
            const faceColor = settings.primaryColor;
            const eyeColor = settings.accentColor;
            const mouthColor = settings.secondaryColor;
            
            ctx.save();
            
            // Face glow effect
            ctx.shadowColor = faceColor;
            ctx.shadowBlur = settings.glowIntensity + (normalizedIntensity * 10);
            
            // Draw face (circle)
            ctx.beginPath();
            ctx.arc(centerX, centerY, faceSize / 2, 0, Math.PI * 2);
            ctx.fillStyle = faceColor;
            ctx.fill();
            
            // Remove shadow for other elements
            ctx.shadowBlur = 0;
            
            // Eyes - size and position react to audio
            const eyeSize = (faceSize / 15) + (normalizedIntensity * 8);
            const eyeOffsetX = faceSize / 6;
            const eyeOffsetY = faceSize / 8;
            
            // Left eye
            ctx.beginPath();
            ctx.arc(centerX - eyeOffsetX, centerY - eyeOffsetY, eyeSize, 0, Math.PI * 2);
            ctx.fillStyle = eyeColor;
            ctx.fill();
            
            // Right eye
            ctx.beginPath();
            ctx.arc(centerX + eyeOffsetX, centerY - eyeOffsetY, eyeSize, 0, Math.PI * 2);
            ctx.fillStyle = eyeColor;
            ctx.fill();
            
            // Eye pupils that follow audio intensity
            const pupilSize = eyeSize * 0.6;
            const pupilOffset = normalizedIntensity * 3;
            
            ctx.fillStyle = '#000000';
            // Left pupil
            ctx.beginPath();
            ctx.arc(centerX - eyeOffsetX + pupilOffset, centerY - eyeOffsetY, pupilSize, 0, Math.PI * 2);
            ctx.fill();
            
            // Right pupil
            ctx.beginPath();
            ctx.arc(centerX + eyeOffsetX - pupilOffset, centerY - eyeOffsetY, pupilSize, 0, Math.PI * 2);
            ctx.fill();
            
            // Mouth - changes expression based on audio intensity
            const mouthWidth = faceSize / 4;
            const mouthHeight = normalizedIntensity * (faceSize / 8) + 5;
            const mouthY = centerY + faceSize / 6;
            
            ctx.strokeStyle = mouthColor;
            ctx.lineWidth = 4;
            ctx.lineCap = 'round';
            
            // Animated mouth based on audio
            if (normalizedIntensity > 0.7) {
                // Big smile for high intensity
                ctx.beginPath();
                ctx.arc(centerX, mouthY - mouthHeight / 2, mouthWidth, 0, Math.PI);
                ctx.stroke();
            } else if (normalizedIntensity > 0.4) {
                // Medium smile
                ctx.beginPath();
                ctx.arc(centerX, mouthY, mouthWidth * 0.8, 0, Math.PI);
                ctx.stroke();
            } else if (normalizedIntensity > 0.1) {
                // Slight smile
                ctx.beginPath();
                ctx.arc(centerX, mouthY + mouthHeight / 4, mouthWidth * 0.6, 0, Math.PI);
                ctx.stroke();
            } else {
                // Neutral/small mouth
                ctx.beginPath();
                ctx.ellipse(centerX, mouthY, mouthWidth * 0.3, mouthHeight / 2, 0, 0, Math.PI * 2);
                ctx.stroke();
            }
            
            // Audio frequency visualization around the face
            const numRays = 32;
            const innerRadius = faceSize / 2 + 20;
            const maxRayLength = faceSize / 3;
            
            for (let i = 0; i < numRays; i++) {
                const angle = (i / numRays) * Math.PI * 2;
                const dataIndex = Math.floor((i / numRays) * dataArray.length);
                const intensity = dataArray[dataIndex] / 255;
                const rayLength = intensity * maxRayLength;
                
                const startX = centerX + Math.cos(angle) * innerRadius;
                const startY = centerY + Math.sin(angle) * innerRadius;
                const endX = centerX + Math.cos(angle) * (innerRadius + rayLength);
                const endY = centerY + Math.sin(angle) * (innerRadius + rayLength);
                
                // Color rays based on intensity
                const rayAlpha = 0.3 + (intensity * 0.7);
                ctx.strokeStyle = `rgba(${hexToRgb(mouthColor).r}, ${hexToRgb(mouthColor).g}, ${hexToRgb(mouthColor).b}, ${rayAlpha})`;
                ctx.lineWidth = 2;
                
                ctx.beginPath();
                ctx.moveTo(startX, startY);
                ctx.lineTo(endX, endY);
                ctx.stroke();
            }
            
            ctx.restore();
        }
        
        // Helper function to convert hex to RGB
        function hexToRgb(hex) {
            const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
            return result ? {
                r: parseInt(result[1], 16),
                g: parseInt(result[2], 16),
                b: parseInt(result[3], 16)
            } : {r: 255, g: 255, b: 0}; // fallback to yellow
        }

        function showChatInterface() {
            debug("Showing chat interface");
            audioVisualizationActive = false;
            
            if (chatInterface) {
                chatInterface.style.display = 'block';
                
                // Only show welcome message once, even if showChatInterface is called multiple times
                if (!chatInterfaceInitialized) {
                    debug("Initializing chat interface for the first time");
                    chatInterfaceInitialized = true;
                    
                    // Show localized welcome message
                    const welcomeMessage = getLocalizedWelcomeMessage();
                    addMessageToChat(welcomeMessage, false);
                    
                    debug("Welcome message added:", welcomeMessage);
                } else {
                    debug("Chat interface already initialized, skipping welcome message");
                }
                
                // Setup voice controls after chat interface is shown
                if (voiceEnabled) {
                    setupVoiceControls();
                }
                
                // Focus the input field on non-mobile devices for better UX
                if (!isMobile && userInput) {
                    setTimeout(() => {
                        userInput.focus();
                    }, 500);
                }
            }
        }

        // --- AUDIO EVENT LISTENERS ---
        if (audio) {
            audio.addEventListener('play', () => {
                debug("Audio play event triggered");
                if (!audioCtx) setupAudioContext();
            });

            audio.addEventListener('ended', () => {
                debug("Audio ended event triggered");
                
                // Only deactivate if no TTS audio is playing
                const ttsAudioPlaying = currentTTSAudio && !currentTTSAudio.paused && !currentTTSAudio.ended;
                if (!ttsAudioPlaying) {
                    audioVisualizationActive = false;
                    debug("Greeting audio visualization deactivated - no TTS audio playing");
                } else {
                    debug("Greeting audio ended but TTS audio still playing - keeping visualization active");
                }
                
                // Hide the play button overlay permanently after audio ends
                hidePlayButtonOverlay();

                // HIDE the Pause/Resume button after audio finishes
                if (pauseBtnContainer) {
                    pauseBtnContainer.style.opacity = 0;
                    pauseBtnContainer.style.pointerEvents = "none";
                }

                showChatInterface();
            });
        }

        if (pauseBtn) {
            pauseBtn.addEventListener('click', () => {
                if (!audio) return;

                if (!audio.paused) {
                    audio.pause();
                    pauseBtn.textContent = "Resume Audio";
                    
                    // Only deactivate if no TTS audio is playing
                    const ttsAudioPlaying = currentTTSAudio && !currentTTSAudio.paused && !currentTTSAudio.ended;
                    if (!ttsAudioPlaying) {
                        audioVisualizationActive = false;
                        debug("Greeting audio paused - no TTS audio playing, deactivating visualization");
                    } else {
                        debug("Greeting audio paused but TTS audio still playing - keeping visualization active");
                    }
                } else {
                    audio.play().then(() => {
                        pauseBtn.textContent = "Pause Audio";
                        audioVisualizationActive = true;
                        drawSoundbar();
                        debug("Greeting audio resumed, reactivating visualization");
                    }).catch(err => {
                        console.error("Resume audio error:", err);
                        showChatInterface();
                    });
                }
            });
        }

        if (skipBtn) {
            skipBtn.addEventListener('click', () => {
                debug("Skip button clicked");
                
                // Stop the current audio
                if (audio) {
                    audio.pause();
                    audio.currentTime = audio.duration || 0; // Skip to end
                }
                
                // Stop audio visualization
                audioVisualizationActive = false;
                
                // Hide the play button overlay
                hidePlayButtonOverlay();

                // Hide the pause/skip button container
                if (pauseBtnContainer) {
                    pauseBtnContainer.style.opacity = 0;
                    pauseBtnContainer.style.pointerEvents = "none";
                }

                // Show chat interface immediately
                showChatInterface();
                
                debug("Audio skipped, chat interface shown");
            });
        }

        // --- ENHANCED CHAT FUNCTIONS WITH VOICE INTEGRATION ---
        function addMessageToChat(text, isUser, enableTTS = true) {
            if (!chatHistory) {
                debug("Chat history element not found");
                return;
            }

            const messageDiv = document.createElement('div');
            messageDiv.classList.add('message');
            messageDiv.classList.add(isUser ? 'user-message' : 'ai-message');
            messageDiv.textContent = text;
            
            // Add TTS button to AI messages if voice is enabled
            if (!isUser && voiceEnabled && enableTTS) {
                addTTSButtonToMessage(messageDiv);
            }
            
            chatHistory.appendChild(messageDiv);
            chatHistory.scrollTop = chatHistory.scrollHeight;
            
            // Auto-play TTS for AI responses if enabled
            if (!isUser && ttsEnabled && voiceEnabled) {
                setTimeout(() => {
                    playTTS(text);
                }, 500); // Small delay to ensure message is rendered
            }
        }

        async function sendToLocalAPI(message) {
            return await sendToLocalAPIWithRetry(message, 0);
        }

        async function sendToLocalAPIWithRetry(message, retryCount = 0) {
            const maxRetries = 3;
            const retryDelay = Math.min(1000 * Math.pow(2, retryCount), 5000); // Exponential backoff, max 5 seconds
            
            // Set loading state
            setUILoadingState(true);
            
            // Start message update timer
            const messageUpdateInterval = setInterval(updateTypingMessage, 3000);

            try {
                debug("Sending message to local API:", message, "Attempt:", retryCount + 1);
                debug("Using nonce:", widgetData.nonce);
                debug("Using AJAX URL:", widgetData.ajaxurl);

                // Use the appropriate system prompt based on detected language
                const systemPrompt = getSystemPrompt();
                debug("Using system prompt for language:", detectedLanguage);

                const formData = new FormData();
                formData.append('action', 'ai_interview_chat');
                formData.append('message', message);
                formData.append('system_prompt', systemPrompt);
                formData.append('nonce', widgetData.nonce);

                const response = await fetch(widgetData.ajaxurl, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });

                debug("API response status:", response.status);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }

                const responseText = await response.text();
                debug("Raw API response:", responseText);
                
                let data;
                try {
                    data = JSON.parse(responseText);
                    debug("Parsed API response:", data);
                } catch (e) {
                    console.error("JSON parse error:", e);
                    debug("Response was not valid JSON:", responseText);
                    throw new Error("Invalid JSON response");
                }

                let replyText = "";
                
                // Enhanced debugging for response format
                debug("Response analysis:", {
                    hasSuccess: 'success' in data,
                    successValue: data.success,
                    hasData: 'data' in data,
                    hasReply: 'reply' in data,
                    dataKeys: Object.keys(data)
                });
                
                if (data.success && data.data && data.data.reply) {
                    replyText = data.data.reply;
                    debug("Using format: success.data.reply");
                } else if (data.success && data.reply) {
                    replyText = data.reply;
                    debug("Using format: success.reply");
                } else if (data.reply) {
                    replyText = data.reply;
                    debug("Using format: direct reply");
                } else if (typeof data === 'string') {
                    replyText = data;
                    debug("Using format: string response");
                } else if (data.success === false && data.data && data.data.message) {
                    // Handle WordPress error format with enhanced error info
                    debug("API returned error:", data.data.message);
                    const error = new Error(`API Error: ${data.data.message}`);
                    error.errorType = data.data.error_type || 'unknown';
                    error.retryable = data.data.retryable || false;
                    throw error;
                } else if (data.success === false && data.message) {
                    // Handle direct error format with enhanced error info
                    debug("API returned error:", data.message);
                    const error = new Error(`API Error: ${data.message}`);
                    error.errorType = data.error_type || 'unknown';
                    error.retryable = data.retryable || false;
                    throw error;
                } else if (data.success === false) {
                    // Handle error with no specific message
                    debug("API returned error with no message");
                    const error = new Error("API Error: Unknown error occurred");
                    error.errorType = 'unknown';
                    error.retryable = true;
                    throw error;
                } else {
                    debug("Unrecognized response format:", data);
                    throw new Error("Unrecognized response format");
                }
                
                if (replyText) {
                    // Show completion message briefly before displaying response
                    const completionMessages = {
                        'en': 'Response ready!',
                        'de': 'Antwort bereit!'
                    };
                    
                    const language = detectedLanguage || 'en';
                    const messageElement = document.querySelector('.processing-text');
                    if (messageElement) {
                        messageElement.textContent = completionMessages[language];
                    }
                    
                    // Brief delay before showing the actual response
                    await new Promise(resolve => setTimeout(resolve, 800));
                    addMessageToChat(replyText, false);
                } else {
                    throw new Error("Empty reply received");
                }
            } catch (error) {
                console.error('API Error:', error);
                
                // Check if this is a retryable error and we haven't exceeded max retries
                const shouldRetry = error.retryable && retryCount < maxRetries;
                
                if (shouldRetry) {
                    debug(`Retrying API call due to ${error.errorType} error. Attempt ${retryCount + 1}/${maxRetries + 1} in ${retryDelay}ms`);
                    
                    // Show retry message
                    const retryMessages = {
                        'en': `Connection issue, retrying... (${retryCount + 1}/${maxRetries})`,
                        'de': `Verbindungsproblem, versuche erneut... (${retryCount + 1}/${maxRetries})`
                    };
                    
                    const language = detectedLanguage || 'en';
                    const messageElement = document.querySelector('.processing-text');
                    if (messageElement) {
                        messageElement.textContent = retryMessages[language];
                    }
                    
                    // Clear the current timer
                    clearInterval(messageUpdateInterval);
                    
                    // Wait before retrying
                    await new Promise(resolve => setTimeout(resolve, retryDelay));
                    
                    // Retry the request
                    return await sendToLocalAPIWithRetry(message, retryCount + 1);
                } else {
                    // Show error message in spinner before switching to fallback
                    const errorMessages = {
                        'en': 'Connection issue, switching to backup...',
                        'de': 'Verbindungsproblem, wechsle zu Backup...'
                    };
                    
                    const language = detectedLanguage || 'en';
                    const messageElement = document.querySelector('.processing-text');
                    if (messageElement) {
                        messageElement.textContent = errorMessages[language];
                    }
                    
                    await new Promise(resolve => setTimeout(resolve, 1000));
                    simulateResponseForError();
                }
            } finally {
                // Clear the message update timer
                clearInterval(messageUpdateInterval);
                
                // Remove loading state
                setUILoadingState(false);
            }
        }

        /**
         * Enhanced error response system supporting all 20 languages
         * Falls back to English if no error messages available for detected language
         */
        function simulateResponseForError() {
            const language = detectedLanguage || 'en';
            
            // Error messages in multiple languages for better user experience
            const errorMessagesByLanguage = {
                'en': [
                    "I apologize for the technical difficulty. Digital glitches are just part of working at the intersection of AI and creative tech. What can I tell you about my digital art or automation workflows?",
                    "Looks like the AI took a coffee break. But I'm still here to chat about 3D visualization, creative coding, or how I use AI in my projects!",
                    "A small glitch in the digital matrix! If you want to know about my experience with AI, automation, or building custom creative tools, just ask.",
                    "Technical hiccups—nothing a little creative problem solving can't handle. Ask me anything about my work in digital media, 3D, or artificial intelligence."
                ],
                'de': [
                    "Entschuldigung für die technischen Schwierigkeiten. Digitale Störungen gehören einfach zur Arbeit an der Schnittstelle von KI und kreativer Technik. Was kann ich Ihnen über meine digitale Kunst oder Automatisierungs-Workflows erzählen?",
                    "Sieht so aus, als hätte die KI eine Kaffeepause eingelegt. Aber ich bin immer noch hier, um über 3D-Visualisierung, kreatives Coding oder wie ich KI in meinen Projekten einsetze zu sprechen!",
                    "Ein kleiner Fehler in der digitalen Matrix! Wenn Sie etwas über meine Erfahrung mit KI, Automatisierung oder dem Bau von benutzerdefinierten kreativen Tools wissen möchten, fragen Sie einfach.",
                    "Technische Probleme – nichts, was ein wenig kreative Problemlösung nicht lösen könnte. Fragen Sie mich alles über meine Arbeit in digitalen Medien, 3D oder künstlicher Intelligenz."
                ],
                'es': [
                    "Disculpas por las dificultades técnicas. Los fallos digitales son parte del trabajo en la intersección de la IA y la tecnología creativa. ¿Qué puedo contarte sobre mi arte digital o flujos de trabajo de automatización?",
                    "Parece que la IA se tomó un descanso para café. ¡Pero sigo aquí para hablar sobre visualización 3D, programación creativa o cómo uso IA en mis proyectos!",
                    "¡Un pequeño fallo en la matriz digital! Si quieres saber sobre mi experiencia con IA, automatización o construcción de herramientas creativas personalizadas, solo pregunta.",
                    "Contratiempos técnicos: nada que un poco de resolución creativa de problemas no pueda manejar. Pregúntame lo que sea sobre mi trabajo en medios digitales, 3D o inteligencia artificial."
                ],
                'fr': [
                    "Désolé pour les difficultés techniques. Les problèmes numériques font partie du travail à l'intersection de l'IA et de la technologie créative. Que puis-je vous dire sur mon art numérique ou mes workflows d'automatisation?",
                    "On dirait que l'IA a pris une pause café. Mais je suis toujours là pour parler de visualisation 3D, de codage créatif ou de comment j'utilise l'IA dans mes projets!",
                    "Un petit problème dans la matrice numérique! Si vous voulez en savoir plus sur mon expérience avec l'IA, l'automatisation ou la création d'outils créatifs personnalisés, demandez simplement.",
                    "Problèmes techniques - rien qu'un peu de résolution créative de problèmes ne peut pas gérer. Demandez-moi n'importe quoi sur mon travail dans les médias numériques, la 3D ou l'intelligence artificielle."
                ]
                // Additional languages can be added here as needed
            };

            // Get error messages for detected language, fallback to English
            const errorResponses = errorMessagesByLanguage[language] || errorMessagesByLanguage['en'];
            
            const randomResponse = errorResponses[Math.floor(Math.random() * errorResponses.length)];
            addMessageToChat(randomResponse, false, false); // Disable TTS for error messages
        }

        // --- CHAT EVENT LISTENERS WITH VOICE INTEGRATION ---
        if (sendButton) {
            sendButton.addEventListener('click', async () => {
                if (!userInput) return;

                const message = userInput.value.trim();
                if (message && !sendButton.disabled) {
                    addMessageToChat(message, true, false); // User messages don't need TTS
                    userInput.value = '';
                    await sendToLocalAPI(message);
                }
            });
        }

        if (userInput) {
            userInput.addEventListener('keypress', async (e) => {
                if (!sendButton) return;

                if (e.key === 'Enter' && !sendButton.disabled) {
                    const message = userInput.value.trim();
                    if (message) {
                        addMessageToChat(message, true, false); // User messages don't need TTS
                        userInput.value = '';
                        await sendToLocalAPI(message);
                    }
                }
            });
        }

        // Legacy pulse effect functions - now handled by CSS
        function applyPulseEffects() {
            // Pulse effects now handled by CSS classes on overlay button
            debug("Pulse effects now handled by CSS overlay");
        }
        
        function removePulseEffects() {
            // Pulse effects now handled by CSS classes on overlay button
            debug("Pulse effects now handled by CSS overlay");
        }
        
        function pausePulseEffects() {
            // Pulse effects now handled by CSS classes on overlay button
            debug("Pulse effects now handled by CSS overlay");
        }
        
        function resumePulseEffects() {
            // Pulse effects now handled by CSS classes on overlay button
            debug("Pulse effects now handled by CSS overlay");
        }
        
        function resetPlayButtonState() {
            showPlayButtonOverlay();
            debug("Play button state reset to initial overlay state");
        }
        
        function ensurePlayButtonPulse() {
            // Pulse effects now handled by CSS classes on overlay button
            debug("Pulse effects now handled by CSS overlay");
        }
        
        function initializePlayButtonPulse() {
            initializePlayButtonOverlay();
            debug("Play button pulse initialized via overlay");
        }

        // Enhanced initialization with overlay button
        debug("Initializing play button overlay");
        if (canvas) {
            // Initialize canvas shadow based on current CSS variables
            updateCanvasShadowFromIntensity();
            
            // Initialize play button overlay for this page load
            setTimeout(() => {
                initializePlayButtonOverlay();
                debug("Play button overlay initialized and ready");
            }, 100);
        }

        // Pre-load audio in background for better performance
        async function preloadAudio() {
            try {
                debug("Preloading audio with automatic detection...");
                const audioSources = await determineAudioSource();
                if (audioSources && (audioSources.primary || audioSources.alt)) {
                    await loadAudioSource(audioSources);
                    debug("Audio preloaded successfully");
                } else {
                    debug("No audio to preload");
                }
            } catch (error) {
                debug("Audio preload failed:", error);
                // Don't show error to user, they can still use chat
            }
        }

        // Start preloading audio with automatic detection
        preloadAudio();

        function updateProgressBarWhenIdle() {
            if (showPlayButton) {
                // Ensure play button overlay is visible when button should be visible
                updatePlayButtonDesign();
            }
        }
        
        // Ensure pulse effects are properly managed - this persists until click
        function ensurePlayButtonPulse() {
            if (showPlayButton && canvas && !fadingOut) {
                // Apply pulse effects when button should be pulsing
                // This ensures pulse continues until the play button is actually clicked
                applyPulseEffects();
            }
        }
        
        // Function to handle returning to play button state (page reload, etc.)
        function initializePlayButtonPulse() {
            if (canvas && showPlayButton) {
                // Reset to initial state with pulse enabled
                resetPlayButtonState();
                debug("Play button pulse initialized for new page load");
            }
        }

        if (audio) {
            audio.addEventListener('timeupdate', updateProgressBarWhenIdle);
            audio.addEventListener('seeked', updateProgressBarWhenIdle);
            audio.addEventListener('loadedmetadata', updateProgressBarWhenIdle);
        }

        // Window event listeners for proper pulse management across page lifecycle
        window.addEventListener('beforeunload', function() {
            // Clean up any animation states before page unload
            if (canvas) {
                canvas.style.animationPlayState = '';
            }
        });
        
        window.addEventListener('pageshow', function(event) {
            // Re-initialize pulse when page is shown (including back/forward navigation)
            if (event.persisted && canvas && showPlayButton) {
                setTimeout(() => {
                    initializePlayButtonPulse();
                    debug("Pulse re-initialized after page show event");
                }, 100);
            }
        });
        
        // Visibility API to handle tab switching - prevent unwanted pulse flashes
        document.addEventListener('visibilitychange', function() {
            // Only apply pulse effects if play button should actually be visible
            if (!document.hidden && canvas && showPlayButton && !fadingOut) {
                // Add a small delay and check state again to prevent flash
                setTimeout(() => {
                    // Double-check that we should still be pulsing
                    if (showPlayButton && !fadingOut && !document.hidden) {
                        const disablePulse = getCSSVariable('--play-button-disable-pulse', 'false').toLowerCase() === 'true';
                        const prefersReducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                        
                        if (!disablePulse && !prefersReducedMotion) {
                            applyPulseEffects();
                            debug("Play button pulse re-applied after tab switch (conditions verified)");
                        }
                    }
                }, 200); // Small delay to prevent flash
            }
        });

        // Hide voice controls if voice features are disabled
        const voiceControls = document.getElementById('voiceControls');
        if (voiceControls && !voiceEnabled) {
            voiceControls.style.display = 'none';
            debug("Voice controls hidden - voice features disabled");
        }

        // Initialize image error handlers to prevent 404 errors
        addImageErrorHandlers();
        
        debug("Widget initialization complete", {
            voiceEnabled: voiceEnabled,
            hasElevenLabsKey: hasElevenLabsKey,
            detectedLanguage: detectedLanguage,
            geolocationDisabled: widgetData.disable_geolocation,
            productionMode: widgetData.production_mode
        });

        // Debug functions for console testing
        window.aiWidgetDebug = {
            showChat: () => showChatInterface(),
            testTTS: (text = 'This is a test') => playTTS(text),
            testVoice: () => setupVoiceControls(),
            getWidgetData: () => widgetData,
            getLanguage: () => detectedLanguage,
            forceLanguage: (lang) => {
                localStorage.setItem('aiWidget_forceLang', lang);
                location.reload();
            },
            clearLanguage: () => {
                localStorage.removeItem('aiWidget_forceLang');
                location.reload();
            },
            refreshPulse: () => {
                // Remove and reapply pulse effects to refresh animation
                if (canvas) {
                    removePulseEffects();
                    setTimeout(() => {
                        initializePlayButtonPulse();
                    }, 100);
                }
            },
            togglePulse: (disable) => {
                // Allow manual pulse toggling for testing
                const root = document.documentElement;
                root.style.setProperty('--play-button-disable-pulse', disable ? 'true' : 'false');
                if (disable) {
                    removePulseEffects();
                } else {
                    initializePlayButtonPulse();
                }
            },
            setPulseSpeed: (speed) => {
                // Allow manual pulse speed setting for testing
                const root = document.documentElement;
                root.style.setProperty('--play-button-pulse-speed', speed.toString());
                // Refresh pulse to apply new speed
                window.aiWidgetDebug.refreshPulse();
            },
            resetPlayButton: () => {
                // Reset play button to initial pulsing state
                resetPlayButtonState();
            },
            pausePulse: () => {
                // Temporarily pause pulse
                pausePulseEffects();
            },
            resumePulse: () => {
                // Resume paused pulse
                resumePulseEffects();
            },
            setButtonSize: (size) => {
                // Allow manual button size setting for testing
                const root = document.documentElement;
                root.style.setProperty('--play-button-size', size.toString());
                // Update overlay to apply new size
                if (showPlayButton && !fadingOut) {
                    updatePlayButtonDesign();
                }
                debug("Button size set to:", size);
            },
            setShadowColor: (color) => {
                // Allow manual shadow color setting for testing
                const root = document.documentElement;
                root.style.setProperty('--aiw-shadow-color', color);
                updateCanvasShadowFromIntensity(); // Update shadow with new color
                debug("Shadow color set to:", color);
            },
            setShadowIntensity: (intensity) => {
                // Allow manual shadow intensity setting for testing and Customizer
                const root = document.documentElement;
                root.style.setProperty('--aiw-shadow-intensity', intensity.toString());
                updateCanvasShadowFromIntensity(); // Update shadow with new intensity
                debug("Shadow intensity set to:", intensity);
            },
            getButtonInfo: () => {
                // Get current button information for debugging
                return {
                    showPlayButton: showPlayButton,
                    fadingOut: fadingOut,
                    hasOverlay: !!playButtonOverlay,
                    hasButton: !!playButton,
                    overlayVisible: playButtonOverlay ? !playButtonOverlay.classList.contains('hidden') : false,
                    buttonDesign: playButton ? playButton.getAttribute('data-design') : 'NOT_SET',
                    cssVarBtnSize: getCSSVariable('--play-button-size', 'NOT_SET'),
                    cssVarShadowColor: getCSSVariable('--aiw-shadow-color', 'NOT_SET'),
                    cssVarShadowIntensity: getCSSVariable('--aiw-shadow-intensity', 'NOT_SET'),
                    cssVarPulseDisabled: getCSSVariable('--play-button-disable-pulse', 'NOT_SET')
                };
            }
        };
    }
});

// Global error handler with production-friendly logging
window.addEventListener('error', function(e) {
    if (e.filename && e.filename.includes('ai-interview-widget')) {
        // Check if we're in debug mode
        const isDebugMode = typeof window.aiWidgetData !== 'undefined' && window.aiWidgetData.debug;
        
        if (isDebugMode) {
            console.error('🚨 AI Interview Widget Error:', e.error);
            console.error('Error details:', {
                message: e.message,
                filename: e.filename,
                lineno: e.lineno,
                colno: e.colno,
                timestamp: new Date().toISOString()
            });
        } else {
            // In production, log minimal error information
            console.error('AI Widget: An error occurred. Enable debug mode for details.');
        }
        
        // Attempt graceful recovery for known issues
        if (e.message.includes('geolocation') || e.message.includes('fetch')) {
            // Geolocation or network errors - widget should continue working
            if (isDebugMode) {
                console.warn('AI Widget: Recovering from network/geolocation error');
            }
        }
    }
});

// Handle unhandled promise rejections
window.addEventListener('unhandledrejection', function(e) {
    if (e.reason && e.reason.message && e.reason.message.includes('ai-interview-widget')) {
        const isDebugMode = typeof window.aiWidgetData !== 'undefined' && window.aiWidgetData.debug;
        
        if (isDebugMode) {
            console.error('🚨 AI Interview Widget Promise Rejection:', e.reason);
        } else {
            console.error('AI Widget: Promise rejection occurred.');
        }
        
        // Prevent unhandled rejection from breaking the page
        e.preventDefault();
    }
});

// Export debug functions for console testing
if (typeof window !== 'undefined' && (window.location.hostname === 'localhost' || window.location.hostname.includes('rorich'))) {
    window.aiWidgetDebugExport = {
        forceLanguage: function(lang) {
            localStorage.setItem('aiWidget_forceLang', lang);
            location.reload();
        },
        clearForcedLanguage: function() {
            localStorage.removeItem('aiWidget_forceLang');
            location.reload();
        },
        getDetectedLanguage: function() {
            return window.aiWidgetDetectedLanguage || 'Not detected yet';
        },
        testTTS: function(text = 'This is a test of the text-to-speech system.') {
            if (window.speechSynthesis) {
                const utterance = new SpeechSynthesisUtterance(text);
                window.speechSynthesis.speak(utterance);
            }
        },
        showChat: function() {
            const chatInterface = document.getElementById('chatInterface');
            if (chatInterface) {
                chatInterface.style.display = 'block';
            }
        },
        getWidgetData: function() {
            return window.aiWidgetData;
        }
    };
    console.log('🛠️ Debug functions available in aiWidgetDebugExport:', Object.keys(window.aiWidgetDebugExport));
}

console.log('🎉 AI Interview Widget v1.9.4 Enhanced Language Detection fully loaded at', new Date().toISOString());
console.log('🌍 Enhanced multi-language detection with comprehensive IP-based country mapping');
console.log('🗣️ Support for 20 languages with automatic fallback to English');
console.log('🎤 Voice Features: TTS/STT with ElevenLabs and browser fallback - FIXED');
console.log('📱 Responsive design for mobile, tablet, and desktop');
console.log('👤 Initialized for user: EricRorich');
console.log('🕐 Current Time:', new Date().toISOString());
console.log('✅ Enhanced language detection system active with robust fallback logic!');
