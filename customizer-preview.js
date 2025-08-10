/**
 * AI Interview Widget - WordPress Customizer Live Preview Script
 * 
 * Enables real-time preview of play button and canvas customizations
 * in the WordPress Customizer interface. Updates CSS properties and
 * DOM elements dynamically as settings change.
 * 
 * @deprecated Play-Button related controls removed from UI in v1.9.5
 *            Listeners maintained for backward compatibility when controls are enabled
 * 
 * @version 1.9.5
 * @author Eric Rorich
 * @since 1.0.0
 */

(function($) {
    'use strict';

    // Wait for the DOM to be ready
    $(document).ready(function() {
        
        // Helper function to update CSS custom property
        function updateCSSProperty(property, value) {
            document.documentElement.style.setProperty(property, value);
        }

        // Helper function to get play button element
        function getPlayButton() {
            return document.querySelector('.play-button') || document.querySelector('#playButton');
        }

        // Helper function to get play button container
        function getPlayButtonContainer() {
            return document.querySelector('.play-button-container') || document.querySelector('#playButtonContainer');
        }

        // Check if deprecated controls are enabled (for development/testing)
        var deprecatedControlsEnabled = typeof window.aiDeprecatedControls !== 'undefined' && window.aiDeprecatedControls;

        // @deprecated Play-Button controls - only register listeners if deprecated controls are enabled
        if (deprecatedControlsEnabled) {
            console.log('AI Interview Widget: Deprecated play-button control listeners enabled for testing');
            
            // Button Size (DEPRECATED)
            if (wp.customize('ai_play_button_size')) {
                wp.customize('ai_play_button_size', function(value) {
                    value.bind(function(newval) {
                        updateCSSProperty('--play-button-size', newval + 'px');
                        updateCSSProperty('--aiw-btn-size', newval);
                        
                        const playButton = getPlayButton();
                        if (playButton) {
                            playButton.style.width = newval + 'px';
                            playButton.style.height = newval + 'px';
                            playButton.style.fontSize = 'calc(' + newval + 'px * 0.4)';
                        }
                    });
                });
            }

            // Button Shape (DEPRECATED)
            if (wp.customize('ai_play_button_shape')) {
                wp.customize('ai_play_button_shape', function(value) {
                    value.bind(function(newval) {
                        const playButton = getPlayButton();
                        if (playButton) {
                            let borderRadius = '50%'; // circle default
                            if (newval === 'rounded') {
                                borderRadius = '15px';
                            } else if (newval === 'square') {
                                borderRadius = '0px';
                            }
                            playButton.style.borderRadius = borderRadius;
                        }
                    });
                });
            }

            // Primary Color (DEPRECATED)
            if (wp.customize('ai_play_button_color')) {
                wp.customize('ai_play_button_color', function(value) {
                    value.bind(function(newval) {
                        updateCSSProperty('--play-button-color', newval);
                        
                        const playButton = getPlayButton();
                        if (playButton) {
                            const gradientEnd = wp.customize.value('ai_play_button_gradient_end') ? wp.customize.value('ai_play_button_gradient_end')() : '';
                            if (gradientEnd && gradientEnd !== '') {
                                playButton.style.background = 'linear-gradient(135deg, ' + newval + ', ' + gradientEnd + ')';
                            } else {
                                playButton.style.background = newval;
                            }
                        }
                    });
                });
            }

            // Secondary Color (Gradient) (DEPRECATED)
            if (wp.customize('ai_play_button_gradient_end')) {
                wp.customize('ai_play_button_gradient_end', function(value) {
                    value.bind(function(newval) {
                        const playButton = getPlayButton();
                        if (playButton) {
                            const primaryColor = wp.customize.value('ai_play_button_color') ? wp.customize.value('ai_play_button_color')() : '#00cfff';
                            if (newval && newval !== '') {
                                playButton.style.background = 'linear-gradient(135deg, ' + primaryColor + ', ' + newval + ')';
                                updateCSSProperty('--play-button-color', 'linear-gradient(135deg, ' + primaryColor + ', ' + newval + ')');
                            } else {
                                playButton.style.background = primaryColor;
                                updateCSSProperty('--play-button-color', primaryColor);
                            }
                        }
                    });
                });
            }

            // Icon Color (DEPRECATED)
            if (wp.customize('ai_play_button_icon_color')) {
                wp.customize('ai_play_button_icon_color', function(value) {
                    value.bind(function(newval) {
                        updateCSSProperty('--play-button-icon-color', newval);
                        
                        const playButton = getPlayButton();
                        if (playButton) {
                            playButton.style.color = newval;
                        }
                    });
                });
            }

            // Pulse Enabled (DEPRECATED)
            if (wp.customize('ai_play_button_pulse_enabled')) {
                wp.customize('ai_play_button_pulse_enabled', function(value) {
                    value.bind(function(newval) {
                        updateCSSProperty('--play-button-disable-pulse', newval ? 'false' : 'true');
                        
                        const playButton = getPlayButton();
                        if (playButton) {
                            playButton.setAttribute('data-disable-pulse', newval ? 'false' : 'true');
                        }
                    });
                });
            }

            // Other deprecated play button controls...
            // (Additional controls omitted for brevity but would follow same pattern)
            
        } else {
            console.log('AI Interview Widget: Deprecated play-button control listeners disabled (default behavior)');
        }

        // Canvas Shadow Color (keeping this one)
        if (wp.customize('ai_canvas_shadow_color')) {
            wp.customize('ai_canvas_shadow_color', function(value) {
                value.bind(function(newval) {
                    // Update canonical CSS variable and backward compatibility alias
                    updateCSSProperty('--aiw-canvas-shadow-color', newval);
                    updateCSSProperty('--aiw-shadow-color', newval);
                    
                    // Update the canvas shadow immediately
                    const canvas = document.querySelector('#soundbar');
                    if (canvas) {
                        // Get current intensity to rebuild shadow
                        const intensity = wp.customize.value('ai_canvas_shadow_intensity') ? wp.customize.value('ai_canvas_shadow_intensity')() || 30 : 30;
                        updateCanvasShadow(newval, intensity);
                    }
                });
            });
        }

        // @deprecated Canvas Shadow Intensity - only if enabled
        if (deprecatedControlsEnabled && wp.customize('ai_canvas_shadow_intensity')) {
            wp.customize('ai_canvas_shadow_intensity', function(value) {
                value.bind(function(newval) {
                    updateCSSProperty('--aiw-shadow-intensity', newval);
                    
                    // Update the canvas shadow immediately
                    const canvas = document.querySelector('#soundbar');
                    if (canvas) {
                        // Get current color to rebuild shadow
                        const color = wp.customize.value('ai_canvas_shadow_color') ? wp.customize.value('ai_canvas_shadow_color')() || '#00cfff' : '#00cfff';
                        updateCanvasShadow(color, newval);
                    }
                });
            });
        }

        // Helper function to update canvas shadow
        function updateCanvasShadow(color, intensity) {
            const canvas = document.querySelector('#soundbar');
            if (!canvas) return;
            
            if (intensity === 0) {
                // No shadow
                canvas.style.boxShadow = 'none';
                updateCSSProperty('--canvas-box-shadow', 'none');
            } else {
                // Convert hex to RGB for shadow calculation
                const hex = color.replace('#', '');
                const r = parseInt(hex.substr(0, 2), 16);
                const g = parseInt(hex.substr(2, 2), 16);
                const b = parseInt(hex.substr(4, 2), 16);
                
                // Calculate glow layers based on intensity
                const glow1 = Math.round(intensity * 0.33);
                const glow2 = Math.round(intensity * 0.66);
                
                // Create layered shadow effect
                const shadowEffect = `0 0 ${intensity}px ${glow1}px rgba(${r}, ${g}, ${b}, 0.5), 0 0 ${intensity}px ${glow2}px rgba(${r}, ${g}, ${b}, 0.3)`;
                
                canvas.style.boxShadow = shadowEffect;
                updateCSSProperty('--canvas-box-shadow', shadowEffect);
            }
        }

        // Debugging helper
        console.log('AI Interview Widget v1.9.5 - Customizer preview script loaded with deprecated control support');
        
        // Helper to refresh widget if needed
        function refreshWidget() {
            // Trigger any widget-specific refresh logic if needed
            if (typeof window.aiWidgetDebug !== 'undefined' && window.aiWidgetDebug.refreshPulse) {
                window.aiWidgetDebug.refreshPulse();
            }
        }
        
        // Call refresh on any setting change
        wp.customize.bind('change', function() {
            setTimeout(refreshWidget, 100);
        });
    });

})(jQuery);