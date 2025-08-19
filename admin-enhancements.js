/**
 * AI Interview Widget - Admin Enhancements JavaScript
 * 
 * Provides enhanced admin UI functionality including:
 * - Dynamic model loading via AJAX
 * - Model capability tooltips
 * - Deprecation warnings and migration suggestions
 * - Responsive provider/model interactions
 * 
 * @since 1.9.6
 * @author Eric Rorich
 */

(function($) {
    'use strict';

    // Configuration and state
    const adminConfig = {
        currentProvider: null,
        currentModel: null,
        isLoading: false,
        cache: {}
    };

    /**
     * Initialize admin enhancements
     */
    function initializeAdminEnhancements() {
        // Override the existing updateModelOptions function
        if (typeof window.updateModelOptions === 'function') {
            window.updateModelOptions = updateModelOptionsEnhanced;
        }

        // Setup enhanced provider change handler
        setupProviderChangeHandler();

        // Initialize tooltips
        initializeTooltips();

        // Load initial model data
        const providerSelect = document.getElementById('api_provider');
        if (providerSelect && providerSelect.value) {
            updateModelOptionsEnhanced(providerSelect.value);
        }
    }

    /**
     * Enhanced model options updater using AJAX
     */
    function updateModelOptionsEnhanced(provider) {
        const modelSelect = document.getElementById('llm_model');
        if (!modelSelect || adminConfig.isLoading) {
            return;
        }

        // Store current provider
        adminConfig.currentProvider = provider;
        adminConfig.currentModel = modelSelect.value;

        // Show loading state
        showLoadingState(modelSelect);

        // Check cache first
        if (adminConfig.cache[provider]) {
            populateModelSelect(adminConfig.cache[provider], modelSelect);
            return;
        }

        // Make AJAX request for models
        $.ajax({
            url: aiwAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'ai_interview_get_models',
                provider: provider,
                nonce: aiwAdmin.nonce
            },
            timeout: 10000,
            beforeSend: function() {
                adminConfig.isLoading = true;
            },
            success: function(response) {
                if (response.success && response.data.models) {
                    // Cache the response
                    adminConfig.cache[provider] = response.data.models;
                    
                    // Populate the select
                    populateModelSelect(response.data.models, modelSelect);
                } else {
                    showErrorState(modelSelect, response.data || aiwAdmin.strings.error);
                }
            },
            error: function(xhr, status, error) {
                console.error('AI Interview Widget: Error loading models:', error);
                showErrorState(modelSelect, aiwAdmin.strings.error);
                
                // Fallback to hardcoded models if AJAX fails
                fallbackToStaticModels(provider, modelSelect);
            },
            complete: function() {
                adminConfig.isLoading = false;
            }
        });
    }

    /**
     * Show loading state in model select
     */
    function showLoadingState(modelSelect) {
        modelSelect.innerHTML = '<option value="">' + aiwAdmin.strings.loading + '</option>';
        modelSelect.disabled = true;
    }

    /**
     * Show error state in model select
     */
    function showErrorState(modelSelect, errorMessage) {
        modelSelect.innerHTML = '<option value="">Error: ' + errorMessage + '</option>';
        modelSelect.disabled = false;
    }

    /**
     * Populate model select with enhanced options
     */
    function populateModelSelect(models, modelSelect) {
        // Clear existing options
        modelSelect.innerHTML = '';
        modelSelect.disabled = false;

        // Add models with enhanced display
        models.forEach(function(model) {
            const option = document.createElement('option');
            option.value = model.value;
            option.textContent = model.label;
            
            // Add data attributes for enhanced functionality
            if (model.description) {
                option.setAttribute('data-description', model.description);
            }
            if (model.capabilities) {
                option.setAttribute('data-capabilities', model.capabilities.join(', '));
            }
            if (model.deprecated) {
                option.setAttribute('data-deprecated', 'true');
                option.style.color = '#d63384';
            }
            if (model.recommended) {
                option.setAttribute('data-recommended', 'true');
            }
            if (model.experimental) {
                option.setAttribute('data-experimental', 'true');
                option.style.fontStyle = 'italic';
            }

            modelSelect.appendChild(option);
        });

        // Restore previous selection if valid
        if (adminConfig.currentModel && window.currentSavedModel) {
            const validOptions = Array.from(modelSelect.options).map(opt => opt.value);
            if (validOptions.includes(window.currentSavedModel)) {
                modelSelect.value = window.currentSavedModel;
            } else if (validOptions.includes(adminConfig.currentModel)) {
                modelSelect.value = adminConfig.currentModel;
            }
        }

        // Setup model change handler for tooltips
        setupModelChangeHandler(modelSelect);

        // Show initial model info
        showModelInfo(modelSelect);
    }

    /**
     * Setup enhanced provider change handler
     */
    function setupProviderChangeHandler() {
        const providerSelect = document.getElementById('api_provider');
        if (!providerSelect) return;

        // Remove existing onchange handler and add our enhanced one
        providerSelect.onchange = function() {
            // Call original API fields toggle if it exists
            if (typeof toggleApiFields === 'function') {
                toggleApiFields(this.value);
            }
            
            // Call our enhanced model updater
            updateModelOptionsEnhanced(this.value);
        };
    }

    /**
     * Setup model change handler for tooltips and info display
     */
    function setupModelChangeHandler(modelSelect) {
        modelSelect.onchange = function() {
            showModelInfo(this);
        };
    }

    /**
     * Show model information and warnings
     */
    function showModelInfo(modelSelect) {
        const selectedOption = modelSelect.options[modelSelect.selectedIndex];
        if (!selectedOption) return;

        // Remove existing info displays
        const existingInfo = modelSelect.parentNode.querySelector('.model-info');
        if (existingInfo) {
            existingInfo.remove();
        }

        // Create info container with responsive design
        const infoContainer = document.createElement('div');
        infoContainer.className = 'model-info';
        infoContainer.style.cssText = `
            margin-top: 8px;
            padding: 0;
            line-height: 1.4;
            word-wrap: break-word;
        `;

        let infoHtml = '';

        // Add description with responsive styling
        const description = selectedOption.getAttribute('data-description');
        if (description) {
            infoHtml += `
                <p class="description" style="
                    margin: 5px 0; 
                    color: #666; 
                    font-size: 13px;
                    line-height: 1.4;
                    word-wrap: break-word;
                ">
                    <strong>Description:</strong> ${description}
                </p>
            `;
        }

        // Add capabilities with responsive badges
        const capabilities = selectedOption.getAttribute('data-capabilities');
        if (capabilities) {
            const capabilityList = capabilities.split(', ').map(cap => 
                `<span style="
                    display: inline-block;
                    background: #f0f6fc;
                    color: #0969da;
                    padding: 2px 6px;
                    margin: 2px 4px 2px 0;
                    border-radius: 3px;
                    font-size: 11px;
                    border: 1px solid #d1d9e0;
                ">${cap}</span>`
            ).join('');
            
            infoHtml += `
                <div style="margin: 8px 0; font-size: 13px;">
                    <strong style="color: #666;">Capabilities:</strong><br>
                    <div style="margin-top: 4px; line-height: 1.6;">
                        ${capabilityList}
                    </div>
                </div>
            `;
        }

        // Add warnings and recommendations with responsive styling
        if (selectedOption.getAttribute('data-deprecated') === 'true') {
            infoHtml += `
                <div class="notice notice-warning inline" style="
                    margin: 8px 0; 
                    padding: 8px 12px;
                    border-left: 4px solid #d63384;
                    background: #fef7f7;
                    border-radius: 0 4px 4px 0;
                ">
                    <p style="margin: 0; color: #d63384; font-size: 13px;">
                        <strong>⚠️ Deprecated:</strong> ${aiwAdmin.strings.deprecated}
                    </p>
                </div>
            `;
        }

        if (selectedOption.getAttribute('data-recommended') === 'true') {
            infoHtml += `
                <div class="notice notice-success inline" style="
                    margin: 8px 0; 
                    padding: 8px 12px;
                    border-left: 4px solid #00a32a;
                    background: #f7fcf7;
                    border-radius: 0 4px 4px 0;
                ">
                    <p style="margin: 0; color: #00a32a; font-size: 13px;">
                        <strong>⭐ Recommended:</strong> ${aiwAdmin.strings.recommended}
                    </p>
                </div>
            `;
        }

        if (selectedOption.getAttribute('data-experimental') === 'true') {
            infoHtml += `
                <div class="notice notice-info inline" style="
                    margin: 8px 0; 
                    padding: 8px 12px;
                    border-left: 4px solid #2271b1;
                    background: #f7f9fc;
                    border-radius: 0 4px 4px 0;
                ">
                    <p style="margin: 0; color: #2271b1; font-size: 13px;">
                        <strong>🧪 Experimental:</strong> ${aiwAdmin.strings.experimental}
                    </p>
                </div>
            `;
        }

        if (infoHtml) {
            infoContainer.innerHTML = infoHtml;
            modelSelect.parentNode.appendChild(infoContainer);
        }
    }

    /**
     * Initialize tooltips for enhanced UX
     */
    function initializeTooltips() {
        // Add hover tooltips to select options and enhanced keyboard navigation
        const modelSelect = document.getElementById('llm_model');
        const providerSelect = document.getElementById('api_provider');
        
        if (modelSelect) {
            // Enhanced select styling and accessibility
            modelSelect.style.transition = 'border-color 0.2s ease, box-shadow 0.2s ease';
            
            // Add ARIA labels for accessibility
            modelSelect.setAttribute('aria-describedby', 'model-description');
            
            // Keyboard navigation enhancement
            modelSelect.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    // Show/update model info on Enter or Space
                    setTimeout(() => showModelInfo(this), 100);
                }
            });
            
            // Focus management
            modelSelect.addEventListener('focus', function() {
                this.style.borderColor = '#0073aa';
                this.style.boxShadow = '0 0 0 1px #0073aa';
            });
            
            modelSelect.addEventListener('blur', function() {
                this.style.borderColor = '';
                this.style.boxShadow = '';
            });
        }
        
        if (providerSelect) {
            // Enhanced provider select accessibility
            providerSelect.setAttribute('aria-describedby', 'provider-description');
            providerSelect.style.transition = 'border-color 0.2s ease, box-shadow 0.2s ease';
            
            // Focus styling
            providerSelect.addEventListener('focus', function() {
                this.style.borderColor = '#0073aa';
                this.style.boxShadow = '0 0 0 1px #0073aa';
            });
            
            providerSelect.addEventListener('blur', function() {
                this.style.borderColor = '';
                this.style.boxShadow = '';
            });
        }
        
        // Add responsive behavior for mobile devices
        setupResponsiveEnhancements();
    }
    
    /**
     * Setup responsive enhancements for mobile and tablet
     */
    function setupResponsiveEnhancements() {
        // Add media query detection
        const isMobile = window.matchMedia('(max-width: 782px)').matches;
        
        if (isMobile) {
            // Enhance mobile experience
            const selects = document.querySelectorAll('#api_provider, #llm_model');
            selects.forEach(select => {
                select.style.fontSize = '16px'; // Prevent zoom on iOS
                select.style.minHeight = '44px'; // Touch target size
            });
        }
        
        // Listen for orientation changes
        window.addEventListener('orientationchange', function() {
            setTimeout(() => {
                // Refresh model info display after orientation change
                const modelSelect = document.getElementById('llm_model');
                if (modelSelect && modelSelect.value) {
                    showModelInfo(modelSelect);
                }
            }, 300);
        });
    }

    /**
     * Fallback to static models if AJAX fails
     */
    function fallbackToStaticModels(provider, modelSelect) {
        console.warn('AI Interview Widget: Falling back to static model list for provider:', provider);
        
        // Static fallback models (simplified)
        const fallbackModels = {
            'openai': [
                { value: 'gpt-4o', label: 'GPT-4o (Latest)' },
                { value: 'gpt-4o-mini', label: 'GPT-4o-mini (Fast)' },
                { value: 'gpt-4-turbo', label: 'GPT-4 Turbo' }
            ],
            'anthropic': [
                { value: 'claude-3-5-sonnet-20241022', label: 'Claude 3.5 Sonnet (Latest)' },
                { value: 'claude-3-5-haiku-20241022', label: 'Claude 3.5 Haiku' }
            ],
            'gemini': [
                { value: 'gemini-1.5-pro', label: 'Gemini 1.5 Pro' },
                { value: 'gemini-1.5-flash', label: 'Gemini 1.5 Flash' }
            ],
            'azure': [
                { value: 'gpt-4o', label: 'GPT-4o (Azure)' },
                { value: 'gpt-4o-mini', label: 'GPT-4o-mini (Azure)' }
            ],
            'custom': [
                { value: 'custom-model', label: 'Custom Model' }
            ]
        };

        const models = fallbackModels[provider] || fallbackModels['openai'];
        populateModelSelect(models, modelSelect);
    }

    // Initialize when DOM is ready
    $(document).ready(function() {
        initializeAdminEnhancements();
    });

})(jQuery);