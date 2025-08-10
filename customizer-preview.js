/**
 * AI Interview Widget - WordPress Customizer Live Preview Script
 * 
 * Enables real-time preview of play button and canvas customizations
 * in the WordPress Customizer interface. Updates CSS properties and
 * DOM elements dynamically as settings change.
 * 
 * @version 1.9.4
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

        // Button Size
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

        // Button Shape
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

        // Primary Color
        wp.customize('ai_play_button_color', function(value) {
            value.bind(function(newval) {
                updateCSSProperty('--play-button-color', newval);
                
                const playButton = getPlayButton();
                if (playButton) {
                    const gradientEnd = wp.customize.value('ai_play_button_gradient_end')();
                    if (gradientEnd && gradientEnd !== '') {
                        playButton.style.background = 'linear-gradient(135deg, ' + newval + ', ' + gradientEnd + ')';
                    } else {
                        playButton.style.background = newval;
                    }
                }
            });
        });

        // Secondary Color (Gradient)
        wp.customize('ai_play_button_gradient_end', function(value) {
            value.bind(function(newval) {
                const playButton = getPlayButton();
                if (playButton) {
                    const primaryColor = wp.customize.value('ai_play_button_color')();
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

        // Icon Color
        wp.customize('ai_play_button_icon_color', function(value) {
            value.bind(function(newval) {
                updateCSSProperty('--play-button-icon-color', newval);
                
                const playButton = getPlayButton();
                if (playButton) {
                    playButton.style.color = newval;
                }
            });
        });

        // Pulse Enabled
        wp.customize('ai_play_button_pulse_enabled', function(value) {
            value.bind(function(newval) {
                updateCSSProperty('--play-button-disable-pulse', newval ? 'false' : 'true');
                
                const playButton = getPlayButton();
                if (playButton) {
                    playButton.setAttribute('data-disable-pulse', newval ? 'false' : 'true');
                }
            });
        });

        // Pulse Color
        wp.customize('ai_play_button_pulse_color', function(value) {
            value.bind(function(newval) {
                updateCSSProperty('--play-button-border-color', newval);
                
                const playButton = getPlayButton();
                if (playButton) {
                    playButton.style.borderColor = newval;
                }
            });
        });

        // Pulse Duration
        wp.customize('ai_play_button_pulse_duration', function(value) {
            value.bind(function(newval) {
                const pulseSpeed = 2.0 / newval;
                updateCSSProperty('--play-button-pulse-speed', pulseSpeed);
                
                // Update animation duration if pulse is enabled
                const playButton = getPlayButton();
                if (playButton && !playButton.getAttribute('data-disable-pulse') === 'true') {
                    const breathingDuration = newval + 's';
                    const dotsDuration = (newval * 0.9) + 's';
                    playButton.style.animationDuration = breathingDuration + ', ' + dotsDuration;
                }
            });
        });

        // Pulse Max Spread
        wp.customize('ai_play_button_pulse_spread', function(value) {
            value.bind(function(newval) {
                updateCSSProperty('--play-button-shadow-intensity', newval + 'px');
                
                const playButton = getPlayButton();
                if (playButton) {
                    const pulseColor = wp.customize.value('ai_play_button_pulse_color')();
                    playButton.style.boxShadow = '0 0 ' + newval + 'px ' + pulseColor;
                }
            });
        });

        // Hover Effect Style
        wp.customize('ai_play_button_hover_style', function(value) {
            value.bind(function(newval) {
                const playButton = getPlayButton();
                if (playButton) {
                    // Remove existing hover classes
                    playButton.classList.remove('hover-scale', 'hover-glow', 'hover-none');
                    
                    // Add new hover class
                    if (newval === 'scale') {
                        playButton.classList.add('hover-scale');
                    } else if (newval === 'glow') {
                        playButton.classList.add('hover-glow');
                    } else if (newval === 'none') {
                        playButton.classList.add('hover-none');
                    }
                }
            });
        });

        // Focus Ring Color
        wp.customize('ai_play_button_focus_color', function(value) {
            value.bind(function(newval) {
                // Create dynamic CSS for focus outline
                let focusStyle = document.getElementById('ai-focus-style');
                if (!focusStyle) {
                    focusStyle = document.createElement('style');
                    focusStyle.id = 'ai-focus-style';
                    document.head.appendChild(focusStyle);
                }
                
                focusStyle.textContent = '.play-button:focus { outline: 2px solid ' + newval + ' !important; outline-offset: 4px !important; }';
            });
        });

        // Canvas Shadow Color
        wp.customize('ai_canvas_shadow_color', function(value) {
            value.bind(function(newval) {
                updateCSSProperty('--aiw-shadow-color', newval);
                
                // Update the canvas shadow immediately
                const canvas = document.querySelector('#soundbar');
                if (canvas) {
                    // Get current intensity to rebuild shadow
                    const intensity = wp.customize.value('ai_canvas_shadow_intensity')() || 30;
                    updateCanvasShadow(newval, intensity);
                }
            });
        });

        // Canvas Shadow Intensity
        wp.customize('ai_canvas_shadow_intensity', function(value) {
            value.bind(function(newval) {
                updateCSSProperty('--aiw-shadow-intensity', newval);
                
                // Update the canvas shadow immediately
                const canvas = document.querySelector('#soundbar');
                if (canvas) {
                    // Get current color to rebuild shadow
                    const color = wp.customize.value('ai_canvas_shadow_color')() || '#00cfff';
                    updateCanvasShadow(color, newval);
                }
            });
        });

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

        // Icon Style
        wp.customize('ai_play_button_icon_style', function(value) {
            value.bind(function(newval) {
                const playIcon = document.querySelector('.play-button .play-icon');
                if (playIcon) {
                    // Reset icon styles
                    playIcon.style.border = '';
                    playIcon.style.background = '';
                    playIcon.style.width = '';
                    playIcon.style.height = '';
                    playIcon.style.borderLeft = '';
                    playIcon.style.borderTop = '';
                    playIcon.style.borderBottom = '';
                    playIcon.style.borderRight = '';
                    playIcon.style.fontSize = '';
                    playIcon.style.opacity = '';
                    
                    const iconColor = wp.customize.value('ai_play_button_icon_color')();
                    
                    if (newval === 'triangle_border') {
                        playIcon.textContent = '';
                        playIcon.style.border = '2px solid ' + iconColor;
                        playIcon.style.background = 'transparent';
                        playIcon.style.width = '0';
                        playIcon.style.height = '0';
                        playIcon.style.borderLeft = '8px solid ' + iconColor;
                        playIcon.style.borderTop = '6px solid transparent';
                        playIcon.style.borderBottom = '6px solid transparent';
                        playIcon.style.borderRight = 'none';
                    } else if (newval === 'minimal') {
                        playIcon.textContent = '▶';
                        playIcon.style.fontSize = '0.8em';
                        playIcon.style.opacity = '0.9';
                    } else {
                        // Default triangle
                        playIcon.textContent = '▶';
                    }
                }
            });
        });

        // Debugging helper
        console.log('AI Interview Widget - Customizer preview script loaded');
        
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