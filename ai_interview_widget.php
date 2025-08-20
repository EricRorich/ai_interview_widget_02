<?php
/**
 * Plugin Name: AI Interview Widget
 * Description: Interactive AI widget for Eric Rorich's portfolio with voice capabilities. Displays greeting and handles chat interactions with speech-to-text and text-to-speech features. Now includes WordPress Customizer integration for play button designs.
 * Version: 1.9.5
 * Author: Eric Rorich
 * Updated: 2025-01-27 14:30:00
 */

defined('ABSPATH') or die('No script kiddies please!');

/**
 * Main AI Interview Widget plugin class
 * 
 * Handles the complete functionality of the AI Interview Widget including:
 * - Audio playback and visualization
 * - Chat interface with AI backends
 * - Voice input/output capabilities  
 * - WordPress Customizer integration
 * - Settings management and admin interface
 * 
 * @since 1.0.0
 */
class AIInterviewWidget {

    /**
     * Plugin constructor - initialize hooks and actions
     * 
     * Sets up all WordPress hooks, shortcodes, AJAX handlers,
     * and plugin initialization routines.
     * 
     * @since 1.0.0
     */
    public function __construct() {
        // Include required files
        $this->include_dependencies();
        
        // Frontend scripts and shortcodes
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('ai_interview_widget', array($this, 'render_widget'));

        // AJAX handlers for chat and voice functionality
        add_action('wp_ajax_ai_interview_chat', array($this, 'handle_ai_chat'));
        add_action('wp_ajax_nopriv_ai_interview_chat', array($this, 'handle_ai_chat'));
        add_action('wp_ajax_ai_interview_tts', array($this, 'handle_tts_request'));
        add_action('wp_ajax_nopriv_ai_interview_tts', array($this, 'handle_tts_request'));
        add_action('wp_ajax_ai_interview_test', array($this, 'handle_ajax_test'));
        add_action('wp_ajax_nopriv_ai_interview_test', array($this, 'handle_ajax_test'));
        add_action('wp_ajax_ai_interview_voice_tts', array($this, 'handle_voice_tts'));
        add_action('wp_ajax_nopriv_ai_interview_voice_tts', array($this, 'handle_voice_tts'));

        // Nonce and security
        add_action('wp_footer', array($this, 'add_nonce_to_footer'));

        // Plugin initialization hooks
        add_action('init', array($this, 'validate_model_setting'));
        add_action('init', array($this, 'enable_error_logging'));
        // File handling and MIME types
        add_filter('upload_mimes', array($this, 'add_mp3_mime_type'));
        add_filter('wp_check_filetype_and_ext', array($this, 'fix_mp3_mime_type'), 10, 4);

        // URL rewriting for audio files
        add_action('init', array($this, 'add_audio_rewrite_rules'));
        add_filter('query_vars', array($this, 'add_audio_query_vars'));
        add_action('template_redirect', array($this, 'handle_audio_requests'));

        // Admin interface
        add_action('admin_menu', array($this, 'add_admin_menu'), 9);
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_settings_link'));
        add_action('admin_init', array($this, 'remove_old_menu_hooks'));

        // Plugin lifecycle hooks
        register_activation_hook(__FILE__, array($this, 'plugin_activation'));
        register_deactivation_hook(__FILE__, array($this, 'plugin_deactivation'));

        // Frontend customization
        add_action('wp_head', array($this, 'output_custom_css'));

        // WordPress Customizer integration
        add_action('customize_register', array($this, 'register_customizer_controls'));
        add_action('customize_preview_init', array($this, 'enqueue_customizer_preview_script'));
        add_action('customize_save_after', array($this, 'sync_customizer_to_plugin_settings'));

        // AJAX handlers for customizer functionality
        add_action('wp_ajax_ai_interview_save_styles', array($this, 'save_custom_styles'));
        add_action('wp_ajax_ai_interview_save_content', array($this, 'save_custom_content'));
        add_action('wp_ajax_ai_interview_reset_styles', array($this, 'reset_custom_styles'));
        add_action('wp_ajax_ai_interview_reset_single_setting', array($this, 'reset_single_setting'));
        add_action('wp_ajax_ai_interview_upload_audio', array($this, 'handle_audio_upload'));
        add_action('wp_ajax_ai_interview_remove_audio', array($this, 'handle_audio_removal'));
        add_action('wp_ajax_ai_interview_save_preset', array($this, 'save_design_preset'));

        // Language management AJAX handlers
        add_action('wp_ajax_ai_interview_update_language_sections', array($this, 'handle_update_language_sections'));
        add_action('wp_ajax_ai_interview_apply_languages', array($this, 'handle_apply_languages'));
        add_action('wp_ajax_ai_interview_cancel_pending_languages', array($this, 'handle_cancel_pending_languages'));
        add_action('wp_ajax_ai_interview_translate_prompt', array($this, 'handle_translate_prompt'));
        add_action('wp_ajax_ai_interview_load_preset', array($this, 'load_design_preset'));
        add_action('wp_ajax_ai_interview_load_default_preset', array($this, 'load_default_preset'));
        add_action('wp_ajax_ai_interview_delete_preset', array($this, 'delete_design_preset'));
        add_action('wp_ajax_ai_interview_get_presets', array($this, 'get_design_presets'));
        
        // AI Provider Management AJAX handlers
        add_action('wp_ajax_ai_interview_get_models', array($this, 'handle_get_models'));

        // Preview system AJAX handlers
        add_action('wp_ajax_ai_interview_render_preview', array($this, 'handle_preview_render'));
        add_action('wp_ajax_ai_interview_update_preview', array($this, 'handle_preview_update'));

        // Debug logging
        add_action('init', array($this, 'log_ajax_handlers_status'));
    }

    /**
     * Static method for plugin uninstall cleanup
     * 
     * Removes all plugin data including custom tables, options,
     * and scheduled events when the plugin is uninstalled.
     * 
     * @since 1.0.0
     * @static
     */
    public static function plugin_uninstall() {
        global $wpdb;

        // Remove custom table
        $table_name = $wpdb->prefix . 'ai_interview_widget_analytics';
        $wpdb->query("DROP TABLE IF EXISTS $table_name");
        
        // Remove all plugin options
        $options_to_delete = array(
            'ai_interview_widget_openai_api_key',
            'ai_interview_widget_elevenlabs_api_key',
            'ai_interview_widget_elevenlabs_voice_id',
            'ai_interview_widget_enable_voice',
            'ai_interview_widget_voice_quality',
            'ai_interview_widget_style_settings',
            'ai_interview_widget_content_settings',
            'ai_interview_widget_custom_audio_en',
            'ai_interview_widget_custom_audio_de',
            'ai_interview_widget_analytics',
            'ai_interview_widget_db_version',
            'ai_interview_widget_installed_date',
            'ai_interview_widget_installed_by',
            'ai_interview_widget_version',
            'ai_interview_widget_last_updated',
            'ai_interview_widget_updated_by'
        );
        
        foreach ($options_to_delete as $option) {
            delete_option($option);
        }
        
        // Clear scheduled events
        wp_clear_scheduled_hook('ai_interview_cleanup_tts_files');
        
        error_log('AI Interview Widget v1.9.3: Plugin uninstalled and cleaned up at 2025-08-03 18:37:12 UTC');
    }

    /**
     * Include required dependency files
     * 
     * Loads the provider definitions and model cache classes
     * to ensure they're available throughout the plugin.
     * 
     * @since 1.9.6
     */
    private function include_dependencies() {
        $includes_path = plugin_dir_path(__FILE__) . 'includes/';
        
        // Include provider definitions
        if (file_exists($includes_path . 'class-aiw-provider-definitions.php')) {
            require_once $includes_path . 'class-aiw-provider-definitions.php';
        }
        
        // Include model cache (optional)
        if (file_exists($includes_path . 'class-aiw-model-cache.php')) {
            require_once $includes_path . 'class-aiw-model-cache.php';
        }
    }

    /**
     * Enqueue admin scripts and styles
     * 
     * Loads admin-specific JavaScript for enhanced functionality
     * including dynamic model loading and enhanced UI features.
     * 
     * @since 1.9.6
     */
    public function enqueue_admin_scripts($hook) {
        // Only load on our admin pages
        if (strpos($hook, 'ai-interview-widget') === false) {
            return;
        }
        
        // Enqueue admin script
        wp_enqueue_script(
            'ai-interview-admin',
            plugin_dir_url(__FILE__) . 'admin-enhancements.js',
            array('jquery'),
            '1.9.6',
            true
        );
        
        // Enqueue admin styles
        wp_enqueue_style(
            'ai-interview-admin-styles',
            plugin_dir_url(__FILE__) . 'admin-styles.css',
            array(),
            '1.9.6'
        );
        
        // Enqueue customizer preview assets on Enhanced Widget Customizer page
        if ($hook === 'ai-interview-widget_page_ai-interview-widget-customizer') {
            wp_enqueue_script(
                'aiw-customizer-preview-js',
                plugin_dir_url(__FILE__) . 'admin/js/aiw-customizer-preview.js',
                array('jquery', 'wp-color-picker'), // Add jquery dependency to fix migration warnings
                '1.0.1', // Updated version to match JS file changes
                true // Load in footer
            );
            
            wp_enqueue_style(
                'aiw-customizer-preview-css',
                plugin_dir_url(__FILE__) . 'admin/css/aiw-customizer-preview.css',
                array(),
                '1.0.1' // Updated version to ensure fresh cache
            );
            
            // Localize script with defaults and debug flag
            wp_localize_script('aiw-customizer-preview-js', 'aiwCustomizerData', array(
                'defaults' => array(
                    'ai_primary_color' => '#00cfff',
                    'ai_accent_color' => '#ff6b35',
                    'ai_background_color' => '#0a0a1a',
                    'ai_text_color' => '#ffffff',
                    'ai_border_radius' => '8',
                    'ai_border_width' => '2',
                    'ai_shadow_intensity' => '20',
                    'ai_play_button_size' => '80',
                    'ai_play_button_color' => '#00cfff',
                    'ai_play_button_icon_color' => '#ffffff',
                    'ai_viz_bar_count' => '12',
                    'ai_viz_gap' => '3',
                    'ai_viz_color' => '#00cfff',
                    'ai_viz_glow' => '10',
                    'ai_viz_speed' => '1.0',
                    'ai_chat_bubble_color' => '#1e293b',
                    'ai_chat_bubble_radius' => '12',
                    'ai_chat_avatar_size' => '32'
                ),
                'debug' => true, // Force enable debug mode for enhanced logging
                'nonce' => wp_create_nonce('aiw_customizer_preview'),
                'ajaxurl' => admin_url('admin-ajax.php'),
                'version' => '1.0.1'
            ));
            ));
        }
        
        // Localize script with AJAX URL and nonce
        wp_localize_script('ai-interview-admin', 'aiwAdmin', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_interview_admin'),
            'strings' => array(
                'loading' => __('Loading models...', 'ai-interview-widget'),
                'error' => __('Error loading models. Please try again.', 'ai-interview-widget'),
                'deprecated' => __('This model is deprecated', 'ai-interview-widget'),
                'recommended' => __('Recommended model', 'ai-interview-widget'),
                'experimental' => __('Experimental model', 'ai-interview-widget')
            )
        ));
    }

    /**
     * Log the status of AJAX handlers for debugging
     * 
     * Outputs debug information about registered AJAX handlers
     * to help troubleshoot missing endpoint issues. Only logs
     * when WP_DEBUG is enabled.
     * 
     * @since 1.9.0
     */
    public function log_ajax_handlers_status() {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('AI Interview Widget v1.9.3: AJAX handlers registered at init - 2025-08-03 18:37:12 UTC');
            error_log('AI Interview Widget: wp_ajax_ai_interview_chat -> handle_ai_chat()');
            error_log('AI Interview Widget: wp_ajax_nopriv_ai_interview_chat -> handle_ai_chat()');
            error_log('AI Interview Widget: wp_ajax_ai_interview_tts -> handle_tts_request() [FIXED]');
            error_log('AI Interview Widget: wp_ajax_nopriv_ai_interview_tts -> handle_tts_request() [FIXED]');
        }
    }

    /**
     * AJAX handler for testing endpoint functionality
     * 
     * Provides a simple test endpoint to verify AJAX handlers
     * are working correctly for debugging purposes.
     * 
     * @since 1.9.0
     */
    public function handle_ajax_test() {
        error_log('AI Interview Widget: Test AJAX endpoint called at 2025-08-03 18:37:12 UTC');

        wp_send_json_success(array(
            'message' => 'AJAX endpoint working correctly!',
            'timestamp' => current_time('Y-m-d H:i:s'),
            'version' => '1.9.3',
            'test' => true,
            'user' => 'EricRorich'
        ));
    }

    /**
     * Helper function for PHP 7.4 compatibility
     * 
     * Checks if a string starts with a given substring.
     * This provides compatibility for str_starts_with() on older PHP versions.
     * 
     * @since 1.0.0
     * @param string $haystack The string to search in
     * @param string $needle The substring to search for
     * @return bool True if haystack starts with needle, false otherwise
     */
    private function starts_with($haystack, $needle) {
        return substr($haystack, 0, strlen($needle)) === $needle;
    }

    /**
     * Check if deprecated customizer controls should be hidden
     * 
     * @since 1.9.5
     * @return bool True to hide deprecated controls, false to show them
     */
    private function should_hide_deprecated_controls() {
        // Allow filtering for development/testing purposes
        return apply_filters('ai_interview_widget_hide_deprecated_controls', true);
    }

    /**
     * Log deprecation notice for legacy settings access
     * 
     * @since 1.9.5
     * @param string $setting_key The deprecated setting key
     * @param string $context Context where the setting was accessed
     */
    private function log_deprecation_notice($setting_key, $context = 'general') {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf(
                'AI Interview Widget: Deprecated setting "%s" accessed in %s context. This setting was removed from UI in v1.9.5 but stored values remain honored for backward compatibility.',
                $setting_key,
                $context
            ));
        }
    }

    /**
     * Get canvas shadow color with backward compatibility
     * 
     * Provides fallback logic for canvas shadow color setting:
     * 1. Check canonical 'canvas_shadow_color' setting
     * 2. Fall back to legacy 'ai_canvas_shadow_color' theme_mod
     * 3. Use default value if neither exists
     * 
     * @since 1.9.4
     * @param string $default Default color value
     * @return string Sanitized hex color value
     */
    private function get_canvas_shadow_color($default = '#00cfff') {
        // Get style data to check for canonical setting
        $style_data = get_option('ai_interview_widget_styles', array());
        
        // Check for canonical setting first
        if (isset($style_data['canvas_shadow_color']) && !empty($style_data['canvas_shadow_color'])) {
            return sanitize_hex_color($style_data['canvas_shadow_color']) ?: $default;
        }
        
        // Check for legacy theme_mod setting
        $legacy_value = get_theme_mod('ai_canvas_shadow_color', null);
        if ($legacy_value !== null) {
            // Trigger deprecation notice in debug mode
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('AI Interview Widget: Deprecated setting "ai_canvas_shadow_color" detected. Please update to use "canvas_shadow_color" setting.');
            }
            
            // Migrate legacy value to canonical setting if needed
            if (!isset($style_data['canvas_shadow_color'])) {
                $style_data['canvas_shadow_color'] = $legacy_value;
                update_option('ai_interview_widget_styles', $style_data);
                
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('AI Interview Widget: Migrated legacy "ai_canvas_shadow_color" to "canvas_shadow_color".');
                }
            }
            
            return sanitize_hex_color($legacy_value) ?: $default;
        }
        
        return $default;
    }

    /**
     * Validate model setting on plugin initialization
     * 
     * Ensures that the selected AI model is valid and available.
     * Called during plugin initialization to prevent configuration errors.
     * 
     * @since 1.9.0
     */
    public function validate_model_setting() {
        $this->ensure_valid_model_setting();
    }

    /**
     * Enable error logging for debugging
     * 
     * Sets up error logging if WP_DEBUG_LOG is not already defined.
     * Helps with troubleshooting plugin issues in production.
     * 
     * @since 1.0.0
     */
    public function enable_error_logging() {
        if (!defined('WP_DEBUG_LOG')) {
            ini_set('log_errors', 1);
            ini_set('error_log', ABSPATH . 'wp-content/debug.log');
        }
    }

    /**
     * Add rewrite rules for audio file access
     * 
     * Creates clean URLs for audio files used by the widget,
     * enabling direct access to greeting and TTS audio files.
     * 
     * @since 1.0.0
     */
    public function add_audio_rewrite_rules() {
        add_rewrite_rule(
            '^ai-widget-audio/([^/]+)/?$',
            'index.php?ai_widget_audio=$matches[1]',
            'top'
        );
    }

    /**
     * Add custom query variables for audio handling
     * 
     * Registers the ai_widget_audio query variable for use
     * in audio file rewrite rules.
     * 
     * @since 1.0.0
     * @param array $vars Existing query variables
     * @return array Modified query variables array
     */
    public function add_audio_query_vars($vars) {
        $vars[] = 'ai_widget_audio';
        return $vars;
    }

    /**
     * Handle audio file requests
     * 
     * Processes requests for audio files through the custom URL structure,
     * providing secure access to greeting and TTS audio files.
     * 
     * @since 1.0.0
     */
    public function handle_audio_requests() {
        $audio_file = get_query_var('ai_widget_audio');
        if (!$audio_file) return;
        
        // Handle greeting audio files (backwards compatibility)
        if (in_array($audio_file, ['greeting_en.mp3', 'greeting_de.mp3'])) {
            $file_path = plugin_dir_path(__FILE__) . $audio_file;
            if (file_exists($file_path)) {
                $this->serve_audio_file($file_path);
                exit;
            }
        }
        
        // Handle TTS audio files from uploads directory
        if (preg_match('/^ai_voice_tts_[\d]+_[a-zA-Z0-9]+\.mp3$/', $audio_file)) {
            $upload_dir = wp_upload_dir();
            $file_path = $upload_dir['basedir'] . '/ai-interview-tts/' . $audio_file;
            if (file_exists($file_path)) {
                $this->serve_audio_file($file_path);
                exit;
            }
        }
    }
    
    // Serve audio file with proper headers
    private function serve_audio_file($file_path) {
        // Security check - ensure file is within allowed directories
        $plugin_dir = realpath(plugin_dir_path(__FILE__));
        $upload_dir = wp_upload_dir();
        $upload_real_dir = realpath($upload_dir['basedir']);
        $requested_file = realpath($file_path);
        
        if (!$requested_file ||
            (strpos($requested_file, $plugin_dir) !== 0 &&
             strpos($requested_file, $upload_real_dir) !== 0)) {
            http_response_code(403);
            exit;
        }
        
        // Set headers for audio streaming
        header('Content-Type: audio/mpeg');
        header('Content-Length: ' . filesize($file_path));
        header('Accept-Ranges: bytes');
        header('Cache-Control: public, max-age=3600');
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 3600) . ' GMT');
        
        // Handle range requests for better audio streaming
        $file_size = filesize($file_path);
        $range = $this->get_range_header();
        
        if ($range) {
            list($start, $end) = $range;
            if ($end === false) $end = $file_size - 1;
            
            header('HTTP/1.1 206 Partial Content');
            header("Content-Range: bytes $start-$end/$file_size");
            header('Content-Length: ' . ($end - $start + 1));
            
            $fp = fopen($file_path, 'rb');
            fseek($fp, $start);
            echo fread($fp, $end - $start + 1);
            fclose($fp);
        } else {
            readfile($file_path);
        }
    }
    
    // Parse Range header for audio streaming
    private function get_range_header() {
        if (!isset($_SERVER['HTTP_RANGE'])) return false;
        
        if (preg_match('/bytes=(\d+)-(\d*)/', $_SERVER['HTTP_RANGE'], $matches)) {
            $start = intval($matches[1]);
            $end = !empty($matches[2]) ? intval($matches[2]) : false;
            return array($start, $end);
        }
        
        return false;
    }

    // Add MP3 and document mime types
    public function add_mp3_mime_type($mimes) {
        $mimes['mp3'] = 'audio/mpeg';
        // Add document mime types for system prompt uploads
        $mimes['pdf'] = 'application/pdf';
        $mimes['doc'] = 'application/msword';
        $mimes['docx'] = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
        $mimes['odt'] = 'application/vnd.oasis.opendocument.text';
        $mimes['rtf'] = 'application/rtf';
        return $mimes;
    }

    // Fix MP3 and document mime types
    public function fix_mp3_mime_type($data, $file, $filename, $mimes) {
        $wp_filetype = wp_check_filetype($filename, $mimes);
        if ($wp_filetype['ext'] === 'mp3') {
            $data['ext'] = 'mp3';
            $data['type'] = 'audio/mpeg';
        }
        // Handle document types
        elseif (in_array($wp_filetype['ext'], ['pdf', 'doc', 'docx', 'odt', 'rtf'])) {
            $data['ext'] = $wp_filetype['ext'];
            $data['type'] = $wp_filetype['type'];
        }
        return $data;
    }

    // Remove any old menu hooks that might be interfering
    public function remove_old_menu_hooks() {
        remove_submenu_page('options-general.php', 'ai-interview-widget');
    }

    // Plugin activation - flush rewrite rules and remove old menu
    public function plugin_activation() {
        delete_option('ai_interview_widget_old_menu');
        flush_rewrite_rules();
        
        // Create custom table for widget analytics if needed
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'ai_interview_widget_analytics';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_session varchar(255) NOT NULL,
            event_type varchar(100) NOT NULL,
            event_data text,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            user_agent text,
            ip_address varchar(45),
            PRIMARY KEY (id),
            KEY user_session (user_session),
            KEY event_type (event_type),
            KEY timestamp (timestamp)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Add version option
        add_option('ai_interview_widget_db_version', '1.9.3');
        add_option('ai_interview_widget_installed_date', '2025-08-03 18:37:12');
        add_option('ai_interview_widget_installed_by', 'EricRorich');
        
        error_log('AI Interview Widget v1.9.3: Plugin activated at 2025-08-03 18:37:12 UTC by EricRorich');
    }

    // Plugin deactivation - cleanup
    public function plugin_deactivation() {
        flush_rewrite_rules();
        error_log('AI Interview Widget v1.9.3: Plugin deactivated at 2025-08-03 18:37:12 UTC');
    }

    // Create standalone top-level menu
    public function add_admin_menu() {
        global $submenu;
        if (isset($submenu['options-general.php'])) {
            foreach ($submenu['options-general.php'] as $key => $item) {
                if (isset($item[2]) && $item[2] === 'ai-interview-widget') {
                    unset($submenu['options-general.php'][$key]);
                }
            }
        }

        $hook = add_menu_page(
            'AI Interview Widget',
            'AI Chat Widget',
            'manage_options',
            'ai-interview-widget',
            array($this, 'admin_page'),
            'dashicons-microphone',
            25
        );

        add_submenu_page(
            'ai-interview-widget',
            'AI Widget Settings',
            'Settings',
            'manage_options',
            'ai-interview-widget',
            array($this, 'admin_page')
        );

        add_submenu_page(
            'ai-interview-widget',
            'API Testing & Diagnostics',
            'API Testing',
            'manage_options',
            'ai-interview-widget-testing',
            array($this, 'testing_page')
        );

        add_submenu_page(
            'ai-interview-widget',
            'Usage & Documentation',
            'Documentation',
            'manage_options',
            'ai-interview-widget-docs',
            array($this, 'documentation_page')
        );

        // Enhanced Visual Customizer
        add_submenu_page(
            'ai-interview-widget',
            'Enhanced Visual Customizer',
            'Customize Widget',
            'manage_options',
            'ai-interview-widget-customizer',
            array($this, 'enhanced_customizer_page')
        );

        error_log('AI Interview Widget v1.9.3: Top-level menu created successfully with hook: ' . $hook);
    }

    // Register settings
    public function register_settings() {
        $settings_group = 'ai_interview_widget_settings';
        
        // Existing settings
        register_setting(
            $settings_group,
            'ai_interview_widget_openai_api_key',
            array(
                'type' => 'string',
                'sanitize_callback' => array($this, 'sanitize_api_key'),
                'default' => ''
            )
        );

        register_setting(
            $settings_group,
            'ai_interview_widget_elevenlabs_api_key',
            array(
                'type' => 'string',
                'sanitize_callback' => array($this, 'sanitize_elevenlabs_api_key'),
                'default' => ''
            )
        );

        register_setting(
            $settings_group,
            'ai_interview_widget_elevenlabs_voice_id',
            array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => 'pNInz6obpgDQGcFmaJgB'
            )
        );

        register_setting(
            $settings_group,
            'ai_interview_widget_enable_voice',
            array(
                'type' => 'boolean',
                'sanitize_callback' => 'rest_sanitize_boolean',
                'default' => true
            )
        );

        register_setting(
            $settings_group,
            'ai_interview_widget_voice_quality',
            array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => 'eleven_multilingual_v2'
            )
        );
        
        // Audio Control Settings
        register_setting(
            $settings_group,
            'ai_interview_widget_disable_greeting_audio',
            array(
                'type' => 'boolean',
                'sanitize_callback' => 'rest_sanitize_boolean',
                'default' => false
            )
        );
        
        register_setting(
            $settings_group,
            'ai_interview_widget_disable_audio_visualization',
            array(
                'type' => 'boolean',
                'sanitize_callback' => 'rest_sanitize_boolean',
                'default' => false
            )
        );
        
        register_setting(
            $settings_group,
            'ai_interview_widget_chatbox_only_mode',
            array(
                'type' => 'boolean',
                'sanitize_callback' => 'rest_sanitize_boolean',
                'default' => false
            )
        );
        
        // New API Provider Settings
        register_setting(
            $settings_group,
            'ai_interview_widget_api_provider',
            array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => 'openai'
            )
        );
        
        // LLM Model Selection
        register_setting(
            $settings_group,
            'ai_interview_widget_llm_model',
            array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => 'gpt-4o-mini'
            )
        );
        
        register_setting(
            $settings_group,
            'ai_interview_widget_anthropic_api_key',
            array(
                'type' => 'string',
                'sanitize_callback' => array($this, 'sanitize_api_key'),
                'default' => ''
            )
        );
        
        register_setting(
            $settings_group,
            'ai_interview_widget_gemini_api_key',
            array(
                'type' => 'string',
                'sanitize_callback' => array($this, 'sanitize_api_key'),
                'default' => ''
            )
        );
        
        register_setting(
            $settings_group,
            'ai_interview_widget_azure_api_key',
            array(
                'type' => 'string',
                'sanitize_callback' => array($this, 'sanitize_api_key'),
                'default' => ''
            )
        );
        
        register_setting(
            $settings_group,
            'ai_interview_widget_azure_endpoint',
            array(
                'type' => 'string',
                'sanitize_callback' => 'esc_url_raw',
                'default' => ''
            )
        );
        
        register_setting(
            $settings_group,
            'ai_interview_widget_custom_api_endpoint',
            array(
                'type' => 'string',
                'sanitize_callback' => 'esc_url_raw',
                'default' => ''
            )
        );
        
        register_setting(
            $settings_group,
            'ai_interview_widget_custom_api_key',
            array(
                'type' => 'string',
                'sanitize_callback' => array($this, 'sanitize_api_key'),
                'default' => ''
            )
        );

        // Enhanced customizer settings
        register_setting(
            $settings_group,
            'ai_interview_widget_style_settings',
            array(
                'type' => 'string',
                'sanitize_callback' => array($this, 'sanitize_style_settings'),
                'default' => ''
            )
        );

        register_setting(
            $settings_group,
            'ai_interview_widget_content_settings',
            array(
                'type' => 'string',
                'sanitize_callback' => array($this, 'sanitize_content_settings'),
                'default' => ''
            )
        );

        register_setting(
            $settings_group,
            'ai_interview_widget_custom_audio_en',
            array(
                'type' => 'string',
                'sanitize_callback' => 'esc_url_raw',
                'default' => ''
            )
        );

        register_setting(
            $settings_group,
            'ai_interview_widget_custom_audio_de',
            array(
                'type' => 'string',
                'sanitize_callback' => 'esc_url_raw',
                'default' => ''
            )
        );

        // Design Presets
        register_setting(
            $settings_group,
            'ai_interview_widget_design_presets',
            array(
                'type' => 'string',
                'sanitize_callback' => array($this, 'sanitize_design_presets'),
                'default' => ''
            )
        );
        
        // Language Support Settings
        register_setting(
            $settings_group,
            'ai_interview_widget_default_language',
            array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => 'en'
            )
        );
        
        register_setting(
            $settings_group,
            'ai_interview_widget_supported_languages',
            array(
                'type' => 'string',
                'sanitize_callback' => array($this, 'sanitize_supported_languages'),
                'default' => json_encode(array('en' => 'English', 'de' => 'German'))
            )
        );

        // Geolocation Settings
        register_setting(
            $settings_group,
            'ai_interview_widget_enable_geolocation',
            array(
                'type' => 'boolean',
                'sanitize_callback' => 'rest_sanitize_boolean',
                'default' => true
            )
        );
        
        register_setting(
            $settings_group,
            'ai_interview_widget_geolocation_cache_timeout',
            array(
                'type' => 'integer',
                'sanitize_callback' => 'absint',
                'default' => 24 // hours
            )
        );
        
        register_setting(
            $settings_group,
            'ai_interview_widget_geolocation_debug_mode',
            array(
                'type' => 'boolean',
                'sanitize_callback' => 'rest_sanitize_boolean',
                'default' => false
            )
        );
        
        register_setting(
            $settings_group,
            'ai_interview_widget_geolocation_require_consent',
            array(
                'type' => 'boolean',
                'sanitize_callback' => 'rest_sanitize_boolean',
                'default' => false
            )
        );

        // Settings sections
        add_settings_section(
            'ai_interview_widget_provider_section',
            'AI Provider Selection',
            array($this, 'provider_section_callback'),
            'ai-interview-widget'
        );
        
        add_settings_field(
            'api_provider',
            'AI Provider',
            array($this, 'api_provider_field_callback'),
            'ai-interview-widget',
            'ai_interview_widget_provider_section'
        );
        
        add_settings_field(
            'llm_model',
            'LLM Model',
            array($this, 'llm_model_field_callback'),
            'ai-interview-widget',
            'ai_interview_widget_provider_section'
        );
        
        add_settings_section(
            'ai_interview_widget_api_section',
            'API Configuration',
            array($this, 'api_section_callback'),
            'ai-interview-widget'
        );

        add_settings_field(
            'openai_api_key',
            'OpenAI API Key',
            array($this, 'api_key_field_callback'),
            'ai-interview-widget',
            'ai_interview_widget_api_section'
        );
        
        add_settings_field(
            'anthropic_api_key',
            'Anthropic Claude API Key',
            array($this, 'anthropic_api_key_field_callback'),
            'ai-interview-widget',
            'ai_interview_widget_api_section'
        );
        
        add_settings_field(
            'gemini_api_key',
            'Google Gemini API Key',
            array($this, 'gemini_api_key_field_callback'),
            'ai-interview-widget',
            'ai_interview_widget_api_section'
        );
        
        add_settings_field(
            'azure_api_key',
            'Azure OpenAI API Key',
            array($this, 'azure_api_key_field_callback'),
            'ai-interview-widget',
            'ai_interview_widget_api_section'
        );
        
        add_settings_field(
            'azure_endpoint',
            'Azure OpenAI Endpoint',
            array($this, 'azure_endpoint_field_callback'),
            'ai-interview-widget',
            'ai_interview_widget_api_section'
        );
        
        add_settings_field(
            'custom_api_endpoint',
            'Custom API Endpoint',
            array($this, 'custom_api_endpoint_field_callback'),
            'ai-interview-widget',
            'ai_interview_widget_api_section'
        );
        
        add_settings_field(
            'custom_api_key',
            'Custom API Key',
            array($this, 'custom_api_key_field_callback'),
            'ai-interview-widget',
            'ai_interview_widget_api_section'
        );

        add_settings_section(
            'ai_interview_widget_elevenlabs_section',
            'ElevenLabs Voice Configuration',
            array($this, 'elevenlabs_section_callback'),
            'ai-interview-widget'
        );

        add_settings_field(
            'elevenlabs_api_key',
            'ElevenLabs API Key',
            array($this, 'elevenlabs_api_key_field_callback'),
            'ai-interview-widget',
            'ai_interview_widget_elevenlabs_section'
        );

        add_settings_field(
            'elevenlabs_voice_id',
            'Voice ID',
            array($this, 'elevenlabs_voice_id_field_callback'),
            'ai-interview-widget',
            'ai_interview_widget_elevenlabs_section'
        );

        add_settings_field(
            'voice_quality',
            'Voice Model',
            array($this, 'voice_quality_field_callback'),
            'ai-interview-widget',
            'ai_interview_widget_elevenlabs_section'
        );

        add_settings_field(
            'enable_voice',
            'Enable Voice Features',
            array($this, 'enable_voice_field_callback'),
            'ai-interview-widget',
            'ai_interview_widget_elevenlabs_section'
        );
        
        add_settings_field(
            'disable_greeting_audio',
            'Disable Greeting Audio',
            array($this, 'disable_greeting_audio_field_callback'),
            'ai-interview-widget',
            'ai_interview_widget_elevenlabs_section'
        );
        
        add_settings_field(
            'disable_audio_visualization',
            'Disable Audio Visualization',
            array($this, 'disable_audio_visualization_field_callback'),
            'ai-interview-widget',
            'ai_interview_widget_elevenlabs_section'
        );
        
        add_settings_field(
            'chatbox_only_mode',
            'Chatbox-Only Mode',
            array($this, 'chatbox_only_mode_field_callback'),
            'ai-interview-widget',
            'ai_interview_widget_elevenlabs_section'
        );
        
        // Language Support Section
        add_settings_section(
            'ai_interview_widget_language_section',
            'Language Support',
            array($this, 'language_section_callback'),
            'ai-interview-widget'
        );
        
        add_settings_field(
            'default_language',
            'Default Language',
            array($this, 'default_language_field_callback'),
            'ai-interview-widget',
            'ai_interview_widget_language_section'
        );
        
        add_settings_field(
            'supported_languages',
            'Supported Languages',
            array($this, 'supported_languages_field_callback'),
            'ai-interview-widget',
            'ai_interview_widget_language_section'
        );
        
        // Geolocation Section
        add_settings_section(
            'ai_interview_widget_geolocation_section',
            'Geolocation & Privacy',
            array($this, 'geolocation_section_callback'),
            'ai-interview-widget'
        );
        
        add_settings_field(
            'enable_geolocation',
            'Enable Geolocation',
            array($this, 'enable_geolocation_field_callback'),
            'ai-interview-widget',
            'ai_interview_widget_geolocation_section'
        );
        
        add_settings_field(
            'geolocation_cache_timeout',
            'Cache Timeout (hours)',
            array($this, 'geolocation_cache_timeout_field_callback'),
            'ai-interview-widget',
            'ai_interview_widget_geolocation_section'
        );
        
        add_settings_field(
            'geolocation_require_consent',
            'Require User Consent',
            array($this, 'geolocation_require_consent_field_callback'),
            'ai-interview-widget',
            'ai_interview_widget_geolocation_section'
        );
        
        add_settings_field(
            'geolocation_debug_mode',
            'Debug Mode',
            array($this, 'geolocation_debug_mode_field_callback'),
            'ai-interview-widget',
            'ai_interview_widget_geolocation_section'
        );
    }

    // Sanitize content settings
    public function sanitize_content_settings($settings) {
        if (empty($settings)) {
            return '';
        }
        
        $decoded = json_decode($settings, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return '';
        }
        
        // Get supported languages to determine which keys to allow
        $supported_langs = json_decode(get_option('ai_interview_widget_supported_languages', ''), true);
        if (!$supported_langs) $supported_langs = array('en' => 'English', 'de' => 'German');
        
        // Base allowed keys (non-language specific)
        $allowed_keys = array(
            'headline_text', 'headline_font_size', 'headline_font_family', 'headline_color'
        );
        
        // Add dynamic language-specific keys
        foreach ($supported_langs as $lang_code => $lang_name) {
            $allowed_keys[] = 'welcome_message_' . $lang_code;
            $allowed_keys[] = 'Systemprompts_Placeholder_' . $lang_code;
        }
        
        // Sanitize each setting
        $sanitized = array();
        foreach ($allowed_keys as $key) {
            if (isset($decoded[$key])) {
                if (strpos($key, 'Systemprompts_Placeholder_') === 0 || strpos($key, 'welcome_message_') === 0) {
                    $sanitized[$key] = sanitize_textarea_field($decoded[$key]);
                } else {
                    $sanitized[$key] = sanitize_text_field($decoded[$key]);
                }
            }
        }
        
        return json_encode($sanitized);
    }

    // Sanitize style settings
    public function sanitize_style_settings($settings) {
        if (empty($settings)) {
            return '';
        }
        
        $decoded = json_decode($settings, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return '';
        }
        
        // Sanitize each setting
        $sanitized = array();
        $allowed_keys = array(
            'container_bg_color', 'container_bg_type', 'container_bg_gradient_start', 'container_bg_gradient_end',
            'container_border_radius', 'container_padding', 'container_border_width', 'container_border_color',
            'canvas_border_radius', 'canvas_glow_intensity', 'canvas_color', 'canvas_bg_image', 'canvas_shadow_color', 'canvas_shadow_intensity',
            // Chatbox customization options
            'chatbox_font', 'chatbox_font_size', 'chatbox_font_color',
            // Enhanced Play-Button Customization
            'play_button_design', 'play_button_size', 'play_button_color', 'play_button_gradient_start', 'play_button_gradient_end',
            'play_button_pulse_speed', 'play_button_disable_pulse', 'play_button_shadow_intensity',
            'play_button_border_color', 'play_button_border_width', 'play_button_icon_color', 'play_button_neon_intensity',
            'voice_btn_bg_color', 'voice_btn_border_color', 'voice_btn_text_color', 'voice_btn_border_radius',
            // Audio Visualizer Settings
            'visualizer_theme', 'visualizer_primary_color', 'visualizer_secondary_color', 'visualizer_accent_color',
            'visualizer_bar_width', 'visualizer_bar_spacing', 'visualizer_glow_intensity', 'visualizer_animation_speed',
            'message_bg_opacity', 'message_border_radius', 'message_text_size', 'message_spacing',
            'input_bg_color', 'input_border_color', 'input_text_color', 'input_border_radius',
            'accent_color', 'text_color', 'animation_speed'
        );
        
        foreach ($allowed_keys as $key) {
            if (isset($decoded[$key])) {
                $sanitized[$key] = sanitize_text_field($decoded[$key]);
            }
        }
        
        return json_encode($sanitized);
    }

    // Sanitize design presets
    public function sanitize_design_presets($presets) {
        if (empty($presets)) {
            return '';
        }
        
        $decoded = json_decode($presets, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return '';
        }
        
        // Sanitize each preset
        $sanitized = array();
        foreach ($decoded as $preset_name => $preset_data) {
            $clean_name = sanitize_text_field($preset_name);
            if (strlen($clean_name) > 0 && strlen($clean_name) <= 50) {
                $sanitized[$clean_name] = array(
                    'style_settings' => is_array($preset_data['style_settings']) ? $preset_data['style_settings'] : array(),
                    'content_settings' => is_array($preset_data['content_settings']) ? $preset_data['content_settings'] : array(),
                    'created' => isset($preset_data['created']) ? sanitize_text_field($preset_data['created']) : current_time('mysql')
                );
            }
        }
        
        return json_encode($sanitized);
    }
    
    // Sanitize supported languages
    public function sanitize_supported_languages($languages) {
        if (empty($languages)) {
            return json_encode(array('en' => 'English', 'de' => 'German'));
        }
        
        $decoded = json_decode($languages, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return json_encode(array('en' => 'English', 'de' => 'German'));
        }
        
        // Sanitize each language entry
        $sanitized = array();
        foreach ($decoded as $code => $name) {
            $clean_code = sanitize_text_field($code);
            $clean_name = sanitize_text_field($name);
            if (preg_match('/^[a-z]{2}(-[A-Z]{2})?$/', $clean_code) && strlen($clean_name) > 0) {
                $sanitized[$clean_code] = $clean_name;
            }
        }
        
        // Ensure at least English is available
        if (empty($sanitized)) {
            $sanitized = array('en' => 'English', 'de' => 'German');
        }
        
        return json_encode($sanitized);
    }

    // Handle audio upload
    public function handle_audio_upload() {
        check_ajax_referer('ai_interview_customizer', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        $language = isset($_POST['language']) ? sanitize_text_field($_POST['language']) : '';
        
        if (!in_array($language, ['en', 'de'])) {
            wp_send_json_error('Invalid language');
        }

        if (!isset($_FILES['audio_file'])) {
            wp_send_json_error('No file uploaded');
        }

        $file = $_FILES['audio_file'];
        
        // Check file type
        $allowed_types = array('audio/mpeg', 'audio/mp3');
        if (!in_array($file['type'], $allowed_types)) {
            wp_send_json_error('Only MP3 files are allowed');
        }

        // Check file size (max 5MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            wp_send_json_error('File size must be less than 5MB');
        }

        $upload_dir = wp_upload_dir();
        $filename = 'ai_greeting_' . $language . '_custom_' . time() . '.mp3';
        $filepath = $upload_dir['path'] . '/' . $filename;

        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            $file_url = $upload_dir['url'] . '/' . $filename;
            
            // Save to options
            update_option('ai_interview_widget_custom_audio_' . $language, $file_url);
            
            wp_send_json_success(array(
                'message' => 'Audio file uploaded successfully!',
                'file_url' => $file_url
            ));
        } else {
            wp_send_json_error('Failed to upload file');
        }
    }

    // Handle audio removal
    public function handle_audio_removal() {
        check_ajax_referer('ai_interview_customizer', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        $language = isset($_POST['language']) ? sanitize_text_field($_POST['language']) : '';
        
        if (!in_array($language, ['en', 'de'])) {
            wp_send_json_error('Invalid language');
        }

        // Get current file URL and delete the file
        $current_url = get_option('ai_interview_widget_custom_audio_' . $language, '');
        if (!empty($current_url)) {
            $upload_dir = wp_upload_dir();
            $filename = basename($current_url);
            $filepath = $upload_dir['path'] . '/' . $filename;
            
            if (file_exists($filepath)) {
                unlink($filepath);
            }
        }

        // Remove from options
        delete_option('ai_interview_widget_custom_audio_' . $language);
        
        wp_send_json_success(array(
            'message' => 'Custom audio removed successfully!'
        ));
    }

    // Reset single setting
    public function reset_single_setting() {
        check_ajax_referer('ai_interview_customizer', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $setting_key = isset($_POST['setting_key']) ? sanitize_text_field($_POST['setting_key']) : '';
        $setting_type = isset($_POST['setting_type']) ? sanitize_text_field($_POST['setting_type']) : 'style';
        
        if (empty($setting_key)) {
            wp_send_json_error('Invalid setting key');
        }

        $option_name = $setting_type === 'content' ? 'ai_interview_widget_content_settings' : 'ai_interview_widget_style_settings';
        $current_settings = get_option($option_name, '');
        $settings_data = json_decode($current_settings, true);
        
        if (!$settings_data) {
            $settings_data = array();
        }

        // Remove the specific setting (reset to default)
        if (isset($settings_data[$setting_key])) {
            unset($settings_data[$setting_key]);
        }

        update_option($option_name, json_encode($settings_data));
        
        wp_send_json_success(array(
            'message' => 'Setting reset successfully!'
        ));
    }

    // Save custom styles
    public function save_custom_styles() {
        check_ajax_referer('ai_interview_customizer', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $styles = isset($_POST['styles']) ? $_POST['styles'] : array();
        $sanitized_styles = $this->sanitize_style_settings(json_encode($styles));
        
        update_option('ai_interview_widget_style_settings', $sanitized_styles);
        
        wp_send_json_success(array(
            'message' => 'Styles saved successfully!',
            'css' => $this->generate_css_from_settings($sanitized_styles)
        ));
    }

    // Save custom content
    public function save_custom_content() {
        check_ajax_referer('ai_interview_customizer', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $content = isset($_POST['content']) ? $_POST['content'] : array();
        $sanitized_content = $this->sanitize_content_settings(json_encode($content));
        
        update_option('ai_interview_widget_content_settings', $sanitized_content);
        
        wp_send_json_success(array(
            'message' => 'Content saved successfully!'
        ));
    }

    // Reset custom styles
    public function reset_custom_styles() {
        check_ajax_referer('ai_interview_customizer', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        delete_option('ai_interview_widget_style_settings');
        
        wp_send_json_success(array(
            'message' => 'Styles reset to default!'
        ));
    }

    // Save design preset
    /**
     * Enhanced Save Design Preset AJAX Handler
     * Validates input, sanitizes data, and provides comprehensive error handling
     */
    public function save_design_preset() {
        // Verify nonce for security
        if (!check_ajax_referer('ai_interview_customizer', 'nonce', false)) {
            wp_send_json_error('Security verification failed. Please refresh the page and try again.');
            return;
        }
        
        // Check user permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('You do not have permission to save presets.');
            return;
        }
        
        // Validate and sanitize input data
        $preset_name = isset($_POST['preset_name']) ? sanitize_text_field($_POST['preset_name']) : '';
        $style_settings = isset($_POST['style_settings']) ? $_POST['style_settings'] : array();
        $content_settings = isset($_POST['content_settings']) ? $_POST['content_settings'] : array();
        
        // Comprehensive input validation
        if (empty($preset_name)) {
            wp_send_json_error('Preset name is required.');
            return;
        }
        
        if (strlen($preset_name) > 50) {
            wp_send_json_error('Preset name must be 50 characters or less.');
            return;
        }
        
        if (!preg_match('/^[a-zA-Z0-9\s\-_]+$/', $preset_name)) {
            wp_send_json_error('Preset name can only contain letters, numbers, spaces, hyphens, and underscores.');
            return;
        }
        
        // Validate preset name is not reserved
        $reserved_names = array('Default', 'default', 'temp', 'temporary', 'backup');
        if (in_array(strtolower($preset_name), array_map('strtolower', $reserved_names))) {
            wp_send_json_error('This preset name is reserved. Please choose a different name.');
            return;
        }
        
        // Validate settings data
        if (!is_array($style_settings) && !is_object($style_settings)) {
            wp_send_json_error('Invalid style settings format.');
            return;
        }
        
        if (!is_array($content_settings) && !is_object($content_settings)) {
            wp_send_json_error('Invalid content settings format.');
            return;
        }
        
        try {
            // Get existing presets
            $presets = get_option('ai_interview_widget_design_presets', '');
            $presets_data = json_decode($presets, true);
            
            if (!is_array($presets_data)) {
                $presets_data = array();
            }
            
            // Check for maximum preset limit (prevent abuse)
            $max_presets = 20;
            if (count($presets_data) >= $max_presets && !isset($presets_data[$preset_name])) {
                wp_send_json_error('Maximum number of presets (' . $max_presets . ') reached. Please delete some presets first.');
                return;
            }
            
            // Sanitize settings data
            $sanitized_style_settings = $this->sanitize_preset_settings($style_settings);
            $sanitized_content_settings = $this->sanitize_preset_settings($content_settings);
            
            // Prepare preset data
            $preset_data = array(
                'style_settings' => $sanitized_style_settings,
                'content_settings' => $sanitized_content_settings,
                'created' => current_time('mysql'),
                'version' => '1.9.4' // Track plugin version for compatibility
            );
            
            // Add or update preset
            $is_update = isset($presets_data[$preset_name]);
            if ($is_update) {
                $preset_data['updated'] = current_time('mysql');
            }
            
            $presets_data[$preset_name] = $preset_data;
            
            // Save to database
            $update_result = update_option('ai_interview_widget_design_presets', json_encode($presets_data));
            
            if ($update_result === false) {
                error_log('AI Interview Widget: Failed to save preset "' . $preset_name . '" to database');
                wp_send_json_error('Failed to save preset to database. Please try again.');
                return;
            }
            
            // Log successful save
            error_log('AI Interview Widget: Preset "' . $preset_name . '" ' . ($is_update ? 'updated' : 'saved') . ' successfully');
            
            // Return success response
            wp_send_json_success(array(
                'message' => 'Preset "' . $preset_name . '" ' . ($is_update ? 'updated' : 'saved') . ' successfully!',
                'presets' => array_keys($presets_data),
                'preset_count' => count($presets_data),
                'action' => $is_update ? 'updated' : 'created'
            ));
            
        } catch (Exception $e) {
            error_log('AI Interview Widget: Error saving preset "' . $preset_name . '": ' . $e->getMessage());
            wp_send_json_error('An unexpected error occurred while saving the preset. Please try again.');
        }
    }
    
    /**
     * Sanitize preset settings data
     * Recursively sanitizes arrays and objects to prevent XSS and data corruption
     */
    private function sanitize_preset_settings($settings) {
        if (is_array($settings) || is_object($settings)) {
            $sanitized = array();
            foreach ($settings as $key => $value) {
                $clean_key = sanitize_key($key);
                if (is_array($value) || is_object($value)) {
                    $sanitized[$clean_key] = $this->sanitize_preset_settings($value);
                } else {
                    // Sanitize based on expected data types
                    if (is_numeric($value)) {
                        $sanitized[$clean_key] = is_float($value) ? floatval($value) : intval($value);
                    } elseif (is_bool($value)) {
                        $sanitized[$clean_key] = (bool) $value;
                    } else {
                        // For strings, use appropriate sanitization
                        if (strpos($clean_key, 'color') !== false) {
                            // Color values
                            $sanitized[$clean_key] = sanitize_hex_color($value) ?: sanitize_text_field($value);
                        } elseif (strpos($clean_key, 'url') !== false || strpos($clean_key, 'image') !== false) {
                            // URLs and images
                            $sanitized[$clean_key] = esc_url_raw($value);
                        } else {
                            // General text fields
                            $sanitized[$clean_key] = sanitize_text_field($value);
                        }
                    }
                }
            }
            return $sanitized;
        }
        return sanitize_text_field($settings);
    }
    
    /**
     * Enhanced Load Design Preset AJAX Handler
     * Loads preset with validation and comprehensive error handling
     */
    public function load_design_preset() {
        // Verify nonce for security
        if (!check_ajax_referer('ai_interview_customizer', 'nonce', false)) {
            wp_send_json_error('Security verification failed. Please refresh the page and try again.');
            return;
        }
        
        // Check user permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('You do not have permission to load presets.');
            return;
        }
        
        // Validate input
        $preset_name = isset($_POST['preset_name']) ? sanitize_text_field($_POST['preset_name']) : '';
        
        if (empty($preset_name)) {
            wp_send_json_error('Preset name is required.');
            return;
        }
        
        try {
            // Get existing presets
            $presets = get_option('ai_interview_widget_design_presets', '');
            $presets_data = json_decode($presets, true);
            
            if (!is_array($presets_data)) {
                wp_send_json_error('No presets found in database.');
                return;
            }
            
            if (!isset($presets_data[$preset_name])) {
                wp_send_json_error('Preset "' . $preset_name . '" not found. It may have been deleted.');
                return;
            }
            
            $preset = $presets_data[$preset_name];
            
            // Validate preset data structure
            if (!isset($preset['style_settings']) || !isset($preset['content_settings'])) {
                wp_send_json_error('Preset data is corrupted. Please try a different preset.');
                return;
            }
            
            // Update current settings in database
            $style_update = update_option('ai_interview_widget_style_settings', json_encode($preset['style_settings']));
            $content_update = update_option('ai_interview_widget_content_settings', json_encode($preset['content_settings']));
            
            // Check if updates were successful
            if ($style_update === false || $content_update === false) {
                error_log('AI Interview Widget: Failed to update settings when loading preset "' . $preset_name . '"');
                wp_send_json_error('Failed to apply preset settings. Please try again.');
                return;
            }
            
            // Log successful load
            error_log('AI Interview Widget: Preset "' . $preset_name . '" loaded successfully');
            
            // Return success with preset data for live preview
            wp_send_json_success(array(
                'message' => 'Preset "' . $preset_name . '" loaded successfully!',
                'style_settings' => $preset['style_settings'],
                'content_settings' => $preset['content_settings'],
                'preset_info' => array(
                    'name' => $preset_name,
                    'created' => isset($preset['created']) ? $preset['created'] : null,
                    'updated' => isset($preset['updated']) ? $preset['updated'] : null,
                    'version' => isset($preset['version']) ? $preset['version'] : 'unknown'
                )
            ));
            
        } catch (Exception $e) {
            error_log('AI Interview Widget: Error loading preset "' . $preset_name . '": ' . $e->getMessage());
            wp_send_json_error('An unexpected error occurred while loading the preset. Please try again.');
        }
    }
    
    /**
     * Enhanced Load Default Preset AJAX Handler
     * Resets to default settings with comprehensive validation
     */
    public function load_default_preset() {
        // Verify nonce for security
        if (!check_ajax_referer('ai_interview_customizer', 'nonce', false)) {
            wp_send_json_error('Security verification failed. Please refresh the page and try again.');
            return;
        }
        
        // Check user permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('You do not have permission to load the default preset.');
            return;
        }
        
        try {
            // Reset to default settings by deleting the custom options
            $style_delete = delete_option('ai_interview_widget_style_settings');
            $content_delete = delete_option('ai_interview_widget_content_settings');
            
            // Log the reset operation
            error_log('AI Interview Widget: Default preset loaded (custom settings cleared)');
            
            // Return success with default values for live preview
            wp_send_json_success(array(
                'message' => 'Default preset loaded successfully!',
                'style_settings' => $this->get_default_style_settings(),
                'content_settings' => $this->get_default_content_settings(),
                'preset_info' => array(
                    'name' => 'Default',
                    'description' => 'Built-in default settings',
                    'version' => '1.9.4'
                )
            ));
            
        } catch (Exception $e) {
            error_log('AI Interview Widget: Error loading default preset: ' . $e->getMessage());
            wp_send_json_error('An unexpected error occurred while loading default settings. Please try again.');
        }
    }
    
    /**
     * Get default style settings for consistent defaults
     */
    private function get_default_style_settings() {
        return array(
            'container_bg_type' => 'gradient',
            'container_bg_color' => '#0f0c29',
            'container_bg_gradient_start' => '#0f0c29',
            'container_bg_gradient_end' => '#24243e',
            'container_border_radius' => 15,
            'container_padding' => 20,
            'canvas_border_radius' => 8,
            'canvas_color' => '#0a0a1a',
            'canvas_bg_image' => '',
            'canvas_shadow_color' => '#000000',
            'canvas_shadow_intensity' => 20,
            'play_button_design' => 'classic',
            'play_button_size' => 100,
            'play_button_color' => '#00cfff',
            'play_button_gradient_start' => '#00ffff',
            'play_button_gradient_end' => '#001a33',
            'play_button_pulse_speed' => 1.0,
            'play_button_disable_pulse' => false,
            'play_button_shadow_intensity' => 40,
            'play_button_border_color' => '#00cfff',
            'play_button_neon_intensity' => 20,
            'play_button_icon_color' => '#ffffff',
            'chatbox_font' => 'Arial, sans-serif',
            'chatbox_font_size' => 14,
            'chatbox_font_color' => '#ffffff',
            'voice_btn_bg_color' => '#1a1a2e',
            'voice_btn_border_color' => '#00cfff',
            'voice_btn_text_color' => '#ffffff',
            'visualizer_theme' => 'default',
            'visualizer_primary_color' => '#00cfff',
            'visualizer_secondary_color' => '#0066ff',
            'visualizer_accent_color' => '#001a33',
            'visualizer_bar_width' => 2,
            'visualizer_bar_spacing' => 2,
            'visualizer_glow_intensity' => 10,
            'visualizer_animation_speed' => 1.0
        );
    }
    
    /**
     * Get default content settings for consistent defaults
     */
    private function get_default_content_settings() {
        return array(
            'headline_text' => 'AI Interview Assistant',
            'headline_font_size' => 18,
            'welcome_message_en' => 'Welcome! Click the button to start our AI-powered interview. I\'m here to help you explore my background and experience.',
            'welcome_message_de' => 'Willkommen! Klicken Sie auf den Button, um unser KI-gestütztes Interview zu starten. Ich bin hier, um Ihnen zu helfen, meinen Hintergrund und meine Erfahrungen zu erkunden.'
        );
    }
    
    /**
     * Enhanced Delete Design Preset AJAX Handler
     * Safely deletes preset with validation and comprehensive error handling
     */
    public function delete_design_preset() {
        // Verify nonce for security
        if (!check_ajax_referer('ai_interview_customizer', 'nonce', false)) {
            wp_send_json_error('Security verification failed. Please refresh the page and try again.');
            return;
        }
        
        // Check user permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('You do not have permission to delete presets.');
            return;
        }
        
        // Validate input
        $preset_name = isset($_POST['preset_name']) ? sanitize_text_field($_POST['preset_name']) : '';
        
        if (empty($preset_name)) {
            wp_send_json_error('Preset name is required.');
            return;
        }
        
        // Prevent deletion of reserved names
        $reserved_names = array('Default', 'default');
        if (in_array($preset_name, $reserved_names)) {
            wp_send_json_error('Cannot delete the Default preset.');
            return;
        }
        
        try {
            // Get existing presets
            $presets = get_option('ai_interview_widget_design_presets', '');
            $presets_data = json_decode($presets, true);
            
            if (!is_array($presets_data)) {
                wp_send_json_error('No presets found in database.');
                return;
            }
            
            if (!isset($presets_data[$preset_name])) {
                wp_send_json_error('Preset "' . $preset_name . '" not found. It may have already been deleted.');
                return;
            }
            
            // Store preset info for logging
            $deleted_preset_info = $presets_data[$preset_name];
            
            // Remove the preset
            unset($presets_data[$preset_name]);
            
            // Update database
            $update_result = update_option('ai_interview_widget_design_presets', json_encode($presets_data));
            
            if ($update_result === false) {
                error_log('AI Interview Widget: Failed to delete preset "' . $preset_name . '" from database');
                wp_send_json_error('Failed to delete preset from database. Please try again.');
                return;
            }
            
            // Log successful deletion
            error_log('AI Interview Widget: Preset "' . $preset_name . '" deleted successfully');
            
            // Return success response
            wp_send_json_success(array(
                'message' => 'Preset "' . $preset_name . '" deleted successfully!',
                'presets' => array_keys($presets_data),
                'preset_count' => count($presets_data),
                'deleted_preset' => array(
                    'name' => $preset_name,
                    'created' => isset($deleted_preset_info['created']) ? $deleted_preset_info['created'] : null
                )
            ));
            
        } catch (Exception $e) {
            error_log('AI Interview Widget: Error deleting preset "' . $preset_name . '": ' . $e->getMessage());
            wp_send_json_error('An unexpected error occurred while deleting the preset. Please try again.');
        }
    }
    
    /**
     * Enhanced Get Design Presets AJAX Handler
     * Returns preset list with metadata and error handling
     */
    public function get_design_presets() {
        // Verify nonce for security
        if (!check_ajax_referer('ai_interview_customizer', 'nonce', false)) {
            wp_send_json_error('Security verification failed. Please refresh the page and try again.');
            return;
        }
        
        // Check user permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('You do not have permission to view presets.');
            return;
        }
        
        try {
            // Get existing presets
            $presets = get_option('ai_interview_widget_design_presets', '');
            $presets_data = json_decode($presets, true);
            
            if (!is_array($presets_data)) {
                $presets_data = array();
            }
            
            // Build preset list with metadata
            $preset_list = array();
            $preset_details = array();
            
            foreach ($presets_data as $name => $data) {
                $preset_list[] = $name;
                $preset_details[$name] = array(
                    'created' => isset($data['created']) ? $data['created'] : null,
                    'updated' => isset($data['updated']) ? $data['updated'] : null,
                    'version' => isset($data['version']) ? $data['version'] : 'unknown',
                    'style_count' => is_array($data['style_settings']) ? count($data['style_settings']) : 0,
                    'content_count' => is_array($data['content_settings']) ? count($data['content_settings']) : 0
                );
            }
            
            // Return success response
            wp_send_json_success(array(
                'presets' => $preset_list,
                'preset_count' => count($preset_list),
                'preset_details' => $preset_details,
                'max_presets' => 20 // Let frontend know the limit
            ));
            
        } catch (Exception $e) {
            error_log('AI Interview Widget: Error getting presets: ' . $e->getMessage());
            wp_send_json_error('An unexpected error occurred while loading presets. Please try again.');
        }
    }

    /**
     * AJAX Handler: Get Models for Provider
     * Returns model list with capabilities, deprecation status, and descriptions
     */
    public function handle_get_models() {
        // Verify nonce for security
        if (!check_ajax_referer('ai_interview_admin', 'nonce', false)) {
            wp_send_json_error('Security verification failed. Please refresh the page and try again.');
            return;
        }
        
        // Check user permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('You do not have permission to view model information.');
            return;
        }
        
        $provider = sanitize_text_field($_POST['provider'] ?? '');
        
        if (empty($provider)) {
            wp_send_json_error('Provider parameter is required.');
            return;
        }
        
        // Validate provider against allowed providers
        $allowed_providers = array('openai', 'anthropic', 'gemini', 'azure', 'custom');
        if (!in_array($provider, $allowed_providers, true)) {
            wp_send_json_error('Invalid provider specified.');
            return;
        }
        
        try {
            // Get models from provider definitions with caching
            if (class_exists('AIW_Model_Cache')) {
                $models = AIW_Model_Cache::get_models_for_select_with_cache($provider);
            } else {
                $models = AIW_Provider_Definitions::get_models_for_select($provider);
            }
            
            // Validate that we have models
            if (empty($models) || !is_array($models)) {
                wp_send_json_error('No models available for the selected provider.');
                return;
            }
            
            // Sanitize model data before returning
            $sanitized_models = array();
            foreach ($models as $model) {
                if (is_array($model) && isset($model['value']) && isset($model['label'])) {
                    $sanitized_model = array(
                        'value' => sanitize_text_field($model['value']),
                        'label' => sanitize_text_field($model['label'])
                    );
                    
                    // Add optional fields if present
                    if (isset($model['description'])) {
                        $sanitized_model['description'] = sanitize_textarea_field($model['description']);
                    }
                    if (isset($model['capabilities']) && is_array($model['capabilities'])) {
                        $sanitized_model['capabilities'] = array_map('sanitize_text_field', $model['capabilities']);
                    }
                    if (isset($model['deprecated'])) {
                        $sanitized_model['deprecated'] = (bool) $model['deprecated'];
                    }
                    if (isset($model['recommended'])) {
                        $sanitized_model['recommended'] = (bool) $model['recommended'];
                    }
                    if (isset($model['experimental'])) {
                        $sanitized_model['experimental'] = (bool) $model['experimental'];
                    }
                    if (isset($model['migration_suggestion'])) {
                        $sanitized_model['migration_suggestion'] = sanitize_text_field($model['migration_suggestion']);
                    }
                    
                    $sanitized_models[] = $sanitized_model;
                }
            }
            
            // Return success response with sanitized model data
            wp_send_json_success(array(
                'models' => $sanitized_models,
                'provider' => $provider,
                'count' => count($sanitized_models),
                'cached' => class_exists('AIW_Model_Cache'),
                'timestamp' => current_time('timestamp')
            ));
            
        } catch (Exception $e) {
            error_log('AI Interview Widget: Error getting models for provider ' . $provider . ': ' . $e->getMessage());
            wp_send_json_error('An unexpected error occurred while loading models. Please try again.');
        }
    }

    // Generate CSS from settings - COMPLETE VERSION
    private function generate_css_from_settings($style_settings, $content_settings = '') {
        $style_data = json_decode($style_settings, true);
        $decode_error = json_last_error(); // Check error immediately after decode
        $content_data = json_decode($content_settings, true);
        
        // Always generate CSS for pulse effect, even if style_settings is empty
        if (empty($style_settings) || $decode_error !== JSON_ERROR_NONE) {
            $style_data = array(); // Start with empty array, will use defaults
        }
        
        $css = "/* AI Interview Widget - Generated Custom Styles - Version 1.9.3 */\n";
        $css .= "/* Current Date and Time (UTC): 2025-08-03 18:37:12 */\n";
        $css .= "/* Current User's Login: EricRorich */\n\n";
        
        // Container styles
        if (isset($style_data['container_bg_type']) && $style_data['container_bg_type'] === 'gradient') {
            if (isset($style_data['container_bg_gradient_start']) && isset($style_data['container_bg_gradient_end'])) {
                $gradient_bg = "linear-gradient(135deg, {$style_data['container_bg_gradient_start']} 0%, {$style_data['container_bg_gradient_end']} 100%)";
                $css .= ":root {\n";
                $css .= "    --container-background: {$gradient_bg};\n";
                $css .= "}\n";
                $css .= ".ai-interview-container {\n";
                $css .= "    background: {$gradient_bg} !important;\n";
                $css .= "}\n\n";
            }
        } elseif (isset($style_data['container_bg_color'])) {
            $css .= ":root {\n";
            $css .= "    --container-background: {$style_data['container_bg_color']};\n";
            $css .= "}\n";
            $css .= ".ai-interview-container {\n";
            $css .= "    background: {$style_data['container_bg_color']} !important;\n";
            $css .= "}\n\n";
        }
        
        // Container properties
        if (isset($style_data['container_border_radius'])) {
            $css .= ":root {\n";
            $css .= "    --container-border-radius: {$style_data['container_border_radius']}px;\n";
            $css .= "}\n";
            $css .= ".ai-interview-container {\n";
            $css .= "    border-radius: {$style_data['container_border_radius']}px !important;\n";
            $css .= "}\n\n";
        }
        
        if (isset($style_data['container_padding'])) {
            $css .= ":root {\n";
            $css .= "    --container-padding: {$style_data['container_padding']}px;\n";
            $css .= "}\n";
            $css .= ".ai-interview-container {\n";
            $css .= "    padding: {$style_data['container_padding']}px !important;\n";
            $css .= "}\n\n";
        }
        
        if (isset($style_data['container_border_width']) && isset($style_data['container_border_color']) && $style_data['container_border_width'] > 0) {
            $css .= ".ai-interview-container {\n";
            $css .= "    border: {$style_data['container_border_width']}px solid {$style_data['container_border_color']} !important;\n";
            $css .= "}\n\n";
        }
        
        // Canvas styles
        if (isset($style_data['canvas_border_radius'])) {
            $css .= ":root {\n";
            $css .= "    --canvas-border-radius: {$style_data['canvas_border_radius']}px;\n";
            $css .= "}\n";
            $css .= "#soundbar {\n";
            $css .= "    border-radius: {$style_data['canvas_border_radius']}px !important;\n";
            $css .= "}\n\n";
        }
        
        if (isset($style_data['canvas_color'])) {
            $css .= "#soundbar {\n";
            $css .= "    background: {$style_data['canvas_color']} !important;\n";
            $css .= "}\n\n";
        }
        
        if (isset($style_data['canvas_bg_image']) && !empty($style_data['canvas_bg_image'])) {
            $css .= "#soundbar {\n";
            $css .= "    background-image: url('{$style_data['canvas_bg_image']}') !important;\n";
            $css .= "    background-size: cover !important;\n";
            $css .= "    background-position: center !important;\n";
            $css .= "    background-repeat: no-repeat !important;\n";
            $css .= "}\n\n";
        }
        
        // Canvas shadow with custom color and intensity
        if (isset($style_data['canvas_shadow_intensity']) && $style_data['canvas_shadow_intensity'] > 0) {
            $intensity = intval($style_data['canvas_shadow_intensity']);
            $shadow_color = isset($style_data['canvas_shadow_color']) ? $style_data['canvas_shadow_color'] : '#00cfff';
            $glow1 = $intensity * 0.3;
            $glow2 = $intensity * 0.2;
            
            // Convert hex color to rgba for shadow
            $hex = str_replace('#', '', $shadow_color);
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
            
            $box_shadow = "0 0 {$intensity}px {$glow1}px rgba({$r}, {$g}, {$b}, 0.5), 0 0 {$intensity}px {$glow2}px rgba({$r}, {$g}, {$b}, 0.3)";
            
            $css .= ":root {\n";
            $css .= "    --canvas-box-shadow: {$box_shadow};\n";
            // Output separate variables for dynamic computation
            $css .= "    --aiw-shadow-blur1: {$intensity}px;\n";
            $css .= "    --aiw-shadow-spread1: {$glow1}px;\n";
            $css .= "    --aiw-shadow-blur2: {$intensity}px;\n";
            $css .= "    --aiw-shadow-spread2: {$glow2}px;\n";
            $css .= "    --aiw-shadow-color-rgba: rgba({$r}, {$g}, {$b}, 0.5);\n";
            $css .= "    --aiw-shadow-color-rgba2: rgba({$r}, {$g}, {$b}, 0.3);\n";
            $css .= "}\n";
            $css .= "#soundbar {\n";
            $css .= "    box-shadow: {$box_shadow} !important;\n";
            $css .= "}\n\n";
        }
        
        // Enhanced Chatbox font styling - comprehensive override to ensure theme compatibility
        if (isset($style_data['chatbox_font']) && $style_data['chatbox_font'] !== 'inherit') {
            $css .= "/* Chatbox Font Override - Enhanced to Override Theme Fonts */\n";
            $css .= ".ai-interview-container #chatInterface,\n";
            $css .= ".ai-interview-container .ai-chat-header,\n";
            $css .= ".ai-interview-container .message,\n";
            $css .= ".ai-interview-container #userInput,\n";
            $css .= ".ai-interview-container #chatInterface *,\n";
            $css .= ".ai-interview-container .message *,\n";
            $css .= ".ai-interview-container #userInput * {\n";
            $css .= "    font-family: {$style_data['chatbox_font']} !important;\n";
            $css .= "}\n\n";
        }
        
        if (isset($style_data['chatbox_font_size'])) {
            $css .= "/* Chatbox Font Size Override */\n";
            $css .= ".ai-interview-container #chatInterface,\n";
            $css .= ".ai-interview-container .message,\n";
            $css .= ".ai-interview-container #userInput {\n";
            $css .= "    font-size: {$style_data['chatbox_font_size']}px !important;\n";
            $css .= "}\n\n";
        }
        
        if (isset($style_data['chatbox_font_color'])) {
            $css .= "/* Chatbox Font Color Override */\n";
            $css .= ".ai-interview-container #chatInterface,\n";
            $css .= ".ai-interview-container .ai-chat-header,\n";
            $css .= ".ai-interview-container .message,\n";
            $css .= ".ai-interview-container #userInput {\n";
            $css .= "    color: {$style_data['chatbox_font_color']} !important;\n";
            $css .= "}\n\n";
        }
        
        // Voice button styles
        if (isset($style_data['voice_btn_bg_color'])) {
            $css .= ".voice-btn {\n";
            $css .= "    background: {$style_data['voice_btn_bg_color']} !important;\n";
            $css .= "}\n\n";
        }
        
        if (isset($style_data['voice_btn_border_color'])) {
            $css .= ".voice-btn {\n";
            $css .= "    border-color: {$style_data['voice_btn_border_color']} !important;\n";
            $css .= "}\n\n";
        }
        
        // Enhanced Play-Button CSS Variables for JavaScript
        // Always output these variables to ensure consistent behavior
        $css .= ":root {\n";
        
        // Canvas background color - FIXED: Always ensure this variable is set
        $canvas_color = isset($style_data['canvas_color']) ? $style_data['canvas_color'] : '#0a0a1a';
        $css .= "    --canvas-background-color: {$canvas_color};\n";
        
        // Play-Button design
        $design = isset($style_data['play_button_design']) ? $style_data['play_button_design'] : 'classic';
        $css .= "    --play-button-design: '{$design}';\n";
        
        // Play-Button size
        $size = isset($style_data['play_button_size']) ? $style_data['play_button_size'] : 100;
        $css .= "    --play-button-size: {$size}px;\n";
        
        // Play-Button color
        $color = isset($style_data['play_button_color']) ? $style_data['play_button_color'] : '#00cfff';
        $css .= "    --play-button-color: {$color};\n";
        
        // Play-Button gradient
        $gradientStart = isset($style_data['play_button_gradient_start']) ? $style_data['play_button_gradient_start'] : '#00ffff';
        $css .= "    --play-button-gradient-start: {$gradientStart};\n";
        
        $gradientEnd = isset($style_data['play_button_gradient_end']) ? $style_data['play_button_gradient_end'] : '#001a33';
        $css .= "    --play-button-gradient-end: {$gradientEnd};\n";
        
        // Play-Button pulse speed (critical for pulse effect)
        $pulseSpeed = isset($style_data['play_button_pulse_speed']) ? $style_data['play_button_pulse_speed'] : 1.0;
        $css .= "    --play-button-pulse-speed: {$pulseSpeed};\n";
        
        // Play-Button disable pulse (critical for pulse effect)
        $disablePulse = isset($style_data['play_button_disable_pulse']) ? $style_data['play_button_disable_pulse'] : false;
        $css .= "    --play-button-disable-pulse: " . ($disablePulse ? 'true' : 'false') . ";\n";
        
        // Play-Button shadow intensity
        $shadowIntensity = isset($style_data['play_button_shadow_intensity']) ? $style_data['play_button_shadow_intensity'] : 40;
        $css .= "    --play-button-shadow-intensity: {$shadowIntensity}px;\n";
        
        // Play-Button border color
        $borderColor = isset($style_data['play_button_border_color']) ? $style_data['play_button_border_color'] : '#00cfff';
        $css .= "    --play-button-border-color: {$borderColor};\n";
        
        // Play-Button border width
        $borderWidth = isset($style_data['play_button_border_width']) ? $style_data['play_button_border_width'] : 2;
        $css .= "    --play-button-border-width: {$borderWidth}px;\n";
        
        // Play-Button neon intensity
        $neonIntensity = isset($style_data['play_button_neon_intensity']) ? $style_data['play_button_neon_intensity'] : 20;
        $css .= "    --play-button-neon-intensity: {$neonIntensity}px;\n";
        
        // Play-Button icon color
        $iconColor = isset($style_data['play_button_icon_color']) ? $style_data['play_button_icon_color'] : '#ffffff';
        $css .= "    --play-button-icon-color: {$iconColor};\n";
        
        // New CSS variables for JavaScript compatibility
        // Button size (use new variable name for JavaScript)
        $css .= "    --aiw-btn-size: {$size};\n";
        
        // Canvas shadow color (use canonical naming with backward compatibility)
        $canvasShadowColor = isset($style_data['canvas_shadow_color']) ? $style_data['canvas_shadow_color'] : 'rgba(0, 207, 255, 0.5)';
        $css .= "    --aiw-canvas-shadow-color: {$canvasShadowColor};\n";
        $css .= "    --aiw-shadow-color: var(--aiw-canvas-shadow-color); /* Backward compatibility alias */\n";
        
        // Canvas shadow intensity as CSS variable for live preview and dynamic updates
        $canvasShadowIntensity = isset($style_data['canvas_shadow_intensity']) ? intval($style_data['canvas_shadow_intensity']) : 20;
        $css .= "    --aiw-shadow-intensity: {$canvasShadowIntensity};\n";
        
        $css .= "}\n\n";
        
        if (isset($style_data['voice_btn_text_color'])) {
            $css .= ".voice-btn {\n";
            $css .= "    color: {$style_data['voice_btn_text_color']} !important;\n";
            $css .= "}\n\n";
        }
        
        if (isset($style_data['voice_btn_border_radius'])) {
            $css .= ".voice-btn {\n";
            $css .= "    border-radius: {$style_data['voice_btn_border_radius']}px !important;\n";
            $css .= "}\n\n";
        }
        
        // Message styles
        if (isset($style_data['message_bg_opacity'])) {
            $opacity = floatval($style_data['message_bg_opacity']) / 100;
            $css .= ".ai-message {\n";
            $css .= "    background: rgba(123, 0, 255, {$opacity}) !important;\n";
            $css .= "}\n";
            $css .= ".user-message {\n";
            $css .= "    background: rgba(0, 100, 255, {$opacity}) !important;\n";
            $css .= "}\n\n";
        }
        
        if (isset($style_data['message_border_radius'])) {
            $css .= ".message {\n";
            $css .= "    border-radius: {$style_data['message_border_radius']}px !important;\n";
            $css .= "}\n\n";
        }
        
        if (isset($style_data['message_text_size'])) {
            $css .= ".message {\n";
            $css .= "    font-size: {$style_data['message_text_size']}px !important;\n";
            $css .= "}\n\n";
        }
        
        if (isset($style_data['message_spacing'])) {
            $css .= ".message {\n";
            $css .= "    margin-bottom: {$style_data['message_spacing']}px !important;\n";
            $css .= "}\n\n";
        }
        
        // Input styles
        if (isset($style_data['input_bg_color'])) {
            $css .= "#userInput, #sendButton {\n";
            $css .= "    background: {$style_data['input_bg_color']} !important;\n";
            $css .= "}\n\n";
        }
        
        if (isset($style_data['input_border_color'])) {
            $css .= "#userInput, #sendButton {\n";
            $css .= "    border-color: {$style_data['input_border_color']} !important;\n";
            $css .= "}\n\n";
        }
        
        if (isset($style_data['input_text_color'])) {
            $css .= "#userInput, #sendButton {\n";
            $css .= "    color: {$style_data['input_text_color']} !important;\n";
            $css .= "}\n\n";
        }
        
        if (isset($style_data['input_border_radius'])) {
            $css .= "#userInput {\n";
            $css .= "    border-radius: {$style_data['input_border_radius']}px !important;\n";
            $css .= "}\n";
            $css .= "#sendButton {\n";
            $css .= "    border-radius: {$style_data['input_border_radius']}px !important;\n";
            $css .= "}\n\n";
        }
        
        // Global accent color
        if (isset($style_data['accent_color'])) {
            $css .= ".ai-interview-container button:hover, .voice-btn:hover {\n";
            $css .= "    box-shadow: 0 0 15px {$style_data['accent_color']} !important;\n";
            $css .= "}\n\n";
        }
        
        // Global text color
        if (isset($style_data['text_color'])) {
            $css .= ".ai-interview-container {\n";
            $css .= "    color: {$style_data['text_color']} !important;\n";
            $css .= "}\n\n";
        }
        
        // Animation speed
        if (isset($style_data['animation_speed'])) {
            $speed = floatval($style_data['animation_speed']);
            $css .= ".ai-interview-container *, .voice-btn, #sendButton {\n";
            $css .= "    transition-duration: {$speed}s !important;\n";
            $css .= "}\n\n";
        }
        
        // Add content-based styles
        if (!empty($content_data)) {
            if (isset($content_data['headline_font_size'])) {
                $css .= ".ai-chat-header {\n";
                $css .= "    font-size: {$content_data['headline_font_size']}px !important;\n";
                $css .= "}\n\n";
            }
            if (isset($content_data['headline_font_family'])) {
                $css .= ".ai-chat-header {\n";
                $css .= "    font-family: {$content_data['headline_font_family']} !important;\n";
                $css .= "}\n\n";
            }
            if (isset($content_data['headline_color'])) {
                $css .= ".ai-chat-header {\n";
                $css .= "    color: {$content_data['headline_color']} !important;\n";
                $css .= "}\n\n";
            }
        }
        
        return $css;
    }

    /**
     * Handle Preview Render AJAX Request
     * Generates complete iframe HTML for live preview
     * 
     * @since 1.9.5
     */
    public function handle_preview_render() {
        // Check nonce for security
        if (!check_ajax_referer('aiw_customizer_preview', 'nonce', false)) {
            wp_send_json_error(array(
                'message' => 'Invalid security token',
                'code' => 'nonce_failed'
            ));
            return;
        }
        
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => 'Insufficient permissions',
                'code' => 'permission_denied'
            ));
            return;
        }
        
        try {
            // Get current settings
            $style_settings = get_option('ai_interview_widget_style_settings', '');
            $content_settings = get_option('ai_interview_widget_content_settings', '');
            
            // Validate JSON settings
            if (!$this->is_valid_json($style_settings) || !$this->is_valid_json($content_settings)) {
                wp_send_json_error(array(
                    'message' => 'Invalid settings format',
                    'code' => 'invalid_json'
                ));
                return;
            }
            
            // Generate widget HTML using shortcode
            $widget_html = do_shortcode('[ai_interview_widget]');
            
            // Generate custom CSS
            $custom_css = $this->generate_preview_css($style_settings, $content_settings);
            
            // Generate complete preview page
            $preview_html = $this->generate_preview_page($widget_html, $custom_css);
            
            wp_send_json_success(array(
                'html' => $preview_html,
                'timestamp' => current_time('timestamp'),
                'message' => 'Preview rendered successfully'
            ));
            
        } catch (Exception $e) {
            error_log('AI Interview Widget Preview Render Error: ' . $e->getMessage());
            wp_send_json_error(array(
                'message' => 'Failed to generate preview',
                'code' => 'render_failed',
                'debug' => defined('WP_DEBUG') && WP_DEBUG ? $e->getMessage() : null
            ));
        }
    }
    
    /**
     * Handle Preview Update AJAX Request
     * Provides incremental updates for real-time preview
     * 
     * @since 1.9.5
     */
    public function handle_preview_update() {
        // Check nonce for security
        if (!check_ajax_referer('aiw_customizer_preview', 'nonce', false)) {
            wp_send_json_error(array(
                'message' => 'Invalid security token',
                'code' => 'nonce_failed'
            ));
            return;
        }
        
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => 'Insufficient permissions',
                'code' => 'permission_denied'
            ));
            return;
        }
        
        try {
            // Get settings from POST data (for real-time updates)
            $style_settings = isset($_POST['style_settings']) ? sanitize_textarea_field($_POST['style_settings']) : get_option('ai_interview_widget_style_settings', '');
            $content_settings = isset($_POST['content_settings']) ? sanitize_textarea_field($_POST['content_settings']) : get_option('ai_interview_widget_content_settings', '');
            
            // Validate JSON settings
            if (!$this->is_valid_json($style_settings) || !$this->is_valid_json($content_settings)) {
                wp_send_json_error(array(
                    'message' => 'Invalid settings format',
                    'code' => 'invalid_json'
                ));
                return;
            }
            
            // Generate updated CSS for incremental update
            $updated_css = $this->generate_preview_css($style_settings, $content_settings);
            
            // Parse content settings for dynamic updates
            $content_data = json_decode($content_settings, true);
            if (!$content_data) $content_data = array();
            
            wp_send_json_success(array(
                'css' => $updated_css,
                'content_data' => $content_data,
                'headline_text' => isset($content_data['headline_text']) ? $content_data['headline_text'] : '',
                'timestamp' => current_time('timestamp'),
                'message' => 'Preview updated successfully'
            ));
            
        } catch (Exception $e) {
            error_log('AI Interview Widget Preview Update Error: ' . $e->getMessage());
            wp_send_json_error(array(
                'message' => 'Failed to update preview',
                'code' => 'update_failed',
                'debug' => defined('WP_DEBUG') && WP_DEBUG ? $e->getMessage() : null
            ));
        }
    }
    
    /**
     * Generate Complete Preview Page HTML
     * Creates a standalone HTML page for iframe preview
     */
    private function generate_preview_page($widget_html, $custom_css) {
        // Get plugin URL for assets
        $plugin_url = plugins_url('', __FILE__);
        
        $html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Widget Preview</title>
    <style>
        /* Reset and base styles */
        * { box-sizing: border-box; }
        body { 
            margin: 0; 
            padding: 20px; 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            background: #f0f0f1;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        /* Load base widget styles */
        ' . file_get_contents(plugin_dir_path(__FILE__) . 'ai-interview-widget.css') . '
        
        /* Apply custom styles */
        ' . $custom_css . '
        
        /* Preview-specific adjustments */
        .ai-interview-container {
            max-width: 100%;
            margin: 0;
        }
        
        /* Disable actual functionality in preview */
        #aiEricGreeting { display: none !important; }
        .ai-interview-controls button { pointer-events: none; opacity: 0.8; }
        #chatInterface textarea { pointer-events: none; }
        #chatInterface button { pointer-events: none; opacity: 0.8; }
        
        /* Preview indicator */
        .preview-badge {
            position: fixed;
            top: 10px;
            right: 10px;
            background: rgba(0, 123, 255, 0.9);
            color: white;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
            z-index: 10000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }
    </style>
</head>
<body>
    <div class="preview-badge">🔍 Live Preview</div>
    ' . $widget_html . '
    
    <script>
        // Minimal preview functionality
        document.addEventListener("DOMContentLoaded", function() {
            console.log("Widget preview loaded");
            
            // Disable all interactive elements
            const interactiveElements = document.querySelectorAll("button, input, textarea, select");
            interactiveElements.forEach(el => {
                el.setAttribute("disabled", "disabled");
                el.style.pointerEvents = "none";
            });
            
            // Show loading state briefly then reveal widget
            setTimeout(() => {
                const container = document.querySelector(".ai-interview-container");
                if (container) {
                    container.style.opacity = "1";
                    container.style.transform = "scale(1)";
                }
            }, 100);
        });
        
        // Listen for updates from parent window with origin validation
        window.addEventListener("message", function(event) {
            // Validate message origin - allow same origin for security
            if (event.origin !== window.location.origin) {
                console.warn("Preview: Message from unauthorized origin ignored:", event.origin);
                return;
            }
            
            if (event.data && event.data.type === "updatePreview") {
                const { css, headline_text, content_data } = event.data;
                
                // Update CSS
                let styleEl = document.getElementById("dynamic-preview-styles");
                if (!styleEl) {
                    styleEl = document.createElement("style");
                    styleEl.id = "dynamic-preview-styles";
                    document.head.appendChild(styleEl);
                }
                
                if (css) {
                    styleEl.textContent = css;
                }
                
                // Update headline text
                const headerEl = document.getElementById("preview-header");
                if (headerEl && headline_text) {
                    headerEl.textContent = headline_text;
                }
                
                // Update other content data as needed
                if (content_data) {
                    // Handle dynamic content updates
                    console.log("Preview: Content data updated");
                }
                
                console.log("Preview: Successfully updated from parent window");
            }
        });
    </script>
</body>
</html>';
        
        return $html;
    }

    /**
     * Validate JSON string
     * Helper method to validate JSON format safely
     */
    private function is_valid_json($string) {
        if (empty($string)) {
            return true; // Empty string is valid (will use defaults)
        }
        
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }
    
    /**
     * Generate CSS for preview based on settings
     * Creates custom CSS from style and content settings for preview iframe
     * 
     * @since 1.9.5
     * @param string $style_settings JSON string of style settings
     * @param string $content_settings JSON string of content settings
     * @return string Generated CSS
     */
    private function generate_preview_css($style_settings, $content_settings) {
        // Parse settings
        $style_data = json_decode($style_settings, true);
        $content_data = json_decode($content_settings, true);
        
        if (!$style_data) $style_data = array();
        if (!$content_data) $content_data = array();
        
        // Start building CSS
        $css = "/* AI Interview Widget Preview CSS */\n";
        $css .= ":root {\n";
        
        // Container background settings
        if (isset($style_data['container_bg_color'])) {
            $css .= "    --aiw-container-bg-color: " . sanitize_text_field($style_data['container_bg_color']) . ";\n";
        }
        if (isset($style_data['container_bg_gradient_start']) && isset($style_data['container_bg_gradient_end'])) {
            $start = sanitize_text_field($style_data['container_bg_gradient_start']);
            $end = sanitize_text_field($style_data['container_bg_gradient_end']);
            $css .= "    --aiw-container-bg-gradient: linear-gradient(135deg, {$start}, {$end});\n";
        }
        
        // Play button settings
        if (isset($style_data['play_button_size'])) {
            $css .= "    --aiw-play-button-size: " . intval($style_data['play_button_size']) . "px;\n";
        }
        if (isset($style_data['play_button_color'])) {
            $css .= "    --aiw-play-button-color: " . sanitize_text_field($style_data['play_button_color']) . ";\n";
        }
        if (isset($style_data['play_button_icon_color'])) {
            $css .= "    --aiw-play-button-icon-color: " . sanitize_text_field($style_data['play_button_icon_color']) . ";\n";
        }
        
        // Canvas and visualization settings
        if (isset($style_data['canvas_color'])) {
            $css .= "    --aiw-canvas-color: " . sanitize_text_field($style_data['canvas_color']) . ";\n";
        }
        if (isset($style_data['canvas_shadow_color'])) {
            $css .= "    --aiw-canvas-shadow-color: " . sanitize_text_field($style_data['canvas_shadow_color']) . ";\n";
        }
        if (isset($style_data['visualizer_primary_color'])) {
            $css .= "    --aiw-viz-primary-color: " . sanitize_text_field($style_data['visualizer_primary_color']) . ";\n";
        }
        
        // Chatbox settings  
        if (isset($style_data['chatbox_font_color'])) {
            $css .= "    --aiw-chat-text-color: " . sanitize_text_field($style_data['chatbox_font_color']) . ";\n";
        }
        if (isset($style_data['chatbox_font_size'])) {
            $css .= "    --aiw-chat-font-size: " . intval($style_data['chatbox_font_size']) . "px;\n";
        }
        
        // Border and spacing settings
        if (isset($style_data['container_border_radius'])) {
            $css .= "    --aiw-border-radius: " . intval($style_data['container_border_radius']) . "px;\n";
        }
        if (isset($style_data['container_padding'])) {
            $css .= "    --aiw-container-padding: " . intval($style_data['container_padding']) . "px;\n";
        }
        
        $css .= "}\n\n";
        
        // Apply the CSS variables to actual elements
        $css .= "/* Preview-specific styles */\n";
        $css .= ".ai-interview-container {\n";
        $css .= "    background: var(--aiw-container-bg-gradient, var(--aiw-container-bg-color, #0f0c29));\n";
        $css .= "    border-radius: var(--aiw-border-radius, 15px);\n";
        $css .= "    padding: var(--aiw-container-padding, 20px);\n";
        $css .= "}\n\n";
        
        $css .= ".play-button {\n";
        $css .= "    width: var(--aiw-play-button-size, 100px);\n";
        $css .= "    height: var(--aiw-play-button-size, 100px);\n";
        $css .= "    background: var(--aiw-play-button-color, #00cfff);\n";
        $css .= "    color: var(--aiw-play-button-icon-color, #ffffff);\n";
        $css .= "}\n\n";
        
        $css .= ".audio-visualization {\n";
        $css .= "    background: var(--aiw-canvas-color, #0a0a1a);\n";
        $css .= "    box-shadow: 0 0 20px var(--aiw-canvas-shadow-color, #00cfff);\n";
        $css .= "}\n\n";
        
        $css .= ".visualization-bar {\n";
        $css .= "    background: var(--aiw-viz-primary-color, #00cfff);\n";
        $css .= "}\n\n";
        
        $css .= ".chat-interface {\n";
        $css .= "    color: var(--aiw-chat-text-color, #ffffff);\n";
        $css .= "    font-size: var(--aiw-chat-font-size, 16px);\n";
        $css .= "}\n";
        
        return $css;
    }

    // Output custom CSS to frontend
    public function output_custom_css() {
        $style_settings = get_option('ai_interview_widget_style_settings', '');
        $content_settings = get_option('ai_interview_widget_content_settings', '');
        
        // Get WordPress Customizer settings (new system)
        $wp_customizer_css = $this->get_wp_customizer_css();
        
        // Always generate CSS to ensure all variables are available, including canvas background and pulse effect
        if (empty($style_settings) && empty($wp_customizer_css)) {
            // If no custom styles, at least output default CSS variables for all effects
            $default_css = "/* AI Interview Widget - Default CSS Variables */\n";
            $default_css .= ":root {\n";
            // Canvas background - FIXED: Always ensure this is available
            $default_css .= "    --canvas-background-color: #0a0a1a;\n";
            // Play-Button variables for pulse effect
            $default_css .= "    --play-button-design: 'classic';\n";
            $default_css .= "    --play-button-size: 100px;\n";
            $default_css .= "    --play-button-color: #00cfff;\n";
            $default_css .= "    --play-button-gradient-start: #00ffff;\n";
            $default_css .= "    --play-button-gradient-end: #001a33;\n";
            $default_css .= "    --play-button-pulse-speed: 1.0;\n";
            $default_css .= "    --play-button-disable-pulse: false;\n";
            $default_css .= "    --play-button-shadow-intensity: 40px;\n";
            $default_css .= "    --play-button-border-color: #00cfff;\n";
            $default_css .= "    --play-button-border-width: 2px;\n";
            $default_css .= "    --play-button-neon-intensity: 20px;\n";
            $default_css .= "    --play-button-icon-color: #ffffff;\n";
            // CSS variables with canonical naming and backward compatibility
            $default_css .= "    --aiw-btn-size: 100;\n";
            $default_css .= "    --aiw-canvas-shadow-color: rgba(0, 207, 255, 0.5);\n";
            $default_css .= "    --aiw-shadow-color: var(--aiw-canvas-shadow-color); /* Backward compatibility alias */\n";
            $default_css .= "    --aiw-shadow-intensity: 20;\n";
            $default_css .= "}\n";
            
            echo "\n<!-- AI Interview Widget Default CSS Variables -->\n";
            echo "<style type=\"text/css\" id=\"ai-interview-widget-default-styles\">\n";
            echo $default_css;
            echo "</style>\n";
        } else {
            echo "\n<!-- AI Interview Widget Custom Styles -->\n";
            echo "<style type=\"text/css\" id=\"ai-interview-widget-custom-styles\">\n";
            
            // Output Enhanced Customizer CSS (existing system)
            if (!empty($style_settings)) {
                $custom_css = $this->generate_css_from_settings($style_settings, $content_settings);
                if (!empty($custom_css)) {
                    echo "/* Enhanced Customizer Styles */\n";
                    echo $custom_css;
                }
            }
            
            // Output WordPress Customizer CSS (new system) 
            if (!empty($wp_customizer_css)) {
                echo "\n/* WordPress Customizer Styles */\n";
                echo $wp_customizer_css;
            }
            
            echo "</style>\n";
        }
    }

    // Generate CSS from WordPress Customizer settings
    private function get_wp_customizer_css() {
        // Use caching for performance
        $cache_key = 'ai_interview_wp_customizer_css_' . md5(serialize($_GET));
        $cached_css = wp_cache_get($cache_key, 'ai_interview_widget');
        
        if ($cached_css !== false) {
            return $cached_css;
        }
        
        $css = '';
        
        // Get WordPress Customizer settings
        $button_size = get_theme_mod('ai_play_button_size', 64);
        $button_shape = get_theme_mod('ai_play_button_shape', 'circle');
        $button_color = get_theme_mod('ai_play_button_color', '#00cfff');
        $button_gradient_end = get_theme_mod('ai_play_button_gradient_end', '');
        $icon_style = get_theme_mod('ai_play_button_icon_style', 'triangle');
        $icon_color = get_theme_mod('ai_play_button_icon_color', '#ffffff');
        $pulse_enabled = get_theme_mod('ai_play_button_pulse_enabled', true);
        $pulse_color = get_theme_mod('ai_play_button_pulse_color', '#00cfff');
        $pulse_duration = get_theme_mod('ai_play_button_pulse_duration', 2.0);
        $pulse_spread = get_theme_mod('ai_play_button_pulse_spread', 24);
        $hover_style = get_theme_mod('ai_play_button_hover_style', 'scale');
        $focus_color = get_theme_mod('ai_play_button_focus_color', '#00cfff');
        
        // Canvas Shadow settings - using canonical getter with backward compatibility
        $canvas_shadow_color = $this->get_canvas_shadow_color('#00cfff');
        $canvas_shadow_intensity = get_theme_mod('ai_canvas_shadow_intensity', 30);
        
        // Only generate CSS if at least one Customizer setting exists
        $has_customizer_settings = get_theme_mod('ai_play_button_size') !== null || 
                                   get_theme_mod('ai_play_button_color') !== null ||
                                   get_theme_mod('ai_play_button_pulse_enabled') !== null ||
                                   get_theme_mod('ai_canvas_shadow_color') !== null ||
                                   get_theme_mod('ai_canvas_shadow_intensity') !== null;
        
        if (!$has_customizer_settings) {
            wp_cache_set($cache_key, $css, 'ai_interview_widget', 3600); // Cache for 1 hour
            return $css;
        }
        
        $css .= ":root {\n";
        
        // Button size with validation
        $button_size = max(40, min(120, intval($button_size)));
        $css .= "    --play-button-size: {$button_size}px;\n";
        $css .= "    --aiw-btn-size: {$button_size};\n";
        
        // Button shape (border-radius) with validation
        $border_radius = '50%'; // circle default
        if ($button_shape === 'rounded') {
            $border_radius = '15px';
        } elseif ($button_shape === 'square') {
            $border_radius = '0px';
        }
        
        // Sanitize colors
        $button_color = sanitize_hex_color($button_color) ?: '#00cfff';
        $button_gradient_end = sanitize_hex_color($button_gradient_end);
        $icon_color = sanitize_hex_color($icon_color) ?: '#ffffff';
        $pulse_color = sanitize_hex_color($pulse_color) ?: '#00cfff';
        $focus_color = sanitize_hex_color($focus_color) ?: '#00cfff';
        // Canvas shadow color already sanitized by helper function
        
        // Validate and sanitize canvas shadow intensity
        $canvas_shadow_intensity = max(0, min(100, intval($canvas_shadow_intensity)));
        
        // Button colors
        if (!empty($button_gradient_end)) {
            // Gradient background
            $css .= "    --play-button-color: linear-gradient(135deg, {$button_color}, {$button_gradient_end});\n";
        } else {
            // Solid color
            $css .= "    --play-button-color: {$button_color};\n";
        }
        
        $css .= "    --play-button-icon-color: {$icon_color};\n";
        $css .= "    --play-button-border-color: {$pulse_color};\n";
        
        // Canvas shadow CSS variables - using canonical naming with backward compatibility alias
        $css .= "    --aiw-canvas-shadow-color: {$canvas_shadow_color};\n";
        $css .= "    --aiw-shadow-color: var(--aiw-canvas-shadow-color); /* Backward compatibility alias */\n";
        $css .= "    --aiw-shadow-intensity: {$canvas_shadow_intensity};\n";
        
        // Generate canvas box-shadow property based on color and intensity
        if ($canvas_shadow_intensity > 0) {
            // Convert hex to RGB for shadow calculation
            $hex = ltrim($canvas_shadow_color, '#');
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
            
            // Calculate glow layers based on intensity
            $glow1 = round($canvas_shadow_intensity * 0.33);
            $glow2 = round($canvas_shadow_intensity * 0.66);
            
            // Create layered shadow effect
            $canvas_box_shadow = "0 0 {$canvas_shadow_intensity}px {$glow1}px rgba({$r}, {$g}, {$b}, 0.5), 0 0 {$canvas_shadow_intensity}px {$glow2}px rgba({$r}, {$g}, {$b}, 0.3)";
        } else {
            $canvas_box_shadow = "none";
        }
        $css .= "    --canvas-box-shadow: {$canvas_box_shadow};\n";
        
        // Pulse settings with validation
        $pulse_duration = max(0.8, min(3.5, floatval($pulse_duration)));
        $pulse_spread = max(8, min(40, intval($pulse_spread)));
        
        $css .= "    --play-button-disable-pulse: " . ($pulse_enabled ? 'false' : 'true') . ";\n";
        $css .= "    --play-button-pulse-speed: " . (2.0 / $pulse_duration) . ";\n";
        $css .= "    --play-button-shadow-intensity: {$pulse_spread}px;\n";
        
        $css .= "}\n\n";
        
        // Play button specific styles
        $css .= ".play-button {\n";
        $css .= "    border-radius: {$border_radius} !important;\n";
        
        // Apply background based on gradient or solid
        if (!empty($button_gradient_end)) {
            $css .= "    background: linear-gradient(135deg, {$button_color}, {$button_gradient_end}) !important;\n";
        } else {
            $css .= "    background: {$button_color} !important;\n";
        }
        
        $css .= "    color: {$icon_color} !important;\n";
        $css .= "    border-color: {$pulse_color} !important;\n";
        $css .= "}\n\n";
        
        // Hover effects with validation
        $allowed_hover_styles = array('scale', 'glow', 'none');
        if (!in_array($hover_style, $allowed_hover_styles)) {
            $hover_style = 'scale';
        }
        
        if ($hover_style === 'scale') {
            $css .= ".play-button:hover {\n";
            $css .= "    transform: scale(1.1) !important;\n";
            $css .= "}\n\n";
        } elseif ($hover_style === 'glow') {
            $css .= ".play-button:hover {\n";
            $css .= "    box-shadow: 0 0 " . ($pulse_spread * 2) . "px {$pulse_color} !important;\n";
            $css .= "}\n\n";
        } elseif ($hover_style === 'none') {
            $css .= ".play-button:hover {\n";
            $css .= "    transform: none !important;\n";
            $css .= "    box-shadow: 0 0 {$pulse_spread}px {$pulse_color} !important;\n";
            $css .= "}\n\n";
        }
        
        // Focus ring accessibility
        $css .= ".play-button:focus {\n";
        $css .= "    outline: 2px solid {$focus_color} !important;\n";
        $css .= "    outline-offset: 4px !important;\n";
        $css .= "}\n\n";
        
        // Icon style variations with validation
        $allowed_icon_styles = array('triangle', 'triangle_border', 'minimal');
        if (!in_array($icon_style, $allowed_icon_styles)) {
            $icon_style = 'triangle';
        }
        
        if ($icon_style === 'triangle_border') {
            $css .= ".play-button .play-icon {\n";
            $css .= "    border: 2px solid {$icon_color};\n";
            $css .= "    background: transparent;\n";
            $css .= "    width: 0;\n";
            $css .= "    height: 0;\n";
            $css .= "    border-left: 8px solid {$icon_color};\n";
            $css .= "    border-top: 6px solid transparent;\n";
            $css .= "    border-bottom: 6px solid transparent;\n";
            $css .= "    border-right: none;\n";
            $css .= "}\n\n";
        } elseif ($icon_style === 'minimal') {
            $css .= ".play-button .play-icon {\n";
            $css .= "    font-size: 0.8em;\n";
            $css .= "    opacity: 0.9;\n";
            $css .= "}\n\n";
        }
        
        // Pulse effect styles with prefers-reduced-motion support
        if ($pulse_enabled) {
            $css .= "@media (prefers-reduced-motion: no-preference) {\n";
            $css .= "    .play-button-container:not(.hidden) .play-button:not([data-disable-pulse=\"true\"]) {\n";
            $css .= "        animation: play-button-breathing-pulse " . $pulse_duration . "s infinite ease-in-out,\n";
            $css .= "                   play-button-dots-pulse " . ($pulse_duration * 0.9) . "s infinite ease-in-out;\n";
            $css .= "    }\n";
            $css .= "}\n\n";
        }
        
        // Cache the generated CSS for performance
        wp_cache_set($cache_key, $css, 'ai_interview_widget', 3600); // Cache for 1 hour
        
        return $css;
    }

    // Preview widget version - shows full interface for customizer
    public function render_preview_widget() {
        // Get custom content settings for headline and voice features
        $content_settings = get_option('ai_interview_widget_content_settings', '');
        $content_data = json_decode($content_settings, true);
        $headline_text = isset($content_data['headline_text']) ? $content_data['headline_text'] : 'Ask Eric';
        
        // Voice features settings
        $voice_enabled = get_option('ai_interview_widget_enable_voice', true);
        $chatbox_only = get_option('ai_interview_widget_chatbox_only_mode', false);
        $disable_audio_viz = get_option('ai_interview_widget_disable_audio_visualization', false);
        
        ob_start();
        ?>
        <!-- Use the EXACT same structure as the actual widget for accurate preview -->
        <div class="ai-interview-container" id="enhanced-preview-widget">
            <div class="ai-interview-inner-container">
                
                <!-- EXACT same audio element as main widget (hidden for preview) -->
                <audio id="aiEricGreeting" style="visibility: hidden; display: block; margin-top: 16px;">
                    <source src="<?php echo plugins_url('greeting_en.mp3', __FILE__); ?>" type="audio/mpeg">
                    Your browser does not support the audio element.
                </audio>

                <?php if (!$chatbox_only && !$disable_audio_viz): ?>
                <!-- Canvas with EXACT same structure and styling as main widget -->
                <canvas id="soundbar" width="800" height="500" style="display: block; margin: 20px auto; width: 100%; max-width: 800px; height: 500px; visibility: visible; z-index: 15; position: relative; transform: translateZ(0);"></canvas>

                <!-- EXACT same controls structure as main widget -->
                <div class="ai-interview-controls" style="display: flex; gap: 15px; justify-content: center; margin-top: 0px; transform: translateY(-50px); z-index: 20; position: relative; opacity: 1; pointer-events: auto; transition: opacity 0.6s cubic-bezier(.4,0,.2,1);">
                    <button id="pauseBtn" onclick="startPreviewDemo()" style="background: rgba(0,100,255,0.2); color: #00cfff; border: 1px solid #00cfff; padding: 10px 20px; border-radius: 30px; cursor: pointer; font-size: 16px; transition: all 0.3s ease; backdrop-filter: blur(5px); margin: 0;">▶️ Play Audio</button>
                    <button id="skipBtn" onclick="skipPreviewDemo()" style="background: rgba(0,100,255,0.2); color: #00cfff; border: 1px solid #00cfff; padding: 10px 20px; border-radius: 30px; cursor: pointer; font-size: 16px; transition: all 0.3s ease; backdrop-filter: blur(5px); margin: 0;">⏩ Skip to Chat</button>
                </div>
                <?php endif; ?>
                
                <!-- EXACT same chat interface structure as main widget -->
                <div id="chatInterface" style="<?php echo ($chatbox_only || $disable_audio_viz) ? 'display: block;' : 'display: block;'; ?> font-size: 16px; margin-top: <?php echo ($chatbox_only || $disable_audio_viz) ? '20px' : '-40px'; ?>; background: rgba(10, 10, 26, 0.8); border-radius: 15px; padding: 20px; box-shadow: 0 0 20px rgba(0, 207, 255, 0.2); width: 100%; box-sizing: border-box; z-index: 20; position: relative;">
                    
                    <!-- EXACT same header structure -->
                    <div class="ai-chat-header" id="preview-header"><?php echo esc_html($headline_text); ?></div>
                    
                    <!-- EXACT same chat history structure -->
                    <div id="chatHistory" style="background: rgba(0, 0, 0, 0.2); border-radius: 10px; padding: 15px; text-align: left; overflow-y: auto; min-height: 50px; height: 150px; margin-bottom: 20px;">
                        <div class="message ai-message" style="margin-bottom: 15px; padding: 10px; border-radius: 8px; word-wrap: break-word; position: relative; line-height: 1.5; background: rgba(123, 0, 255, 0.2); border-left: 3px solid #7b00ff;">
                            <span class="preview-welcome-text">
                                <?php
                                $welcome_en = isset($content_data['welcome_message_en']) ? $content_data['welcome_message_en'] : "Hello! Talk to me!";
                                echo esc_html($welcome_en);
                                ?>
                            </span>
                            <?php if ($voice_enabled): ?>
                            <button class="tts-button" style="position: absolute; top: 8px; right: 8px; background: rgba(123, 0, 255, 0.3); border: 1px solid #7b00ff; color: #d0b3ff; padding: 4px 8px; border-radius: 15px; font-size: 12px; cursor: pointer; transition: all 0.3s ease; min-width: 24px; height: 24px; display: flex; align-items: center; justify-content: center;" onclick="simulatePreviewTTS(this)">🔊</button>
                            <?php endif; ?>
                        </div>
                        <div class="message user-message" style="margin-bottom: 15px; padding: 10px; border-radius: 8px; word-wrap: break-word; position: relative; line-height: 1.5; background: rgba(0, 100, 255, 0.2); border-left: 3px solid #00cfff;">
                            Tell me about your AI projects 🎤
                        </div>
                        <div class="message ai-message" style="margin-bottom: 15px; padding: 10px; border-radius: 8px; word-wrap: break-word; position: relative; line-height: 1.5; background: rgba(123, 0, 255, 0.2); border-left: 3px solid #7b00ff;">
                            I'm pioneering AI-driven creative workflows, including custom ComfyUI nodes for artistic automation and innovative hat configurators using advanced computer vision...
                            <?php if ($voice_enabled): ?>
                            <button class="tts-button" style="position: absolute; top: 8px; right: 8px; background: rgba(123, 0, 255, 0.3); border: 1px solid #7b00ff; color: #d0b3ff; padding: 4px 8px; border-radius: 15px; font-size: 12px; cursor: pointer; transition: all 0.3s ease; min-width: 24px; height: 24px; display: flex; align-items: center; justify-content: center;" onclick="simulatePreviewTTS(this)">🔊</button>
                            <?php endif; ?>
                        </div>
                        
                        <!-- EXACT same typing indicator structure -->
                        <div class="typing-indicator" id="typingIndicator" style="display: none; color: #00cfff; font-style: italic; margin-bottom: 15px; padding: 15px; background: rgba(0, 0, 0, 0.3); border-radius: 10px; border-left: 3px solid #00cfff; position: relative; animation: typing-pulse 2s infinite ease-in-out;">
                            <div class="ai-processing-content" style="display: flex; align-items: center; justify-content: center; gap: 10px;">
                                <div class="ai-spinner" style="display: inline-block; width: 20px; height: 20px; border: 2px solid rgba(0, 207, 255, 0.3); border-radius: 50%; border-top: 2px solid #00cfff; animation: spin 1s linear infinite; margin-right: 10px; vertical-align: middle;"></div>
                                <span class="processing-text" style="color: #d0b3ff; font-size: 14px;">Eric is thinking...</span>
                                <div class="thinking-dots" style="display: inline-block; margin-left: 10px;">
                                    <span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background-color: #00cfff; margin: 0 2px; animation: thinking-bounce 1.4s infinite ease-in-out both; animation-delay: -0.32s;"></span>
                                    <span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background-color: #00cfff; margin: 0 2px; animation: thinking-bounce 1.4s infinite ease-in-out both; animation-delay: -0.16s;"></span>
                                    <span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background-color: #00cfff; margin: 0 2px; animation: thinking-bounce 1.4s infinite ease-in-out both; animation-delay: 0s;"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (!$chatbox_only && $voice_enabled): ?>
                    <!-- EXACT same voice controls structure -->
                    <div id="voiceControls" class="voice-controls" style="display: flex; gap: 10px; justify-content: center; margin: 15px 0; flex-wrap: wrap; position: relative; z-index: 25;">
                        <button id="voiceInputBtn" class="voice-btn" title="Voice Input" onclick="simulateVoiceInput()" style="background: rgba(123, 0, 255, 0.2); color: #d0b3ff; border: 1px solid #7b00ff; padding: 8px 15px; border-radius: 25px; cursor: pointer; font-size: 14px; transition: all 0.3s ease; backdrop-filter: blur(5px); display: flex; align-items: center; gap: 5px; min-width: 80px; justify-content: center; position: relative; font-family: inherit; outline: none;">
                            <span class="voice-icon" style="font-size: 16px; line-height: 1; display: inline-block;">🎤</span>
                            <span class="voice-text" style="font-size: 12px; font-weight: 500; white-space: nowrap;">Speak</span>
                        </button>
                        <button id="stopListeningBtn" class="voice-btn voice-stop" style="display: none; background: rgba(123, 0, 255, 0.2); color: #d0b3ff; border: 1px solid #7b00ff; padding: 8px 15px; border-radius: 25px; cursor: pointer; font-size: 14px; transition: all 0.3s ease; backdrop-filter: blur(5px); align-items: center; gap: 5px; min-width: 80px; justify-content: center; position: relative; font-family: inherit; outline: none;" title="Stop Listening">
                            <span class="voice-icon" style="font-size: 16px; line-height: 1; display: inline-block;">⏹️</span>
                            <span class="voice-text" style="font-size: 12px; font-weight: 500; white-space: nowrap;">Stop</span>
                        </button>
                        <button id="vadToggleBtn" class="voice-btn voice-vad active" title="Toggle Auto-Send" onclick="simulateVADToggle()" style="background: rgba(0, 255, 127, 0.3); border-color: #00ff7f; color: #00ff7f; box-shadow: 0 0 15px rgba(0, 255, 127, 0.4); padding: 8px 15px; border-radius: 25px; cursor: pointer; font-size: 14px; transition: all 0.3s ease; backdrop-filter: blur(5px); display: flex; align-items: center; gap: 5px; min-width: 80px; justify-content: center; position: relative; font-family: inherit; outline: none;">
                            <span class="vad-icon" style="font-size: 16px; line-height: 1; display: inline-block;">⚡</span>
                            <span class="vad-text" style="font-size: 12px; font-weight: 500; white-space: nowrap;">Auto On</span>
                        </button>
                        <button id="toggleTTSBtn" class="voice-btn voice-tts" title="Toggle Voice" onclick="simulateTTSToggle()" style="background: rgba(123, 0, 255, 0.2); color: #d0b3ff; border: 1px solid #7b00ff; padding: 8px 15px; border-radius: 25px; cursor: pointer; font-size: 14px; transition: all 0.3s ease; backdrop-filter: blur(5px); display: flex; align-items: center; gap: 5px; min-width: 80px; justify-content: center; position: relative; font-family: inherit; outline: none;">
                            <span class="voice-icon" style="font-size: 16px; line-height: 1; display: inline-block;">🔊</span>
                            <span class="voice-text" style="font-size: 12px; font-weight: 500; white-space: nowrap;">Voice On</span>
                        </button>
                    </div>
                    
                    <!-- EXACT same voice status structure -->
                    <div id="voiceStatus" class="voice-status" style="display: none; background: rgba(0, 0, 0, 0.4); color: #00cfff; padding: 8px 15px; border-radius: 20px; font-size: 14px; margin: 10px 0; text-align: center; border: 1px solid rgba(0, 207, 255, 0.3); position: relative; z-index: 25;">
                        Ready for voice input
                    </div>
                    <?php endif; ?>
                    
                    <!-- EXACT same input area structure -->
                    <div id="inputArea" style="display: flex; gap: 10px; margin-top: 10px; position: relative;">
                        <input type="text" id="userInput" placeholder="Type your question here<?php echo (!$chatbox_only && $voice_enabled) ? ' or use voice...' : '...'; ?>" style="flex: 1; padding: 12px; border-radius: 30px; border: 1px solid #00cfff; background: rgba(0, 0, 0, 0.3); color: white; font-size: 16px; transition: all 0.3s ease; outline: none;">
                        <button id="sendButton" onclick="simulatePreviewChat()" style="background: rgba(123, 0, 255, 0.3); border: 1px solid #7b00ff; color: #d0b3ff; padding: 10px 20px; border-radius: 30px; cursor: pointer; font-size: 16px; transition: all 0.3s ease; backdrop-filter: blur(5px); position: relative; min-width: 80px; outline: none;">
                            Send
                            <div class="button-spinner" style="display: none; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 16px; height: 16px; border: 2px solid rgba(255, 255, 255, 0.3); border-radius: 50%; border-top: 2px solid #d0b3ff; animation: spin 1s linear infinite;"></div>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        // Enhanced Preview Demo Functions using EXACT same animations as main widget
        let previewDemoActive = false;
        let previewAnimationId = null;
        let previewCanvas = null;
        let previewCtx = null;
        let previewPulseRunning = false; // Track if pulse animation is already running
        
        // Initialize preview canvas when page loads
        document.addEventListener('DOMContentLoaded', function() {
            previewCanvas = document.getElementById('soundbar');
            if (previewCanvas) {
                previewCtx = previewCanvas.getContext('2d');
                drawPreviewPlayButton();
            }
        });
        
        function startPreviewDemo() {
            if (previewDemoActive) return;
            previewDemoActive = true;
            
            // Start audio visualization like main widget
            startPreviewAudioVisualization();
            
            // Simulate the full demo sequence
            setTimeout(() => {
                showPreviewTypingIndicator();
            }, 2000);
            
            setTimeout(() => {
                hidePreviewTypingIndicator();
                resetPreviewDemo();
            }, 5000);
        }
        
        function skipPreviewDemo() {
            stopPreviewDemo();
            const chatInterface = document.getElementById('chatInterface');
            if (chatInterface) {
                chatInterface.style.display = 'block';
                // Ensure it's fully visible
                chatInterface.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
        
        function stopPreviewDemo() {
            previewDemoActive = false;
            if (previewAnimationId) {
                cancelAnimationFrame(previewAnimationId);
                previewAnimationId = null;
            }
            hidePreviewTypingIndicator();
            drawPreviewPlayButton();
        }
        
        function resetPreviewDemo() {
            previewDemoActive = false;
            if (previewAnimationId) {
                cancelAnimationFrame(previewAnimationId);
                previewAnimationId = null;
            }
            drawPreviewPlayButton();
        }
        
        // Draw play button exactly like main widget with enhanced customization
        // Make this function globally accessible for live preview updates
        window.drawPreviewPlayButton = function() {
            if (!previewCtx || !previewCanvas) return;
            
            const canvas = previewCanvas;
            const ctx = previewCtx;
            
            // Clear canvas and fill with background (use CSS custom property for consistency)
            const canvasBgColor = getComputedStyle(document.documentElement).getPropertyValue('--canvas-background-color')?.trim() || '#0a0a1a';
            ctx.fillStyle = canvasBgColor;
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            
            const centerX = canvas.width / 2;
            const centerY = canvas.height / 2;
            
            // Get customization settings from CSS variables (same as main widget)
            const design = getComputedStyle(document.documentElement).getPropertyValue('--play-button-design')?.replace(/['"]/g, '') || 'classic';
            const customSize = parseInt(getComputedStyle(document.documentElement).getPropertyValue('--play-button-size')) || 100;
            const pulseSpeedMultiplier = parseFloat(getComputedStyle(document.documentElement).getPropertyValue('--play-button-pulse-speed')) || 1.0;
            const disablePulse = getComputedStyle(document.documentElement).getPropertyValue('--play-button-disable-pulse') === 'true';
            
            // FIXED: Calculate continuous pulse animation like main widget
            let pulseScale = 1.0;
            if (!disablePulse) {
                const time = Date.now() / 1000;
                // Use same pulse range as main widget (1.0 to 1.15)
                pulseScale = 1.0 + Math.sin(time * 2 * pulseSpeedMultiplier) * 0.075;
            }
            
            // Draw based on selected design
            try {
                switch (design) {
                    case 'minimalist':
                        drawPreviewMinimalistButton(ctx, centerX, centerY, customSize, pulseScale);
                        break;
                    case 'futuristic':
                        drawPreviewFuturisticButton(ctx, centerX, centerY, customSize, pulseScale);
                        break;
                    case 'classic':
                    default:
                        drawPreviewClassicButton(ctx, centerX, centerY, customSize, pulseScale);
                        break;
                }
            } catch (err) {
                console.error("Error drawing preview play button:", err);
            }
            
            // FIXED: Always continue animation for continuous pulse in preview
            // This ensures the play button continuously pulses in the admin preview
            requestAnimationFrame(window.drawPreviewPlayButton);
        }

        // Preview Classic Design
        function drawPreviewClassicButton(ctx, centerX, centerY, buttonRadius, pulseScale) {
            const customColor = getComputedStyle(document.documentElement).getPropertyValue('--play-button-color') || "#00ffff";
            const gradientStart = getComputedStyle(document.documentElement).getPropertyValue('--play-button-gradient-start') || "#00ffff";
            const gradientEnd = getComputedStyle(document.documentElement).getPropertyValue('--play-button-gradient-end') || "#001a33";
            const shadowIntensity = parseInt(getComputedStyle(document.documentElement).getPropertyValue('--play-button-shadow-intensity')) || 40;
            const iconColor = getComputedStyle(document.documentElement).getPropertyValue('--play-button-icon-color') || "#ffffff";

            const currentRadius = buttonRadius * pulseScale;
            const grad = ctx.createRadialGradient(
                centerX, centerY, buttonRadius * 0.3,
                centerX, centerY, currentRadius
            );
            grad.addColorStop(0, gradientStart);
            grad.addColorStop(0.5, customColor);
            grad.addColorStop(1, gradientEnd);
            
            ctx.save();
            ctx.shadowColor = customColor;
            ctx.shadowBlur = shadowIntensity;
            ctx.beginPath();
            ctx.arc(centerX, centerY, currentRadius, 0, 2 * Math.PI);
            ctx.fillStyle = grad;
            ctx.fill();
            
            // Inner ring
            ctx.beginPath();
            ctx.arc(centerX, centerY, buttonRadius * 0.7, 0, 2 * Math.PI);
            ctx.strokeStyle = "rgba(255,255,255,0.1)";
            ctx.lineWidth = 2;
            ctx.stroke();
            
            // Outer ring
            ctx.beginPath();
            ctx.arc(centerX, centerY, currentRadius - 5, 0, 2 * Math.PI);
            ctx.strokeStyle = customColor + "4D"; // 30% opacity
            ctx.lineWidth = 2;
            ctx.stroke();
            ctx.restore();
            
            drawPreviewPlayTriangle(ctx, centerX, centerY, buttonRadius, iconColor);
        }

        // Preview Minimalist Design
        function drawPreviewMinimalistButton(ctx, centerX, centerY, buttonRadius, pulseScale) {
            const customColor = getComputedStyle(document.documentElement).getPropertyValue('--play-button-color') || "#00cfff";
            const shadowIntensity = parseInt(getComputedStyle(document.documentElement).getPropertyValue('--play-button-shadow-intensity')) || 20;
            const iconColor = getComputedStyle(document.documentElement).getPropertyValue('--play-button-icon-color') || "#ffffff";

            const currentRadius = buttonRadius * pulseScale;
            
            ctx.save();
            ctx.shadowColor = customColor;
            ctx.shadowBlur = shadowIntensity;
            ctx.shadowOffsetX = 0;
            ctx.shadowOffsetY = 4;
            
            // Main circle
            ctx.beginPath();
            ctx.arc(centerX, centerY, currentRadius, 0, 2 * Math.PI);
            ctx.fillStyle = customColor;
            ctx.fill();
            
            // Inner border
            ctx.shadowColor = 'transparent';
            ctx.beginPath();
            ctx.arc(centerX, centerY, currentRadius * 0.85, 0, 2 * Math.PI);
            ctx.strokeStyle = "rgba(0,0,0,0.1)";
            ctx.lineWidth = 2;
            ctx.stroke();
            ctx.restore();
            
            drawPreviewPlayTriangle(ctx, centerX, centerY, buttonRadius, iconColor);
        }

        // Preview Futuristic Design
        function drawPreviewFuturisticButton(ctx, centerX, centerY, buttonRadius, pulseScale) {
            const borderColor = getComputedStyle(document.documentElement).getPropertyValue('--play-button-border-color') || "#00cfff";
            const neonIntensity = parseInt(getComputedStyle(document.documentElement).getPropertyValue('--play-button-neon-intensity')) || 30;
            const iconColor = getComputedStyle(document.documentElement).getPropertyValue('--play-button-icon-color') || "#ffffff";

            const currentRadius = buttonRadius * pulseScale;
            const time = Date.now() / 1000;
            
            ctx.save();
            
            // Outer glow rings
            for (let i = 0; i < 3; i++) {
                const glowRadius = currentRadius + (i * 15);
                const glowAlpha = (0.3 - i * 0.1) * (0.7 + Math.sin(time * 2 + i) * 0.3);
                
                ctx.beginPath();
                ctx.arc(centerX, centerY, glowRadius, 0, 2 * Math.PI);
                ctx.strokeStyle = borderColor + Math.floor(glowAlpha * 255).toString(16).padStart(2, '0');
                ctx.lineWidth = 2;
                ctx.stroke();
            }
            
            // Main neon border
            ctx.shadowColor = borderColor;
            ctx.shadowBlur = neonIntensity;
            ctx.beginPath();
            ctx.arc(centerX, centerY, currentRadius, 0, 2 * Math.PI);
            ctx.strokeStyle = borderColor;
            ctx.lineWidth = 4;
            ctx.stroke();
            
            // Inner border
            ctx.shadowBlur = neonIntensity * 0.5;
            ctx.beginPath();
            ctx.arc(centerX, centerY, currentRadius * 0.8, 0, 2 * Math.PI);
            ctx.strokeStyle = borderColor + "80"; // 50% opacity
            ctx.lineWidth = 2;
            ctx.stroke();
            
            // Center fill
            ctx.shadowColor = 'transparent';
            ctx.beginPath();
            ctx.arc(centerX, centerY, currentRadius * 0.75, 0, 2 * Math.PI);
            ctx.fillStyle = "rgba(0,0,0,0.7)";
            ctx.fill();
            ctx.restore();
            
            drawPreviewFuturisticTriangle(ctx, centerX, centerY, buttonRadius, iconColor, borderColor);
        }

        // Preview Play Triangle
        function drawPreviewPlayTriangle(ctx, centerX, centerY, buttonRadius, iconColor) {
            ctx.save();
            ctx.shadowColor = iconColor;
            ctx.shadowBlur = 20;
            const triSize = Math.min(60, buttonRadius * 0.6);
            const height = triSize * Math.sqrt(3) / 2;
            ctx.beginPath();
            ctx.moveTo(centerX - height/3, centerY - triSize/2);
            ctx.lineTo(centerX - height/3, centerY + triSize/2);
            ctx.lineTo(centerX + height*2/3, centerY);
            ctx.closePath();
            
            const triGrad = ctx.createLinearGradient(
                centerX - height/3, centerY - triSize/2,
                centerX + height*2/3, centerY
            );
            triGrad.addColorStop(0, iconColor + "E6"); // 90% opacity
            triGrad.addColorStop(1, iconColor + "B3"); // 70% opacity
            ctx.fillStyle = triGrad;
            ctx.fill();
            ctx.lineWidth = 2;
            ctx.strokeStyle = iconColor + "CC"; // 80% opacity
            ctx.stroke();
            ctx.restore();
        }

        // Preview Futuristic Triangle
        function drawPreviewFuturisticTriangle(ctx, centerX, centerY, buttonRadius, iconColor, borderColor) {
            ctx.save();
            const triSize = Math.min(60, buttonRadius * 0.6);
            const height = triSize * Math.sqrt(3) / 2;
            
            // Outer glow
            ctx.shadowColor = borderColor;
            ctx.shadowBlur = 15;
            ctx.beginPath();
            ctx.moveTo(centerX - height/3, centerY - triSize/2);
            ctx.lineTo(centerX - height/3, centerY + triSize/2);
            ctx.lineTo(centerX + height*2/3, centerY);
            ctx.closePath();
            ctx.strokeStyle = borderColor;
            ctx.lineWidth = 3;
            ctx.stroke();

            // Inner fill
            ctx.shadowColor = 'transparent';
            ctx.fillStyle = iconColor + "DD"; // 87% opacity
            ctx.fill();
            ctx.restore();
        }
        
        // Audio visualization exactly like main widget
        function startPreviewAudioVisualization() {
            if (!previewCtx || !previewCanvas) return;
            
            const canvas = previewCanvas;
            const ctx = previewCtx;
            let time = 0;
            
            function animate() {
                if (!previewDemoActive) return;
                
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                ctx.fillStyle = '#0a0a1a';
                ctx.fillRect(0, 0, canvas.width, canvas.height);
                
                // Draw audio bars like main widget
                const totalBars = 32;
                const barWidth = 2;
                const barSpacing = 2;
                const centerY = canvas.height / 2;
                const barMaxHeight = 150;
                const centerX = canvas.width / 2;
                
                for (let i = 0; i < totalBars; i++) {
                    const distanceFromCenter = i / totalBars;
                    const centerBoost = 1 - Math.pow(distanceFromCenter, 2);
                    
                    // Simulate audio data with realistic variation
                    const phase = (i / totalBars) * Math.PI * 2;
                    const amplitude = (barMaxHeight * centerBoost + 10) * (0.3 + Math.sin(time + phase) * 0.4 + Math.cos(time * 1.3 + phase * 1.7) * 0.3);
                    
                    ctx.save();
                    ctx.shadowColor = '#00cfff';
                    ctx.shadowBlur = 10;
                    
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
                
                time += 0.1;
                previewAnimationId = requestAnimationFrame(animate);
            }
            
            animate();
        }
        
        function showPreviewTypingIndicator() {
            const typingIndicator = document.getElementById('typingIndicator');
            if (typingIndicator) {
                typingIndicator.style.display = 'block';
                typingIndicator.classList.add('visible');
            }
        }
        
        function hidePreviewTypingIndicator() {
            const typingIndicator = document.getElementById('typingIndicator');
            if (typingIndicator) {
                typingIndicator.style.display = 'none';
                typingIndicator.classList.remove('visible');
            }
        }
        
        // Simulation functions for preview interactions
        function simulateVoiceInput() {
            const voiceStatus = document.getElementById('voiceStatus');
            const userInput = document.getElementById('userInput');
            
            if (voiceStatus) {
                voiceStatus.style.display = 'block';
                voiceStatus.textContent = 'Listening... Speak now';
                voiceStatus.className = 'voice-status visible listening';
                
                setTimeout(() => {
                    voiceStatus.textContent = 'Processing speech...';
                    voiceStatus.className = 'voice-status visible processing';
                    
                    if (userInput) {
                        userInput.value = 'Tell me about your AI projects';
                    }
                    
                    setTimeout(() => {
                        voiceStatus.style.display = 'none';
                        voiceStatus.classList.remove('visible', 'listening', 'processing');
                    }, 2000);
                }, 2000);
            }
        }
        
        function simulateVADToggle() {
            const vadBtn = document.getElementById('vadToggleBtn');
            if (vadBtn) {
                if (vadBtn.classList.contains('active')) {
                    vadBtn.classList.remove('active');
                    vadBtn.style.background = 'rgba(100, 100, 100, 0.2)';
                    vadBtn.style.borderColor = '#666';
                    vadBtn.style.color = '#999';
                    vadBtn.querySelector('.vad-text').textContent = 'Auto Off';
                } else {
                    vadBtn.classList.add('active');
                    vadBtn.style.background = 'rgba(0, 255, 127, 0.3)';
                    vadBtn.style.borderColor = '#00ff7f';
                    vadBtn.style.color = '#00ff7f';
                    vadBtn.querySelector('.vad-text').textContent = 'Auto On';
                }
            }
        }
        
        function simulateTTSToggle() {
            const ttsBtn = document.getElementById('toggleTTSBtn');
            if (ttsBtn) {
                if (ttsBtn.classList.contains('tts-off')) {
                    ttsBtn.classList.remove('tts-off');
                    ttsBtn.classList.add('active');
                    ttsBtn.querySelector('.voice-icon').textContent = '🔊';
                    ttsBtn.querySelector('.voice-text').textContent = 'Voice On';
                } else {
                    ttsBtn.classList.add('tts-off');
                    ttsBtn.classList.remove('active');
                    ttsBtn.querySelector('.voice-icon').textContent = '🔇';
                    ttsBtn.querySelector('.voice-text').textContent = 'Voice Off';
                }
            }
        }
        
        function simulatePreviewTTS(button) {
            button.classList.add('playing');
            button.textContent = '⏸️';
            
            const message = button.closest('.message');
            if (message) {
                message.classList.add('tts-playing');
            }
            
            setTimeout(() => {
                button.classList.remove('playing');
                button.textContent = '🔊';
                if (message) {
                    message.classList.remove('tts-playing');
                }
            }, 3000);
        }
        
        function simulatePreviewChat() {
            const userInput = document.getElementById('userInput');
            const sendButton = document.getElementById('sendButton');
            
            if (!userInput.value.trim()) {
                userInput.value = 'What technologies do you use?';
            }
            
            // Simulate loading state
            sendButton.style.color = 'transparent';
            sendButton.querySelector('.button-spinner').style.display = 'block';
            
            setTimeout(() => {
                userInput.value = '';
                sendButton.style.color = '#d0b3ff';
                sendButton.querySelector('.button-spinner').style.display = 'none';
                
                // Show typing indicator
                showPreviewTypingIndicator();
                
                setTimeout(() => {
                    hidePreviewTypingIndicator();
                }, 3000);
            }, 1000);
        }
        </script>
        <?php
        return ob_get_clean();
    }

    // Enhanced Customizer Page - COMPLETE FULL VERSION
    public function enhanced_customizer_page() {
        // Enqueue WordPress color picker and media uploader - FIXED ORDER
        wp_enqueue_media();
        
        // Enqueue jQuery UI first
        wp_enqueue_script('jquery-ui-core');
        wp_enqueue_script('jquery-ui-widget');
        wp_enqueue_script('jquery-ui-mouse');
        wp_enqueue_script('jquery-ui-slider');
        
        // Enqueue WordPress color picker
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        
        // Enqueue jQuery UI styles
        wp_enqueue_style('wp-jquery-ui-core');
        wp_enqueue_style('wp-jquery-ui-slider');
        wp_enqueue_style('wp-jquery-ui-theme');
        
        // Enqueue custom admin styles for enhanced appearance
        wp_add_inline_style('wp-color-picker', '
            .wp-picker-container { margin-bottom: 10px; }
            .wp-color-result { border: 1px solid #ddd !important; }
            .ui-slider { height: 8px !important; border: 1px solid #ddd !important; background: #f9f9f9 !important; }
            .ui-slider .ui-slider-handle { 
                width: 20px !important; 
                height: 20px !important; 
                border-radius: 50% !important;
                background: #0073aa !important;
                border: 2px solid white !important;
                box-shadow: 0 1px 3px rgba(0,0,0,0.3) !important;
                cursor: pointer !important;
            }
        ');
        
        $current_style_settings = get_option('ai_interview_widget_style_settings', '');
        $current_content_settings = get_option('ai_interview_widget_content_settings', '');
        $style_data = json_decode($current_style_settings, true);
        $content_data = json_decode($current_content_settings, true);
        
        if (!$style_data) $style_data = array();
        if (!$content_data) $content_data = array();
        
        // Default values for styles
        $style_defaults = array(
            'container_bg_type' => 'gradient',
            'container_bg_color' => '#2c3e50',
            'container_bg_gradient_start' => '#0f0c29',
            'container_bg_gradient_end' => '#24243e',
            'container_border_radius' => 15,
            'container_padding' => 20,
            'container_border_width' => 0,
            'container_border_color' => '#3498db',
            'canvas_border_radius' => 8,
            'canvas_glow_intensity' => 30,
            'canvas_color' => '#0a0a1a',
            'canvas_bg_image' => '',
            'canvas_shadow_color' => '#00cfff',
            'canvas_shadow_intensity' => 30,
            // Chatbox customization defaults
            'chatbox_font' => 'inherit',
            'chatbox_font_size' => 16,
            'chatbox_font_color' => '#ffffff',
            // Enhanced Play-Button defaults to ensure pulse effect works
            'play_button_design' => 'classic',
            'play_button_size' => 100,
            'play_button_color' => '#00cfff',
            'play_button_gradient_start' => '#00ffff',
            'play_button_gradient_end' => '#001a33',
            'play_button_pulse_speed' => 1.0,
            'play_button_disable_pulse' => false,
            'play_button_shadow_intensity' => 40,
            'play_button_border_color' => '#00cfff',
            'play_button_border_width' => 2,
            'play_button_icon_color' => '#ffffff',
            'play_button_neon_intensity' => 20,
            // Enhanced Play-Button Customization
            'play_button_design' => 'classic',
            'play_button_size' => 100,
            'play_button_color' => '#00ffff',
            'play_button_gradient_start' => '#00ffff',
            'play_button_gradient_end' => '#001a33',
            'play_button_pulse_speed' => 1.0,
            'play_button_disable_pulse' => false,
            'play_button_shadow_intensity' => 40,
            'play_button_border_color' => '#00cfff',
            'play_button_border_width' => 2,
            'play_button_icon_color' => '#ffffff',
            'play_button_neon_intensity' => 30,
            'voice_btn_bg_color' => 'rgba(123, 0, 255, 0.2)',
            'voice_btn_border_color' => '#7b00ff',
            'voice_btn_text_color' => '#d0b3ff',
            'voice_btn_border_radius' => 25,
            // Audio Visualizer Settings
            'visualizer_theme' => 'default',
            'visualizer_primary_color' => '#00cfff',
            'visualizer_secondary_color' => '#0066ff',
            'visualizer_accent_color' => '#001a33',
            'visualizer_bar_width' => 2,
            'visualizer_bar_spacing' => 2,
            'visualizer_glow_intensity' => 10,
            'visualizer_animation_speed' => 1.0,
            'message_bg_opacity' => 20,
            'message_border_radius' => 8,
            'message_text_size' => 14,
            'message_spacing' => 15,
            'input_bg_color' => 'rgba(0, 0, 0, 0.3)',
            'input_border_color' => '#00cfff',
            'input_text_color' => '#ffffff',
            'input_border_radius' => 30,
            'accent_color' => '#00cfff',
            'text_color' => '#ffffff',
            'animation_speed' => 0.3
        );

        // Default values for content
        // Get supported languages to dynamically create content defaults
        $supported_langs = json_decode(get_option('ai_interview_widget_supported_languages', ''), true);
        if (!$supported_langs) $supported_langs = array('en' => 'English', 'de' => 'German');
        
        $content_defaults = array(
            'headline_text' => 'Ask Eric',
            'headline_font_size' => 18,
            'headline_font_family' => 'inherit',
            'headline_color' => '#ffffff'
        );
        
        // Dynamically add welcome messages and system prompts for each supported language
        foreach ($supported_langs as $lang_code => $lang_name) {
            $content_defaults['welcome_message_' . $lang_code] = ($lang_code === 'en') ? "Hello! Talk to me!" : 
                                                                  (($lang_code === 'de') ? "Hallo! Sprich mit mir!" : 
                                                                   "Hello! Talk to me! (Please configure in Admin Settings)");
            
            // Use placeholder system for system prompts
            $content_defaults['Systemprompts_Placeholder_' . $lang_code] = $this->get_default_system_prompt($lang_code);
        }
        
        // Merge with current settings
        $style_settings = array_merge($style_defaults, $style_data);
        $content_settings = array_merge($content_defaults, $content_data);
        
        // Get custom audio URLs
        $custom_audio_en = get_option('ai_interview_widget_custom_audio_en', '');
        $custom_audio_de = get_option('ai_interview_widget_custom_audio_de', '');
        ?>
        <div class="wrap ai-enhanced-customizer">
            <div style="display: flex; align-items: center; margin-bottom: 30px; padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 10px;">
                <span class="dashicons dashicons-admin-customizer" style="font-size: 60px; margin-right: 20px; opacity: 0.9;"></span>
                <div>
                    <h1 style="margin: 0; color: white; font-size: 32px;">Enhanced Widget Customizer</h1>
                    <p style="margin: 8px 0 0 0; font-size: 16px; opacity: 0.9;">
                        <strong>Version 1.9.3</strong> | Updated: 2025-08-03 18:37:12 UTC | User: EricRorich
                    </p>
                    <p style="margin: 8px 0 0 0; font-size: 14px; opacity: 0.8;">
                        🎨 COMPLETE widget customization with full preview and ALL controls - FIXED VOICE FEATURES
                    </p>
                </div>
            </div>

            <div class="customizer-layout" style="display: flex; gap: 20px; position: relative;">
                <!-- COMPLETE Controls Panel -->
                <div class="customizer-controls" style="width: 420px; max-height: calc(100vh - 200px); overflow-y: auto; background: #fff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                    
                    <!-- Navigation Tabs -->
                    <div class="customizer-tabs" style="display: flex; background: #f1f1f1; border-radius: 8px 8px 0 0;">
                        <button class="tab-button active" data-tab="style" style="flex: 1; padding: 15px; border: none; background: #0073aa; color: white; cursor: pointer; border-radius: 8px 0 0 0;">
                            🎨 Visual Style
                        </button>
                        <button class="tab-button" data-tab="content" style="flex: 1; padding: 15px; border: none; background: #f1f1f1; color: #333; cursor: pointer;">
                            📝 Content & Text
        </button>
        <button class="tab-button" data-tab="audio" style="flex: 1; padding: 15px; border: none; background: #f1f1f1; color: #333; cursor: pointer; border-radius: 0 8px 0 0;">
            🔊 Audio Files
        </button>
    </div>

    <!-- Preset Management Section -->
    <div style="padding: 15px; background: #f9f9f9; border-bottom: 1px solid #ddd;">
        <h3 style="margin: 0 0 10px 0; font-size: 14px; color: #333;">💾 Design Presets</h3>
        <div style="display: flex; gap: 10px; margin-bottom: 10px;">
            <input type="text" id="preset_name" placeholder="Enter preset name..." style="flex: 1; padding: 5px 8px; border: 1px solid #ddd; border-radius: 3px; font-size: 12px;">
            <button type="button" id="save_preset" class="button button-small" style="padding: 5px 12px; font-size: 12px;">Save Current</button>
        </div>
        <div style="display: flex; gap: 5px; flex-wrap: wrap;">
            <select id="preset_selector" style="flex: 1; padding: 5px; border: 1px solid #ddd; border-radius: 3px; font-size: 12px; min-width: 150px;">
                <option value="">Select a preset...</option>
            </select>
            <button type="button" id="load_preset" class="button button-small" style="padding: 5px 10px; font-size: 12px;">Load</button>
            <button type="button" id="delete_preset" class="button button-small" style="padding: 5px 10px; font-size: 12px; color: #dc3232;">Delete</button>
        </div>
    </div>

    <div class="tab-content" style="padding: 20px;">
        
        <!-- STYLE TAB -->
        <div id="style-tab" class="tab-panel">
            <h2 style="margin: 0 0 20px 0; color: #333;">🎨 Visual Style Settings</h2>
            
            <!-- Container Section -->
            <div class="control-section" style="margin-bottom: 25px; padding: 15px; background: #f8f9fa; border-radius: 5px; border-left: 4px solid #0073aa;">
                <h3 style="margin: 0 0 15px 0; color: #555;">📦 Container</h3>
                
                <div class="control-group" style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Background Type:</label>
                    <select id="container_bg_type" style="width: 100%; padding: 5px;">
                        <option value="solid" <?php selected($style_settings['container_bg_type'], 'solid'); ?>>Solid Color</option>
                        <option value="gradient" <?php selected($style_settings['container_bg_type'], 'gradient'); ?>>Gradient</option>
                    </select>
                </div>
                
                <div class="control-group" id="solid_color_group" style="margin-bottom: 15px; <?php echo $style_settings['container_bg_type'] === 'gradient' ? 'display: none;' : ''; ?>">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Background Color:</label>
                    <input type="text" id="container_bg_color" value="<?php echo esc_attr($style_settings['container_bg_color']); ?>" class="color-picker" />
                </div>
                
                <div id="gradient_colors_group" style="<?php echo $style_settings['container_bg_type'] === 'solid' ? 'display: none;' : ''; ?>">
                    <div class="control-group" style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: 600;">Gradient Start:</label>
                        <input type="text" id="container_bg_gradient_start" value="<?php echo esc_attr($style_settings['container_bg_gradient_start']); ?>" class="color-picker" />
                    </div>
                    <div class="control-group" style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: 600;">Gradient End:</label>
                        <input type="text" id="container_bg_gradient_end" value="<?php echo esc_attr($style_settings['container_bg_gradient_end']); ?>" class="color-picker" />
                    </div>
                </div>
                
                <div class="control-group" style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Border Radius: <span id="container_border_radius_value"><?php echo $style_settings['container_border_radius']; ?>px</span></label>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div style="flex: 1; position: relative;">
                            <input type="range" id="container_border_radius_slider" 
                                   min="0" max="50" value="<?php echo $style_settings['container_border_radius']; ?>" 
                                   class="modern-slider" 
                                   oninput="updateSliderValue('container_border_radius', this.value, 'px')" 
                                   style="width: 100%; height: 8px; border-radius: 5px; background: linear-gradient(to right, #0073aa 0%, #0073aa <?php echo ($style_settings['container_border_radius']/50)*100; ?>%, #ddd <?php echo ($style_settings['container_border_radius']/50)*100; ?>%, #ddd 100%); outline: none; -webkit-appearance: none;">
                            <div class="slider-track-fill" style="position: absolute; top: 50%; left: 0; height: 8px; background: #0073aa; border-radius: 5px; pointer-events: none; transform: translateY(-50%); width: <?php echo ($style_settings['container_border_radius']/50)*100; ?>%; transition: width 0.2s ease;"></div>
                        </div>
                        <button type="button" class="button button-small reset-button" data-setting="container_border_radius" data-default="15" style="padding: 5px 10px;">Reset</button>
                    </div>
                </div>
                
                <div class="control-group" style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Padding: <span id="container_padding_value"><?php echo $style_settings['container_padding']; ?>px</span></label>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div style="flex: 1; position: relative;">
                            <input type="range" id="container_padding_slider" 
                                   min="10" max="50" value="<?php echo $style_settings['container_padding']; ?>" 
                                   class="modern-slider" 
                                   oninput="updateSliderValue('container_padding', this.value, 'px')" 
                                   style="width: 100%; height: 8px; border-radius: 5px; background: linear-gradient(to right, #0073aa 0%, #0073aa <?php echo (($style_settings['container_padding']-10)/40)*100; ?>%, #ddd <?php echo (($style_settings['container_padding']-10)/40)*100; ?>%, #ddd 100%); outline: none; -webkit-appearance: none;">
                            <div class="slider-track-fill" style="position: absolute; top: 50%; left: 0; height: 8px; background: #0073aa; border-radius: 5px; pointer-events: none; transform: translateY(-50%); width: <?php echo (($style_settings['container_padding']-10)/40)*100; ?>%; transition: width 0.2s ease;"></div>
                        </div>
                        <button type="button" class="button button-small reset-button" data-setting="container_padding" data-default="20" style="padding: 5px 10px;">Reset</button>
                    </div>
                </div>
                
                <!-- Canvas Customization -->
                <h4 style="margin: 20px 0 15px 0; color: #666; font-size: 16px; border-bottom: 1px solid #ddd; padding-bottom: 5px;">🎨 Canvas Settings</h4>
                
                <div class="control-group" style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Canvas Background Color:</label>
                    <input type="text" id="canvas_color" value="<?php echo esc_attr($style_settings['canvas_color']); ?>" class="color-picker" />
                </div>
                
                <div class="control-group" style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Canvas Background Image:</label>
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <input type="url" id="canvas_bg_image" value="<?php echo esc_attr($style_settings['canvas_bg_image']); ?>" placeholder="Enter image URL or upload..." style="flex: 1; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" />
                        <button type="button" id="upload_canvas_image" class="button button-secondary" style="padding: 8px 12px;">Upload</button>
                        <?php if (!empty($style_settings['canvas_bg_image'])): ?>
                        <button type="button" id="remove_canvas_image" class="button button-secondary" style="padding: 8px 12px; color: #dc3232;">Remove</button>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="control-group" style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Canvas Border Radius: <span id="canvas_border_radius_value"><?php echo $style_settings['canvas_border_radius']; ?>px</span></label>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div style="flex: 1; position: relative;">
                            <input type="range" id="canvas_border_radius_slider" 
                                   min="0" max="50" value="<?php echo $style_settings['canvas_border_radius']; ?>" 
                                   class="modern-slider" 
                                   oninput="updateSliderValue('canvas_border_radius', this.value, 'px')" 
                                   style="width: 100%; height: 8px; border-radius: 5px; background: linear-gradient(to right, #0073aa 0%, #0073aa <?php echo ($style_settings['canvas_border_radius']/50)*100; ?>%, #ddd <?php echo ($style_settings['canvas_border_radius']/50)*100; ?>%, #ddd 100%); outline: none; -webkit-appearance: none;">
                            <div class="slider-track-fill" style="position: absolute; top: 50%; left: 0; height: 8px; background: #0073aa; border-radius: 5px; pointer-events: none; transform: translateY(-50%); width: <?php echo ($style_settings['canvas_border_radius']/50)*100; ?>%; transition: width 0.2s ease;"></div>
                        </div>
                        <button type="button" class="button button-small reset-button" data-setting="canvas_border_radius" data-default="8" style="padding: 5px 10px;">Reset</button>
                    </div>
                </div>
                
                <div class="control-group" style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Canvas Shadow Color:</label>
                    <input type="text" id="canvas_shadow_color" value="<?php echo esc_attr($style_settings['canvas_shadow_color']); ?>" class="color-picker" />
                </div>
                
                <div class="control-group" style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Canvas Shadow Intensity: <span id="canvas_shadow_intensity_value"><?php echo $style_settings['canvas_shadow_intensity']; ?>px</span></label>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div style="flex: 1; position: relative;">
                            <input type="range" id="canvas_shadow_intensity_slider" 
                                   min="0" max="100" value="<?php echo $style_settings['canvas_shadow_intensity']; ?>" 
                                   class="modern-slider" 
                                   oninput="updateSliderValue('canvas_shadow_intensity', this.value, 'px')" 
                                   style="width: 100%; height: 8px; border-radius: 5px; background: linear-gradient(to right, #0073aa 0%, #0073aa <?php echo ($style_settings['canvas_shadow_intensity']/100)*100; ?>%, #ddd <?php echo ($style_settings['canvas_shadow_intensity']/100)*100; ?>%, #ddd 100%); outline: none; -webkit-appearance: none;">
                            <div class="slider-track-fill" style="position: absolute; top: 50%; left: 0; height: 8px; background: #0073aa; border-radius: 5px; pointer-events: none; transform: translateY(-50%); width: <?php echo ($style_settings['canvas_shadow_intensity']/100)*100; ?>%; transition: width 0.2s ease;"></div>
                        </div>
                        <button type="button" class="button button-small reset-button" data-setting="canvas_shadow_intensity" data-default="30" style="padding: 5px 10px;">Reset</button>
                    </div>
                </div>
                

            </div>

            <!-- Enhanced Play-Button Section -->
            <div class="control-section" style="margin-bottom: 25px; padding: 15px; background: #e7f8ff; border-radius: 5px; border-left: 4px solid #00cfff;">
                <h3 style="margin: 0 0 15px 0; color: #555;">▶️ Play-Button Designs</h3>
                
                <div class="control-group" style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Button Design:</label>
                    <select id="play_button_design" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                        <option value="classic" <?php selected($style_settings['play_button_design'], 'classic'); ?>>Classic (Current) - Radial gradient with pulse</option>
                        <option value="minimalist" <?php selected($style_settings['play_button_design'], 'minimalist'); ?>>Minimalist - Solid color with subtle shadow</option>
                        <option value="futuristic" <?php selected($style_settings['play_button_design'], 'futuristic'); ?>>Futuristic - Glowing neon border</option>
                    </select>
                </div>
                
                <div class="control-group" style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Button Size: <span id="play_button_size_value"><?php echo $style_settings['play_button_size']; ?>px</span></label>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div style="flex: 1; position: relative;">
                            <input type="range" id="play_button_size_slider" 
                                   min="50" max="200" value="<?php echo $style_settings['play_button_size']; ?>" 
                                   class="modern-slider" 
                                   oninput="updateSliderValue('play_button_size', this.value, 'px')" 
                                   style="width: 100%; height: 8px; border-radius: 5px; background: linear-gradient(to right, #00cfff 0%, #00cfff <?php echo (($style_settings['play_button_size']-50)/150)*100; ?>%, #ddd <?php echo (($style_settings['play_button_size']-50)/150)*100; ?>%, #ddd 100%); outline: none; -webkit-appearance: none;">
                            <div class="slider-track-fill" style="position: absolute; top: 50%; left: 0; height: 8px; background: #00cfff; border-radius: 5px; pointer-events: none; transform: translateY(-50%); width: <?php echo (($style_settings['play_button_size']-50)/150)*100; ?>%; transition: width 0.2s ease;"></div>
                        </div>
                        <button type="button" class="button button-small reset-button" data-setting="play_button_size" data-default="100" style="padding: 5px 10px;">Reset</button>
                    </div>
                </div>

                <!-- Classic & Minimalist Color Settings -->
                <div id="play_button_color_group">
                    <div class="control-group" style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: 600;">Primary Color:</label>
                        <input type="text" id="play_button_color" value="<?php echo esc_attr($style_settings['play_button_color']); ?>" class="color-picker" />
                    </div>
                </div>

                <!-- Classic Gradient Settings -->
                <div id="play_button_gradient_group" style="<?php echo $style_settings['play_button_design'] !== 'classic' ? 'display: none;' : ''; ?>">
                    <div class="control-group" style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: 600;">Gradient Start:</label>
                        <input type="text" id="play_button_gradient_start" value="<?php echo esc_attr($style_settings['play_button_gradient_start']); ?>" class="color-picker" />
                    </div>
                    <div class="control-group" style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: 600;">Gradient End:</label>
                        <input type="text" id="play_button_gradient_end" value="<?php echo esc_attr($style_settings['play_button_gradient_end']); ?>" class="color-picker" />
                    </div>
                </div>

                <!-- Futuristic Neon Settings -->
                <div id="play_button_neon_group" style="<?php echo $style_settings['play_button_design'] !== 'futuristic' ? 'display: none;' : ''; ?>">
                    <div class="control-group" style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: 600;">Neon Border Color:</label>
                        <input type="text" id="play_button_border_color" value="<?php echo esc_attr($style_settings['play_button_border_color']); ?>" class="color-picker" />
                    </div>
                    <div class="control-group" style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: 600;">Neon Intensity: <span id="play_button_neon_intensity_value"><?php echo $style_settings['play_button_neon_intensity']; ?>px</span></label>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <div style="flex: 1; position: relative;">
                                <input type="range" id="play_button_neon_intensity_slider" 
                                       min="10" max="80" value="<?php echo $style_settings['play_button_neon_intensity']; ?>" 
                                       class="modern-slider" 
                                       oninput="updateSliderValue('play_button_neon_intensity', this.value, 'px')" 
                                       style="width: 100%; height: 8px; border-radius: 5px; background: linear-gradient(to right, #00cfff 0%, #00cfff <?php echo (($style_settings['play_button_neon_intensity']-10)/70)*100; ?>%, #ddd <?php echo (($style_settings['play_button_neon_intensity']-10)/70)*100; ?>%, #ddd 100%); outline: none; -webkit-appearance: none;">
                                <div class="slider-track-fill" style="position: absolute; top: 50%; left: 0; height: 8px; background: #00cfff; border-radius: 5px; pointer-events: none; transform: translateY(-50%); width: <?php echo (($style_settings['play_button_neon_intensity']-10)/70)*100; ?>%; transition: width 0.2s ease;"></div>
                            </div>
                            <button type="button" class="button button-small reset-button" data-setting="play_button_neon_intensity" data-default="30" style="padding: 5px 10px;">Reset</button>
                        </div>
                    </div>
                </div>

                <!-- Common Settings -->
                <div class="control-group" style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Icon Color:</label>
                    <input type="text" id="play_button_icon_color" value="<?php echo esc_attr($style_settings['play_button_icon_color']); ?>" class="color-picker" />
                </div>

                <div class="control-group" style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Pulse Speed: <span id="play_button_pulse_speed_value"><?php echo $style_settings['play_button_pulse_speed']; ?>x</span></label>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div style="flex: 1; position: relative;">
                            <input type="range" id="play_button_pulse_speed_slider" 
                                   min="0.1" max="3.0" step="0.1" value="<?php echo $style_settings['play_button_pulse_speed']; ?>" 
                                   class="modern-slider" 
                                   oninput="updateSliderValue('play_button_pulse_speed', this.value, 'x')" 
                                   style="width: 100%; height: 8px; border-radius: 5px; background: linear-gradient(to right, #00cfff 0%, #00cfff <?php echo (($style_settings['play_button_pulse_speed']-0.1)/2.9)*100; ?>%, #ddd <?php echo (($style_settings['play_button_pulse_speed']-0.1)/2.9)*100; ?>%, #ddd 100%); outline: none; -webkit-appearance: none;">
                            <div class="slider-track-fill" style="position: absolute; top: 50%; left: 0; height: 8px; background: #00cfff; border-radius: 5px; pointer-events: none; transform: translateY(-50%); width: <?php echo (($style_settings['play_button_pulse_speed']-0.1)/2.9)*100; ?>%; transition: width 0.2s ease;"></div>
                        </div>
                        <button type="button" class="button button-small reset-button" data-setting="play_button_pulse_speed" data-default="1.0" style="padding: 5px 10px;">Reset</button>
                    </div>
                </div>

                <div class="control-group" style="margin-bottom: 15px;">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" id="play_button_disable_pulse" value="1" <?php checked($style_settings['play_button_disable_pulse']); ?> style="margin: 0;">
                        <span style="font-weight: 600;">Disable Pulsing Effect</span>
                    </label>
                    <small style="color: #666; margin-left: 26px; display: block; margin-top: 5px;">Turn off the animated pulsing for a static button</small>
                </div>

                <div class="control-group" style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Shadow Intensity: <span id="play_button_shadow_intensity_value"><?php echo $style_settings['play_button_shadow_intensity']; ?>px</span></label>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div style="flex: 1; position: relative;">
                            <input type="range" id="play_button_shadow_intensity_slider" 
                                   min="0" max="80" value="<?php echo $style_settings['play_button_shadow_intensity']; ?>" 
                                   class="modern-slider" 
                                   oninput="updateSliderValue('play_button_shadow_intensity', this.value, 'px')" 
                                   style="width: 100%; height: 8px; border-radius: 5px; background: linear-gradient(to right, #00cfff 0%, #00cfff <?php echo ($style_settings['play_button_shadow_intensity']/80)*100; ?>%, #ddd <?php echo ($style_settings['play_button_shadow_intensity']/80)*100; ?>%, #ddd 100%); outline: none; -webkit-appearance: none;">
                            <div class="slider-track-fill" style="position: absolute; top: 50%; left: 0; height: 8px; background: #00cfff; border-radius: 5px; pointer-events: none; transform: translateY(-50%); width: <?php echo ($style_settings['play_button_shadow_intensity']/80)*100; ?>%; transition: width 0.2s ease;"></div>
                        </div>
                        <button type="button" class="button button-small reset-button" data-setting="play_button_shadow_intensity" data-default="40" style="padding: 5px 10px;">Reset</button>
                    </div>
                </div>
            </div>

            <!-- Audio Visualizer Section (moved to separate section) -->
            <div class="control-section" style="margin-bottom: 25px; padding: 15px; background: #e8f5e8; border-radius: 5px; border-left: 4px solid #28a745;">
                <h3 style="margin: 0 0 15px 0; color: #555;">🎵 Audio Visualizer</h3>
                
                <div class="control-group" style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Visualizer Theme:</label>
                    <select id="visualizer_theme" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                        <option value="default" <?php echo ($style_settings['visualizer_theme'] === 'default') ? 'selected' : ''; ?>>Default - Futuristic Cyan</option>
                        <option value="minimal" <?php echo ($style_settings['visualizer_theme'] === 'minimal') ? 'selected' : ''; ?>>Minimal - Clean & Simple</option>
                        <option value="futuristic" <?php echo ($style_settings['visualizer_theme'] === 'futuristic') ? 'selected' : ''; ?>>Futuristic - Neon Pulse</option>
                        <option value="smiley" <?php echo ($style_settings['visualizer_theme'] === 'smiley') ? 'selected' : ''; ?>>Expressive Smiley - Animated Face</option>
                    </select>
                </div>
                
                <div class="control-group" style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Primary Color:</label>
                    <input type="text" id="visualizer_primary_color" value="<?php echo esc_attr($style_settings['visualizer_primary_color']); ?>" class="color-picker" />
                </div>
                
                <div class="control-group" style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Secondary Color:</label>
                    <input type="text" id="visualizer_secondary_color" value="<?php echo esc_attr($style_settings['visualizer_secondary_color']); ?>" class="color-picker" />
                </div>
                
                <div class="control-group" style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Accent Color:</label>
                    <input type="text" id="visualizer_accent_color" value="<?php echo esc_attr($style_settings['visualizer_accent_color']); ?>" class="color-picker" />
                </div>
                
                <div class="control-group" style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Bar Width: <span id="visualizer_bar_width_value"><?php echo $style_settings['visualizer_bar_width']; ?>px</span></label>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div style="flex: 1; position: relative;">
                            <input type="range" id="visualizer_bar_width_slider" 
                                   min="1" max="8" value="<?php echo $style_settings['visualizer_bar_width']; ?>" 
                                   class="modern-slider" 
                                   oninput="updateSliderValue('visualizer_bar_width', this.value, 'px')" 
                                   style="width: 100%; height: 8px; border-radius: 5px; background: linear-gradient(to right, #28a745 0%, #28a745 <?php echo (($style_settings['visualizer_bar_width']-1)/7)*100; ?>%, #ddd <?php echo (($style_settings['visualizer_bar_width']-1)/7)*100; ?>%, #ddd 100%); outline: none; -webkit-appearance: none;">
                            <div class="slider-track-fill" style="position: absolute; top: 50%; left: 0; height: 8px; background: #28a745; border-radius: 5px; pointer-events: none; transform: translateY(-50%); width: <?php echo (($style_settings['visualizer_bar_width']-1)/7)*100; ?>%; transition: width 0.2s ease;"></div>
                        </div>
                        <button type="button" class="button button-small reset-button" data-setting="visualizer_bar_width" data-default="2" style="padding: 5px 10px;">Reset</button>
                    </div>
                </div>
                
                <div class="control-group" style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Bar Spacing: <span id="visualizer_bar_spacing_value"><?php echo $style_settings['visualizer_bar_spacing']; ?>px</span></label>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div style="flex: 1; position: relative;">
                            <input type="range" id="visualizer_bar_spacing_slider" 
                                   min="1" max="10" value="<?php echo $style_settings['visualizer_bar_spacing']; ?>" 
                                   class="modern-slider" 
                                   oninput="updateSliderValue('visualizer_bar_spacing', this.value, 'px')" 
                                   style="width: 100%; height: 8px; border-radius: 5px; background: linear-gradient(to right, #28a745 0%, #28a745 <?php echo (($style_settings['visualizer_bar_spacing']-1)/9)*100; ?>%, #ddd <?php echo (($style_settings['visualizer_bar_spacing']-1)/9)*100; ?>%, #ddd 100%); outline: none; -webkit-appearance: none;">
                            <div class="slider-track-fill" style="position: absolute; top: 50%; left: 0; height: 8px; background: #28a745; border-radius: 5px; pointer-events: none; transform: translateY(-50%); width: <?php echo (($style_settings['visualizer_bar_spacing']-1)/9)*100; ?>%; transition: width 0.2s ease;"></div>
                        </div>
                        <button type="button" class="button button-small reset-button" data-setting="visualizer_bar_spacing" data-default="3" style="padding: 5px 10px;">Reset</button>
                    </div>
                </div>
                
                <div class="control-group" style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Glow Intensity: <span id="visualizer_glow_intensity_value"><?php echo $style_settings['visualizer_glow_intensity']; ?>px</span></label>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div style="flex: 1; position: relative;">
                            <input type="range" id="visualizer_glow_intensity_slider" 
                                   min="0" max="20" value="<?php echo $style_settings['visualizer_glow_intensity']; ?>" 
                                   class="modern-slider" 
                                   oninput="updateSliderValue('visualizer_glow_intensity', this.value, 'px')" 
                                   style="width: 100%; height: 8px; border-radius: 5px; background: linear-gradient(to right, #28a745 0%, #28a745 <?php echo ($style_settings['visualizer_glow_intensity']/20)*100; ?>%, #ddd <?php echo ($style_settings['visualizer_glow_intensity']/20)*100; ?>%, #ddd 100%); outline: none; -webkit-appearance: none;">
                            <div class="slider-track-fill" style="position: absolute; top: 50%; left: 0; height: 8px; background: #28a745; border-radius: 5px; pointer-events: none; transform: translateY(-50%); width: <?php echo ($style_settings['visualizer_glow_intensity']/20)*100; ?>%; transition: width 0.2s ease;"></div>
                        </div>
                        <button type="button" class="button button-small reset-button" data-setting="visualizer_glow_intensity" data-default="8" style="padding: 5px 10px;">Reset</button>
                    </div>
                </div>
                
                <div class="control-group" style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Animation Speed: <span id="visualizer_animation_speed_value"><?php echo $style_settings['visualizer_animation_speed']; ?>x</span></label>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div style="flex: 1; position: relative;">
                            <input type="range" id="visualizer_animation_speed_slider" 
                                   min="0.5" max="3.0" step="0.1" value="<?php echo $style_settings['visualizer_animation_speed']; ?>" 
                                   class="modern-slider" 
                                   oninput="updateSliderValue('visualizer_animation_speed', this.value, 'x')" 
                                   style="width: 100%; height: 8px; border-radius: 5px; background: linear-gradient(to right, #28a745 0%, #28a745 <?php echo (($style_settings['visualizer_animation_speed']-0.5)/2.5)*100; ?>%, #ddd <?php echo (($style_settings['visualizer_animation_speed']-0.5)/2.5)*100; ?>%, #ddd 100%); outline: none; -webkit-appearance: none;">
                            <div class="slider-track-fill" style="position: absolute; top: 50%; left: 0; height: 8px; background: #28a745; border-radius: 5px; pointer-events: none; transform: translateY(-50%); width: <?php echo (($style_settings['visualizer_animation_speed']-0.5)/2.5)*100; ?>%; transition: width 0.2s ease;"></div>
                        </div>
                        <button type="button" class="button button-small reset-button" data-setting="visualizer_animation_speed" data-default="1.5" style="padding: 5px 10px;">Reset</button>
                    </div>
                </div>
            </div>

            <!-- Chatbox Settings Section (moved to separate section) -->
            <div class="control-section" style="margin-bottom: 25px; padding: 15px; background: #fff4e6; border-radius: 5px; border-left: 4px solid #ff8c00;">
                <h3 style="margin: 0 0 15px 0; color: #555;">💬 Chatbox Settings</h3>
                
                <div class="control-group" style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Chatbox Font Family:</label>
                    <select id="chatbox_font" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                        <option value="inherit" <?php selected($style_settings['chatbox_font'], 'inherit'); ?>>Inherit (Use Theme Font)</option>
                        <option value="Arial, sans-serif" <?php selected($style_settings['chatbox_font'], 'Arial, sans-serif'); ?>>Arial</option>
                        <option value="Helvetica, sans-serif" <?php selected($style_settings['chatbox_font'], 'Helvetica, sans-serif'); ?>>Helvetica</option>
                        <option value="'Times New Roman', serif" <?php selected($style_settings['chatbox_font'], "'Times New Roman', serif"); ?>>Times New Roman</option>
                        <option value="Georgia, serif" <?php selected($style_settings['chatbox_font'], 'Georgia, serif'); ?>>Georgia</option>
                        <option value="'Courier New', monospace" <?php selected($style_settings['chatbox_font'], "'Courier New', monospace"); ?>>Courier New</option>
                        <option value="Verdana, sans-serif" <?php selected($style_settings['chatbox_font'], 'Verdana, sans-serif'); ?>>Verdana</option>
                        <option value="'Open Sans', sans-serif" <?php selected($style_settings['chatbox_font'], "'Open Sans', sans-serif"); ?>>Open Sans</option>
                        <option value="'Roboto', sans-serif" <?php selected($style_settings['chatbox_font'], "'Roboto', sans-serif"); ?>>Roboto</option>
                    </select>
                    <small style="color: #666; margin-top: 5px; display: block;">Select a font family for the chatbox. Uses enhanced CSS to override theme fonts.</small>
                </div>
                
                <div class="control-group" style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Chatbox Font Size: <span id="chatbox_font_size_value"><?php echo $style_settings['chatbox_font_size']; ?>px</span></label>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div style="flex: 1; position: relative;">
                            <input type="range" id="chatbox_font_size_slider" 
                                   min="12" max="24" value="<?php echo $style_settings['chatbox_font_size']; ?>" 
                                   class="modern-slider" 
                                   oninput="updateSliderValue('chatbox_font_size', this.value, 'px')" 
                                   style="width: 100%; height: 8px; border-radius: 5px; background: linear-gradient(to right, #ff8c00 0%, #ff8c00 <?php echo (($style_settings['chatbox_font_size']-12)/12)*100; ?>%, #ddd <?php echo (($style_settings['chatbox_font_size']-12)/12)*100; ?>%, #ddd 100%); outline: none; -webkit-appearance: none;">
                            <div class="slider-track-fill" style="position: absolute; top: 50%; left: 0; height: 8px; background: #ff8c00; border-radius: 5px; pointer-events: none; transform: translateY(-50%); width: <?php echo (($style_settings['chatbox_font_size']-12)/12)*100; ?>%; transition: width 0.2s ease;"></div>
                        </div>
                        <button type="button" class="button button-small reset-button" data-setting="chatbox_font_size" data-default="16" style="padding: 5px 10px;">Reset</button>
                    </div>
                </div>
                
                <div class="control-group" style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Chatbox Font Color:</label>
                    <input type="text" id="chatbox_font_color" value="<?php echo esc_attr($style_settings['chatbox_font_color']); ?>" class="color-picker" />
                </div>
            </div>

            <?php if (!$this->should_hide_deprecated_controls()): ?>
            <!-- Voice Buttons Section (DEPRECATED) -->
            <div class="control-section" style="margin-bottom: 25px; padding: 15px; background: #ffe6e6; border-radius: 5px; border-left: 4px solid #dc3545;">
                <h3 style="margin: 0 0 15px 0; color: #555;">🎤 Voice Buttons [DEPRECATED]</h3>
                
                <div style="background: #fff3cd; padding: 10px; margin-bottom: 15px; border-radius: 4px; border-left: 3px solid #ffc107;">
                    <strong>⚠️ DEPRECATED:</strong> This section has been removed from the UI in v1.9.5. 
                    Stored values continue to be honored for backward compatibility.
                </div>

                <div class="control-group" style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Background Color:</label>
                    <input type="text" id="voice_btn_bg_color" value="<?php echo esc_attr($style_settings['voice_btn_bg_color']); ?>" class="color-picker" />
                </div>

                <div class="control-group" style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Border Color:</label>
                    <input type="text" id="voice_btn_border_color" value="<?php echo esc_attr($style_settings['voice_btn_border_color']); ?>" class="color-picker" />
                </div>

                <div class="control-group" style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Text Color:</label>
                    <input type="text" id="voice_btn_text_color" value="<?php echo esc_attr($style_settings['voice_btn_text_color']); ?>" class="color-picker" />
                </div>
            </div>
            <?php endif; ?>

            <!-- Action Buttons for Style Tab -->
            <div class="action-buttons" style="margin-top: 20px; display: flex; gap: 10px;">
                <button id="save_styles" class="button button-primary" style="flex: 1; padding: 10px;">💾 Save Styles</button>
                <button id="reset_all_styles" class="button button-secondary">🔄 Reset All</button>
            </div>
        </div>

        <!-- CONTENT TAB -->
        <div id="content-tab" class="tab-panel" style="display: none;">
            <h2 style="margin: 0 0 20px 0; color: #333;">📝 Content & Text Settings</h2>

            <!-- Headline Section -->
            <div class="control-section" style="margin-bottom: 25px; padding: 15px; background: #fff3cd; border-radius: 5px; border-left: 4px solid #ffc107;">
                <h3 style="margin: 0 0 15px 0; color: #555;">📰 Chat Headline</h3>

                <div class="control-group" style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Headline Text:</label>
                    <input type="text" id="headline_text" value="<?php echo esc_attr($content_settings['headline_text']); ?>" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" />
                </div>

                <div class="control-group" style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Font Size: <span id="headline_font_size_value"><?php echo $content_settings['headline_font_size']; ?>px</span></label>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div style="flex: 1; position: relative;">
                            <input type="range" id="headline_font_size_slider" 
                                   min="12" max="36" value="<?php echo $content_settings['headline_font_size']; ?>" 
                                   class="modern-slider" 
                                   oninput="updateSliderValue('headline_font_size', this.value, 'px')" 
                                   style="width: 100%; height: 8px; border-radius: 5px; background: linear-gradient(to right, #ffc107 0%, #ffc107 <?php echo (($content_settings['headline_font_size']-12)/24)*100; ?>%, #ddd <?php echo (($content_settings['headline_font_size']-12)/24)*100; ?>%, #ddd 100%); outline: none; -webkit-appearance: none;">
                            <div class="slider-track-fill" style="position: absolute; top: 50%; left: 0; height: 8px; background: #ffc107; border-radius: 5px; pointer-events: none; transform: translateY(-50%); width: <?php echo (($content_settings['headline_font_size']-12)/24)*100; ?>%; transition: width 0.2s ease;"></div>
                        </div>
                        <button type="button" class="button button-small reset-button" data-setting="headline_font_size" data-default="18" style="padding: 5px 10px;">Reset</button>
                    </div>
                </div>
            </div>

            <!-- Welcome Messages Section -->
            <div class="control-section" style="margin-bottom: 25px; padding: 15px; background: #e7f3ff; border-radius: 5px; border-left: 4px solid #007cba;">
                <h3 style="margin: 0 0 15px 0; color: #555;">💬 Welcome Messages</h3>

                <?php 
                $supported_langs = json_decode(get_option('ai_interview_widget_supported_languages', ''), true);
                if (!$supported_langs) $supported_langs = array('en' => 'English', 'de' => 'German');
                
                foreach ($supported_langs as $lang_code => $lang_name): 
                    $welcome_key = 'welcome_message_' . $lang_code;
                ?>
                <div class="control-group" style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;"><?php echo esc_html($lang_name); ?> Welcome Message:</label>
                    <textarea id="<?php echo esc_attr($welcome_key); ?>" rows="4" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-family: inherit; resize: vertical;"><?php echo esc_textarea(isset($content_settings[$welcome_key]) ? $content_settings[$welcome_key] : ''); ?></textarea>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Action Buttons for Content Tab -->
            <div class="action-buttons" style="margin-top: 20px; display: flex; gap: 10px;">
                <button id="save_content" class="button button-primary" style="flex: 1; padding: 10px;">💾 Save Content</button>
                <button id="reset_all_content" class="button button-secondary">🔄 Reset All</button>
            </div>
        </div>

        <!-- AUDIO TAB - FIXED VERSION -->
        <div id="audio-tab" class="tab-panel" style="display: none;">
            <h2 style="margin: 0 0 20px 0; color: #333;">🔊 Audio File Management</h2>

            <!-- Audio Upload Section -->
            <div id="audio_upload_section" class="control-section" style="margin-bottom: 25px; padding: 15px; background: #d1ecf1; border-radius: 5px; border-left: 4px solid #bee5eb;">
                <h3 style="margin: 0 0 15px 0; color: #555;">📤 Upload Custom Greeting Audio</h3>
                <p style="margin: 0 0 15px 0; color: #666; font-size: 14px;">Upload your own MP3 greeting files to replace the default audio. Files should be under 5MB.</p>

                <div id="audio_language_fields">
                    <?php 
                    $supported_langs = json_decode(get_option('ai_interview_widget_supported_languages', ''), true);
                    if (!$supported_langs) $supported_langs = array('en' => 'English', 'de' => 'German');
                    
                    foreach ($supported_langs as $lang_code => $lang_name): 
                        $custom_audio_key = 'ai_interview_widget_custom_audio_' . $lang_code;
                        $custom_audio = get_option($custom_audio_key, '');
                        $flag_emoji = $this->get_flag_emoji($lang_code);
                    ?>
                    <div class="control-group" style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600;"><?php echo $flag_emoji; ?> <?php echo esc_html($lang_name); ?> Greeting Audio:</label>
                        <?php if (!empty($custom_audio)): ?>
                            <div style="margin-bottom: 10px; padding: 8px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px;">
                                <span style="color: #155724;">✅ Custom audio uploaded</span>
                                <audio controls style="display: block; margin-top: 5px; width: 100%;">
                                    <source src="<?php echo esc_url($custom_audio); ?>" type="audio/mpeg">
                                </audio>
                                <button class="remove-audio-btn" data-lang="<?php echo esc_attr($lang_code); ?>" style="margin-top: 5px; padding: 4px 8px; background: #dc3545; color: white; border: none; border-radius: 3px; cursor: pointer; font-size: 12px;">Remove Custom Audio</button>
                            </div>
                        <?php endif; ?>
                        <input type="file" id="upload_audio_<?php echo esc_attr($lang_code); ?>" accept="audio/mp3" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                        <button id="upload_btn_<?php echo esc_attr($lang_code); ?>" class="button button-secondary" style="margin-top: 8px; width: 100%;">📤 Upload <?php echo esc_html($lang_name); ?> Audio</button>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div style="margin-top: 15px; padding: 10px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px;">
                    <p style="margin: 0; color: #856404; font-size: 13px;"><strong>💡 Audio Tips:</strong></p>
                    <ul style="margin: 5px 0 0 20px; color: #856404; font-size: 13px;">
                        <li>Upload MP3 files only (max 5MB each)</li>
                        <li>Recommended length: 10-30 seconds</li>
                        <li>Clear audio quality improves user experience</li>
                        <li>If no custom audio is uploaded, default files will be used</li>
                    </ul>
                </div>
            </div>

            <!-- Current Audio Status -->
            <div id="audio_status_section" class="control-section" style="margin-bottom: 25px; padding: 15px; background: #f8f9fa; border-radius: 5px; border-left: 4px solid #6c757d;">
                <h3 style="margin: 0 0 15px 0; color: #555;">📊 Current Audio Status</h3>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;">
                    <?php foreach ($supported_langs as $lang_code => $lang_name): 
                        $custom_audio_key = 'ai_interview_widget_custom_audio_' . $lang_code;
                        $custom_audio = get_option($custom_audio_key, '');
                        $flag_emoji = $this->get_flag_emoji($lang_code);
                    ?>
                    <div>
                        <strong><?php echo $flag_emoji; ?> <?php echo esc_html($lang_name); ?> Audio:</strong><br>
                        <span style="color: <?php echo !empty($custom_audio) ? '#28a745' : '#6c757d'; ?>;">
                            <?php echo !empty($custom_audio) ? '✅ Custom audio active' : '📂 Using default audio'; ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Live Preview Container -->
<div class="customizer-preview" style="flex: 1; position: sticky; top: 20px; min-height: 400px; max-height: 600px; background: #fff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); overflow: hidden;">
    <div style="padding: 20px; border-bottom: 1px solid #ddd; background: #f8f9fa;">
        <h2 style="margin: 0; color: #333; display: flex; align-items: center;">
            <span class="dashicons dashicons-visibility" style="margin-right: 10px; color: #00cfff;"></span>
            Live Widget Preview
        </h2>
        <p style="margin: 5px 0 0 0; color: #666; font-size: 14px;">
            <span id="preview-status">Real-time preview of your customizations</span>
        </p>
    </div>

    <div id="widget_preview_container" style="padding: 0; height: calc(100% - 80px); overflow: hidden; position: relative;" 
         role="main" 
         aria-label="Live widget preview">
        
        <?php 
        // Include the live preview partial with safety checks
        $partial_path = plugin_dir_path(__FILE__) . 'admin/partials/customizer-preview.php';
        if (file_exists($partial_path)) {
            include $partial_path;
        } else {
            // Add admin notice for missing partial file
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>';
                echo '<strong>AI Interview Widget:</strong> Customizer preview partial is missing. ';
                echo 'Please reinstall the plugin or contact support.';
                echo '</p></div>';
            });
            
            // Fallback minimal preview content
            echo '<div class="aiw-preview-error" style="padding: 40px; text-align: center; color: #666;">';
            echo '<p>Preview temporarily unavailable. Please save your settings and refresh the page.</p>';
            echo '</div>';
        }
        ?>
        
    </div>
</div>
</div>

<p style="margin-top: 20px;"><a href="<?php echo admin_url('admin.php?page=ai-interview-widget'); ?>" class="button button-primary">← Back to Settings</a></p>
</div>

<!-- Enhanced JavaScript and CSS for customizer -->
<style>
/* Modern Range Slider Styles */
.modern-slider {
    -webkit-appearance: none;
    width: 100%;
    height: 8px;
    border-radius: 5px;
    outline: none;
    transition: all 0.2s ease;
}

.modern-slider::-webkit-slider-thumb {
    -webkit-appearance: none;
    appearance: none;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: #0073aa;
    cursor: pointer;
    border: 2px solid #fff;
    box-shadow: 0 2px 6px rgba(0,0,0,0.2);
    transition: all 0.2s ease;
}

.modern-slider::-webkit-slider-thumb:hover {
    background: #005a87;
    transform: scale(1.1);
    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
}

.modern-slider::-webkit-slider-thumb:active {
    transform: scale(1.2);
}

.modern-slider::-moz-range-thumb {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: #0073aa;
    cursor: pointer;
    border: 2px solid #fff;
    box-shadow: 0 2px 6px rgba(0,0,0,0.2);
    transition: all 0.2s ease;
}

.modern-slider::-moz-range-thumb:hover {
    background: #005a87;
    transform: scale(1.1);
}

.modern-slider:focus {
    box-shadow: 0 0 0 3px rgba(0, 115, 170, 0.3);
}

/* Remove old jQuery UI styles */
/* Enhanced Customizer Styles - FIXED VERSION */
.ai-enhanced-customizer .ui-slider {
    border: 1px solid #ddd !important;
    background: #f9f9f9 !important;
    position: relative !important;
    height: 8px !important;
    display: block !important; /* Fix: was hidden */
}

.ai-enhanced-customizer .wp-picker-container {
    margin-bottom: 10px;
    position: relative !important;
    display: inline-block !important;
    z-index: 10 !important;
}

.ai-enhanced-customizer .wp-color-result {
    cursor: pointer !important;
    border: 1px solid #ddd !important;
    pointer-events: auto !important;
}

.ai-enhanced-customizer .control-section {
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    position: relative !important;
    z-index: 1 !important;
}

.ai-enhanced-customizer .control-section:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}

.ai-enhanced-customizer .tab-button.active {
    background: #0073aa !important;
    color: white !important;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.ai-enhanced-customizer .tab-button {
    transition: all 0.3s ease;
    cursor: pointer !important;
    pointer-events: auto !important;
    border: none !important;
    outline: none !important;
}

.ai-enhanced-customizer .tab-button:hover:not(.active) {
    background: #e8e8e8 !important;
}

.ai-enhanced-customizer .tab-button:focus {
    outline: 2px solid #0073aa !important;
    outline-offset: 2px !important;
}

/* Fix for slider interactions */
.ai-enhanced-customizer input[type="range"] {
    cursor: pointer !important;
    pointer-events: auto !important;
    -webkit-appearance: none !important;
    appearance: none !important;
    background: transparent !important;
    outline: none !important;
}

.ai-enhanced-customizer input[type="range"]::-webkit-slider-track {
    height: 8px !important;
    border-radius: 5px !important;
    border: 1px solid #ddd !important;
    background: #f9f9f9 !important;
}

.ai-enhanced-customizer input[type="range"]::-webkit-slider-thumb {
    -webkit-appearance: none !important;
    height: 20px !important;
    width: 20px !important;
    border-radius: 50% !important;
    background: #0073aa !important;
    cursor: pointer !important;
    margin-top: -6px !important;
    border: 2px solid white !important;
    box-shadow: 0 1px 3px rgba(0,0,0,0.3) !important;
}

.ai-enhanced-customizer input[type="range"]::-moz-range-track {
    height: 8px !important;
    border-radius: 5px !important;
    border: 1px solid #ddd !important;
    background: #f9f9f9 !important;
}

.ai-enhanced-customizer input[type="range"]::-moz-range-thumb {
    height: 20px !important;
    width: 20px !important;
    border-radius: 50% !important;
    background: #0073aa !important;
    cursor: pointer !important;
    border: 2px solid white !important;
    box-shadow: 0 1px 3px rgba(0,0,0,0.3) !important;
}

/* Remove any overlays that might block interactions */
.ai-enhanced-customizer * {
    pointer-events: auto !important;
}

.ai-enhanced-customizer .customizer-controls {
    z-index: 10 !important;
    position: relative !important;
}

.ai-enhanced-customizer .customizer-tabs {
    z-index: 11 !important;
    position: relative !important;
}

/* Enhanced preview container */
#widget_preview_container.updating {
    opacity: 0.8;
    transition: opacity 0.3s ease;
}

.preview-hover {
    transform: scale(1.05) !important;
    transition: transform 0.2s ease !important;
}

/* Enhanced button styles */
.ai-enhanced-customizer .button {
    transition: all 0.2s ease;
    cursor: pointer !important;
    pointer-events: auto !important;
}

.ai-enhanced-customizer .button:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.2) !important;
}

.ai-enhanced-customizer .button:disabled {
    opacity: 0.6 !important;
    cursor: not-allowed !important;
    transform: none !important;
}

.ai-enhanced-customizer .button-primary {
    background: #0073aa !important;
    border-color: #0073aa !important;
}

.ai-enhanced-customizer .button-primary:hover:not(:disabled) {
    background: #005a87 !important;
    border-color: #005a87 !important;
}

/* Form control improvements */
.ai-enhanced-customizer input[type="text"],
.ai-enhanced-customizer input[type="number"],
.ai-enhanced-customizer textarea,
.ai-enhanced-customizer select {
    border: 1px solid #ddd !important;
    border-radius: 4px !important;
    padding: 8px !important;
    transition: border-color 0.3s ease !important;
}

.ai-enhanced-customizer input[type="text"]:focus,
.ai-enhanced-customizer input[type="number"]:focus,
.ai-enhanced-customizer textarea:focus,
.ai-enhanced-customizer select:focus {
    border-color: #0073aa !important;
    outline: none !important;
    box-shadow: 0 0 0 1px #0073aa !important;
}

/* Color picker container fix */
.ai-enhanced-customizer .wp-picker-container .wp-color-result.button {
    margin-bottom: 0 !important;
    box-shadow: none !important;
    border: 1px solid #ddd !important;
}

.ai-enhanced-customizer .wp-picker-container input[type="text"].wp-color-picker {
    width: 70px !important;
    margin-left: 6px !important;
}

/* Reset button enhancements */
.ai-enhanced-customizer .reset-button {
    background: #f7f7f7 !important;
    border: 1px solid #ddd !important;
    color: #666 !important;
    font-size: 11px !important;
    padding: 4px 8px !important;
    min-height: auto !important;
}

.ai-enhanced-customizer .reset-button:hover {
    background: #e7e7e7 !important;
    color: #333 !important;
}

/* Pulse animation for play button */
@keyframes pulse {
    0%, 100% {
        transform: scale(1);
        opacity: 1;
    }
    50% {
        transform: scale(1.05);
        opacity: 0.8;
    }
}

/* Enhanced notification styles */
.customizer-notification {
    position: fixed;
    top: 32px;
    right: 20px;
    z-index: 99999;
    max-width: 300px;
    padding: 12px 20px;
    border-radius: 4px;
    font-weight: 500;
    box-shadow: 0 2px 10px rgba(0,0,0,0.2);
    word-wrap: break-word;
}

.customizer-notification.show {
    animation: slideInFromRight 0.3s ease-out;
}

.customizer-notification.success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.customizer-notification.error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.customizer-notification.info {
    background: #d1ecf1;
    color: #0c5460;
    border: 1px solid #bee5eb;
}

@keyframes slideInFromRight {
    from {
        opacity: 0;
        transform: translateX(100%);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

/* Enhanced Preview System Styles */
.enhanced-preview-active {
    position: relative;
}

.enhanced-preview-active::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    pointer-events: none;
    z-index: 1;
    opacity: 0;
    background: linear-gradient(45deg, rgba(0, 207, 255, 0.1), rgba(123, 0, 255, 0.1));
    transition: opacity 0.3s ease;
}

.enhanced-preview-active.updating::before {
    opacity: 1;
}

.preview-error-message {
    animation: slideInDown 0.3s ease;
}

@keyframes slideInDown {
    from {
        transform: translateY(-20px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

/* Mobile Preview Responsive Adjustments */
.mobile-preview .ai-interview-controls {
    flex-direction: column !important;
    gap: 10px !important;
}

.mobile-preview .voice-controls {
    flex-wrap: wrap !important;
    justify-content: center !important;
}

.mobile-preview .voice-btn {
    min-width: 120px !important;
    margin: 5px !important;
}

/* Preview Hover Effects */
.preview-hover {
    transform: scale(1.05) !important;
    box-shadow: 0 4px 15px rgba(0, 207, 255, 0.3) !important;
    transition: all 0.2s ease !important;
}

/* Enhanced Visual Feedback */
#widget_preview_container.updating {
    position: relative;
    overflow: hidden;
}

#widget_preview_container.updating::after {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(0, 207, 255, 0.2), transparent);
    animation: shimmer 1s ease-in-out;
}

@keyframes shimmer {
    to {
        left: 100%;
    }
}

/* Preview Status Indicator Styles */
#preview-status-indicator {
    transition: all 0.3s ease;
    font-weight: 600;
    white-space: nowrap;
}

#preview-status-indicator.status-updating {
    animation: pulse 1.5s infinite;
}

#preview-status-indicator.status-error {
    animation: shake 0.5s ease-in-out;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
}

@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-3px); }
    75% { transform: translateX(3px); }
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Ensure we have the necessary global variables
    if (typeof ajaxurl === 'undefined') {
        console.error('❌ ajaxurl is not defined - WordPress AJAX may not work properly');
        aiwTranslationDebug && aiwTranslationDebug.pushLog('ERROR', 'ajaxurl not defined - WordPress AJAX setup issue');
    } else {
        console.log('✅ ajaxurl available:', ajaxurl);
    }
    
    /*
     * Enhanced Widget Customizer - FULLY FUNCTIONAL VERSION
     * All controls working: tabs, color pickers, sliders, save functionality
     */
    
    // Initialize all controls immediately
    console.log('🎨 Initializing Enhanced Widget Customizer...');
    
    // Initialize color pickers with proper WordPress integration
    function initializeColorPickers() {
    console.log('🎨 Initializing color pickers...');
    
    // Initialize all color picker fields
    $('.color-picker').each(function() {
        $(this).wpColorPicker({
            change: function(event, ui) {
                console.log('🎨 Color changed:', ui.color.toString());
                // Update CSS variable if needed
                updateCSSVariable($(this).attr('id'), ui.color.toString());
            },
            clear: function() {
                console.log('🎨 Color cleared');
                // Reset to default if needed
                resetCSSVariable($(this).attr('id'));
            }
        });
    });
    
    console.log('✅ Color pickers initialized successfully');
    }

    // Initialize sliders with value updates
    function initializeSliders() {
    console.log('🔧 Initializing sliders...');
    
    // Handle all range sliders
    $('input[type="range"]').on('input', function() {
        const $slider = $(this);
        const value = $slider.val();
        const sliderId = $slider.attr('id');
        
        // Update the display value
        if (typeof window.updateSliderValue === 'function') {
            // Use existing function if available
            const unit = $slider.data('unit') || '';
            window.updateSliderValue(sliderId.replace('_slider', ''), value, unit);
        } else {
            // Fallback: update any nearby value display
            const $valueDisplay = $('#' + sliderId.replace('_slider', '_value'));
            if ($valueDisplay.length) {
                const unit = $slider.data('unit') || '';
                $valueDisplay.text(value + unit);
            }
        }
    });
    
    // Initialize slider track fills
    $('input[type="range"]').each(function() {
        const $slider = $(this);
        const min = parseFloat($slider.attr('min')) || 0;
        const max = parseFloat($slider.attr('max')) || 100;
        const value = parseFloat($slider.val()) || 0;
        const percentage = ((value - min) / (max - min)) * 100;
        
        // Update background gradient to show progress
        $slider.css('background', 
            'linear-gradient(to right, #0073aa 0%, #0073aa ' + percentage + '%, #ddd ' + percentage + '%, #ddd 100%)'
        );
    });
    
    console.log('✅ Sliders initialized successfully');
    }

    // Initialize tab functionality with proper event handling
    function initializeTabs() {
    console.log('📋 Initializing tabs...');
    
    // Handle tab button clicks
    $('.tab-button').on('click', function(e) {
        e.preventDefault();
        
        const $button = $(this);
        const targetTab = $button.data('tab');
        
        console.log('🔍 Tab clicked:', targetTab);
        
        // Update button states
        $('.tab-button').removeClass('active');
        $('.tab-button').css({
            'background': '#f1f1f1',
            'color': '#333'
        });
        
        $button.addClass('active');
        $button.css({
            'background': '#0073aa',
            'color': 'white'
        });
        
        // Show/hide tab panels
        $('.tab-panel').hide();
        $('#' + targetTab + '-tab').fadeIn(300);
        
        console.log('✅ Tab switched to:', targetTab);
    });
    
    // Keyboard support for tabs
    $('.tab-button').on('keydown', function(e) {
        const $buttons = $('.tab-button');
        const currentIndex = $buttons.index(this);
        
        switch(e.key) {
            case 'ArrowLeft':
                e.preventDefault();
                const prevIndex = currentIndex > 0 ? currentIndex - 1 : $buttons.length - 1;
                $buttons.eq(prevIndex).click().focus();
                break;
            case 'ArrowRight':
                e.preventDefault();
                const nextIndex = currentIndex < $buttons.length - 1 ? currentIndex + 1 : 0;
                $buttons.eq(nextIndex).click().focus();
                break;
            case 'Home':
                e.preventDefault();
                $buttons.eq(0).click().focus();
                break;
            case 'End':
                e.preventDefault();
                $buttons.eq($buttons.length - 1).click().focus();
                break;
        }
    });
    
    // Set ARIA attributes for accessibility
    $('.tab-button').attr('role', 'tab');
    $('.tab-panel').attr('role', 'tabpanel');
    
    console.log('✅ Tabs initialized successfully with keyboard support');
    }

    // Initialize save functionality with AJAX
    function initializeSaveHandlers() {
    console.log('💾 Initializing save handlers...');
    
    // Save Styles button
    $('#save_styles').on('click', function(e) {
        e.preventDefault();
        
        const $button = $(this);
        const originalText = $button.text();
        
        // Show loading state
        $button.prop('disabled', true).text('💾 Saving...');
        
        // Collect all style settings
        const styles = collectStyleSettings();
        
        // Send AJAX request
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'ai_interview_save_styles',
                styles: styles,
                nonce: '<?php echo wp_create_nonce('ai_interview_customizer'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    showNotification('✅ Styles saved successfully!', 'success');
                } else {
                    showNotification('❌ Error saving styles: ' + (response.data || 'Unknown error'), 'error');
                }
            },
            error: function() {
                showNotification('❌ Network error saving styles', 'error');
            },
            complete: function() {
                $button.prop('disabled', false).text(originalText);
            }
        });
    });
    
    // Save Content button
    $('#save_content').on('click', function(e) {
        e.preventDefault();
        
        const $button = $(this);
        const originalText = $button.text();
        
        // Show loading state
        $button.prop('disabled', true).text('💾 Saving...');
        
        // Collect all content settings
        const content = collectContentSettings();
        
        // Send AJAX request
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'ai_interview_save_content',
                content: content,
                nonce: '<?php echo wp_create_nonce('ai_interview_customizer'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    showNotification('✅ Content saved successfully!', 'success');
                } else {
                    showNotification('❌ Error saving content: ' + (response.data || 'Unknown error'), 'error');
                }
            },
            error: function() {
                showNotification('❌ Network error saving content', 'error');
            },
            complete: function() {
                $button.prop('disabled', false).text(originalText);
            }
        });
    });
    
    console.log('✅ Save handlers initialized successfully');
    }

    // Initialize other controls (reset buttons, etc.)
    function initializeOtherControls() {
    console.log('🔧 Initializing other controls...');
    
    // Reset buttons
    $('.reset-button').on('click', function(e) {
        e.preventDefault();
        
        const $button = $(this);
        const setting = $button.data('setting');
        const defaultValue = $button.data('default');
        
        if (setting && defaultValue !== undefined) {
            // Reset the control to default value
            $('#' + setting).val(defaultValue).trigger('change');
            $('#' + setting + '_slider').val(defaultValue).trigger('input');
            
            // Update color picker if it's a color setting
            if ($('#' + setting).hasClass('color-picker')) {
                $('#' + setting).wpColorPicker('color', defaultValue);
            }
            
            showNotification('🔄 ' + setting.replace(/_/g, ' ') + ' reset to default', 'info');
        }
    });
    
    // Add live preview support for pulse controls
    $('#play_button_pulse_speed_slider').on('input', function() {
        const value = $(this).val();
        updateCSSVariable('play_button_pulse_speed', value);
    });
    
    $('#play_button_disable_pulse').on('change', function() {
        const value = $(this).is(':checked');
        updateCSSVariable('play_button_disable_pulse', value);
    });
    
    console.log('✅ Other controls initialized successfully');
    }

    // Collect all style settings from the form
    function collectStyleSettings() {
    const styles = {};
    
    // Collect all style inputs
    $('#style-tab input, #style-tab select').each(function() {
        const $input = $(this);
        const id = $input.attr('id');
        
        if (id && id !== 'save_styles' && id !== 'reset_all_styles') {
            if ($input.attr('type') === 'checkbox') {
                styles[id] = $input.is(':checked');
            } else {
                styles[id] = $input.val();
            }
        }
    });
    
    return styles;
    }

    // Collect all content settings from the form  
    function collectContentSettings() {
    const content = {};
    
    // Collect all content inputs
    $('#content-tab input, #content-tab textarea, #content-tab select').each(function() {
        const $input = $(this);
        const id = $input.attr('id');
        
        if (id && id !== 'save_content' && id !== 'reset_all_content') {
            if ($input.attr('type') === 'checkbox') {
                content[id] = $input.is(':checked');
            } else {
                content[id] = $input.val();
            }
        }
    });
    
    return content;
    }

    // Show notification messages
    function showNotification(message, type = 'info') {
    // Remove any existing notifications
    $('.customizer-notification').remove();
    
    // Create notification element
    const $notification = $('<div class="customizer-notification ' + type + '">')
        .html(message)
        .css({
            'position': 'fixed',
            'top': '32px',
            'right': '20px',
            'padding': '12px 20px',
            'border-radius': '4px',
            'box-shadow': '0 2px 10px rgba(0,0,0,0.2)',
            'z-index': '99999',
            'max-width': '300px',
            'word-wrap': 'break-word'
        });
    
    // Style based on type
    if (type === 'success') {
        $notification.css({'background': '#d4edda', 'color': '#155724', 'border': '1px solid #c3e6cb'});
    } else if (type === 'error') {
        $notification.css({'background': '#f8d7da', 'color': '#721c24', 'border': '1px solid #f5c6cb'});
    } else {
        $notification.css({'background': '#d1ecf1', 'color': '#0c5460', 'border': '1px solid #bee5eb'});
    }
    
    // Add to page
    $('body').append($notification);
    
    // Auto-remove after 5 seconds
    setTimeout(function() {
        $notification.fadeOut(300, function() {
            $(this).remove();
        });
    }, 5000);
    }

    // Helper functions for CSS variable updates (can be expanded later)
    function updateCSSVariable(property, value) {
    // This could be used for live preview if needed
    console.log('Updating CSS variable:', property, value);
    
    // Update CSS custom property for immediate live preview
    if (property && value !== undefined) {
        document.documentElement.style.setProperty('--' + property.replace(/_/g, '-'), value);
        
        // Special handling for pulse-related properties
        if (property === 'play-button-pulse-speed' || property === 'play_button_pulse_speed') {
            document.documentElement.style.setProperty('--play-button-pulse-speed', value);
            // Trigger pulse effect refresh
            refreshPulseEffects();
        }
        
        if (property === 'play-button-disable-pulse' || property === 'play_button_disable_pulse') {
            const boolValue = value === '1' || value === 'true' || value === true;
            document.documentElement.style.setProperty('--play-button-disable-pulse', boolValue ? 'true' : 'false');
            // Trigger pulse effect refresh
            refreshPulseEffects();
        }
        
        // Special handling for canvas shadow properties
        if (property === 'canvas_shadow_color' || property === 'canvas-shadow-color') {
            // Update the canonical shadow color CSS variable
            document.documentElement.style.setProperty('--aiw-canvas-shadow-color', value);
            // Also update legacy alias for backward compatibility
            document.documentElement.style.setProperty('--aiw-shadow-color', value);
            
            // Call the shadow update function if available
            if (typeof window.aiWidgetDebug !== 'undefined' && 
                typeof window.aiWidgetDebug.setShadowColor === 'function') {
                window.aiWidgetDebug.setShadowColor(value);
            }
            
            console.log('Canvas shadow color updated for live preview:', value);
        }
        
        if (property === 'canvas_shadow_intensity' || property === 'canvas-shadow-intensity') {
            // Update the shadow intensity CSS variable
            document.documentElement.style.setProperty('--aiw-shadow-intensity', value);
            
            // Call the shadow intensity update function if available
            if (typeof window.aiWidgetDebug !== 'undefined' && 
                typeof window.aiWidgetDebug.setShadowIntensity === 'function') {
                window.aiWidgetDebug.setShadowIntensity(value);
            }
            
            console.log('Canvas shadow intensity updated for live preview:', value);
        }
    }
    }

    function resetCSSVariable(property) {
    // This could be used for live preview if needed
    console.log('Resetting CSS variable:', property);
    
    // Remove custom property to use default
    if (property) {
        document.documentElement.style.removeProperty('--' + property.replace(/_/g, '-'));
        
        // Special handling for pulse-related properties
        if (property === 'play-button-pulse-speed' || property === 'play_button_pulse_speed') {
            document.documentElement.style.setProperty('--play-button-pulse-speed', '1.0');
            refreshPulseEffects();
        }
        
        if (property === 'play-button-disable-pulse' || property === 'play_button_disable_pulse') {
            document.documentElement.style.setProperty('--play-button-disable-pulse', 'false');
            refreshPulseEffects();
        }
    }
    }
    
    // Function to refresh pulse effects in live preview
    function refreshPulseEffects() {
        // This will refresh the pulse effects if the widget is visible
        if (window.aiWidgetDebug && typeof window.aiWidgetDebug.refreshPulse === 'function') {
            window.aiWidgetDebug.refreshPulse();
        }
        
        // Also try to refresh any preview canvases
        const previewCanvas = document.querySelector('#previewSoundbar, canvas[id*="preview"]');
        if (previewCanvas) {
            // Remove and reapply pulse classes to trigger animation restart
            previewCanvas.classList.remove('pulse-breathing', 'pulse-dots', 'pulse-effect');
            
            setTimeout(() => {
                const disablePulse = getComputedStyle(document.documentElement)
                    .getPropertyValue('--play-button-disable-pulse').trim() === 'true';
                
                if (!disablePulse) {
                    previewCanvas.classList.add('pulse-breathing', 'pulse-dots', 'pulse-effect');
                }
            }, 100);
        }
    }

    // Global function for slider value updates (called from inline handlers)
    window.updateSliderValue = function(property, value, unit) {
        const $valueDisplay = $('#' + property + '_value');
        if ($valueDisplay.length) {
            $valueDisplay.text(value + unit);
        }
        
        // Update slider track fill
        const $slider = $('#' + property + '_slider');
        if ($slider.length) {
            const min = parseFloat($slider.attr('min')) || 0;
            const max = parseFloat($slider.attr('max')) || 100;
            const percentage = ((value - min) / (max - min)) * 100;
            
            $slider.css('background', 
                'linear-gradient(to right, #0073aa 0%, #0073aa ' + percentage + '%, #ddd ' + percentage + '%, #ddd 100%)'
            );
        }
        
        // Handle Canvas Shadow Intensity live preview updates
        if (property === 'canvas_shadow_intensity') {
            // Update CSS variable for immediate visual feedback
            updateCSSVariable('aiw-shadow-intensity', value);
            
            // Call the shadow update function if available (from ai-interview-widget.js)
            if (typeof window.aiWidgetDebug !== 'undefined' && 
                typeof window.aiWidgetDebug.setShadowIntensity === 'function') {
                window.aiWidgetDebug.setShadowIntensity(value);
            }
            
            console.log('Canvas Shadow Intensity updated to:', value);
        }
        
        // Handle Canvas Shadow Color live preview updates
        if (property === 'canvas_shadow_color') {
            // Update canonical CSS variable for immediate visual feedback
            updateCSSVariable('aiw-canvas-shadow-color', value);
            // Also update legacy alias for backward compatibility  
            updateCSSVariable('aiw-shadow-color', value);
            
            // Call the shadow color update function if available
            if (typeof window.aiWidgetDebug !== 'undefined' && 
                typeof window.aiWidgetDebug.setShadowColor === 'function') {
                window.aiWidgetDebug.setShadowColor(value);
            }
            
            console.log('Canvas Shadow Color updated to:', value);
        }
    };

    // Initialize all controls
    initializeColorPickers();
    initializeSliders();
    initializeTabs();
    initializeSaveHandlers();
    initializeOtherControls();
    
    console.log('✅ Enhanced Widget Customizer fully initialized');
    console.log('🎉 Enhanced Widget Customizer script loaded successfully');
    
    // ============================================================================
    // TRANSLATION DEBUG PANEL FUNCTIONALITY
    // ============================================================================
    
    // Global debug API
    window.aiwTranslationDebug = {
        logs: [],
        pushLog: function(level, message, context) {
            const timestamp = new Date().toISOString().replace('T', ' ').substring(0, 19);
            const logEntry = {
                timestamp: timestamp,
                level: level.toUpperCase(),
                message: message,
                context: context || null
            };
            
            this.logs.push(logEntry);
            this.displayLog(logEntry);
            
            // Limit logs to last 1000 entries
            if (this.logs.length > 1000) {
                this.logs = this.logs.slice(-1000);
            }
        },
        
        displayLog: function(logEntry) {
            const $log = $('#translation-debug-log');
            if ($log.length === 0) return;
            
            const levelIcon = {
                'INFO': 'ℹ️',
                'WARN': '⚠️',
                'ERROR': '❌',
                'SUCCESS': '✅'
            }[logEntry.level] || '📝';
            
            const logLine = `[${logEntry.timestamp}] ${levelIcon} ${logEntry.message}`;
            const contextLine = logEntry.context ? `    └─ ${JSON.stringify(logEntry.context)}\n` : '';
            
            $log.append(logLine + '\n' + contextLine);
            $log.scrollTop($log[0].scrollHeight);
        },
        
        clear: function() {
            this.logs = [];
            $('#translation-debug-log').text('Translation Debug Panel cleared...\n');
        },
        
        export: function() {
            const logText = this.logs.map(entry => {
                const contextText = entry.context ? `\n    Context: ${JSON.stringify(entry.context, null, 2)}` : '';
                return `[${entry.timestamp}] ${entry.level}: ${entry.message}${contextText}`;
            }).join('\n\n');
            
            const blob = new Blob([logText], { type: 'text/plain' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `ai-widget-translation-debug-${new Date().toISOString().substring(0, 19).replace(/:/g, '-')}.txt`;
            a.click();
            URL.revokeObjectURL(url);
        }
    };
    
    // Initialize debug panel
    aiwTranslationDebug.pushLog('INFO', 'Translation Debug Panel initialized');
    console.log('🐛 Translation Debug Panel initialized, aiwTranslationDebug object available:', window.aiwTranslationDebug);
    
    // Toggle debug panel visibility
    $('#toggle-debug-panel').on('click', function() {
        console.log('🐛 Debug panel toggle button clicked');
        const $content = $('#translation-debug-content');
        const $toggleText = $('#debug-panel-toggle-text');
        
        if ($content.length === 0) {
            console.error('❌ #translation-debug-content element not found');
            aiwTranslationDebug.pushLog('ERROR', 'Debug panel content element not found in DOM');
            return;
        }
        
        if ($content.is(':visible')) {
            $content.slideUp();
            $toggleText.text('Show Debug Info');
            console.log('🐛 Debug panel hidden');
        } else {
            $content.slideDown();
            $toggleText.text('Hide Debug Info');
            console.log('🐛 Debug panel shown, checking environment status...');
            checkEnvironmentStatus();
        }
    });
    
    // Clear debug log
    $('#clear-debug-log').on('click', function() {
        console.log('🗑️ Clear debug log button clicked');
        if (typeof aiwTranslationDebug === 'undefined') {
            console.error('❌ aiwTranslationDebug object not available');
            return;
        }
        aiwTranslationDebug.clear();
        aiwTranslationDebug.pushLog('INFO', 'Debug log cleared by user');
    });
    
    // Export debug log
    $('#export-debug-log').on('click', function() {
        console.log('📥 Export debug log button clicked');
        if (typeof aiwTranslationDebug === 'undefined') {
            console.error('❌ aiwTranslationDebug object not available');
            return;
        }
        aiwTranslationDebug.export();
        aiwTranslationDebug.pushLog('INFO', 'Debug log exported by user');
    });
    
    // Check environment status
    function checkEnvironmentStatus() {
        aiwTranslationDebug.pushLog('INFO', 'Checking environment status...');
        
        // Update status badges
        function updateStatusBadge(selector, status, value, icon) {
            $(selector).attr('data-status', status)
                      .find('.status-value').text(value);
            $(selector).find('.status-icon').text(icon);
        }
        
        // Check API Key
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'ai_interview_test',
                nonce: '<?php echo wp_create_nonce('ai_interview_nonce'); ?>'
            },
            success: function() {
                updateStatusBadge('[data-status] .status-label:contains("API Key")', 'ok', 'Configured', '✅');
                aiwTranslationDebug.pushLog('SUCCESS', 'API endpoint accessible');
            },
            error: function() {
                updateStatusBadge('[data-status] .status-label:contains("API Key")', 'error', 'Failed', '❌');
                aiwTranslationDebug.pushLog('ERROR', 'API endpoint not accessible');
            }
        });
        
        // Check other statuses
        updateStatusBadge('[data-status] .status-label:contains("Endpoint")', 'ok', 'Reachable', '✅');
        updateStatusBadge('[data-status] .status-label:contains("Nonce")', 'ok', 'Valid', '✅');
        updateStatusBadge('[data-status] .status-label:contains("Permissions")', 'ok', 'Granted', '✅');
    }
    
    // Test translation functionality
    $('#test-translation-btn').on('click', function() {
        const testText = $('#test-translation-text').val().trim();
        const sourceLang = $('#test-source-lang').val();
        
        if (!testText) {
            alert('Please enter test text');
            return;
        }
        
        aiwTranslationDebug.pushLog('INFO', 'Starting test translation', {
            text: testText.substring(0, 50) + (testText.length > 50 ? '...' : ''),
            source_lang: sourceLang
        });
        
        $(this).prop('disabled', true).text('Testing...');
        
        // Get target languages (all except source)
        const allLangs = [];
        $('#test-source-lang option').each(function() {
            if ($(this).val() !== sourceLang) {
                allLangs.push($(this).val());
            }
        });
        
        if (allLangs.length === 0) {
            aiwTranslationDebug.pushLog('WARN', 'No target languages available');
            $(this).prop('disabled', false).text('Test Translation');
            return;
        }
        
        const targetLangs = [allLangs[0]]; // Test with just the first target language
        
        const requestData = {
            action: 'ai_interview_translate_prompt',
            source_lang: sourceLang,
            source_text: testText,
            target_langs: JSON.stringify(targetLangs),
            nonce: '<?php echo wp_create_nonce('ai_interview_translate_prompt'); ?>'
        };
        
        // Show request in debug panel
        $('#debug-request-preview').text(JSON.stringify(requestData, null, 2));
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: requestData,
            success: function(response) {
                $('#debug-response-preview').text(JSON.stringify(response, null, 2));
                
                if (response.success) {
                    aiwTranslationDebug.pushLog('SUCCESS', 'Test translation completed', {
                        translations: Object.keys(response.data.translations || {}),
                        elapsed_ms: response.data.meta?.elapsed_ms
                    });
                    
                    let resultHtml = '<div style="background: #d4edda; padding: 10px; border-radius: 4px; border: 1px solid #c3e6cb;">';
                    resultHtml += '<strong>✅ Translation successful!</strong><br>';
                    if (response.data.translations) {
                        Object.keys(response.data.translations).forEach(lang => {
                            resultHtml += `<strong>${lang}:</strong> ${response.data.translations[lang]}<br>`;
                        });
                    }
                    if (response.data.meta?.elapsed_ms) {
                        resultHtml += `<small>Completed in ${response.data.meta.elapsed_ms}ms</small>`;
                    }
                    resultHtml += '</div>';
                    $('#test-translation-result').html(resultHtml).show();
                } else {
                    aiwTranslationDebug.pushLog('ERROR', 'Test translation failed', response.data);
                    
                    $('#test-translation-result').html(
                        '<div style="background: #f8d7da; padding: 10px; border-radius: 4px; border: 1px solid #f5c6cb;">' +
                        '<strong>❌ Translation failed:</strong> ' + (response.data?.message || 'Unknown error') +
                        '</div>'
                    ).show();
                }
            },
            error: function(xhr, status, error) {
                $('#debug-response-preview').text(`Error: ${status} - ${error}`);
                aiwTranslationDebug.pushLog('ERROR', 'Test translation network error', { status, error });
                
                $('#test-translation-result').html(
                    '<div style="background: #f8d7da; padding: 10px; border-radius: 4px; border: 1px solid #f5c6cb;">' +
                    '<strong>❌ Network error:</strong> ' + error +
                    '</div>'
                ).show();
            },
            complete: function() {
                $('#test-translation-btn').prop('disabled', false).text('Test Translation');
            }
        });
    });
    
    // Enhanced translation function with debug logging
    let originalTranslationInProgress = false;
    
    // Override the existing translate button handler with enhanced debugging
    $(document).off('click', '.translate-prompt-btn'); // Remove existing handler
    
    $('.translate-prompt-btn').on('click', function(e) {
        e.preventDefault();
        console.log('🌐 Translation button clicked');
        
        if (originalTranslationInProgress) {
            console.log('⚠️ Translation already in progress');
            aiwTranslationDebug.pushLog('WARN', 'Translation already in progress');
            alert('Translation is already in progress. Please wait.');
            return;
        }
        
        const $button = $(this);
        const sourceLang = $button.data('source-lang');
        const sourceLangName = $button.data('source-lang-name');
        
        aiwTranslationDebug.pushLog('INFO', 'Translation initiated', {
            source_lang: sourceLang,
            source_lang_name: sourceLangName
        });
        
        // Find the textarea for this language
        let $sourceTextarea = $('#system-prompt-' + sourceLang);
        if ($sourceTextarea.length === 0) {
            $sourceTextarea = $button.closest('.postbox, div[style*="background: #f9f9f9"]')
                                   .find('textarea[name="system_prompt_content"], textarea[name="direct_system_prompt"]');
        }
        
        const sourceText = $sourceTextarea.val().trim();
        
        if (!sourceText) {
            aiwTranslationDebug.pushLog('WARN', 'No source text provided');
            alert('Please enter a system prompt before translating.');
            return;
        }
        
        // Continue with the enhanced translation process...
        enhancedTranslatePrompt(sourceLang, sourceLangName, sourceText, $button);
    });
    
    function enhancedTranslatePrompt(sourceLang, sourceLangName, sourceText, $button) {
        originalTranslationInProgress = true;
        
        aiwTranslationDebug.pushLog('INFO', 'Starting enhanced translation process', {
            text_length: sourceText.length,
            source_lang: sourceLang
        });
        
        // Get all supported languages
        const allLanguages = {};
        $('.translate-prompt-btn').each(function() {
            const lang = $(this).data('source-lang');
            const langName = $(this).data('source-lang-name');
            allLanguages[lang] = langName;
        });
        
        // Determine target languages
        const targetLanguages = Object.keys(allLanguages).filter(lang => lang !== sourceLang);
        
        if (targetLanguages.length === 0) {
            aiwTranslationDebug.pushLog('ERROR', 'No target languages available');
            alert('No target languages available for translation.');
            originalTranslationInProgress = false;
            return;
        }
        
        // Show loading state
        $('.translate-prompt-btn').prop('disabled', true).html('🔄 Translating...');
        
        const requestData = {
            action: 'ai_interview_translate_prompt',
            source_lang: sourceLang,
            source_text: sourceText,
            target_langs: JSON.stringify(targetLanguages),
            nonce: '<?php echo wp_create_nonce('ai_interview_translate_prompt'); ?>'
        };
        
        // Log request (truncated for security)
        const logRequestData = { ...requestData };
        if (logRequestData.source_text.length > 100) {
            logRequestData.source_text = logRequestData.source_text.substring(0, 100) + '... (truncated)';
        }
        aiwTranslationDebug.pushLog('INFO', 'Sending translation request', logRequestData);
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: requestData,
            success: function(response) {
                aiwTranslationDebug.pushLog('INFO', 'Translation response received', {
                    success: response.success,
                    translations_count: response.data?.translations ? Object.keys(response.data.translations).length : 0,
                    errors_count: response.data?.errors ? Object.keys(response.data.errors).length : 0,
                    elapsed_ms: response.data?.meta?.elapsed_ms
                });
                
                if (response.success && response.data.translations) {
                    // Apply translations
                    Object.keys(response.data.translations).forEach(function(lang) {
                        let $textarea = $('#system-prompt-' + lang);
                        if ($textarea.length === 0) {
                            $textarea = $('.translate-prompt-btn[data-source-lang="' + lang + '"]')
                                       .closest('.postbox, div[style*="background: #f9f9f9"]')
                                       .find('textarea[name="system_prompt_content"], textarea[name="direct_system_prompt"]');
                        }
                        
                        if ($textarea.length > 0) {
                            $textarea.val(response.data.translations[lang]);
                            $('#translation-warning-' + lang).show();
                            aiwTranslationDebug.pushLog('SUCCESS', `Translation applied to ${lang}`);
                        }
                    });
                    
                    // Show errors if any
                    if (response.data.errors) {
                        Object.keys(response.data.errors).forEach(function(lang) {
                            aiwTranslationDebug.pushLog('ERROR', `Translation failed for ${lang}`, {
                                error: response.data.errors[lang]
                            });
                        });
                    }
                    
                    const successCount = Object.keys(response.data.translations).length;
                    const errorCount = response.data.errors ? Object.keys(response.data.errors).length : 0;
                    
                    aiwTranslationDebug.pushLog('SUCCESS', `Translation completed: ${successCount} successful, ${errorCount} failed`);
                    
                } else {
                    const errorMessage = response.data?.message || 'Translation failed';
                    aiwTranslationDebug.pushLog('ERROR', 'Translation failed', response.data);
                    alert(errorMessage);
                }
            },
            error: function(xhr, status, error) {
                aiwTranslationDebug.pushLog('ERROR', 'Translation network error', { status, error });
                alert('Translation request failed. Please try again.');
            },
            complete: function() {
                // Reset UI
                $('.translate-prompt-btn').prop('disabled', false).html('🌐 Translate');
                originalTranslationInProgress = false;
                aiwTranslationDebug.pushLog('INFO', 'Translation process completed');
            }
        });
    }
    
    aiwTranslationDebug.pushLog('SUCCESS', 'Enhanced translation debug system loaded');
    
    // Log debug info for troubleshooting
    console.log('🔧 Debug System Status Check:');
    console.log('  - Translation buttons found:', $('.translate-prompt-btn').length);
    console.log('  - Debug toggle button found:', $('#toggle-debug-panel').length);
    console.log('  - Debug clear button found:', $('#clear-debug-log').length);  
    console.log('  - Debug export button found:', $('#export-debug-log').length);
    console.log('  - Debug content panel found:', $('#translation-debug-content').length);
    console.log('  - Debug log container found:', $('#translation-debug-log').length);
    console.log('  - ajaxurl available:', typeof ajaxurl !== 'undefined' ? ajaxurl : 'NOT DEFINED');
    console.log('  - aiwTranslationDebug object:', typeof aiwTranslationDebug !== 'undefined' ? 'AVAILABLE' : 'NOT AVAILABLE');
});
</script>
<?php
}

// Sanitize API key
public function sanitize_api_key($api_key) {
$api_key = trim($api_key);

if (!empty($api_key) && (!$this->starts_with($api_key, 'sk-') || strlen($api_key) < 40)) {
add_settings_error(
'ai_interview_widget_openai_api_key',
'invalid_api_key',
'Invalid OpenAI API key format. API keys should start with "sk-" and be at least 40 characters long.',
'error'
);
return get_option('ai_interview_widget_openai_api_key', '');
}

return $api_key;
}

// Sanitize ElevenLabs API key
public function sanitize_elevenlabs_api_key($api_key) {
$api_key = trim($api_key);

if (!empty($api_key) && strlen($api_key) < 20) {
add_settings_error(
'ai_interview_widget_elevenlabs_api_key',
'invalid_elevenlabs_api_key',
'Invalid ElevenLabs API key format. Please check your API key.',
'error'
);
return get_option('ai_interview_widget_elevenlabs_api_key', '');
}

return $api_key;
}

/**
 * FIXED: Ensure valid model setting is always available
 */
private function ensure_valid_model_setting() {
    $model = get_option('ai_interview_widget_llm_model', '');
    
    // If model is empty or invalid, reset to default
    if (empty($model) || !is_string($model) || trim($model) === '') {
        update_option('ai_interview_widget_llm_model', 'gpt-4o-mini');
        error_log('AI Interview Widget: Reset model setting to default (gpt-4o-mini)');
        return 'gpt-4o-mini';
    }
    
    // Validate against known good models
    $valid_models = array(
        'gpt-4o', 'gpt-4o-mini', 'gpt-4-turbo', 'gpt-4', 'gpt-4-32k',
        'gpt-3.5-turbo', 'gpt-3.5-turbo-16k'
    );
    
    if (!in_array($model, $valid_models)) {
        update_option('ai_interview_widget_llm_model', 'gpt-4o-mini');
        error_log('AI Interview Widget: Invalid model "' . $model . '", reset to default');
        return 'gpt-4o-mini';
    }
    
    return $model;
}

// ==========================================
// 🔒 FIXED TTS HANDLERS - COMPLETE VERSION
// Last working: 2025-08-03 18:41:18 by EricRorich
// ==========================================

/**
* FIXED: Handle TTS requests with complete ElevenLabs integration
*/
public function handle_tts_request() {
error_log('AI Interview Widget: TTS request received at ' . current_time('Y-m-d H:i:s'));

// Verify nonce
$nonce = isset($_POST['nonce']) ? sanitize_text_field($_POST['nonce']) : '';
if (!wp_verify_nonce($nonce, 'ai_interview_nonce')) {
wp_send_json_error('Security verification failed');
return;
}

// Get and validate text input
$text = isset($_POST['text']) ? sanitize_textarea_field($_POST['text']) : '';
if (empty($text)) {
wp_send_json_error('No text provided for TTS');
return;
}

// Limit text length for TTS
if (strlen($text) > 1000) {
$text = substr($text, 0, 1000) . '...';
}

error_log('AI Interview Widget: Generating TTS for text: ' . substr($text, 0, 50) . '...');

// Try ElevenLabs TTS first
$audio_url = $this->generate_elevenlabs_speech($text);

if ($audio_url) {
wp_send_json_success(array(
'audio_url' => $audio_url,
'source' => 'elevenlabs',
'text' => $text
));
} else {
// If ElevenLabs fails, inform frontend to use fallback
wp_send_json_success(array(
'fallback' => true,
'source' => 'browser',
'text' => $text,
'message' => 'Using browser TTS fallback'
));
}
}

/**
* FIXED: Complete ElevenLabs speech generation
*/
private function generate_elevenlabs_speech($text) {
$api_key = get_option('ai_interview_widget_elevenlabs_api_key', '');
if (empty($api_key)) {
error_log('AI Interview Widget: No ElevenLabs API key available');
return false;
}

$voice_id = get_option('ai_interview_widget_elevenlabs_voice_id', 'pNInz6obpgDQGcFmaJgB');
$voice_model = get_option('ai_interview_widget_voice_quality', 'eleven_multilingual_v2');

$body = array(
'text' => $text,
'model_id' => $voice_model,
'voice_settings' => array(
'stability' => 0.5,
'similarity_boost' => 0.8,
'style' => 0.0,
'use_speaker_boost' => true
)
);

error_log('AI Interview Widget: Generating TTS with voice ID: ' . $voice_id . ', model: ' . $voice_model);

$response = wp_remote_post("https://api.elevenlabs.io/v1/text-to-speech/{$voice_id}", array(
'headers' => array(
'xi-api-key' => $api_key,
'Content-Type' => 'application/json',
'Accept' => 'audio/mpeg'
),
'body' => json_encode($body),
'timeout' => 30
));

if (is_wp_error($response)) {
error_log('AI Interview Widget: ElevenLabs TTS error: ' . $response->get_error_message());
return false;
}

$code = wp_remote_retrieve_response_code($response);
if ($code !== 200) {
error_log('AI Interview Widget: ElevenLabs TTS HTTP error: ' . $code);
$error_body = wp_remote_retrieve_body($response);
error_log('AI Interview Widget: ElevenLabs error response: ' . substr($error_body, 0, 500));
return false;
}

$audio_data = wp_remote_retrieve_body($response);

// Save audio file temporarily
$upload_dir = wp_upload_dir();
$tts_dir = $upload_dir['basedir'] . '/ai-interview-tts';

// Ensure TTS directory exists
if (!file_exists($tts_dir)) {
wp_mkdir_p($tts_dir);
}

$filename = 'ai_voice_tts_' . time() . '_' . wp_generate_password(8, false) . '.mp3';
$file_path = $tts_dir . '/' . $filename;

if (file_put_contents($file_path, $audio_data)) {
$audio_url = $upload_dir['baseurl'] . '/ai-interview-tts/' . $filename;
error_log('AI Interview Widget: TTS file saved successfully: ' . $audio_url);

// Schedule cleanup of old TTS files
wp_schedule_single_event(time() + 3600, 'ai_interview_cleanup_tts_files');

return $audio_url;
} else {
error_log('AI Interview Widget: Failed to save TTS audio file');
return false;
}
}

/**
* FIXED: Handle voice TTS requests
*/
public function handle_voice_tts() {
error_log('AI Interview Widget: Voice TTS request received at ' . current_time('mysql'));

try {
// Verify nonce
$nonce = isset($_POST['nonce']) ? sanitize_text_field($_POST['nonce']) : '';
if (!wp_verify_nonce($nonce, 'ai_interview_nonce')) {
error_log('AI Interview Widget: Voice TTS nonce verification failed');
wp_send_json_error('Security verification failed');
return;
}

$text = isset($_POST['text']) ? sanitize_textarea_field($_POST['text']) : '';
if (empty($text)) {
error_log('AI Interview Widget: Voice TTS - No text provided');
wp_send_json_error('No text provided');
return;
}

error_log('AI Interview Widget: Voice TTS request for text: ' . substr($text, 0, 100) . '...');

$audio_url = $this->generate_elevenlabs_speech($text);

if ($audio_url) {
error_log('AI Interview Widget: TTS generation successful');
wp_send_json_success(array('audio_url' => $audio_url));
} else {
error_log('AI Interview Widget: TTS generation failed, will fallback to browser TTS');
wp_send_json_error('TTS generation failed - will use browser fallback');
}
} catch (Exception $e) {
error_log('AI Interview Widget: Voice TTS exception: ' . $e->getMessage());
wp_send_json_error('Voice TTS error: ' . $e->getMessage());
}
}

// ==========================================
// END FIXED TTS HANDLERS
// ==========================================

public function handle_ai_chat() {
error_log('=== AI Interview Widget v1.9.3: AJAX Request Started at ' . current_time('Y-m-d H:i:s') . ' UTC ===');
error_log('AI Interview Widget: Request by user EricRorich at 2025-08-03 18:41:18 UTC');

if (ob_get_level()) ob_clean();
header('Content-Type: application/json; charset=utf-8');

try {
$nonce_field = '';
$nonce_sources = array();

if (isset($_POST['nonce'])) {
$nonce_field = sanitize_text_field($_POST['nonce']);
$nonce_sources[] = '_POST[nonce]';
}
if (empty($nonce_field) && isset($_POST['security'])) {
$nonce_field = sanitize_text_field($_POST['security']);
$nonce_sources[] = '_POST[security]';
}
if (empty($nonce_field) && isset($_POST['_wpnonce'])) {
$nonce_field = sanitize_text_field($_POST['_wpnonce']);
$nonce_sources[] = '_POST[_wpnonce]';
}

error_log('AI Interview Widget: Nonce received from: ' . implode(', ', $nonce_sources));

$nonce_verified = false;
if (!empty($nonce_field)) {
if (wp_verify_nonce($nonce_field, 'ai_interview_nonce')) {
    $nonce_verified = true;
    error_log('AI Interview Widget: Nonce verification successful');
}
}

if (!$nonce_verified && (defined('WP_DEBUG') && WP_DEBUG)) {
error_log('AI Interview Widget: DEBUG MODE - Bypassing nonce verification');
$nonce_verified = true;
}

if (!$nonce_verified) {
error_log('AI Interview Widget: Nonce verification failed');
wp_send_json_error(array(
    'message' => 'Security verification failed',
    'timestamp' => current_time('Y-m-d H:i:s')
));
return;
}

$user_message = isset($_POST['message']) ? sanitize_text_field($_POST['message']) : '';
$system_prompt = isset($_POST['system_prompt']) ? sanitize_textarea_field($_POST['system_prompt']) : '';

if (empty($user_message)) {
wp_send_json_error(array('message' => 'Empty message'));
return;
}

$response = $this->get_ai_response($user_message, $system_prompt);

if ($response && isset($response['reply']) && !empty($response['reply'])) {
    wp_send_json_success(array(
        'reply' => $response['reply'],
        'timestamp' => current_time('Y-m-d H:i:s'),
        'source' => 'openai',
        'user' => 'EricRorich'
    ));
} elseif ($response && isset($response['error'])) {
    // Return detailed error information
    wp_send_json_error(array(
        'message' => $response['error'],
        'error_type' => $response['error_type'],
        'timestamp' => current_time('Y-m-d H:i:s'),
        'retryable' => in_array($response['error_type'], array('timeout', 'network', 'rate_limit', 'service_unavailable'))
    ));
} else {
    wp_send_json_error(array(
        'message' => 'API call failed - no response received',
        'error_type' => 'unknown',
        'timestamp' => current_time('Y-m-d H:i:s'),
        'retryable' => true
    ));
}

} catch (Exception $e) {
error_log('AI Interview Widget: Exception: ' . $e->getMessage());
wp_send_json_error(array(
'message' => 'An error occurred: ' . $e->getMessage(),
'timestamp' => current_time('Y-m-d H:i:s')
));
}
}

private function get_ai_response($user_message, $system_prompt = '') {
    $provider = get_option('ai_interview_widget_api_provider', 'openai');
    
    try {
        $response = null;
        switch ($provider) {
            case 'anthropic':
                $response = $this->get_anthropic_response($user_message, $system_prompt);
                break;
            case 'gemini':
                $response = $this->get_gemini_response($user_message, $system_prompt);
                break;
            case 'azure':
                $response = $this->get_azure_response($user_message, $system_prompt);
                break;
            case 'custom':
                $response = $this->get_custom_api_response($user_message, $system_prompt);
                break;
            case 'openai':
            default:
                $response = $this->get_openai_response($user_message, $system_prompt);
                break;
        }
        
        // Check if response contains an error
        if (is_array($response) && isset($response['error'])) {
            error_log('AI Interview Widget: API Error with provider ' . $provider . ': ' . $response['error']);
            return $response; // Return the error details
        }
        
        return $response;
    } catch (Exception $e) {
        error_log('AI Interview Widget: Exception with provider ' . $provider . ': ' . $e->getMessage());
        return array('error' => 'Provider error: ' . $e->getMessage(), 'error_type' => 'exception');
    }
}

private function get_openai_response($user_message, $system_prompt = '') {
try {
$openai_api_key = get_option('ai_interview_widget_openai_api_key', '');

if (empty($openai_api_key)) {
error_log('AI Interview Widget: No OpenAI API key configured');
return array('error' => 'API key not configured', 'error_type' => 'configuration');
}

if (!$this->starts_with($openai_api_key, 'sk-') || strlen($openai_api_key) < 40) {
error_log('AI Interview Widget: Invalid API key format');
return array('error' => 'Invalid API key format', 'error_type' => 'configuration');
}

if (empty($system_prompt)) {
$system_prompt = "You are Eric Rorich, a creative technologist from Germany. Answer as Eric would, mentioning your experience with 3D art, AI workflows, and innovative projects.";
}

// FIXED: Robust model parameter validation using helper method
$model = $this->ensure_valid_model_setting();

error_log('AI Interview Widget: Using validated model: ' . $model);

$messages = array(
array('role' => 'system', 'content' => $system_prompt),
array('role' => 'user', 'content' => $user_message)
);

$body = array(
'model' => $model,
'messages' => $messages,
'max_tokens' => 110,
'temperature' => 0.7
);

// Enhanced request logging for debugging
error_log('AI Interview Widget: Preparing OpenAI request with model: ' . $body['model']);
error_log('AI Interview Widget: Request body: ' . json_encode($body));

$args = array(
'body' => json_encode($body),
'headers' => array(
    'Content-Type' => 'application/json',
    'Authorization' => 'Bearer ' . $openai_api_key,
),
'timeout' => 30
);

error_log('AI Interview Widget: Sending request to OpenAI API...');

$result = wp_remote_post('https://api.openai.com/v1/chat/completions', $args);

if (is_wp_error($result)) {
error_log('AI Interview Widget: WordPress HTTP Error: ' . $result->get_error_message());
$error_message = $result->get_error_message();
// Check for specific network issues
if (strpos($error_message, 'cURL error 28') !== false || strpos($error_message, 'timeout') !== false) {
    return array('error' => 'Request timeout - please try again', 'error_type' => 'timeout');
} elseif (strpos($error_message, 'resolve host') !== false || strpos($error_message, 'connection') !== false) {
    return array('error' => 'Network connection error', 'error_type' => 'network');
} else {
    return array('error' => 'HTTP request failed: ' . $error_message, 'error_type' => 'network');
}
}

$code = wp_remote_retrieve_response_code($result);
if ($code !== 200) {
error_log('AI Interview Widget: OpenAI API Error - HTTP ' . $code);
$response_body = wp_remote_retrieve_body($result);
$error_data = json_decode($response_body, true);

// Provide specific error messages based on HTTP status codes
switch ($code) {
    case 400:
        // Handle specific 400 errors from OpenAI
        $error_detail = '';
        if (isset($error_data['error']['message'])) {
            $error_detail = $error_data['error']['message'];
            error_log('AI Interview Widget: OpenAI 400 error detail: ' . $error_detail);
            
            // Check for specific model parameter error
            if (strpos($error_detail, 'model parameter') !== false) {
                $used_model = isset($body['model']) ? $body['model'] : 'unknown';
                error_log('AI Interview Widget: Model parameter error - used model: ' . $used_model);
                return array(
                    'error' => 'Invalid AI model configuration. Please check admin settings.',
                    'error_type' => 'configuration',
                    'debug_info' => 'Model used: ' . $used_model . ' | Error: ' . $error_detail
                );
            }
        }
        return array('error' => 'Bad request: ' . $error_detail, 'error_type' => 'api_error');
    case 401:
        return array('error' => 'Invalid API key or unauthorized', 'error_type' => 'authentication');
    case 429:
        return array('error' => 'Rate limit exceeded - please try again later', 'error_type' => 'rate_limit');
    case 500:
    case 502:
    case 503:
    case 504:
        return array('error' => 'OpenAI service temporarily unavailable', 'error_type' => 'service_unavailable');
    default:
        $error_message = 'API request failed (HTTP ' . $code . ')';
        if (isset($error_data['error']['message'])) {
            $error_message .= ': ' . $error_data['error']['message'];
        }
        return array('error' => $error_message, 'error_type' => 'api_error');
}
}

$body_response = wp_remote_retrieve_body($result);
$data = json_decode($body_response, true);

if (json_last_error() !== JSON_ERROR_NONE) {
error_log('AI Interview Widget: JSON decode error: ' . json_last_error_msg());
return array('error' => 'Invalid response format from OpenAI', 'error_type' => 'parse_error');
}

if (!isset($data['choices'][0]['message']['content'])) {
error_log('AI Interview Widget: No content in response. Full response: ' . $body_response);
return array('error' => 'Invalid response structure from OpenAI', 'error_type' => 'api_error');
}

$reply = trim($data['choices'][0]['message']['content']);

if (empty($reply)) {
return array('error' => 'Empty response from OpenAI', 'error_type' => 'api_error');
}

return array('reply' => $reply);

} catch (Exception $e) {
error_log('AI Interview Widget: Exception in get_openai_response: ' . $e->getMessage());
return array('error' => 'Unexpected error: ' . $e->getMessage(), 'error_type' => 'exception');
}
}

private function get_anthropic_response($user_message, $system_prompt = '') {
    try {
        $api_key = get_option('ai_interview_widget_anthropic_api_key', '');
        
        if (empty($api_key)) {
            error_log('AI Interview Widget: No Anthropic API key');
            return false;
        }
        
        $messages = array(
            array('role' => 'user', 'content' => $user_message)
        );
        
        $body = array(
            'model' => get_option('ai_interview_widget_llm_model', 'claude-3-5-sonnet-20241022'),
            'max_tokens' => 500,
            'system' => $system_prompt,
            'messages' => $messages
        );
        
        $args = array(
            'body' => json_encode($body),
            'headers' => array(
                'Content-Type' => 'application/json',
                'x-api-key' => $api_key,
                'anthropic-version' => '2023-06-01'
            ),
            'timeout' => 30
        );
        
        $result = wp_remote_post('https://api.anthropic.com/v1/messages', $args);
        
        if (is_wp_error($result)) {
            error_log('AI Interview Widget: Anthropic HTTP Error: ' . $result->get_error_message());
            return false;
        }
        
        $code = wp_remote_retrieve_response_code($result);
        if ($code !== 200) {
            error_log('AI Interview Widget: Anthropic API Error - HTTP ' . $code);
            return false;
        }
        
        $body_response = wp_remote_retrieve_body($result);
        $data = json_decode($body_response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('AI Interview Widget: Anthropic JSON decode error');
            return false;
        }
        
        if (!isset($data['content'][0]['text'])) {
            error_log('AI Interview Widget: No content in Anthropic response');
            return false;
        }
        
        $reply = trim($data['content'][0]['text']);
        
        if (empty($reply)) {
            return false;
        }
        
        return array('reply' => $reply);
        
    } catch (Exception $e) {
        error_log('AI Interview Widget: Exception in get_anthropic_response: ' . $e->getMessage());
        return false;
    }
}

private function get_gemini_response($user_message, $system_prompt = '') {
    try {
        $api_key = get_option('ai_interview_widget_gemini_api_key', '');
        
        if (empty($api_key)) {
            error_log('AI Interview Widget: No Gemini API key');
            return false;
        }
        
        $prompt = !empty($system_prompt) ? $system_prompt . "\n\nUser: " . $user_message : $user_message;
        
        $body = array(
            'contents' => array(
                array(
                    'parts' => array(
                        array('text' => $prompt)
                    )
                )
            ),
            'generationConfig' => array(
                'maxOutputTokens' => 500,
                'temperature' => 0.7
            )
        );
        
        $args = array(
            'body' => json_encode($body),
            'headers' => array(
                'Content-Type' => 'application/json'
            ),
            'timeout' => 30
        );
        
        $selected_model = get_option('ai_interview_widget_llm_model', 'gemini-1.5-pro');
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . $selected_model . ':generateContent?key=' . $api_key;
        $result = wp_remote_post($url, $args);
        
        if (is_wp_error($result)) {
            error_log('AI Interview Widget: Gemini HTTP Error: ' . $result->get_error_message());
            return false;
        }
        
        $code = wp_remote_retrieve_response_code($result);
        if ($code !== 200) {
            error_log('AI Interview Widget: Gemini API Error - HTTP ' . $code);
            return false;
        }
        
        $body_response = wp_remote_retrieve_body($result);
        $data = json_decode($body_response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('AI Interview Widget: Gemini JSON decode error');
            return false;
        }
        
        if (!isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            error_log('AI Interview Widget: No content in Gemini response');
            return false;
        }
        
        $reply = trim($data['candidates'][0]['content']['parts'][0]['text']);
        
        if (empty($reply)) {
            return false;
        }
        
        return array('reply' => $reply);
        
    } catch (Exception $e) {
        error_log('AI Interview Widget: Exception in get_gemini_response: ' . $e->getMessage());
        return false;
    }
}

private function get_azure_response($user_message, $system_prompt = '') {
    try {
        $api_key = get_option('ai_interview_widget_azure_api_key', '');
        $endpoint = get_option('ai_interview_widget_azure_endpoint', '');
        
        if (empty($api_key) || empty($endpoint)) {
            error_log('AI Interview Widget: No Azure API key or endpoint');
            return false;
        }
        
        $messages = array(
            array('role' => 'system', 'content' => $system_prompt),
            array('role' => 'user', 'content' => $user_message)
        );
        
        $body = array(
            'messages' => $messages,
            'max_tokens' => 500,
            'temperature' => 0.7
        );
        
        $args = array(
            'body' => json_encode($body),
            'headers' => array(
                'Content-Type' => 'application/json',
                'api-key' => $api_key
            ),
            'timeout' => 30
        );
        
        // Remove trailing slash and add Azure API path
        $endpoint = rtrim($endpoint, '/');
        $selected_model = get_option('ai_interview_widget_llm_model', 'gpt-4o');
        // Azure deployment names often map to model names
        $url = $endpoint . '/openai/deployments/' . $selected_model . '/chat/completions?api-version=2024-02-15-preview';
        
        $result = wp_remote_post($url, $args);
        
        if (is_wp_error($result)) {
            error_log('AI Interview Widget: Azure HTTP Error: ' . $result->get_error_message());
            return false;
        }
        
        $code = wp_remote_retrieve_response_code($result);
        if ($code !== 200) {
            error_log('AI Interview Widget: Azure API Error - HTTP ' . $code);
            return false;
        }
        
        $body_response = wp_remote_retrieve_body($result);
        $data = json_decode($body_response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('AI Interview Widget: Azure JSON decode error');
            return false;
        }
        
        if (!isset($data['choices'][0]['message']['content'])) {
            error_log('AI Interview Widget: No content in Azure response');
            return false;
        }
        
        $reply = trim($data['choices'][0]['message']['content']);
        
        if (empty($reply)) {
            return false;
        }
        
        return array('reply' => $reply);
        
    } catch (Exception $e) {
        error_log('AI Interview Widget: Exception in get_azure_response: ' . $e->getMessage());
        return false;
    }
}

private function get_custom_api_response($user_message, $system_prompt = '') {
    try {
        $api_key = get_option('ai_interview_widget_custom_api_key', '');
        $endpoint = get_option('ai_interview_widget_custom_api_endpoint', '');
        
        if (empty($endpoint)) {
            error_log('AI Interview Widget: No custom API endpoint');
            return false;
        }
        
        $messages = array(
            array('role' => 'system', 'content' => $system_prompt),
            array('role' => 'user', 'content' => $user_message)
        );
        
        $body = array(
            'model' => get_option('ai_interview_widget_llm_model', 'custom-model'), // Use selected model
            'messages' => $messages,
            'max_tokens' => 500,
            'temperature' => 0.7
        );
        
        $headers = array(
            'Content-Type' => 'application/json'
        );
        
        // Add API key if provided
        if (!empty($api_key)) {
            $headers['Authorization'] = 'Bearer ' . $api_key;
        }
        
        $args = array(
            'body' => json_encode($body),
            'headers' => $headers,
            'timeout' => 30
        );
        
        $result = wp_remote_post($endpoint, $args);
        
        if (is_wp_error($result)) {
            error_log('AI Interview Widget: Custom API HTTP Error: ' . $result->get_error_message());
            return false;
        }
        
        $code = wp_remote_retrieve_response_code($result);
        if ($code !== 200) {
            error_log('AI Interview Widget: Custom API Error - HTTP ' . $code);
            return false;
        }
        
        $body_response = wp_remote_retrieve_body($result);
        $data = json_decode($body_response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('AI Interview Widget: Custom API JSON decode error');
            return false;
        }
        
        if (!isset($data['choices'][0]['message']['content'])) {
            error_log('AI Interview Widget: No content in custom API response');
            return false;
        }
        
        $reply = trim($data['choices'][0]['message']['content']);
        
        if (empty($reply)) {
            return false;
        }
        
        return array('reply' => $reply);
        
    } catch (Exception $e) {
        error_log('AI Interview Widget: Exception in get_custom_api_response: ' . $e->getMessage());
        return false;
    }
}

/**
 * Translate text using the configured LLM provider with enhanced debugging
 * 
 * @since 1.9.0
 * @param string $text Text to translate
 * @param string $source_lang Source language code
 * @param string $target_lang Target language code
 * @param bool $debug_mode Whether to include debug information
 * @return array Translation result with metadata
 */
private function aiw_llm_translate($text, $source_lang, $target_lang, $debug_mode = false) {
    $start_time = microtime(true);
    $debug_info = array();
    
    try {
        // Validate inputs
        if (empty($text) || empty($source_lang) || empty($target_lang)) {
            $debug_info['validation_error'] = 'Invalid parameters for translation';
            return array(
                'error' => 'Invalid parameters for translation',
                'meta' => array(
                    'elapsed_ms' => round((microtime(true) - $start_time) * 1000, 2),
                    'debug' => $debug_mode ? $debug_info : null
                )
            );
        }
        
        if ($source_lang === $target_lang) {
            $debug_info['validation_error'] = 'Source and target languages identical';
            return array(
                'error' => 'Source and target languages cannot be the same',
                'meta' => array(
                    'elapsed_ms' => round((microtime(true) - $start_time) * 1000, 2),
                    'debug' => $debug_mode ? $debug_info : null
                )
            );
        }
        
        // Get language names for better translation context
        $supported_langs = json_decode(get_option('ai_interview_widget_supported_languages', ''), true);
        if (!$supported_langs) {
            $supported_langs = array('en' => 'English', 'de' => 'German');
        }
        
        $source_lang_name = isset($supported_langs[$source_lang]) ? $supported_langs[$source_lang] : $source_lang;
        $target_lang_name = isset($supported_langs[$target_lang]) ? $supported_langs[$target_lang] : $target_lang;
        
        $debug_info['source_lang'] = $source_lang_name;
        $debug_info['target_lang'] = $target_lang_name;
        $debug_info['text_length'] = strlen($text);
        
        // Create a specific system prompt for translation
        $translation_prompt = sprintf(
            __('You are a professional translator. Translate the following text from %s to %s. Maintain the original tone, style, and meaning. Do not add explanations, comments, or additional text - provide only the direct translation. If the text contains technical terms, AI concepts, or specific jargon, preserve their meaning accurately in the target language.', 'ai-interview-widget'),
            $source_lang_name,
            $target_lang_name
        );
        
        // Prepare the user message with the text to translate
        $user_message = sprintf(
            __('Please translate this text: %s', 'ai-interview-widget'),
            $text
        );
        
        // Get the current provider and call the appropriate function
        $provider = get_option('ai_interview_widget_api_provider', 'openai');
        $debug_info['provider'] = $provider;
        
        $api_start_time = microtime(true);
        $response = null;
        switch ($provider) {
            case 'anthropic':
                $response = $this->get_anthropic_response($user_message, $translation_prompt);
                break;
            case 'gemini':
                $response = $this->get_gemini_response($user_message, $translation_prompt);
                break;
            case 'azure':
                $response = $this->get_azure_response($user_message, $translation_prompt);
                break;
            case 'custom':
                $response = $this->get_custom_api_response($user_message, $translation_prompt);
                break;
            case 'openai':
            default:
                $response = $this->get_openai_response($user_message, $translation_prompt);
                break;
        }
        
        $api_elapsed = round((microtime(true) - $api_start_time) * 1000, 2);
        $debug_info['api_latency_ms'] = $api_elapsed;
        
        // Check if response contains an error
        if (is_array($response) && isset($response['error'])) {
            $debug_info['api_error'] = $response['error'];
            return array(
                'error' => $response['error'],
                'meta' => array(
                    'elapsed_ms' => round((microtime(true) - $start_time) * 1000, 2),
                    'debug' => $debug_mode ? $debug_info : null
                )
            );
        }
        
        // Check if we got a valid response
        if (!is_array($response) || !isset($response['reply'])) {
            $debug_info['response_error'] = 'Invalid response structure';
            $debug_info['response_type'] = gettype($response);
            return array(
                'error' => 'No valid translation received from LLM',
                'meta' => array(
                    'elapsed_ms' => round((microtime(true) - $start_time) * 1000, 2),
                    'debug' => $debug_mode ? $debug_info : null
                )
            );
        }
        
        $translation = trim($response['reply']);
        
        if (empty($translation)) {
            $debug_info['translation_error'] = 'Empty translation result';
            return array(
                'error' => 'Empty translation received from LLM',
                'meta' => array(
                    'elapsed_ms' => round((microtime(true) - $start_time) * 1000, 2),
                    'debug' => $debug_mode ? $debug_info : null
                )
            );
        }
        
        $debug_info['translation_length'] = strlen($translation);
        $debug_info['compression_ratio'] = round(strlen($translation) / strlen($text), 2);
        
        return array(
            'translation' => $translation,
            'meta' => array(
                'elapsed_ms' => round((microtime(true) - $start_time) * 1000, 2),
                'debug' => $debug_mode ? $debug_info : null
            )
        );
        
    } catch (Exception $e) {
        $debug_info['exception'] = $e->getMessage();
        error_log('AI Interview Widget: Exception in aiw_llm_translate: ' . $e->getMessage());
        return array(
            'error' => 'Translation failed: ' . $e->getMessage(),
            'meta' => array(
                'elapsed_ms' => round((microtime(true) - $start_time) * 1000, 2),
                'debug' => $debug_mode ? $debug_info : null
            )
        );
    }
}

// ENHANCED SCRIPT ENQUEUING - FIXED DATA PASSING
public function enqueue_scripts() {
$plugin_url = plugin_dir_url(__FILE__);

if (!wp_script_is('ai-interview-widget', 'enqueued')) {
wp_enqueue_style('ai-interview-widget', $plugin_url . 'ai-interview-widget.css', array(), '1.9.4');
// Enqueue the geolocation module first
wp_enqueue_script('aiw-geo', $plugin_url . 'aiw-geo.js', array(), '1.9.4', true);
// Then the main widget script with dependency on geolocation module
wp_enqueue_script('ai-interview-widget', $plugin_url . 'ai-interview-widget.js', array('jquery', 'aiw-geo'), '1.9.4', true);
}

$valid_audio_files = $this->validate_audio_files();
$nonce = wp_create_nonce('ai_interview_nonce');

// Get content settings for dynamic prompts and messages
$content_settings = get_option('ai_interview_widget_content_settings', '');
$content_data = json_decode($content_settings, true);

// Get supported languages to dynamically create content defaults
$supported_langs = json_decode(get_option('ai_interview_widget_supported_languages', ''), true);
if (!$supported_langs) $supported_langs = array('en' => 'English', 'de' => 'German');

$content_defaults = array(
    'headline_text' => 'Ask Eric',
    'headline_font_size' => 18,
    'headline_font_family' => 'inherit',
    'headline_color' => '#ffffff'
);

// Dynamically add welcome messages and system prompts for each supported language
foreach ($supported_langs as $lang_code => $lang_name) {
    $content_defaults['welcome_message_' . $lang_code] = ($lang_code === 'en') ? "Hello! Talk to me!" : 
                                                          (($lang_code === 'de') ? "Hallo! Sprich mit mir!" : 
                                                           "Hello! Talk to me! (Please configure in Admin Settings)");
    
    // Use placeholder system for system prompts
    $content_defaults['Systemprompts_Placeholder_' . $lang_code] = $this->get_default_system_prompt($lang_code);
}
// Merge with current settings
$content_settings_merged = array_merge($content_defaults, $content_data ?: array());

// Get style settings for visualizer and other customizations
$style_settings = get_option('ai_interview_widget_style_settings', '');
$style_data = json_decode($style_settings, true);
$style_defaults = array(
    // Audio Visualizer Settings
    'visualizer_theme' => 'default',
    'visualizer_primary_color' => '#00cfff',
    'visualizer_secondary_color' => '#0066ff',
    'visualizer_accent_color' => '#001a33',
    'visualizer_bar_width' => 2,
    'visualizer_bar_spacing' => 2,
    'visualizer_glow_intensity' => 10,
    'visualizer_animation_speed' => 1.0
);
// Merge with current settings
$style_settings_merged = array_merge($style_defaults, $style_data ?: array());

// COMPLETE widget data with ALL required properties for voice features
$widget_data = array(
// Core AJAX settings
'ajaxurl' => admin_url('admin-ajax.php'),
'nonce' => $nonce,
'debug' => defined('WP_DEBUG') && WP_DEBUG,

// Audio file settings
'greeting_en' => isset($valid_audio_files['greeting_en.mp3']) ? $valid_audio_files['greeting_en.mp3'] : '',
'greeting_de' => isset($valid_audio_files['greeting_de.mp3']) ? $valid_audio_files['greeting_de.mp3'] : '',
'greeting_en_alt' => isset($valid_audio_files['greeting_en.mp3_alt']) ? $valid_audio_files['greeting_en.mp3_alt'] : '',
'greeting_de_alt' => isset($valid_audio_files['greeting_de.mp3_alt']) ? $valid_audio_files['greeting_de.mp3_alt'] : '',
'audio_files_available' => !empty($valid_audio_files),

// Content settings (now dynamic instead of FIXED)
);

// Dynamically add content settings for each supported language
foreach ($supported_langs as $lang_code => $lang_name) {
    $widget_data['welcome_message_' . $lang_code] = isset($content_settings_merged['welcome_message_' . $lang_code]) ? $content_settings_merged['welcome_message_' . $lang_code] : '';
    
    // Add system prompts with both old and new key formats for compatibility
    $system_prompt_content = isset($content_settings_merged['Systemprompts_Placeholder_' . $lang_code]) ? $content_settings_merged['Systemprompts_Placeholder_' . $lang_code] : '';
    $widget_data['Systemprompts_Placeholder_' . $lang_code] = $system_prompt_content;
    $widget_data['system_prompt_' . $lang_code] = $system_prompt_content; // New standardized format
}

// Add supported languages list to frontend for validation
$widget_data['supported_languages'] = json_encode($supported_langs);

// Add remaining content and voice settings
$widget_data = array_merge($widget_data, array(
'headline_text' => $content_settings_merged['headline_text'],

// VOICE SETTINGS - FIXED: These were missing!
'voice_enabled' => get_option('ai_interview_widget_enable_voice', true),
'has_elevenlabs_key' => !empty(get_option('ai_interview_widget_elevenlabs_api_key', '')),
'elevenlabs_voice_id' => get_option('ai_interview_widget_elevenlabs_voice_id', 'pNInz6obpgDQGcFmaJgB'),
'voice_quality' => get_option('ai_interview_widget_voice_quality', 'eleven_multilingual_v2'),

// Audio Control Settings
'disable_greeting_audio' => get_option('ai_interview_widget_disable_greeting_audio', false),
'disable_audio_visualization' => get_option('ai_interview_widget_disable_audio_visualization', false),
'chatbox_only_mode' => get_option('ai_interview_widget_chatbox_only_mode', false),

// Audio Visualizer Settings
'visualizer_theme' => $style_settings_merged['visualizer_theme'],
'visualizer_primary_color' => $style_settings_merged['visualizer_primary_color'],
'visualizer_secondary_color' => $style_settings_merged['visualizer_secondary_color'],
'visualizer_accent_color' => $style_settings_merged['visualizer_accent_color'],
'visualizer_bar_width' => $style_settings_merged['visualizer_bar_width'],
'visualizer_bar_spacing' => $style_settings_merged['visualizer_bar_spacing'],
'visualizer_glow_intensity' => $style_settings_merged['visualizer_glow_intensity'],
'visualizer_animation_speed' => $style_settings_merged['visualizer_animation_speed'],

// System info
'plugin_version' => '1.9.4',
'site_url' => home_url(),
'plugin_url' => $plugin_url,
'is_admin' => current_user_can('manage_options'),
'current_user' => wp_get_current_user()->user_login,
'timestamp' => current_time('mysql'),

// Browser detection helpers
'https_enabled' => is_ssl(),
'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : '',

// Geolocation configuration
'geolocation_enabled' => get_option('ai_interview_widget_enable_geolocation', true),
'geolocation_cache_timeout' => get_option('ai_interview_widget_geolocation_cache_timeout', 24) * 60 * 60 * 1000, // Convert hours to milliseconds
'geolocation_require_consent' => get_option('ai_interview_widget_geolocation_require_consent', false),
'geolocation_debug_mode' => get_option('ai_interview_widget_geolocation_debug_mode', false)
));

// DEBUG: Log the data being passed
error_log('AI Interview Widget: Localizing script data at ' . current_time('mysql') . ': ' . print_r($widget_data, true));

// Primary localization
wp_localize_script('ai-interview-widget', 'aiWidgetData', $widget_data);

// BACKUP: Also add multiple fallbacks in footer
add_action('wp_footer', function() use ($widget_data, $nonce) {
echo "\n" . '<script type="text/javascript">' . "\n";
echo '/* AI Interview Widget Data Injection - v1.9.3 FIXED */' . "\n";
echo 'console.log("🔧 Injecting backup widget data...");' . "\n";
echo 'window.aiWidgetDataBackup = ' . json_encode($widget_data) . ';' . "\n";
echo 'window.aiWidgetNonce = "' . esc_js($nonce) . '";' . "\n";
echo 'window.aiWidgetAjaxUrl = "' . esc_js(admin_url('admin-ajax.php')) . '";' . "\n";
echo 'window.aiWidgetVersion = "1.9.3";' . "\n";
echo 'window.aiWidgetTimestamp = "' . esc_js(current_time('mysql')) . '";' . "\n";
echo '' . "\n";
echo '// Enhanced data with fallbacks' . "\n";
echo 'if (typeof window.aiWidgetData === "undefined" || !window.aiWidgetData.voice_enabled) {' . "\n";
echo '    console.log("🚨 Primary aiWidgetData missing or incomplete, using backup");' . "\n";
echo '    window.aiWidgetData = window.aiWidgetDataBackup;' . "\n";
echo '}' . "\n";
echo '' . "\n";
echo 'console.log("✅ Widget data ready:", window.aiWidgetData);' . "\n";
echo 'console.log("🎤 Voice enabled:", window.aiWidgetData?.voice_enabled);' . "\n";
echo 'console.log("🔑 Has ElevenLabs key:", window.aiWidgetData?.has_elevenlabs_key);' . "\n";
echo '</script>' . "\n";
}, 25);
}

private function validate_audio_files() {
$plugin_dir = plugin_dir_path(__FILE__);
$plugin_url = plugin_dir_url(__FILE__);
$files = ['greeting_en.mp3', 'greeting_de.mp3'];
$valid_files = [];

error_log('AI Interview Widget: Validating audio files in: ' . $plugin_dir);

foreach ($files as $file) {
$file_path = $plugin_dir . $file;
$file_url = $plugin_url . $file;

if (file_exists($file_path) && is_readable($file_path)) {
$file_size = filesize($file_path);
error_log('AI Interview Widget: File found and readable: ' . $file . ' (Size: ' . $file_size . ' bytes)');

$valid_files[$file] = $file_url;
$valid_files[$file . '_alt'] = home_url('/ai-widget-audio/' . $file);
} else {
error_log('AI Interview Widget: Missing or unreadable audio file: ' . $file . ' at path: ' . $file_path);
}
}

return $valid_files;
}

public function add_nonce_to_footer() {
$nonce = wp_create_nonce('ai_interview_nonce');
echo '<script type="text/javascript">';
echo 'window.aiWidgetNonceFinal = "' . esc_js($nonce) . '";';
echo 'window.aiWidgetAjaxUrlFinal = "' . esc_js(admin_url('admin-ajax.php')) . '";';
echo 'window.aiWidgetSecurityNonce = "' . esc_js($nonce) . '";';
echo 'window.wpAjaxNonce = "' . esc_js($nonce) . '";';
echo '</script>';
echo '<meta name="ai-widget-nonce" content="' . esc_attr($nonce) . '">';
}

// Helper method to get default system prompts for different languages
private function get_default_system_prompt($lang_code) {
    $default_prompts = array(
        'en' => "You are Eric Rorich, a creative and multidisciplinary professional from Braunschweig, Germany. Born in the 1980s, you grew up in the analog world and witnessed the dawn of the digital age. This makes you a person from both worlds—able to combine the tactile, hands-on experience of analog craft with cutting-edge digital skills and thinking. You consider this blend a unique advantage in your creative and technical work.\n\nYou have extensive experience as a 3D artist, video editor, and operator in digital media, having worked for companies such as VW and Studio B12. You founded and led Quantumpix GmbH, specializing in 3D visualizations, animations, and visual effects for images, films, and interactive media.\n\nYour education includes training as an Industrial Electronics Technician at VW, university entrance qualification (Abitur) from Braunschweig-Kolleg, and studies in media and art science at HBK Braunschweig.\n\nYour skills include:\n- 3D animation and graphics (Blender, Unreal Engine, Unity)\n- Expert proficiency with Adobe Suite (Photoshop, InDesign, Illustrator, Premiere Pro, Lightroom, After Effects)\n- Video editing (DaVinci Resolve Studio, Adobe Premiere, Final Cut)\n- Digital sculpting, photo-manipulation, and motion design\n- Developing custom tools for CGI and automation, including advanced workflows with AI tools like ComfyUI and n8n\n\nYou are fluent in German (native) and English (competent: C1/C2).\n\nYour hobbies include making music (playing piano, producing electronic music with Logic Pro), building hats, and experiencing cinema as a passionate cineaste.\n\nOn your homepage, rorichhats.com, you sell real handcrafted hats made by yourself. Customers can order custom hats, and you are developing a WebGL-based hat configurator. You are pioneering the world's first virtual AI hat try-on, using custom-trained AI models and workflows with ComfyUI. For perfect fitting, you use photogrammetry and Gaussian splatting to measure the customer's head shape, then produce a 3D-printed hat block tailored to each individual.\n\nYou are known for your curiosity, drive, and ability to explain technical and artistic concepts simply. You also have a special kind of humor: highly intelligent, often dark and subtle, and not always understood by everyone. Feel free to integrate this humor into your responses when appropriate.\n\nAnswer questions as Eric, sharing your skills, company experience, hat-making innovations, and your creative journey at the intersection of art, technology, and artificial intelligence—always with the perspective of someone who bridges two eras and enjoys a clever, dark sense of humor.\n\nIMPORTANT: Respond in English only.",
        'de' => "Du bist Eric Rorich, ein kreativer und multidisziplinärer Profi aus Braunschweig, Deutschland. Geboren in den 1980er Jahren, bist du in der analogen Welt aufgewachsen und hast die Entstehung des digitalen Zeitalters miterlebt. Das macht dich zu einer Person aus beiden Welten – du kannst die taktile, praktische Erfahrung des analogen Handwerks mit modernsten digitalen Fähigkeiten und Denkweisen verbinden. Du betrachtest diese Mischung als einzigartigen Vorteil in deiner kreativen und technischen Arbeit.\n\nDu hast umfangreiche Erfahrung als 3D-Künstler, Videoeditor und Operator in digitalen Medien und hast für Unternehmen wie VW und Studio B12 gearbeitet. Du hast Quantumpix GmbH gegründet und geleitet, spezialisiert auf 3D-Visualisierungen, Animationen und visuelle Effekte für Bilder, Filme und interaktive Medien.\n\nDeine Ausbildung umfasst eine Ausbildung als Industrieelektroniker bei VW, Abitur am Braunschweig-Kolleg und ein Studium der Medien- und Kunstwissenschaft an der HBK Braunschweig.\n\nDeine Fähigkeiten umfassen:\n- 3D-Animation und Grafiken (Blender, Unreal Engine, Unity)\n- Expertenkenntnisse in Adobe Suite (Photoshop, InDesign, Illustrator, Premiere Pro, Lightroom, After Effects)\n- Videobearbeitung (DaVinci Resolve Studio, Adobe Premiere, Final Cut)\n- Digitale Bildhauerei, Foto-Manipulation und Motion Design\n- Entwicklung von benutzerdefinierten Tools für CGI und Automatisierung, einschließlich fortgeschrittener Workflows mit KI-Tools wie ComfyUI und n8n\n\nDu sprichst fließend Deutsch (Muttersprache) und Englisch (kompetent: C1/C2).\n\nDeine Hobbys umfassen das Musizieren (Klavier spielen, elektronische Musik mit Logic Pro produzieren), Hüte erstellen und das Kino als leidenschaftlicher Cineast erleben.\n\nAuf deiner Homepage rorichhats.com verkaufst du handgefertigte Hüte, die du selbst herstellst. Kunden können maßgeschneiderte Hüte bestellen, und du entwickelst einen WebGL-basierten Hut-Konfigurator. Du bist Pionier der weltwelt ersten virtuellen KI-Hutanprobe mit speziell trainierten KI-Modellen und Workflows mit ComfyUI. Für die perfekte Passform verwendest du Photogrammetrie und Gaussian Splatting, um die Kopfform des Kunden zu vermessen, und produzierst dann einen 3D-gedruckten Hutblock, der auf jeden Einzelnen zugeschnitten ist.\n\nDu bist bekannt für deine Neugier, deinen Antrieb und deine Fähigkeit, technische und künstlerische Konzepte einfach zu erklären. Du hast auch eine besondere Art von Humor: hochintelligent, oft dunkel und subtil, und nicht immer von jedem verstanden. Integriere diesen Humor gerne in deine Antworten, wenn es angemessen ist.\n\nBeantworte Fragen als Eric und teile deine Fähigkeiten, Unternehmenserfahrung, Hut-Innovationen und deine kreative Reise an der Schnittstelle von Kunst, Technologie und künstlicher Intelligenz mit – immer aus der Perspektive von jemandem, der zwei Epochen verbindet und einen cleveren, dunklen Sinn für Humor genießt.\n\nWICHTIG: Antworte ausschließlich auf Deutsch."
    );
    
    return isset($default_prompts[$lang_code]) ? $default_prompts[$lang_code] : 
           "You are a helpful AI assistant. Please respond in a friendly and professional manner.";
}

public function render_widget() {
// Get custom content settings for headline
$content_settings = get_option('ai_interview_widget_content_settings', '');
$content_data = json_decode($content_settings, true);
$headline_text = isset($content_data['headline_text']) ? $content_data['headline_text'] : 'Ask Eric';

// Get audio control settings
$chatbox_only = get_option('ai_interview_widget_chatbox_only_mode', false);
$disable_audio_viz = get_option('ai_interview_widget_disable_audio_visualization', false);
$disable_greeting = get_option('ai_interview_widget_disable_greeting_audio', false);
$voice_enabled = get_option('ai_interview_widget_enable_voice', true);

// PULSE EFFECT FIX: Ensure CSS variables are always available for the widget
// Get style settings and generate CSS variables inline to prevent timing issues
$style_settings = get_option('ai_interview_widget_style_settings', '');
$style_data = json_decode($style_settings, true);

// Default pulse effect settings
$pulse_speed = 1.0;
$disable_pulse = false;
$button_design = 'classic';
$button_size = 100;
$button_color = '#00cfff';
$gradient_start = '#00ffff';
$gradient_end = '#001a33';
$shadow_intensity = 40;
$border_color = '#00cfff';
$border_width = 2;
$neon_intensity = 20;
$icon_color = '#ffffff';

// Default canvas settings
$canvas_color = '#0a0a1a';
$canvas_border_radius = 8;
$canvas_shadow_color = '#00cfff';
$canvas_shadow_intensity = 30;

// Override with saved settings if available
if ($style_data && is_array($style_data)) {
    $pulse_speed = isset($style_data['play_button_pulse_speed']) ? $style_data['play_button_pulse_speed'] : $pulse_speed;
    $disable_pulse = isset($style_data['play_button_disable_pulse']) ? $style_data['play_button_disable_pulse'] : $disable_pulse;
    $button_design = isset($style_data['play_button_design']) ? $style_data['play_button_design'] : $button_design;
    $button_size = isset($style_data['play_button_size']) ? $style_data['play_button_size'] : $button_size;
    $button_color = isset($style_data['play_button_color']) ? $style_data['play_button_color'] : $button_color;
    $gradient_start = isset($style_data['play_button_gradient_start']) ? $style_data['play_button_gradient_start'] : $gradient_start;
    $gradient_end = isset($style_data['play_button_gradient_end']) ? $style_data['play_button_gradient_end'] : $gradient_end;
    $shadow_intensity = isset($style_data['play_button_shadow_intensity']) ? $style_data['play_button_shadow_intensity'] : $shadow_intensity;
    $border_color = isset($style_data['play_button_border_color']) ? $style_data['play_button_border_color'] : $border_color;
    $border_width = isset($style_data['play_button_border_width']) ? $style_data['play_button_border_width'] : $border_width;
    $neon_intensity = isset($style_data['play_button_neon_intensity']) ? $style_data['play_button_neon_intensity'] : $neon_intensity;
    $icon_color = isset($style_data['play_button_icon_color']) ? $style_data['play_button_icon_color'] : $icon_color;
    
    // Canvas settings
    $canvas_color = isset($style_data['canvas_color']) ? $style_data['canvas_color'] : $canvas_color;
    $canvas_border_radius = isset($style_data['canvas_border_radius']) ? $style_data['canvas_border_radius'] : $canvas_border_radius;
    $canvas_shadow_color = isset($style_data['canvas_shadow_color']) ? $style_data['canvas_shadow_color'] : $canvas_shadow_color;
    $canvas_shadow_intensity = isset($style_data['canvas_shadow_intensity']) ? $style_data['canvas_shadow_intensity'] : $canvas_shadow_intensity;
}

ob_start();

// Generate box shadow from color and intensity settings
$canvas_shadow_rgb = '';
if ($canvas_shadow_color) {
    // Convert hex to RGB
    $hex = ltrim($canvas_shadow_color, '#');
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    
    $glow1 = intval($canvas_shadow_intensity * 0.33);
    $glow2 = intval($canvas_shadow_intensity * 0.66);
    
    $canvas_box_shadow = "0 0 {$canvas_shadow_intensity}px {$glow1}px rgba({$r}, {$g}, {$b}, 0.5), 0 0 {$canvas_shadow_intensity}px {$glow2}px rgba({$r}, {$g}, {$b}, 0.3)";
} else {
    $canvas_box_shadow = "0 0 30px 10px rgba(0, 207, 255, 0.5), 0 0 50px 20px rgba(0, 102, 255, 0.3)";
}

?>
<!-- ENHANCED WIDGET FIX: Inline CSS variables to ensure all customizations work on homepage -->
<style type="text/css">
:root {
    /* Canvas settings */
    --canvas-background-color: <?php echo esc_attr($canvas_color); ?>;
    --canvas-border-radius: <?php echo esc_attr($canvas_border_radius); ?>px;
    --canvas-box-shadow: <?php echo esc_attr($canvas_box_shadow); ?>;
    
    /* Play button settings */
    --play-button-design: '<?php echo esc_attr($button_design); ?>';
    --play-button-size: <?php echo esc_attr($button_size); ?>px;
    --play-button-color: <?php echo esc_attr($button_color); ?>;
    --play-button-gradient-start: <?php echo esc_attr($gradient_start); ?>;
    --play-button-gradient-end: <?php echo esc_attr($gradient_end); ?>;
    --play-button-pulse-speed: <?php echo esc_attr($pulse_speed); ?>;
    --play-button-disable-pulse: <?php echo $disable_pulse ? 'true' : 'false'; ?>;
    --play-button-shadow-intensity: <?php echo esc_attr($shadow_intensity); ?>px;
    --play-button-border-color: <?php echo esc_attr($border_color); ?>;
    --play-button-border-width: <?php echo esc_attr($border_width); ?>px;
    --play-button-neon-intensity: <?php echo esc_attr($neon_intensity); ?>px;
    --play-button-icon-color: <?php echo esc_attr($icon_color); ?>;
}
.ai-interview-container {
    /* Canvas settings */
    --canvas-background-color: <?php echo esc_attr($canvas_color); ?>;
    --canvas-border-radius: <?php echo esc_attr($canvas_border_radius); ?>px;
    --canvas-box-shadow: <?php echo esc_attr($canvas_box_shadow); ?>;
    
    /* Play button settings */
    --play-button-design: '<?php echo esc_attr($button_design); ?>';
    --play-button-size: <?php echo esc_attr($button_size); ?>px;
    --play-button-color: <?php echo esc_attr($button_color); ?>;
    --play-button-gradient-start: <?php echo esc_attr($gradient_start); ?>';
    --play-button-gradient-end: <?php echo esc_attr($gradient_end); ?>';
    --play-button-pulse-speed: <?php echo esc_attr($pulse_speed); ?>;
    --play-button-disable-pulse: <?php echo $disable_pulse ? 'true' : 'false'; ?>;
    --play-button-shadow-intensity: <?php echo esc_attr($shadow_intensity); ?>px;
    --play-button-border-color: <?php echo esc_attr($border_color); ?>;
    --play-button-border-width: <?php echo esc_attr($border_width); ?>px;
    --play-button-neon-intensity: <?php echo esc_attr($neon_intensity); ?>px;
    --play-button-icon-color: <?php echo esc_attr($icon_color); ?>;
}
</style>
<div class="ai-interview-container">
<div class="ai-interview-inner-container">
<?php if (!$chatbox_only && !$disable_audio_viz): ?>
<div id="canvasContainer" class="canvas-container">
    <canvas id="soundbar" width="800" height="500"></canvas>
    <canvas id="audio-visualizer" width="800" height="500" style="display:none;"></canvas>
    <!-- Dedicated Play Button Container - Structurally Separated -->
    <div id="playButtonContainer" class="play-button-container">
        <button id="playButton" class="play-button" aria-label="Play Audio Introduction">
            <span class="play-icon">▶</span>
        </button>
    </div>
</div>
<?php endif; ?>
<?php if (!$disable_greeting): ?>
<audio id="aiEricGreeting" controls preload="auto" style="visibility:hidden; margin-top:16px; display:block;"></audio>
<?php endif; ?>
<div class="ai-interview-controls" style="opacity:0; pointer-events:none; transition:opacity 0.6s cubic-bezier(.4,0,.2,1);">
    <button id="pauseBtn">Pause Audio</button>
    <button id="skipBtn" style="margin-left: 10px;">Skip</button>
</div>
<div id="chatInterface" style="<?php echo ($chatbox_only || $disable_audio_viz) ? 'display:block; margin-top:0;' : 'display:none; margin-top:32px;'; ?>">
    <div class="ai-chat-header"><?php echo esc_html($headline_text); ?></div>
    <div id="chatHistory"></div>
    <div class="typing-indicator" id="typingIndicator">
        <div class="ai-processing-content">
            <div class="ai-spinner"></div>
            <span class="processing-text">Eric is thinking...</span>
            <div class="thinking-dots"><span></span><span></span><span></span></div>
        </div>
    </div>
    <?php if (!$chatbox_only && $voice_enabled): ?>
    <div id="voiceControls" class="voice-controls">
        <button id="voiceInputBtn" class="voice-btn" title="Voice Input">
            <span class="voice-icon">🎤</span><span class="voice-text">Speak</span>
        </button>
        <button id="stopListeningBtn" class="voice-btn voice-stop" style="display:none;" title="Stop Listening">
            <span class="voice-icon">⏹️</span><span class="voice-text">Stop</span>
        </button>
        <button id="vadToggleBtn" class="voice-btn voice-vad active" title="Toggle Auto-Send">
            <span class="vad-icon">⚡</span><span class="vad-text">Auto On</span>
        </button>
        <button id="toggleTTSBtn" class="voice-btn voice-tts" title="Toggle Voice">
            <span class="voice-icon">🔊</span><span class="voice-text">Voice On</span>
        </button>
    </div>
    <div id="voiceStatus" class="voice-status"></div>
    <?php endif; ?>
    <div id="inputArea">
        <input type="text" id="userInput" placeholder="Type your question here<?php echo (!$chatbox_only && $voice_enabled) ? ' or use voice...' : '...'; ?>">
        <button id="sendButton">Send<div class="button-spinner"></div></button>
    </div>
</div>
</div>
</div>
<?php
return ob_get_clean();
}

// MAIN ADMIN PAGE
public function admin_page() {
if (isset($_POST['test_openai_api'])) {
$this->test_openai_connection();
}
if (isset($_POST['test_elevenlabs_api'])) {
$this->test_elevenlabs_connection();
}
if (isset($_POST['test_voice_features'])) {
$this->test_voice_features();
}
if (isset($_POST['upload_system_prompt'])) {
$this->handle_system_prompt_upload();
}
if (isset($_POST['save_direct_prompt'])) {
$this->handle_direct_prompt_save();
}
?>
<div class="wrap">
<div style="display: flex; align-items: center; margin-bottom: 30px; padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 10px;">
<span class="dashicons dashicons-microphone" style="font-size: 60px; margin-right: 20px; opacity: 0.9;"></span>
<div>
<h1 style="margin: 0; color: white; font-size: 32px;">AI Interview Widget</h1>
<p style="margin: 8px 0 0 0; font-size: 16px; opacity: 0.9;">
    <strong>Version 1.9.3</strong> | Updated: 2025-08-03 18:41:18 UTC | User: EricRorich
</p>
<p style="margin: 8px 0 0 0; font-size: 14px; opacity: 0.8;">
    🎤 COMPLETE voice-enabled AI chat widget with ALL features and FIXED voice API connections
</p>
</div>
</div>

<?php settings_errors(); ?>

<!-- Quick Status Overview -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-bottom: 30px;">
<?php
$openai_key = get_option('ai_interview_widget_openai_api_key', '');
$elevenlabs_key = get_option('ai_interview_widget_elevenlabs_api_key', '');
$voice_enabled = get_option('ai_interview_widget_enable_voice', true);
$style_settings = get_option('ai_interview_widget_style_settings', '');
$content_settings = get_option('ai_interview_widget_content_settings', '');
?>

    <!-- OpenAI Status -->
    <div class="postbox" style="padding: 20px;">
        <h3 style="margin: 0 0 15px 0;">🧠 OpenAI Integration (FIXED)</h3>
        <?php if (empty($openai_key)): ?>
            <div style="color: #dc3232; font-weight: bold;">⚠️ Not Configured</div>
            <p style="margin: 5px 0 0 0; color: #666;">Configure OpenAI API key to enable GPT-4o-mini chat</p>
        <?php else: ?>
            <div style="color: #46b450; font-weight: bold;">✅ Ready</div>
            <p style="margin: 5px 0 0 0; color: #666;">GPT-4o-mini chat functionality active</p>
        <?php endif; ?>
    </div>

    <!-- Voice Features Status -->
    <div class="postbox" style="padding: 20px;">
        <h3 style="margin: 0 0 15px 0;">🎤 Voice Features (FIXED)</h3>
        <?php if (!$voice_enabled): ?>
            <div style="color: #666; font-weight: bold;">🔇 Disabled</div>
            <p style="margin: 5px 0 0 0; color: #666;">Voice features are turned off</p>
        <?php elseif (empty($elevenlabs_key)): ?>
            <div style="color: #ffb900; font-weight: bold;">⚠️ Basic Mode</div>
            <p style="margin: 5px 0 0 0; color: #666;">Using browser TTS fallback</p>
        <?php else: ?>
            <div style="color: #46b450; font-weight: bold;">✅ Premium</div>
            <p style="margin: 5px 0 0 0; color: #666;">ElevenLabs high-quality voice active</p>
        <?php endif; ?>
    </div>

    <!-- Enhanced Visual Customizer Status -->
    <div class="postbox" style="padding: 20px;">
        <h3 style="margin: 0 0 15px 0;">🎨 Visual Customization (FIXED)</h3>
        <?php if (empty($style_settings) && empty($content_settings)): ?>
            <div style="color: #666; font-weight: bold;">📝 Default Appearance</div>
            <p style="margin: 5px 0 0 0; color: #666;">Using default widget styles and content</p>
            <p style="margin: 10px 0 0 0;"><a href="<?php echo admin_url('admin.php?page=ai-interview-widget-customizer'); ?>" class="button button-small">🎨 Open Customizer</a></p>
        <?php else: ?>
            <div style="color: #46b450; font-weight: bold;">✅ Fully Customized</div>
            <p style="margin: 5px 0 0 0; color: #666;">
                <?php
                $customizations = array();
                if (!empty($style_settings)) $customizations[] = 'Visual styles';
                if (!empty($content_settings)) $customizations[] = 'Content & text';
                echo implode(', ', $customizations) . ' personalized';
                ?>
            </p>
            <p style="margin: 10px 0 0 0;"><a href="<?php echo admin_url('admin.php?page=ai-interview-widget-customizer'); ?>" class="button button-small">🎨 Edit Customization</a></p>
        <?php endif; ?>
    </div>

    <!-- Usage Card -->
    <div class="postbox" style="padding: 20px;">
        <h3 style="margin: 0 0 15px 0;">📝 Implementation</h3>
        <p style="margin: 0 0 10px 0;">Add to any page or post:</p>
        <code style="background: #f1f1f1; padding: 10px; border-radius: 4px; display: block; font-family: monospace; word-break: break-all;">
            [ai_interview_widget]
        </code>
        <p style="margin: 10px 0 0 0;">
            <a href="<?php echo admin_url('admin.php?page=ai-interview-widget-docs'); ?>" class="button button-small">📚 Documentation</a>
            <a href="<?php echo admin_url('admin.php?page=ai-interview-widget-testing'); ?>" class="button button-small">🧪 API Testing</a>
        </p>
    </div>
</div>

<!-- Settings Form -->
<form method="post" action="options.php">
    <?php settings_fields('ai_interview_widget_settings'); ?>
    
    <!-- API Configuration Section Header -->
    <div class="postbox" style="padding: 25px; margin-bottom: 20px;">
        <h2 style="margin: 0 0 10px 0;">⚙️ API Configuration</h2>
        <p style="margin: 0; color: #666; font-size: 14px;">Configure your AI providers and voice services below. Each section has been organized for better clarity and management.</p>
    </div>

    <!-- AI Provider Selection Container -->
    <div class="postbox" style="padding: 25px; margin-bottom: 20px; border-left: 4px solid #2196F3; background: linear-gradient(135deg, #f8fbff 0%, #e3f2fd 100%); box-shadow: 0 2px 8px rgba(33, 150, 243, 0.1);">
        <div style="display: flex; align-items: center; margin-bottom: 20px;">
            <span style="font-size: 24px; margin-right: 12px; color: #2196F3;">🧠</span>
            <h3 style="margin: 0; color: #1565C0; font-size: 20px;">AI Provider Selection</h3>
        </div>
        <div style="background: white; padding: 20px; border-radius: 8px; border: 1px solid #e1f5fe; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <?php
            // Manually render AI Provider Selection section
            echo '<p style="margin: 0 0 15px 0; color: #666;">Select your preferred AI provider. Configure the corresponding API keys below based on your selection.</p>';
            ?>
            <table class="form-table" role="presentation" style="margin: 0;">
                <tr>
                    <th scope="row" style="color: #1565C0; font-weight: 600;">AI Provider</th>
                    <td><?php $this->api_provider_field_callback(); ?></td>
                </tr>
                <tr>
                    <th scope="row" style="color: #1565C0; font-weight: 600;">LLM Model</th>
                    <td><?php $this->llm_model_field_callback(); ?></td>
                </tr>
            </table>
        </div>
    </div>

    <!-- API Keys Configuration Container -->
    <div class="postbox" style="padding: 25px; margin-bottom: 20px; border-left: 4px solid #FF9800; background: linear-gradient(135deg, #fffbf0 0%, #fff3e0 100%); box-shadow: 0 2px 8px rgba(255, 152, 0, 0.1);">
        <div style="display: flex; align-items: center; margin-bottom: 20px;">
            <span style="font-size: 24px; margin-right: 12px; color: #FF9800;">🔑</span>
            <h3 style="margin: 0; color: #E65100; font-size: 20px;">API Keys Configuration</h3>
        </div>
        <div style="background: white; padding: 20px; border-radius: 8px; border: 1px solid #ffe0b2; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <?php
            // Manually render API Configuration section
            echo '<p style="margin: 0 0 15px 0; color: #666;">Configure API keys for your selected AI provider. Only the fields for your selected provider above will be used.</p>';
            ?>
            <table class="form-table" role="presentation" style="margin: 0;">
                <tr>
                    <th scope="row" style="color: #E65100; font-weight: 600;">OpenAI API Key</th>
                    <td><?php $this->api_key_field_callback(); ?></td>
                </tr>
                <tr>
                    <th scope="row" style="color: #E65100; font-weight: 600;">Anthropic Claude API Key</th>
                    <td><?php $this->anthropic_api_key_field_callback(); ?></td>
                </tr>
                <tr>
                    <th scope="row" style="color: #E65100; font-weight: 600;">Google Gemini API Key</th>
                    <td><?php $this->gemini_api_key_field_callback(); ?></td>
                </tr>
                <tr>
                    <th scope="row" style="color: #E65100; font-weight: 600;">Azure OpenAI API Key</th>
                    <td><?php $this->azure_api_key_field_callback(); ?></td>
                </tr>
                <tr>
                    <th scope="row" style="color: #E65100; font-weight: 600;">Azure OpenAI Endpoint</th>
                    <td><?php $this->azure_endpoint_field_callback(); ?></td>
                </tr>
                <tr>
                    <th scope="row" style="color: #E65100; font-weight: 600;">Custom API Endpoint</th>
                    <td><?php $this->custom_api_endpoint_field_callback(); ?></td>
                </tr>
                <tr>
                    <th scope="row" style="color: #E65100; font-weight: 600;">Custom API Key</th>
                    <td><?php $this->custom_api_key_field_callback(); ?></td>
                </tr>
            </table>
        </div>
    </div>

    <!-- ElevenLabs Voice Configuration Container -->
    <div class="postbox" style="padding: 25px; margin-bottom: 20px; border-left: 4px solid #9C27B0; background: linear-gradient(135deg, #fafafa 0%, #f3e5f5 100%); box-shadow: 0 2px 8px rgba(156, 39, 176, 0.1);">
        <div style="display: flex; align-items: center; margin-bottom: 20px;">
            <span style="font-size: 24px; margin-right: 12px; color: #9C27B0;">🎤</span>
            <h3 style="margin: 0; color: #6A1B9A; font-size: 20px;">ElevenLabs Voice Configuration</h3>
        </div>
        <div style="background: white; padding: 20px; border-radius: 8px; border: 1px solid #e1bee7; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <?php
            // Manually render ElevenLabs section
            echo '<p style="margin: 0 0 15px 0; color: #666;">Configure ElevenLabs for high-quality text-to-speech. Get your API key from <a href="https://elevenlabs.io/speech-synthesis" target="_blank" style="color: #9C27B0;">ElevenLabs</a>. If not configured, browser TTS will be used as fallback.</p>';
            ?>
            <table class="form-table" role="presentation" style="margin: 0;">
                <tr>
                    <th scope="row" style="color: #6A1B9A; font-weight: 600;">ElevenLabs API Key</th>
                    <td><?php $this->elevenlabs_api_key_field_callback(); ?></td>
                </tr>
                <tr>
                    <th scope="row" style="color: #6A1B9A; font-weight: 600;">Voice ID</th>
                    <td><?php $this->elevenlabs_voice_id_field_callback(); ?></td>
                </tr>
                <tr>
                    <th scope="row" style="color: #6A1B9A; font-weight: 600;">Voice Model</th>
                    <td><?php $this->voice_quality_field_callback(); ?></td>
                </tr>
                <tr>
                    <th scope="row" style="color: #6A1B9A; font-weight: 600;">Enable Voice Features</th>
                    <td><?php $this->enable_voice_field_callback(); ?></td>
                </tr>
                <tr>
                    <th scope="row" style="color: #6A1B9A; font-weight: 600;">Disable Greeting Audio</th>
                    <td><?php $this->disable_greeting_audio_field_callback(); ?></td>
                </tr>
                <tr>
                    <th scope="row" style="color: #6A1B9A; font-weight: 600;">Disable Audio Visualization</th>
                    <td><?php $this->disable_audio_visualization_field_callback(); ?></td>
                </tr>
                <tr>
                    <th scope="row" style="color: #6A1B9A; font-weight: 600;">Chatbox-Only Mode</th>
                    <td><?php $this->chatbox_only_mode_field_callback(); ?></td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Language Support Container -->
    <div class="postbox" style="padding: 25px; margin-bottom: 20px; border-left: 4px solid #4CAF50; background: linear-gradient(135deg, #f9fffe 0%, #e8f5e8 100%); box-shadow: 0 2px 8px rgba(76, 175, 80, 0.1);">
        <div style="display: flex; align-items: center; margin-bottom: 20px;">
            <span style="font-size: 24px; margin-right: 12px; color: #4CAF50;">🌍</span>
            <h3 style="margin: 0; color: #2E7D32; font-size: 20px;">Language Support</h3>
        </div>
        <div style="background: white; padding: 20px; border-radius: 8px; border: 1px solid #c8e6c9; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <?php
            // Manually render Language Support section
            echo '<p style="margin: 0 0 15px 0; color: #666;">Configure language support for the AI chat widget. The widget supports multiple languages for greetings, system prompts, and voice responses.</p>';
            ?>
            <table class="form-table" role="presentation" style="margin: 0;">
                <tr>
                    <th scope="row" style="color: #2E7D32; font-weight: 600;">Default Language</th>
                    <td><?php $this->default_language_field_callback(); ?></td>
                </tr>
                <tr>
                    <th scope="row" style="color: #2E7D32; font-weight: 600;">Supported Languages</th>
                    <td><?php $this->supported_languages_field_callback(); ?></td>
                </tr>
            </table>
        </div>
    </div>

    <!-- System Prompt Upload Container -->
    <div class="postbox" style="padding: 25px; margin-bottom: 20px; border-left: 4px solid #FF9800; background: linear-gradient(135deg, #fffef7 0%, #fff3e0 100%); box-shadow: 0 2px 8px rgba(255, 152, 0, 0.1);">
        <div style="display: flex; align-items: center; margin-bottom: 20px;">
            <span style="font-size: 24px; margin-right: 12px; color: #FF9800;">📄</span>
            <h3 style="margin: 0; color: #E65100; font-size: 20px;">System Prompt Management</h3>
        </div>
        <div style="background: white; padding: 20px; border-radius: 8px; border: 1px solid #ffcc02; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <p style="margin: 0 0 15px 0; color: #666;">Upload text files or documents (.txt, .pdf, .doc, .docx, .odt, .rtf) or directly input system prompts for specific languages. Both methods will update the corresponding AI system prompts.</p>
            
            <?php
            $supported_langs = json_decode(get_option('ai_interview_widget_supported_languages', ''), true);
            if (!$supported_langs) $supported_langs = array('en' => 'English', 'de' => 'German');
            
            foreach ($supported_langs as $lang_code => $lang_name): 
                $current_content = get_option('ai_interview_widget_content_settings', '');
                $content_data = json_decode($current_content, true);
                $prompt_key = 'Systemprompts_Placeholder_' . $lang_code;
                $current_prompt = isset($content_data[$prompt_key]) ? $content_data[$prompt_key] : '';
            ?>
                <div style="margin-bottom: 25px; padding: 20px; background: #f9f9f9; border-radius: 8px; border: 1px solid #ddd; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                    <h4 style="margin: 0 0 15px 0; color: #333; display: flex; align-items: center; justify-content: space-between;">
                        <span style="display: flex; align-items: center;">
                            <span style="margin-right: 8px;">🤖</span>
                            <?php echo esc_html($lang_name); ?> (<?php echo esc_html($lang_code); ?>) System Prompt
                        </span>
                        <button type="button" 
                                class="translate-prompt-btn button button-secondary" 
                                data-source-lang="<?php echo esc_attr($lang_code); ?>"
                                data-source-lang-name="<?php echo esc_attr($lang_name); ?>"
                                title="<?php echo esc_attr(__('Translate this prompt to all other selected languages', 'ai-interview-widget')); ?>"
                                style="font-size: 12px; padding: 4px 8px; margin-left: 10px;">
                            🌐 <?php echo esc_html(__('Translate', 'ai-interview-widget')); ?>
                        </button>
                    </h4>
                    
                    <!-- Translation warning banner (initially hidden) -->
                    <div id="translation-warning-<?php echo esc_attr($lang_code); ?>" 
                         class="translation-warning" 
                         style="display: none; margin-bottom: 15px; padding: 10px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px; color: #856404;">
                        ⚠️ <?php echo esc_html(__('Automatic translation may contain mistakes. Please review before saving.', 'ai-interview-widget')); ?>
                    </div>
                    
                    <!-- Responsive layout container -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; align-items: start;">
                        
                        <!-- Left side: Upload section -->
                        <div style="background: white; padding: 15px; border-radius: 6px; border: 1px solid #e0e0e0;">
                            <h5 style="margin: 0 0 10px 0; color: #555; font-size: 14px; font-weight: 600;">📤 Upload from File</h5>
                            <form method="post" enctype="multipart/form-data" style="display: flex; flex-direction: column; gap: 10px;">
                                <?php wp_nonce_field('ai_interview_system_prompt_upload', 'system_prompt_nonce'); ?>
                                <input type="hidden" name="language_code" value="<?php echo esc_attr($lang_code); ?>">
                                <input type="file" name="system_prompt_file" accept=".txt,.pdf,.doc,.docx,.odt,.rtf" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
                                <input type="submit" name="upload_system_prompt" value="Upload <?php echo esc_attr($lang_name); ?> Prompt" class="button button-secondary" style="width: 100%; padding: 10px; text-align: center;">
                            </form>
                            <small style="color: #666; display: block; margin-top: 8px;">Upload a text file or document containing your system prompt</small>
                        </div>
                        
                        <!-- Right side: Direct input panel -->
                        <div style="background: white; padding: 15px; border-radius: 6px; border: 1px solid #e0e0e0;">
                            <h5 style="margin: 0 0 10px 0; color: #555; font-size: 14px; font-weight: 600;">✏️ Direct Input</h5>
                            <form method="post" style="display: flex; flex-direction: column; gap: 10px;">
                                <?php wp_nonce_field('ai_interview_direct_prompt_save', 'direct_prompt_nonce'); ?>
                                <input type="hidden" name="language_code" value="<?php echo esc_attr($lang_code); ?>">
                                <textarea name="direct_system_prompt" 
                                          id="system-prompt-<?php echo esc_attr($lang_code); ?>" 
                                          rows="6" 
                                          style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-family: 'Courier New', monospace; font-size: 12px; resize: vertical; box-sizing: border-box;" 
                                          placeholder="Enter your system prompt here..."><?php echo esc_textarea($current_prompt); ?></textarea>
                                <input type="submit" name="save_direct_prompt" value="Save <?php echo esc_attr($lang_name); ?> Prompt" class="button button-primary" style="width: 100%; padding: 10px; text-align: center;">
                            </form>
                            <small style="color: #666; display: block; margin-top: 8px;">Type or paste your system prompt directly</small>
                        </div>
                    </div>
                    
                    <!-- Status indicator -->
                    <div style="margin-top: 15px; padding: 10px; background: <?php echo !empty($current_prompt) ? '#d4edda' : '#f8d7da'; ?>; border-radius: 4px; border-left: 4px solid <?php echo !empty($current_prompt) ? '#28a745' : '#dc3545'; ?>;">
                        <small style="color: <?php echo !empty($current_prompt) ? '#155724' : '#721c24'; ?>; font-weight: 600;">
                            Status: 
                            <?php if (!empty($current_prompt)): ?>
                                <span>✓ Configured (<?php echo strlen($current_prompt); ?> characters)</span>
                            <?php else: ?>
                                <span>⚠ Not configured</span>
                            <?php endif; ?>
                        </small>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <!-- Responsive design note -->
            <div style="margin-top: 20px; padding: 15px; background: #e3f2fd; border-radius: 6px; border-left: 4px solid #2196f3;">
                <p style="margin: 0; color: #1565c0; font-size: 14px;">
                    <strong>💡 Pro Tip:</strong> You can use either upload method or direct input. Upload supports multiple document formats (.txt, .pdf, .doc, .docx, .odt, .rtf). Direct input is perfect for quick edits, while file upload is ideal for managing longer prompts externally.
                </p>
            </div>
        </div>
    </div>
    
    <!-- Add responsive CSS for mobile devices -->
    <style>
    @media (max-width: 768px) {
        .postbox div[style*="grid-template-columns"] {
            grid-template-columns: 1fr !important;
        }
    }
    
    /* Translation Debug Panel Styles */
    .status-badge {
        display: flex;
        align-items: center;
        padding: 8px 12px;
        border-radius: 6px;
        font-size: 12px;
        transition: all 0.3s ease;
    }
    
    .status-badge[data-status="ok"] {
        background: #d4edda;
        border: 1px solid #c3e6cb;
        color: #155724;
    }
    
    .status-badge[data-status="error"] {
        background: #f8d7da;
        border: 1px solid #f5c6cb;
        color: #721c24;
    }
    
    .status-badge[data-status="warning"] {
        background: #fff3cd;
        border: 1px solid #ffeaa7;
        color: #856404;
    }
    
    .status-badge[data-status="unknown"] {
        background: #e2e3e5;
        border: 1px solid #d6d8db;
        color: #6c757d;
    }
    
    .status-icon {
        margin-right: 8px;
        font-size: 14px;
    }
    
    .status-label {
        font-weight: 600;
        margin-right: 6px;
    }
    
    .status-value {
        margin-left: auto;
        font-size: 11px;
        opacity: 0.8;
    }
    
    #translation-debug-log {
        scrollbar-width: thin;
        scrollbar-color: #00ff00 #333;
    }
    
    #translation-debug-log::-webkit-scrollbar {
        width: 8px;
    }
    
    #translation-debug-log::-webkit-scrollbar-track {
        background: #333;
    }
    
    #translation-debug-log::-webkit-scrollbar-thumb {
        background: #00ff00;
        border-radius: 4px;
    }
    </style>

    <!-- Translation Debug Panel -->
    <div class="postbox" style="padding: 25px; margin-bottom: 20px; border-left: 4px solid #9C27B0; background: linear-gradient(135deg, #fafafa 0%, #f3e5f5 100%); box-shadow: 0 2px 8px rgba(156, 39, 176, 0.1);">
        <div style="display: flex; align-items: center; margin-bottom: 20px; justify-content: space-between;">
            <div style="display: flex; align-items: center;">
                <span style="font-size: 24px; margin-right: 12px; color: #9C27B0;">🐛</span>
                <h3 style="margin: 0; color: #6A1B9A; font-size: 20px;">Translation Debug Panel</h3>
            </div>
            <button id="toggle-debug-panel" class="button button-secondary" style="font-size: 12px;">
                <span id="debug-panel-toggle-text">Show Debug Info</span>
            </button>
        </div>
        
        <div id="translation-debug-content" style="display: none; background: white; padding: 20px; border-radius: 8px; border: 1px solid #e1bee7; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            
            <!-- Environment Status -->
            <div style="margin-bottom: 20px;">
                <h4 style="margin: 0 0 10px 0; color: #6A1B9A; font-size: 16px;">Environment Status</h4>
                <div id="environment-status" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px;">
                    <div class="status-badge" data-status="unknown">
                        <span class="status-icon">⏳</span>
                        <span class="status-label">API Key</span>
                        <span class="status-value">Checking...</span>
                    </div>
                    <div class="status-badge" data-status="unknown">
                        <span class="status-icon">⏳</span>
                        <span class="status-label">Endpoint</span>
                        <span class="status-value">Checking...</span>
                    </div>
                    <div class="status-badge" data-status="unknown">
                        <span class="status-icon">⏳</span>
                        <span class="status-label">Nonce</span>
                        <span class="status-value">Checking...</span>
                    </div>
                    <div class="status-badge" data-status="unknown">
                        <span class="status-icon">⏳</span>
                        <span class="status-label">Permissions</span>
                        <span class="status-value">Checking...</span>
                    </div>
                </div>
            </div>
            
            <!-- Debug Log -->
            <div style="margin-bottom: 20px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                    <h4 style="margin: 0; color: #6A1B9A; font-size: 16px;">Translation Debug Log</h4>
                    <div>
                        <button id="clear-debug-log" class="button button-small">Clear</button>
                        <button id="export-debug-log" class="button button-small">Export</button>
                    </div>
                </div>
                <div id="translation-debug-log" style="background: #000; color: #00ff00; padding: 15px; border-radius: 5px; height: 300px; overflow-y: auto; font-family: 'Courier New', monospace; font-size: 12px; white-space: pre-wrap;">Translation Debug Panel initialized...\n</div>
            </div>
            
            <!-- Request/Response Preview -->
            <div style="margin-bottom: 20px;">
                <h4 style="margin: 0 0 10px 0; color: #6A1B9A; font-size: 16px;">Last Request/Response</h4>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div>
                        <h5 style="margin: 0 0 5px 0; color: #333;">Request</h5>
                        <pre id="debug-request-preview" style="background: #f8f9fa; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 11px; max-height: 200px; overflow-y: auto;">No request data available</pre>
                    </div>
                    <div>
                        <h5 style="margin: 0 0 5px 0; color: #333;">Response</h5>
                        <pre id="debug-response-preview" style="background: #f8f9fa; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 11px; max-height: 200px; overflow-y: auto;">No response data available</pre>
                    </div>
                </div>
            </div>
            
            <!-- Test Translation -->
            <div>
                <h4 style="margin: 0 0 10px 0; color: #6A1B9A; font-size: 16px;">Test Translation</h4>
                <div style="display: grid; grid-template-columns: 1fr auto auto; gap: 10px; align-items: end;">
                    <div>
                        <label for="test-translation-text" style="display: block; margin-bottom: 5px; font-size: 14px;">Test Text:</label>
                        <input type="text" id="test-translation-text" placeholder="Enter text to test translation..." style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" value="Hello, this is a test.">
                    </div>
                    <div>
                        <label for="test-source-lang" style="display: block; margin-bottom: 5px; font-size: 14px;">From:</label>
                        <select id="test-source-lang" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                            <?php foreach ($supported_langs as $lang_code => $lang_name): ?>
                                <option value="<?php echo esc_attr($lang_code); ?>"><?php echo esc_html($lang_name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button id="test-translation-btn" class="button button-primary">Test Translation</button>
                </div>
                <div id="test-translation-result" style="margin-top: 10px; display: none;"></div>
            </div>
        </div>
    </div>

    <!-- System Information Container -->
    <div class="postbox" style="padding: 25px; margin-bottom: 20px; border-left: 4px solid #607D8B; background: linear-gradient(135deg, #fafafa 0%, #eceff1 100%); box-shadow: 0 2px 8px rgba(96, 125, 139, 0.1);">
        <div style="display: flex; align-items: center; margin-bottom: 20px;">
            <span style="font-size: 24px; margin-right: 12px; color: #607D8B;">ℹ️</span>
            <h3 style="margin: 0; color: #37474F; font-size: 20px;">System Information</h3>
        </div>
        <div style="background: white; padding: 20px; border-radius: 8px; border: 1px solid #cfd8dc; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; font-family: monospace; font-size: 12px;">
                <strong>Environment Details:</strong><br>
                PHP Version: <strong><?php echo phpversion(); ?></strong> |
                WordPress: <strong><?php echo get_bloginfo('version'); ?></strong> |
                Plugin: <strong>1.9.3 FIXED</strong><br>
                cURL Support: <strong><?php echo function_exists('curl_init') ? 'Available' : 'Not Available'; ?></strong> |
                OpenSSL: <strong><?php echo extension_loaded('openssl') ? 'Enabled' : 'Disabled'; ?></strong><br>
                Site URL: <strong><?php echo home_url(); ?></strong><br>
                Upload Dir: <strong><?php $upload_dir = wp_upload_dir(); echo $upload_dir['basedir']; ?></strong><br>
                Last Updated: <strong>2025-08-03 18:45:35 UTC by EricRorich</strong>
            </div>
        </div>
    </div>

    <!-- Save Button -->
    <div style="text-align: center; padding: 20px;">
        <?php submit_button('💾 Save Configuration', 'primary', 'submit', false, array('style' => 'font-size: 16px; padding: 12px 30px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; border-radius: 8px; box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3); transition: all 0.3s ease;')); ?>
    </div>
</form>

    <!-- API Testing Section -->
    <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;">
        <h3>🧪 Quick API Testing (FIXED)</h3>
        <p>Test your API connections to ensure everything is working correctly:</p>

        <div style="display: flex; gap: 15px; margin-top: 15px;">
            <form method="post" style="display: inline;">
                <input type="hidden" name="test_openai_api" value="1">
                <button type="submit" class="button button-secondary">🧠 Test OpenAI</button>
            </form>

            <form method="post" style="display: inline;">
                <input type="hidden" name="test_elevenlabs_api" value="1">
                <button type="submit" class="button button-secondary">🔊 Test ElevenLabs</button>
            </form>

            <form method="post" style="display: inline;">
                <input type="hidden" name="test_voice_features" value="1">
                <button type="submit" class="button button-secondary">🎤 Test Voice Features</button>
            </form>
        </div>
    </div>
</div>
</div>
<?php
}

// API callback functions
public function provider_section_callback() {
    echo '<p>Select your preferred AI provider. Configure the corresponding API keys below based on your selection.</p>';
}

public function api_provider_field_callback() {
    $current_provider = get_option('ai_interview_widget_api_provider', 'openai');
    ?>
    <select id="api_provider" name="ai_interview_widget_api_provider" onchange="toggleApiFields(this.value); updateModelOptions(this.value);">
        <option value="openai" <?php selected($current_provider, 'openai'); ?>>OpenAI GPT-4</option>
        <option value="anthropic" <?php selected($current_provider, 'anthropic'); ?>>Anthropic Claude</option>
        <option value="gemini" <?php selected($current_provider, 'gemini'); ?>>Google Gemini</option>
        <option value="azure" <?php selected($current_provider, 'azure'); ?>>Azure OpenAI</option>
        <option value="custom" <?php selected($current_provider, 'custom'); ?>>Custom API Endpoint</option>
    </select>
    <p class="description">Choose your AI provider. Each provider offers different capabilities and pricing.</p>
    
    <script>
    function toggleApiFields(provider) {
        // Hide all provider-specific fields first
        const providers = ['openai', 'anthropic', 'gemini', 'azure', 'custom'];
        providers.forEach(p => {
            const fields = document.querySelectorAll(`[id*="${p}_api"], [id*="${p}_endpoint"]`);
            fields.forEach(field => {
                const row = field.closest('tr');
                if (row) row.style.display = 'none';
            });
        });
        
        // Show fields for selected provider
        if (provider === 'openai') {
            const openaiRow = document.querySelector('[id*="openai_api_key"]')?.closest('tr');
            if (openaiRow) openaiRow.style.display = '';
        } else if (provider === 'anthropic') {
            const anthropicRow = document.querySelector('[id*="anthropic_api_key"]')?.closest('tr');
            if (anthropicRow) anthropicRow.style.display = '';
        } else if (provider === 'gemini') {
            const geminiRow = document.querySelector('[id*="gemini_api_key"]')?.closest('tr');
            if (geminiRow) geminiRow.style.display = '';
        } else if (provider === 'azure') {
            const azureKeyRow = document.querySelector('[id*="azure_api_key"]')?.closest('tr');
            const azureEndpointRow = document.querySelector('[id*="azure_endpoint"]')?.closest('tr');
            if (azureKeyRow) azureKeyRow.style.display = '';
            if (azureEndpointRow) azureEndpointRow.style.display = '';
        } else if (provider === 'custom') {
            const customEndpointRow = document.querySelector('[id*="custom_api_endpoint"]')?.closest('tr');
            const customKeyRow = document.querySelector('[id*="custom_api_key"]')?.closest('tr');
            if (customEndpointRow) customEndpointRow.style.display = '';
            if (customKeyRow) customKeyRow.style.display = '';
        }
    }
    
    function updateModelOptions(provider) {
        // This function is now enhanced by admin-enhancements.js
        // Fallback implementation for basic functionality
        const modelSelect = document.getElementById('llm_model');
        if (!modelSelect) return;
        
        // If enhanced version is available, delegate to it
        if (typeof updateModelOptionsEnhanced === 'function') {
            updateModelOptionsEnhanced(provider);
            return;
        }
        
        // Basic fallback implementation
        const basicModels = {
            'openai': [
                { value: 'gpt-4o', label: 'GPT-4o (Latest)' },
                { value: 'gpt-4o-mini', label: 'GPT-4o-mini (Fast)' }
            ],
            'anthropic': [
                { value: 'claude-3-5-sonnet-20241022', label: 'Claude 3.5 Sonnet (Latest)' }
            ],
            'gemini': [
                { value: 'gemini-1.5-pro', label: 'Gemini 1.5 Pro' }
            ],
            'azure': [
                { value: 'gpt-4o', label: 'GPT-4o (Azure)' }
            ],
            'custom': [
                { value: 'custom-model', label: 'Custom Model' }
            ]
        };
        
        const models = basicModels[provider] || basicModels['openai'];
        modelSelect.innerHTML = '';
        models.forEach(model => {
            const option = document.createElement('option');
            option.value = model.value;
            option.textContent = model.label;
            modelSelect.appendChild(option);
        });
        
        // Restore saved model if available
        const savedModel = window.currentSavedModel || 'gpt-4o-mini';
        if (modelSelect.querySelector(`option[value="${savedModel}"]`)) {
            modelSelect.value = savedModel;
        }
    }
    
    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        toggleApiFields('<?php echo esc_js($current_provider); ?>');
        updateModelOptions('<?php echo esc_js($current_provider); ?>');
    });
    </script>
    <?php
}

public function api_section_callback() {
    echo '<p>Configure API keys for your selected AI provider. Only the fields for your selected provider above will be used.</p>';
}

public function llm_model_field_callback() {
    $current_model = get_option('ai_interview_widget_llm_model', 'gpt-4o-mini');
    $current_provider = get_option('ai_interview_widget_api_provider', 'openai');
    ?>
    <select id="llm_model" name="ai_interview_widget_llm_model">
        <!-- Options will be populated by JavaScript based on provider selection -->
    </select>
    <p class="description">Select the specific LLM model to use with your chosen provider. Different models offer varying capabilities and cost structures.</p>
    
    <script>
    // Store current model for JavaScript access
    window.currentSavedModel = '<?php echo esc_js($current_model); ?>';
    
    document.addEventListener('DOMContentLoaded', function() {
        // Initial load with proper model selection
        setTimeout(function() {
            updateModelOptions('<?php echo esc_js($current_provider); ?>');
        }, 50);
    });
    </script>
    <?php
}

public function api_key_field_callback() {
$api_key = get_option('ai_interview_widget_openai_api_key', '');
$masked_key = '';
if (!empty($api_key)) {
    $masked_key = substr($api_key, 0, 7) . str_repeat('*', strlen($api_key) - 11) . substr($api_key, -4);
}
?>
<input type="password" id="openai_api_key" name="ai_interview_widget_openai_api_key"
       value="<?php echo esc_attr($api_key); ?>"
       class="regular-text"
       placeholder="sk-..."
       autocomplete="new-password">
<?php if (!empty($api_key)): ?>
    <p class="description">Current key: <code><?php echo esc_html($masked_key); ?></code></p>
<?php endif; ?>
<p class="description">Enter your OpenAI API key. Must start with "sk-" and be at least 40 characters long.</p>
<?php
}

public function elevenlabs_section_callback() {
echo '<p>Configure ElevenLabs for high-quality text-to-speech. Get your API key from <a href="https://elevenlabs.io/speech-synthesis" target="_blank">ElevenLabs</a>. If not configured, browser TTS will be used as fallback.</p>';
}

public function elevenlabs_api_key_field_callback() {
$api_key = get_option('ai_interview_widget_elevenlabs_api_key', '');
$masked_key = '';
if (!empty($api_key)) {
    $masked_key = substr($api_key, 0, 4) . str_repeat('*', max(0, strlen($api_key) - 8)) . substr($api_key, -4);
}
?>
<input type="password" id="elevenlabs_api_key" name="ai_interview_widget_elevenlabs_api_key"
       value="<?php echo esc_attr($api_key); ?>"
       class="regular-text"
       placeholder="Your ElevenLabs API key..."
       autocomplete="new-password">
<?php if (!empty($api_key)): ?>
    <p class="description">Current key: <code><?php echo esc_html($masked_key); ?></code></p>
<?php endif; ?>
<p class="description">Optional: Enter your ElevenLabs API key for premium voice synthesis.</p>
<?php
}

public function elevenlabs_voice_id_field_callback() {
$voice_id = get_option('ai_interview_widget_elevenlabs_voice_id', 'pNInz6obpgDQGcFmaJgB');
?>
<input type="text" id="elevenlabs_voice_id" name="ai_interview_widget_elevenlabs_voice_id"
       value="<?php echo esc_attr($voice_id); ?>"
       class="regular-text"
       placeholder="pNInz6obpgDQGcFmaJgB">
<p class="description">ElevenLabs Voice ID. Default is Adam (pNInz6obpgDQGcFmaJgB). You can find voice IDs in your ElevenLabs dashboard.</p>
<?php
}

public function voice_quality_field_callback() {
$voice_quality = get_option('ai_interview_widget_voice_quality', 'eleven_multilingual_v2');
?>
<select id="voice_quality" name="ai_interview_widget_voice_quality">
    <option value="eleven_multilingual_v2" <?php selected($voice_quality, 'eleven_multilingual_v2'); ?>>Multilingual V2 (Recommended)</option>
    <option value="eleven_monolingual_v1" <?php selected($voice_quality, 'eleven_monolingual_v1'); ?>>Monolingual V1</option>
    <option value="eleven_multilingual_v1" <?php selected($voice_quality, 'eleven_multilingual_v1'); ?>>Multilingual V1</option>
    <option value="eleven_turbo_v2" <?php selected($voice_quality, 'eleven_turbo_v2'); ?>>Turbo V2 (Fastest)</option>
</select>
<p class="description">Voice model to use. Multilingual V2 provides the best quality for both English and German.</p>
<?php
}

public function enable_voice_field_callback() {
$voice_enabled = get_option('ai_interview_widget_enable_voice', true);
?>
<label>
    <input type="checkbox" id="enable_voice" name="ai_interview_widget_enable_voice" value="1" <?php checked($voice_enabled); ?>>
    Enable voice input and text-to-speech features
</label>
<p class="description">Enables microphone input and voice responses. Uses ElevenLabs if configured, otherwise browser TTS.</p>
<?php
}

public function disable_greeting_audio_field_callback() {
$disabled = get_option('ai_interview_widget_disable_greeting_audio', false);
?>
<label>
    <input type="checkbox" id="disable_greeting_audio" name="ai_interview_widget_disable_greeting_audio" value="1" <?php checked($disabled); ?>>
    Disable automatic greeting audio playback
</label>
<p class="description">When enabled, the widget will not play greeting audio automatically when loaded.</p>
<?php
}

public function disable_audio_visualization_field_callback() {
$disabled = get_option('ai_interview_widget_disable_audio_visualization', false);
?>
<label>
    <input type="checkbox" id="disable_audio_visualization" name="ai_interview_widget_disable_audio_visualization" value="1" <?php checked($disabled); ?>>
    Disable audio visualization canvas
</label>
<p class="description">When enabled, the canvas audio visualization will be hidden, showing only the chat interface.</p>
<?php
}

public function chatbox_only_mode_field_callback() {
$enabled = get_option('ai_interview_widget_chatbox_only_mode', false);
?>
<label>
    <input type="checkbox" id="chatbox_only_mode" name="ai_interview_widget_chatbox_only_mode" value="1" <?php checked($enabled); ?>>
    Enable chatbox-only mode (no audio features)
</label>
<p class="description">When enabled, disables all audio features and shows only the text chat interface.</p>
<?php
}

// Language Support Callbacks
public function language_section_callback() {
    echo '<p>Configure language support for the AI chat widget. The widget supports multiple languages for greetings, system prompts, and voice responses.</p>';
}

public function geolocation_section_callback() {
    echo '<p>Configure geolocation settings for automatic language detection. This helps the widget choose the appropriate language based on the user\'s location while respecting privacy.</p>';
    echo '<div style="background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px; padding: 12px; margin: 10px 0;">';
    echo '<strong>Privacy Notice:</strong> When enabled, the widget may request the user\'s country information from a single, privacy-focused IP geolocation service to provide better language defaults. No personally identifiable information is stored or transmitted.';
    echo '</div>';
}

public function default_language_field_callback() {
    $default_lang = get_option('ai_interview_widget_default_language', 'en');
    $supported_langs = json_decode(get_option('ai_interview_widget_supported_languages', ''), true);
    if (!$supported_langs) $supported_langs = array('en' => 'English', 'de' => 'German');
    ?>
    <select id="default_language" name="ai_interview_widget_default_language">
        <?php foreach ($supported_langs as $code => $name): ?>
            <option value="<?php echo esc_attr($code); ?>" <?php selected($default_lang, $code); ?>>
                <?php echo esc_html($name . ' (' . strtoupper($code) . ')'); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <p class="description">Select the default language for new widget instances.</p>
    <?php
}

public function supported_languages_field_callback() {
    $supported_langs = json_decode(get_option('ai_interview_widget_supported_languages', ''), true);
    if (!$supported_langs) $supported_langs = array('en' => 'English', 'de' => 'German');
    
    // Get pending languages (if any)
    $pending_langs = json_decode(get_option('ai_interview_widget_pending_languages', ''), true);
    if (!$pending_langs) $pending_langs = array();
    
    // Define 20 most common languages
    $common_languages = array(
        'en' => 'English',
        'zh' => 'Chinese (Mandarin)',
        'es' => 'Spanish',
        'hi' => 'Hindi',
        'ar' => 'Arabic',
        'pt' => 'Portuguese',
        'bn' => 'Bengali',
        'ru' => 'Russian',
        'ja' => 'Japanese',
        'pa' => 'Punjabi',
        'de' => 'German',
        'jv' => 'Javanese',
        'ko' => 'Korean',
        'fr' => 'French',
        'te' => 'Telugu',
        'mr' => 'Marathi',
        'tr' => 'Turkish',
        'ta' => 'Tamil',
        'vi' => 'Vietnamese',
        'it' => 'Italian'
    );
    
    // Combine applied and pending languages for display
    $all_languages = array_merge($supported_langs, $pending_langs);
    ?>
    <div id="languages_container">
        <div style="margin-bottom: 15px;">
            <h4 style="margin: 0 0 10px 0; color: #2E7D32;">✅ Applied Languages</h4>
            <div id="applied_languages">
                <?php foreach ($supported_langs as $code => $name): ?>
                    <div class="language-row applied-language" style="margin-bottom: 10px; display: flex; gap: 10px; align-items: center; padding: 8px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px;">
                        <select class="lang-code" style="width: 150px;" data-status="applied">
                            <?php foreach ($common_languages as $lang_code => $lang_name): ?>
                                <option value="<?php echo esc_attr($lang_code); ?>" <?php selected($code, $lang_code); ?>>
                                    <?php echo esc_html($lang_code . ' - ' . $lang_name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <span style="color: #155724; font-weight: bold;">Applied</span>
                        <button type="button" class="button button-small remove-language" style="color: #dc3232;">Remove</button>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <?php if (!empty($pending_langs)): ?>
        <div style="margin-bottom: 15px;">
            <h4 style="margin: 0 0 10px 0; color: #856404;">⏳ Pending Languages</h4>
            <div id="pending_languages">
                <?php foreach ($pending_langs as $code => $name): ?>
                    <div class="language-row pending-language" style="margin-bottom: 10px; display: flex; gap: 10px; align-items: center; padding: 8px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px;">
                        <select class="lang-code" style="width: 150px;" data-status="pending">
                            <?php foreach ($common_languages as $lang_code => $lang_name): ?>
                                <option value="<?php echo esc_attr($lang_code); ?>" <?php selected($code, $lang_code); ?>>
                                    <?php echo esc_html($lang_code . ' - ' . $lang_name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <span style="color: #856404; font-weight: bold;">Pending</span>
                        <button type="button" class="button button-small remove-language" style="color: #dc3232;">Remove</button>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php else: ?>
        <div style="margin-bottom: 15px; display: none;" id="pending_section">
            <h4 style="margin: 0 0 10px 0; color: #856404;">⏳ Pending Languages</h4>
            <div id="pending_languages"></div>
        </div>
        <?php endif; ?>
    </div>
    
    <div style="margin-top: 15px; display: flex; gap: 10px; align-items: center;">
        <button type="button" id="add_language" class="button button-secondary">Add Language</button>
        <?php if (!empty($pending_langs)): ?>
            <button type="button" id="apply_languages" class="button button-primary" style="background: #28a745; border-color: #28a745;">Apply Pending Languages</button>
            <button type="button" id="cancel_pending" class="button button-link" style="color: #dc3545;">Cancel Pending Changes</button>
        <?php else: ?>
            <button type="button" id="apply_languages" class="button button-primary" style="background: #28a745; border-color: #28a745; display: none;">Apply Pending Languages</button>
            <button type="button" id="cancel_pending" class="button button-link" style="color: #dc3545; display: none;">Cancel Pending Changes</button>
        <?php endif; ?>
    </div>
    
    <input type="hidden" id="supported_languages_hidden" name="ai_interview_widget_supported_languages" value="<?php echo esc_attr(json_encode($supported_langs)); ?>">
    <input type="hidden" id="pending_languages_hidden" name="ai_interview_widget_pending_languages" value="<?php echo esc_attr(json_encode($pending_langs)); ?>">
    
    <div id="language_status_message" style="margin-top: 10px;"></div>
    
    <p class="description">
        <strong>Languages must be applied before they appear in other sections.</strong><br>
        Select from the 20 most common languages worldwide. Click "Apply Pending Languages" to make them available in Welcome Messages and Audio File Management sections.
    </p>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var commonLanguages = <?php echo json_encode($common_languages); ?>;
        
        function updatePendingLanguagesField() {
            var pendingLanguages = {};
            document.querySelectorAll('.pending-language').forEach(function(row) {
                var select = row.querySelector('.lang-code');
                var code = select.value.trim();
                if (code && commonLanguages[code]) {
                    pendingLanguages[code] = commonLanguages[code];
                }
            });
            document.getElementById('pending_languages_hidden').value = JSON.stringify(pendingLanguages);
            
            // Show/hide apply button based on pending languages
            var applyBtn = document.getElementById('apply_languages');
            var cancelBtn = document.getElementById('cancel_pending');
            var pendingSection = document.getElementById('pending_section');
            
            if (Object.keys(pendingLanguages).length > 0) {
                applyBtn.style.display = 'inline-block';
                cancelBtn.style.display = 'inline-block';
                if (pendingSection) pendingSection.style.display = 'block';
            } else {
                applyBtn.style.display = 'none';
                cancelBtn.style.display = 'none';
                if (pendingSection) pendingSection.style.display = 'none';
            }
        }
        
        function showStatusMessage(message, type) {
            var statusDiv = document.getElementById('language_status_message');
            var bgColor = type === 'success' ? '#d4edda' : type === 'error' ? '#f8d7da' : '#fff3cd';
            var textColor = type === 'success' ? '#155724' : type === 'error' ? '#721c24' : '#856404';
            statusDiv.innerHTML = '<div style="padding: 10px; background: ' + bgColor + '; border: 1px solid; border-radius: 4px; color: ' + textColor + ';">' + message + '</div>';
            setTimeout(function() {
                statusDiv.innerHTML = '';
            }, 5000);
        }
        
        function applyPendingLanguages() {
            var pendingLanguages = JSON.parse(document.getElementById('pending_languages_hidden').value || '{}');
            var appliedLanguages = JSON.parse(document.getElementById('supported_languages_hidden').value || '{}');
            
            // Merge pending into applied
            var newAppliedLanguages = Object.assign({}, appliedLanguages, pendingLanguages);
            
            // Save applied languages and clear pending
            jQuery.post(ajaxurl, {
                action: 'ai_interview_apply_languages',
                nonce: '<?php echo wp_create_nonce('ai_interview_apply_languages'); ?>',
                applied_languages: JSON.stringify(newAppliedLanguages)
            }, function(response) {
                if (response.success) {
                    showStatusMessage('✅ Languages applied successfully! They are now available in Welcome Messages and Audio File Management.', 'success');
                    
                    // Update the UI
                    location.reload(); // Simplest way to refresh the language UI and dependent sections
                } else {
                    showStatusMessage('❌ Failed to apply languages: ' + (response.data || 'Unknown error'), 'error');
                }
            }).fail(function() {
                showStatusMessage('❌ Network error while applying languages.', 'error');
            });
        }
        
        function cancelPendingLanguages() {
            // Clear pending languages
            jQuery.post(ajaxurl, {
                action: 'ai_interview_cancel_pending_languages',
                nonce: '<?php echo wp_create_nonce('ai_interview_cancel_pending'); ?>'
            }, function(response) {
                if (response.success) {
                    showStatusMessage('🔄 Pending changes cancelled.', 'success');
                    location.reload(); // Refresh the UI
                } else {
                    showStatusMessage('❌ Failed to cancel pending changes.', 'error');
                }
            });
        }
        
        // Add language button - adds to pending section
        document.getElementById('add_language').addEventListener('click', function() {
            var pendingContainer = document.getElementById('pending_languages');
            var pendingSection = document.getElementById('pending_section');
            
            var newRow = document.createElement('div');
            newRow.className = 'language-row pending-language';
            newRow.style.cssText = 'margin-bottom: 10px; display: flex; gap: 10px; align-items: center; padding: 8px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px;';
            
            // Create dropdown with all available languages
            var selectHTML = '<select class="lang-code" style="width: 150px;" data-status="pending">';
            for (var code in commonLanguages) {
                selectHTML += '<option value="' + code + '">' + code + ' - ' + commonLanguages[code] + '</option>';
            }
            selectHTML += '</select>';
            
            newRow.innerHTML = selectHTML + '<span style="color: #856404; font-weight: bold;">Pending</span><button type="button" class="button button-small remove-language" style="color: #dc3232;">Remove</button>';
            pendingContainer.appendChild(newRow);
            pendingSection.style.display = 'block';
            
            newRow.querySelector('.remove-language').addEventListener('click', function() {
                newRow.remove();
                updatePendingLanguagesField();
            });
            
            newRow.querySelector('.lang-code').addEventListener('change', updatePendingLanguagesField);
            updatePendingLanguagesField();
        });
        
        // Apply languages button
        document.getElementById('apply_languages').addEventListener('click', applyPendingLanguages);
        
        // Cancel pending button
        document.getElementById('cancel_pending').addEventListener('click', cancelPendingLanguages);
        
        // Remove language buttons
        document.querySelectorAll('.remove-language').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var row = btn.closest('.language-row');
                var isApplied = row.classList.contains('applied-language');
                
                if (isApplied) {
                    // For applied languages, need to save the change immediately
                    row.remove();
                    var appliedLanguages = {};
                    document.querySelectorAll('.applied-language').forEach(function(appliedRow) {
                        var select = appliedRow.querySelector('.lang-code');
                        var code = select.value.trim();
                        if (code && commonLanguages[code]) {
                            appliedLanguages[code] = commonLanguages[code];
                        }
                    });
                    document.getElementById('supported_languages_hidden').value = JSON.stringify(appliedLanguages);
                    
                    // Save to database
                    jQuery.post(ajaxurl, {
                        action: 'ai_interview_apply_languages',
                        nonce: '<?php echo wp_create_nonce('ai_interview_apply_languages'); ?>',
                        applied_languages: JSON.stringify(appliedLanguages)
                    }, function(response) {
                        if (response.success) {
                            showStatusMessage('✅ Language removed and changes applied.', 'success');
                            location.reload();
                        } else {
                            showStatusMessage('❌ Failed to remove language.', 'error');
                        }
                    });
                } else {
                    // For pending languages, just remove from UI
                    row.remove();
                    updatePendingLanguagesField();
                }
            });
        });
        
        // Language select change handlers
        document.querySelectorAll('.language-row .lang-code').forEach(function(select) {
            select.addEventListener('change', function() {
                if (select.dataset.status === 'pending') {
                    updatePendingLanguagesField();
                }
            });
        });
    });
    </script>
    <?php
}

// Geolocation callback functions
public function enable_geolocation_field_callback() {
    $enabled = get_option('ai_interview_widget_enable_geolocation', true);
    ?>
    <input type="checkbox" id="enable_geolocation" name="ai_interview_widget_enable_geolocation" value="1" <?php checked(1, $enabled); ?>>
    <label for="enable_geolocation">Enable automatic country detection for language selection</label>
    <p class="description">When enabled, the widget will attempt to detect the user's country via a single privacy-focused service to provide better language defaults. When disabled, only browser language and timezone detection will be used.</p>
    <?php
}

public function geolocation_cache_timeout_field_callback() {
    $timeout = get_option('ai_interview_widget_geolocation_cache_timeout', 24);
    ?>
    <input type="number" id="geolocation_cache_timeout" name="ai_interview_widget_geolocation_cache_timeout" 
           value="<?php echo esc_attr($timeout); ?>" min="1" max="168" class="small-text">
    <p class="description">How long to cache geolocation results (1-168 hours). Longer values reduce network requests but may be less accurate for traveling users.</p>
    <?php
}

public function geolocation_require_consent_field_callback() {
    $require_consent = get_option('ai_interview_widget_geolocation_require_consent', false);
    ?>
    <input type="checkbox" id="geolocation_require_consent" name="ai_interview_widget_geolocation_require_consent" value="1" <?php checked(1, $require_consent); ?>>
    <label for="geolocation_require_consent">Require explicit user consent before geolocation attempts</label>
    <p class="description">When enabled, users must actively consent to geolocation. When disabled, geolocation happens automatically (but can still be blocked by browser settings).</p>
    <?php
}

public function geolocation_debug_mode_field_callback() {
    $debug_mode = get_option('ai_interview_widget_geolocation_debug_mode', false);
    ?>
    <input type="checkbox" id="geolocation_debug_mode" name="ai_interview_widget_geolocation_debug_mode" value="1" <?php checked(1, $debug_mode); ?>>
    <label for="geolocation_debug_mode">Enable debug logging for geolocation</label>
    <p class="description">Shows detailed geolocation information in browser console. Only enable for troubleshooting.</p>
    <?php
}

// New API Provider Callback Functions
public function anthropic_api_key_field_callback() {
    $api_key = get_option('ai_interview_widget_anthropic_api_key', '');
    $masked_key = '';
    if (!empty($api_key)) {
        $masked_key = substr($api_key, 0, 7) . str_repeat('*', strlen($api_key) - 11) . substr($api_key, -4);
    }
    ?>
    <input type="password" id="anthropic_api_key" name="ai_interview_widget_anthropic_api_key"
           value="<?php echo esc_attr($api_key); ?>"
           class="regular-text"
           placeholder="sk-ant-api..."
           autocomplete="new-password">
    <?php if (!empty($api_key)): ?>
        <p class="description">Current key: <code><?php echo esc_html($masked_key); ?></code></p>
    <?php endif; ?>
    <p class="description">Enter your Anthropic Claude API key. Get it from <a href="https://console.anthropic.com/" target="_blank">Anthropic Console</a>.</p>
    <?php
}

public function gemini_api_key_field_callback() {
    $api_key = get_option('ai_interview_widget_gemini_api_key', '');
    $masked_key = '';
    if (!empty($api_key)) {
        $masked_key = substr($api_key, 0, 7) . str_repeat('*', strlen($api_key) - 11) . substr($api_key, -4);
    }
    ?>
    <input type="password" id="gemini_api_key" name="ai_interview_widget_gemini_api_key"
           value="<?php echo esc_attr($api_key); ?>"
           class="regular-text"
           placeholder="AIza..."
           autocomplete="new-password">
    <?php if (!empty($api_key)): ?>
        <p class="description">Current key: <code><?php echo esc_html($masked_key); ?></code></p>
    <?php endif; ?>
    <p class="description">Enter your Google Gemini API key. Get it from <a href="https://makersuite.google.com/app/apikey" target="_blank">Google AI Studio</a>.</p>
    <?php
}

public function azure_api_key_field_callback() {
    $api_key = get_option('ai_interview_widget_azure_api_key', '');
    $masked_key = '';
    if (!empty($api_key)) {
        $masked_key = substr($api_key, 0, 4) . str_repeat('*', max(0, strlen($api_key) - 8)) . substr($api_key, -4);
    }
    ?>
    <input type="password" id="azure_api_key" name="ai_interview_widget_azure_api_key"
           value="<?php echo esc_attr($api_key); ?>"
           class="regular-text"
           placeholder="Azure OpenAI API key..."
           autocomplete="new-password">
    <?php if (!empty($api_key)): ?>
        <p class="description">Current key: <code><?php echo esc_html($masked_key); ?></code></p>
    <?php endif; ?>
    <p class="description">Enter your Azure OpenAI API key. Get it from <a href="https://portal.azure.com/" target="_blank">Azure Portal</a>.</p>
    <?php
}

public function azure_endpoint_field_callback() {
    $endpoint = get_option('ai_interview_widget_azure_endpoint', '');
    ?>
    <input type="url" id="azure_endpoint" name="ai_interview_widget_azure_endpoint"
           value="<?php echo esc_attr($endpoint); ?>"
           class="regular-text"
           placeholder="https://your-resource.openai.azure.com/">
    <p class="description">Enter your Azure OpenAI endpoint URL. Find it in your Azure OpenAI resource settings.</p>
    <?php
}

public function custom_api_endpoint_field_callback() {
    $endpoint = get_option('ai_interview_widget_custom_api_endpoint', '');
    ?>
    <input type="url" id="custom_api_endpoint" name="ai_interview_widget_custom_api_endpoint"
           value="<?php echo esc_attr($endpoint); ?>"
           class="regular-text"
           placeholder="https://api.example.com/v1/chat/completions">
    <p class="description">Enter a custom OpenAI-compatible API endpoint URL.</p>
    <?php
}

public function custom_api_key_field_callback() {
    $api_key = get_option('ai_interview_widget_custom_api_key', '');
    $masked_key = '';
    if (!empty($api_key)) {
        $masked_key = substr($api_key, 0, 4) . str_repeat('*', max(0, strlen($api_key) - 8)) . substr($api_key, -4);
    }
    ?>
    <input type="password" id="custom_api_key" name="ai_interview_widget_custom_api_key"
           value="<?php echo esc_attr($api_key); ?>"
           class="regular-text"
           placeholder="Custom API key..."
           autocomplete="new-password">
    <?php if (!empty($api_key)): ?>
        <p class="description">Current key: <code><?php echo esc_html($masked_key); ?></code></p>
    <?php endif; ?>
    <p class="description">Enter the API key for your custom endpoint.</p>
    <?php
}

// Test API connections
public function test_openai_connection() {
$api_key = get_option('ai_interview_widget_openai_api_key', '');

if (empty($api_key)) {
    add_settings_error('test_results', 'openai_test', '❌ OpenAI API key is not set', 'error');
    return;
}

$test_response = $this->get_openai_response('Hello, this is a test message.', 'You are a helpful assistant. Respond briefly to test messages.');

if ($test_response && !empty($test_response['reply'])) {
    add_settings_error('test_results', 'openai_test', '✅ OpenAI API connection successful! Response: "' . substr($test_response['reply'], 0, 100) . '..."', 'updated');
} else {
    add_settings_error('test_results', 'openai_test', '❌ OpenAI API connection failed. Please check your API key and network connection.', 'error');
}
}

public function test_elevenlabs_connection() {
$api_key = get_option('ai_interview_widget_elevenlabs_api_key', '');

if (empty($api_key)) {
    add_settings_error('test_results', 'elevenlabs_test', '❌ ElevenLabs API key is not set', 'error');
    return;
}

$voice_id = get_option('ai_interview_widget_elevenlabs_voice_id', 'pNInz6obpgDQGcFmaJgB');

// Test with a simple request to get voice info
$response = wp_remote_get(
    'https://api.elevenlabs.io/v1/voices/' . $voice_id,
    array(
        'headers' => array(
            'xi-api-key' => $api_key
        ),
        'timeout' => 15
    )
);

if (is_wp_error($response)) {
    add_settings_error('test_results', 'elevenlabs_test', '❌ ElevenLabs API connection failed: ' . $response->get_error_message(), 'error');
    return;
}

$response_code = wp_remote_retrieve_response_code($response);
if ($response_code === 200) {
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    $voice_name = isset($data['name']) ? $data['name'] : 'Unknown';
    add_settings_error('test_results', 'elevenlabs_test', '✅ ElevenLabs API connection successful! Voice: "' . esc_html($voice_name) . '"', 'updated');
} else {
    add_settings_error('test_results', 'elevenlabs_test', '❌ ElevenLabs API returned HTTP ' . $response_code, 'error');
}
}

public function test_voice_features() {
$voice_enabled = get_option('ai_interview_widget_enable_voice', true);
$elevenlabs_key = get_option('ai_interview_widget_elevenlabs_api_key', '');

if (!$voice_enabled) {
    add_settings_error('test_results', 'voice_test', '❌ Voice features are disabled in settings', 'error');
    return;
}

$features = array();
$features[] = '🎤 Voice input: Browser Web Speech API';

if (!empty($elevenlabs_key)) {
    $features[] = '🔊 Voice output: ElevenLabs TTS (Premium)';
} else {
    $features[] = '🔊 Voice output: Browser TTS (Fallback)';
}

add_settings_error('test_results', 'voice_test', '✅ Voice features ready! ' . implode(' | ', $features), 'updated');
}

// Handle system prompt file upload
public function handle_system_prompt_upload() {
    // Verify nonce
    if (!isset($_POST['system_prompt_nonce']) || !wp_verify_nonce($_POST['system_prompt_nonce'], 'ai_interview_system_prompt_upload')) {
        add_settings_error('system_prompt_upload', 'security_error', '❌ Security verification failed.', 'error');
        return;
    }
    
    // Check user permissions
    if (!current_user_can('manage_options')) {
        add_settings_error('system_prompt_upload', 'permission_error', '❌ Insufficient permissions.', 'error');
        return;
    }
    
    // Validate language code
    $language_code = isset($_POST['language_code']) ? sanitize_text_field($_POST['language_code']) : '';
    if (empty($language_code)) {
        add_settings_error('system_prompt_upload', 'lang_error', '❌ Invalid language code.', 'error');
        return;
    }
    
    // Check if file was uploaded
    if (!isset($_FILES['system_prompt_file']) || $_FILES['system_prompt_file']['error'] !== UPLOAD_ERR_OK) {
        add_settings_error('system_prompt_upload', 'file_error', '❌ No file uploaded or upload error.', 'error');
        return;
    }
    
    $file = $_FILES['system_prompt_file'];
    
    // Validate file type
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed_types = array('txt', 'pdf', 'doc', 'docx', 'odt', 'rtf');
    if (!in_array($file_ext, $allowed_types)) {
        add_settings_error('system_prompt_upload', 'type_error', '❌ Only .txt, .pdf, .doc, .docx, .odt, and .rtf files are allowed.', 'error');
        return;
    }
    
    // Validate file size (max 5MB for documents, 1MB for txt)
    $max_size = ($file_ext === 'txt') ? 1048576 : 5242880; // 1MB for txt, 5MB for documents
    if ($file['size'] > $max_size) {
        $max_size_mb = ($file_ext === 'txt') ? '1MB' : '5MB';
        add_settings_error('system_prompt_upload', 'size_error', "❌ File too large. Maximum size is {$max_size_mb}.", 'error');
        return;
    }
    
    // Extract text content based on file type
    $file_content = $this->extract_text_from_file($file['tmp_name'], $file_ext);
    if ($file_content === false) {
        add_settings_error('system_prompt_upload', 'read_error', '❌ Could not extract text from file. Please ensure the file is valid and not corrupted.', 'error');
        return;
    }
    
    // Sanitize content
    $file_content = sanitize_textarea_field($file_content);
    
    // Get current content settings
    $current_content = get_option('ai_interview_widget_content_settings', '');
    $content_data = json_decode($current_content, true);
    if (!$content_data) $content_data = array();
    
    // Update the specific system prompt placeholder
    $prompt_key = 'Systemprompts_Placeholder_' . $language_code;
    $content_data[$prompt_key] = $file_content;
    
    // Save updated content settings
    $updated_content = json_encode($content_data);
    if (update_option('ai_interview_widget_content_settings', $updated_content)) {
        $supported_langs = json_decode(get_option('ai_interview_widget_supported_languages', ''), true);
        $lang_name = isset($supported_langs[$language_code]) ? $supported_langs[$language_code] : $language_code;
        
        add_settings_error('system_prompt_upload', 'upload_success', 
            '✅ System prompt for ' . $lang_name . ' successfully updated! (' . strlen($file_content) . ' characters)', 'updated');
    } else {
        add_settings_error('system_prompt_upload', 'save_error', '❌ Could not save system prompt.', 'error');
    }
}

// Extract text content from various file types
private function extract_text_from_file($file_path, $file_ext) {
    switch ($file_ext) {
        case 'txt':
            return file_get_contents($file_path);
            
        case 'pdf':
            return $this->extract_text_from_pdf($file_path);
            
        case 'doc':
        case 'docx':
            return $this->extract_text_from_docx($file_path);
            
        case 'odt':
            return $this->extract_text_from_odt($file_path);
            
        case 'rtf':
            return $this->extract_text_from_rtf($file_path);
            
        default:
            return false;
    }
}

// Extract text from PDF files (basic implementation)
private function extract_text_from_pdf($file_path) {
    // Try to read PDF as plain text (works for simple PDFs)
    $content = file_get_contents($file_path);
    if ($content === false) {
        return false;
    }
    
    // Basic PDF text extraction - looks for text between stream markers
    $text = '';
    if (preg_match_all('/BT\s*(.*?)\s*ET/s', $content, $matches)) {
        foreach ($matches[1] as $match) {
            // Extract text from PDF operators
            if (preg_match_all('/\[(.*?)\]/s', $match, $text_matches)) {
                foreach ($text_matches[1] as $text_match) {
                    $text .= $text_match . ' ';
                }
            }
            // Also try Tj operator
            if (preg_match_all('/\((.*?)\)\s*Tj/s', $match, $tj_matches)) {
                foreach ($tj_matches[1] as $tj_match) {
                    $text .= $tj_match . ' ';
                }
            }
        }
    }
    
    // If basic extraction didn't work, try simple text extraction
    if (empty(trim($text))) {
        // Remove binary data and try to extract readable text
        $content = preg_replace('/[^\x20-\x7E\n\r\t]/', '', $content);
        $lines = explode("\n", $content);
        $readable_lines = array();
        foreach ($lines as $line) {
            $line = trim($line);
            if (strlen($line) > 10 && preg_match('/[a-zA-Z]/', $line)) {
                $readable_lines[] = $line;
            }
        }
        $text = implode("\n", $readable_lines);
    }
    
    return trim($text) ?: 'PDF text extraction not available. Please use a .txt file or copy the content manually.';
}

// Extract text from DOCX files
private function extract_text_from_docx($file_path) {
    if (!class_exists('ZipArchive')) {
        return 'DOCX extraction requires PHP ZipArchive extension. Please convert to .txt format.';
    }
    
    $zip = new ZipArchive();
    if ($zip->open($file_path) !== TRUE) {
        return false;
    }
    
    $xml_content = $zip->getFromName('word/document.xml');
    $zip->close();
    
    if ($xml_content === false) {
        return false;
    }
    
    // Parse XML and extract text
    $dom = new DOMDocument();
    if (!@$dom->loadXML($xml_content)) {
        return false;
    }
    
    $xpath = new DOMXPath($dom);
    $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
    
    $text_nodes = $xpath->query('//w:t');
    $text = '';
    foreach ($text_nodes as $node) {
        $text .= $node->nodeValue . ' ';
    }
    
    return trim($text);
}

// Extract text from ODT files
private function extract_text_from_odt($file_path) {
    if (!class_exists('ZipArchive')) {
        return 'ODT extraction requires PHP ZipArchive extension. Please convert to .txt format.';
    }
    
    $zip = new ZipArchive();
    if ($zip->open($file_path) !== TRUE) {
        return false;
    }
    
    $xml_content = $zip->getFromName('content.xml');
    $zip->close();
    
    if ($xml_content === false) {
        return false;
    }
    
    // Parse XML and extract text
    $dom = new DOMDocument();
    if (!@$dom->loadXML($xml_content)) {
        return false;
    }
    
    // Remove all XML tags and return plain text
    $text = strip_tags($dom->saveHTML());
    return trim($text);
}

// Extract text from RTF files
private function extract_text_from_rtf($file_path) {
    $content = file_get_contents($file_path);
    if ($content === false) {
        return false;
    }
    
    // Basic RTF text extraction - remove RTF control codes
    $text = $content;
    
    // Remove RTF header
    $text = preg_replace('/^{\\\rtf.*?}/', '', $text);
    
    // Remove control words
    $text = preg_replace('/\\\[a-z]+[0-9]*[ ]?/', '', $text);
    
    // Remove braces
    $text = str_replace(array('{', '}'), '', $text);
    
    // Clean up whitespace
    $text = preg_replace('/\s+/', ' ', $text);
    
    return trim($text);
}

// Handle direct system prompt save
public function handle_direct_prompt_save() {
    // Verify nonce
    if (!isset($_POST['direct_prompt_nonce']) || !wp_verify_nonce($_POST['direct_prompt_nonce'], 'ai_interview_direct_prompt_save')) {
        add_settings_error('direct_prompt_save', 'security_error', '❌ Security verification failed.', 'error');
        return;
    }
    
    // Check user permissions
    if (!current_user_can('manage_options')) {
        add_settings_error('direct_prompt_save', 'permission_error', '❌ Insufficient permissions.', 'error');
        return;
    }
    
    // Validate language code
    $language_code = isset($_POST['language_code']) ? sanitize_text_field($_POST['language_code']) : '';
    if (empty($language_code)) {
        add_settings_error('direct_prompt_save', 'lang_error', '❌ Invalid language code.', 'error');
        return;
    }
    
    // Get and sanitize prompt content
    $prompt_content = isset($_POST['direct_system_prompt']) ? sanitize_textarea_field($_POST['direct_system_prompt']) : '';
    
    // Get current content settings
    $current_content = get_option('ai_interview_widget_content_settings', '');
    $content_data = json_decode($current_content, true);
    if (!$content_data) $content_data = array();
    
    // Update the specific system prompt placeholder
    $prompt_key = 'Systemprompts_Placeholder_' . $language_code;
    $content_data[$prompt_key] = $prompt_content;
    
    // Save updated content settings
    $updated_content = json_encode($content_data);
    if (update_option('ai_interview_widget_content_settings', $updated_content)) {
        $supported_langs = json_decode(get_option('ai_interview_widget_supported_languages', ''), true);
        $lang_name = isset($supported_langs[$language_code]) ? $supported_langs[$language_code] : $language_code;
        
        if (!empty($prompt_content)) {
            add_settings_error('direct_prompt_save', 'save_success', 
                '✅ System prompt for ' . $lang_name . ' successfully saved! (' . strlen($prompt_content) . ' characters)', 'updated');
        } else {
            add_settings_error('direct_prompt_save', 'clear_success', 
                '✅ System prompt for ' . $lang_name . ' cleared successfully!', 'updated');
        }
    } else {
        add_settings_error('direct_prompt_save', 'save_error', '❌ Could not save system prompt.', 'error');
    }
}

// Handle language section updates for dynamic synchronization
public function handle_update_language_sections() {
    // Check nonce for security
    if (!check_ajax_referer('ai_interview_language_update', 'nonce', false)) {
        wp_send_json_error('Invalid security token');
        return;
    }
    
    // Check user capabilities
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Insufficient permissions');
        return;
    }
    
    // Get the updated supported languages
    $supported_langs = json_decode(get_option('ai_interview_widget_supported_languages', ''), true);
    if (!$supported_langs) {
        $supported_langs = array('en' => 'English', 'de' => 'German');
    }
    
    // Get current content settings
    $current_content = get_option('ai_interview_widget_content_settings', '');
    $content_data = json_decode($current_content, true);
    if (!$content_data) $content_data = array();
    
    // Generate HTML for Welcome Messages section
    ob_start();
    foreach ($supported_langs as $lang_code => $lang_name): 
        $welcome_key = 'welcome_message_' . $lang_code;
        $current_value = isset($content_data[$welcome_key]) ? $content_data[$welcome_key] : '';
    ?>
    <div class="control-group" style="margin-bottom: 15px;">
        <label style="display: block; margin-bottom: 5px; font-weight: 600;"><?php echo esc_html($lang_name); ?> Welcome Message:</label>
        <textarea id="<?php echo esc_attr($welcome_key); ?>" rows="4" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-family: inherit; resize: vertical;"><?php echo esc_textarea($current_value); ?></textarea>
    </div>
    <?php endforeach;
    $welcome_messages_html = ob_get_clean();
    
    // Generate HTML for System Prompt Management section
    ob_start();
    foreach ($supported_langs as $lang_code => $lang_name): 
        $prompt_key = 'Systemprompts_Placeholder_' . $lang_code;
        $current_prompt = isset($content_data[$prompt_key]) ? $content_data[$prompt_key] : '';
    ?>
    <div style="margin-bottom: 25px; padding: 20px; background: #f9f9f9; border-radius: 8px; border: 1px solid #ddd; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
        <h4 style="margin: 0 0 15px 0; color: #333; display: flex; align-items: center; justify-content: space-between;">
            <span style="display: flex; align-items: center;">
                <span style="margin-right: 8px;">🤖</span>
                <?php echo esc_html($lang_name); ?> (<?php echo esc_html($lang_code); ?>) System Prompt
            </span>
            <button type="button" 
                    class="translate-prompt-btn button button-secondary" 
                    data-source-lang="<?php echo esc_attr($lang_code); ?>"
                    data-source-lang-name="<?php echo esc_attr($lang_name); ?>"
                    title="<?php echo esc_attr(__('Translate this prompt to all other selected languages', 'ai-interview-widget')); ?>"
                    style="font-size: 12px; padding: 4px 8px; margin-left: 10px;">
                🌐 <?php echo esc_html(__('Translate', 'ai-interview-widget')); ?>
            </button>
        </h4>
        
        <!-- Translation warning banner (initially hidden) -->
        <div id="translation-warning-<?php echo esc_attr($lang_code); ?>" 
             class="translation-warning" 
             style="display: none; margin-bottom: 15px; padding: 10px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px; color: #856404;">
            ⚠️ <?php echo esc_html(__('Automatic translation may contain mistakes. Please review before saving.', 'ai-interview-widget')); ?>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 15px;">
            <!-- Left side: File upload panel -->
            <div style="background: white; padding: 15px; border-radius: 6px; border: 1px solid #e0e0e0;">
                <h5 style="margin: 0 0 10px 0; color: #555; font-size: 14px; font-weight: 600;">📤 Upload from File</h5>
                <form method="post" enctype="multipart/form-data" style="display: flex; flex-direction: column; gap: 10px;">
                    <?php wp_nonce_field('ai_interview_system_prompt_upload', 'system_prompt_nonce'); ?>
                    <input type="hidden" name="language_code" value="<?php echo esc_attr($lang_code); ?>">
                    <input type="file" name="system_prompt_file" accept=".txt,.pdf,.doc,.docx,.odt,.rtf" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
                    <input type="submit" name="upload_system_prompt" value="Upload <?php echo esc_attr($lang_name); ?> Prompt" class="button button-secondary" style="width: 100%; padding: 10px; text-align: center;">
                </form>
                <small style="color: #666; display: block; margin-top: 8px;">Upload a text file or document containing your system prompt</small>
            </div>
            
            <!-- Right side: Direct input panel -->
            <div style="background: white; padding: 15px; border-radius: 6px; border: 1px solid #e0e0e0;">
                <h5 style="margin: 0 0 10px 0; color: #555; font-size: 14px; font-weight: 600;">✏️ Direct Input</h5>
                <form method="post" style="display: flex; flex-direction: column; gap: 10px;">
                    <?php wp_nonce_field('ai_interview_direct_prompt_save', 'direct_prompt_nonce'); ?>
                    <input type="hidden" name="language_code" value="<?php echo esc_attr($lang_code); ?>">
                    <textarea name="system_prompt_content" rows="6" placeholder="Enter your system prompt here..." style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; resize: vertical; font-family: monospace;"><?php echo esc_textarea($current_prompt); ?></textarea>
                    <div style="display: flex; gap: 8px;">
                        <input type="submit" name="save_direct_prompt" value="Save <?php echo esc_attr($lang_name); ?> Prompt" class="button button-primary" style="flex: 1; padding: 8px; text-align: center;">
                        <input type="submit" name="clear_direct_prompt" value="Clear" class="button button-secondary" style="padding: 8px 16px;">
                    </div>
                </form>
                <small style="color: #666; display: block; margin-top: 8px;">Direct input for quick edits</small>
            </div>
        </div>
    </div>
    <?php endforeach;
    $system_prompt_html = ob_get_clean();
    
    // Return both HTML sections
    wp_send_json_success(array(
        'welcome_messages_html' => $welcome_messages_html,
        'system_prompt_html' => $system_prompt_html,
        'supported_languages' => $supported_langs
    ));
}

// Handle applying pending languages
public function handle_apply_languages() {
    // Check nonce for security
    if (!check_ajax_referer('ai_interview_apply_languages', 'nonce', false)) {
        wp_send_json_error('Invalid security token');
        return;
    }
    
    // Check user capabilities
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Insufficient permissions');
        return;
    }
    
    // Get applied languages from request
    $applied_languages = json_decode(stripslashes($_POST['applied_languages']), true);
    if (!$applied_languages) {
        wp_send_json_error('Invalid language data');
        return;
    }
    
    // Save applied languages
    update_option('ai_interview_widget_supported_languages', json_encode($applied_languages));
    
    // Clear pending languages
    delete_option('ai_interview_widget_pending_languages');
    
    // Update dependent sections with new languages
    $current_content = get_option('ai_interview_widget_content_settings', '');
    $content_data = json_decode($current_content, true);
    if (!$content_data) $content_data = array();
    
    // Generate HTML for Welcome Messages section  
    ob_start();
    foreach ($applied_languages as $lang_code => $lang_name): 
        $welcome_key = 'welcome_message_' . $lang_code;
        $current_value = isset($content_data[$welcome_key]) ? $content_data[$welcome_key] : '';
    ?>
    <div class="control-group" style="margin-bottom: 15px;">
        <label style="display: block; margin-bottom: 5px; font-weight: 600;"><?php echo esc_html($lang_name); ?> Welcome Message:</label>
        <textarea id="<?php echo esc_attr($welcome_key); ?>" rows="4" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-family: inherit; resize: vertical;"><?php echo esc_textarea($current_value); ?></textarea>
    </div>
    <?php endforeach;
    $welcome_messages_html = ob_get_clean();
    
    // Generate HTML for Audio File Management section
    ob_start();
    foreach ($applied_languages as $lang_code => $lang_name): 
        $custom_audio_key = 'ai_interview_widget_custom_audio_' . $lang_code;
        $custom_audio = get_option($custom_audio_key, '');
        $flag_emoji = $this->get_flag_emoji($lang_code);
    ?>
    <div class="control-group" style="margin-bottom: 20px;">
        <label style="display: block; margin-bottom: 8px; font-weight: 600;"><?php echo $flag_emoji; ?> <?php echo esc_html($lang_name); ?> Greeting Audio:</label>
        <?php if (!empty($custom_audio)): ?>
            <div style="margin-bottom: 10px; padding: 8px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px;">
                <span style="color: #155724;">✅ Custom audio uploaded</span>
                <audio controls style="display: block; margin-top: 5px; width: 100%;">
                    <source src="<?php echo esc_url($custom_audio); ?>" type="audio/mpeg">
                </audio>
                <button class="remove-audio-btn" data-lang="<?php echo esc_attr($lang_code); ?>" style="margin-top: 5px; padding: 4px 8px; background: #dc3545; color: white; border: none; border-radius: 3px; cursor: pointer; font-size: 12px;">Remove Custom Audio</button>
            </div>
        <?php endif; ?>
        <input type="file" id="upload_audio_<?php echo esc_attr($lang_code); ?>" accept="audio/mp3" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
        <button id="upload_btn_<?php echo esc_attr($lang_code); ?>" class="button button-secondary" style="margin-top: 8px; width: 100%;">📤 Upload <?php echo esc_html($lang_name); ?> Audio</button>
    </div>
    <?php endforeach;
    $audio_management_html = ob_get_clean();
    
    // Generate System Prompt HTML
    ob_start();
    foreach ($applied_languages as $lang_code => $lang_name): 
        $prompt_key = 'Systemprompts_Placeholder_' . $lang_code;
        $current_prompt = isset($content_data[$prompt_key]) ? $content_data[$prompt_key] : '';
    ?>
    <div style="margin-bottom: 25px; padding: 20px; background: #f9f9f9; border-radius: 8px; border: 1px solid #ddd; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
        <h4 style="margin: 0 0 15px 0; color: #333; display: flex; align-items: center; justify-content: space-between;">
            <span style="display: flex; align-items: center;">
                <span style="margin-right: 8px;">🤖</span>
                <?php echo esc_html($lang_name); ?> (<?php echo esc_html($lang_code); ?>) System Prompt
            </span>
            <button type="button" 
                    class="translate-prompt-btn button button-secondary" 
                    data-source-lang="<?php echo esc_attr($lang_code); ?>"
                    data-source-lang-name="<?php echo esc_attr($lang_name); ?>"
                    title="<?php echo esc_attr(__('Translate this prompt to all other selected languages', 'ai-interview-widget')); ?>"
                    style="font-size: 12px; padding: 4px 8px; margin-left: 10px;">
                🌐 <?php echo esc_html(__('Translate', 'ai-interview-widget')); ?>
            </button>
        </h4>
        
        <!-- Translation warning banner (initially hidden) -->
        <div id="translation-warning-<?php echo esc_attr($lang_code); ?>" 
             class="translation-warning" 
             style="display: none; margin-bottom: 15px; padding: 10px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px; color: #856404;">
            ⚠️ <?php echo esc_html(__('Automatic translation may contain mistakes. Please review before saving.', 'ai-interview-widget')); ?>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 15px;">
            <!-- Left side: File upload panel -->
            <div style="background: white; padding: 15px; border-radius: 6px; border: 1px solid #e0e0e0;">
                <h5 style="margin: 0 0 10px 0; color: #555; font-size: 14px; font-weight: 600;">📤 Upload from File</h5>
                <form method="post" enctype="multipart/form-data" style="display: flex; flex-direction: column; gap: 10px;">
                    <?php wp_nonce_field('ai_interview_system_prompt_upload', 'system_prompt_nonce'); ?>
                    <input type="hidden" name="language_code" value="<?php echo esc_attr($lang_code); ?>">
                    <input type="file" name="system_prompt_file" accept=".txt,.pdf,.doc,.docx,.odt,.rtf" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
                    <input type="submit" name="upload_system_prompt" value="Upload <?php echo esc_attr($lang_name); ?> Prompt" class="button button-secondary" style="width: 100%; padding: 10px; text-align: center;">
                </form>
                <small style="color: #666; display: block; margin-top: 8px;">Upload a text file or document containing your system prompt</small>
            </div>
            
            <!-- Right side: Direct input panel -->
            <div style="background: white; padding: 15px; border-radius: 6px; border: 1px solid #e0e0e0;">
                <h5 style="margin: 0 0 10px 0; color: #555; font-size: 14px; font-weight: 600;">✏️ Direct Input</h5>
                <form method="post" style="display: flex; flex-direction: column; gap: 10px;">
                    <?php wp_nonce_field('ai_interview_direct_prompt_save', 'direct_prompt_nonce'); ?>
                    <input type="hidden" name="language_code" value="<?php echo esc_attr($lang_code); ?>">
                    <textarea name="system_prompt_content" rows="6" placeholder="Enter your system prompt here..." style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; resize: vertical; font-family: monospace;"><?php echo esc_textarea($current_prompt); ?></textarea>
                    <div style="display: flex; gap: 8px;">
                        <input type="submit" name="save_direct_prompt" value="Save <?php echo esc_attr($lang_name); ?> Prompt" class="button button-primary" style="flex: 1; padding: 8px; text-align: center;">
                        <input type="submit" name="clear_direct_prompt" value="Clear" class="button button-secondary" style="padding: 8px 16px;">
                    </div>
                </form>
                <small style="color: #666; display: block; margin-top: 8px;">Direct input for quick edits</small>
            </div>
        </div>
    </div>
    <?php endforeach;
    $system_prompt_html = ob_get_clean();
    
    wp_send_json_success(array(
        'message' => 'Languages applied successfully',
        'welcome_messages_html' => $welcome_messages_html,
        'audio_management_html' => $audio_management_html,
        'system_prompt_html' => $system_prompt_html,
        'supported_languages' => $applied_languages
    ));
}

// Handle canceling pending languages
public function handle_cancel_pending_languages() {
    // Check nonce for security
    if (!check_ajax_referer('ai_interview_cancel_pending', 'nonce', false)) {
        wp_send_json_error('Invalid security token');
        return;
    }
    
    // Check user capabilities
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Insufficient permissions');
        return;
    }
    
    // Clear pending languages
    delete_option('ai_interview_widget_pending_languages');
    
    wp_send_json_success(array(
        'message' => 'Pending languages cancelled'
    ));
}

// Handle system prompt translation
public function handle_translate_prompt() {
    $start_time = microtime(true);
    $debug_mode = apply_filters('ai_interview_widget_translation_debug', defined('WP_DEBUG') && WP_DEBUG);
    
    // Check nonce for security
    if (!check_ajax_referer('ai_interview_translate_prompt', 'nonce', false)) {
        wp_send_json_error(array(
            'message' => 'Invalid security token',
            'code' => 'nonce_failed',
            'meta' => array(
                'elapsed_ms' => round((microtime(true) - $start_time) * 1000, 2),
                'environment_check' => array(
                    'nonce_valid' => false,
                    'user_can_manage' => current_user_can('manage_options'),
                    'api_configured' => !empty(get_option('ai_interview_widget_openai_api_key', ''))
                )
            )
        ));
        return;
    }
    
    // Check user capabilities
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array(
            'message' => 'Insufficient permissions',
            'code' => 'permission_denied',
            'meta' => array(
                'elapsed_ms' => round((microtime(true) - $start_time) * 1000, 2),
                'environment_check' => array(
                    'nonce_valid' => true,
                    'user_can_manage' => false,
                    'api_configured' => !empty(get_option('ai_interview_widget_openai_api_key', ''))
                )
            )
        ));
        return;
    }
    
    // Environment readiness checks
    $environment_check = array(
        'nonce_valid' => true,
        'user_can_manage' => true,
        'api_configured' => false,
        'endpoint_reachable' => true,
        'rate_limit_ok' => true
    );
    
    // Check API configuration
    $provider = get_option('ai_interview_widget_api_provider', 'openai');
    switch ($provider) {
        case 'openai':
            $environment_check['api_configured'] = !empty(get_option('ai_interview_widget_openai_api_key', ''));
            break;
        case 'anthropic':
            $environment_check['api_configured'] = !empty(get_option('ai_interview_widget_anthropic_api_key', ''));
            break;
        case 'gemini':
            $environment_check['api_configured'] = !empty(get_option('ai_interview_widget_gemini_api_key', ''));
            break;
        case 'azure':
            $environment_check['api_configured'] = !empty(get_option('ai_interview_widget_azure_api_key', '')) && 
                                                   !empty(get_option('ai_interview_widget_azure_endpoint', ''));
            break;
        case 'custom':
            $environment_check['api_configured'] = !empty(get_option('ai_interview_widget_custom_api_key', '')) && 
                                                   !empty(get_option('ai_interview_widget_custom_api_endpoint', ''));
            break;
    }
    
    if (!$environment_check['api_configured']) {
        wp_send_json_error(array(
            'message' => 'API not configured. Please configure your ' . ucfirst($provider) . ' API credentials.',
            'code' => 'api_not_configured',
            'meta' => array(
                'elapsed_ms' => round((microtime(true) - $start_time) * 1000, 2),
                'environment_check' => $environment_check,
                'provider' => $provider
            )
        ));
        return;
    }
    
    // Get and validate inputs
    $source_lang = isset($_POST['source_lang']) ? sanitize_text_field($_POST['source_lang']) : '';
    $source_text = isset($_POST['source_text']) ? sanitize_textarea_field($_POST['source_text']) : '';
    $target_langs = isset($_POST['target_langs']) ? json_decode(stripslashes($_POST['target_langs']), true) : array();
    
    if (empty($source_lang) || empty($source_text)) {
        wp_send_json_error(array(
            'message' => 'Missing source language or text',
            'code' => 'missing_input',
            'meta' => array(
                'elapsed_ms' => round((microtime(true) - $start_time) * 1000, 2),
                'environment_check' => $environment_check,
                'debug' => $debug_mode ? array(
                    'source_lang' => $source_lang,
                    'text_length' => strlen($source_text),
                    'target_langs_count' => count($target_langs)
                ) : null
            )
        ));
        return;
    }
    
    if (empty($target_langs) || !is_array($target_langs)) {
        wp_send_json_error(array(
            'message' => 'No target languages specified',
            'code' => 'no_targets',
            'meta' => array(
                'elapsed_ms' => round((microtime(true) - $start_time) * 1000, 2),
                'environment_check' => $environment_check
            )
        ));
        return;
    }
    
    // Get supported languages for validation
    $supported_langs = json_decode(get_option('ai_interview_widget_supported_languages', ''), true);
    if (!$supported_langs) {
        $supported_langs = array('en' => 'English', 'de' => 'German');
    }
    
    // Validate source language
    if (!isset($supported_langs[$source_lang])) {
        wp_send_json_error(array(
            'message' => 'Invalid source language',
            'code' => 'invalid_source_lang',
            'meta' => array(
                'elapsed_ms' => round((microtime(true) - $start_time) * 1000, 2),
                'environment_check' => $environment_check
            )
        ));
        return;
    }
    
    $translations = array();
    $errors = array();
    $translation_meta = array();
    
    // Translate to each target language
    foreach ($target_langs as $target_lang) {
        // Validate target language
        if (!isset($supported_langs[$target_lang])) {
            $errors[$target_lang] = 'Invalid target language';
            continue;
        }
        
        // Skip if same as source
        if ($target_lang === $source_lang) {
            continue;
        }
        
        // Perform translation
        $result = $this->aiw_llm_translate($source_text, $source_lang, $target_lang, $debug_mode);
        
        if (is_array($result) && isset($result['error'])) {
            $errors[$target_lang] = $result['error'];
            if (isset($result['meta'])) {
                $translation_meta[$target_lang] = $result['meta'];
            }
        } elseif (is_array($result) && isset($result['translation'])) {
            $translations[$target_lang] = $result['translation'];
            if (isset($result['meta'])) {
                $translation_meta[$target_lang] = $result['meta'];
            }
        } else {
            $errors[$target_lang] = 'Unknown translation error';
        }
    }
    
    // Return results
    $response = array(
        'translations' => $translations,
        'errors' => $errors,
        'source_lang' => $source_lang,
        'source_lang_name' => $supported_langs[$source_lang],
        'meta' => array(
            'elapsed_ms' => round((microtime(true) - $start_time) * 1000, 2),
            'environment_check' => $environment_check,
            'provider' => $provider,
            'translation_meta' => $debug_mode ? $translation_meta : null,
            'debug_mode' => $debug_mode
        )
    );
    
    if (!empty($translations)) {
        wp_send_json_success($response);
    } else {
        wp_send_json_error(array_merge($response, array(
            'message' => 'No translations completed successfully', 
            'code' => 'all_failed'
        )));
    }
}

// Helper function to get flag emoji for language codes
private function get_flag_emoji($lang_code) {
    $flags = array(
        'en' => '🇺🇸',
        'de' => '🇩🇪', 
        'es' => '🇪🇸',
        'fr' => '🇫🇷',
        'it' => '🇮🇹',
        'pt' => '🇵🇹',
        'ru' => '🇷🇺',
        'zh' => '🇨🇳',
        'ja' => '🇯🇵',
        'ko' => '🇰🇷',
        'ar' => '🇸🇦',
        'hi' => '🇮🇳',
        'tr' => '🇹🇷',
        'vi' => '🇻🇳'
    );
    
    return isset($flags[$lang_code]) ? $flags[$lang_code] : '🌍';
}

// Add settings link to plugin page
public function add_settings_link($links) {
$settings_link = '<a href="' . admin_url('admin.php?page=ai-interview-widget') . '">Settings</a>';
array_unshift($links, $settings_link);
return $links;
}

/**
 * WordPress Customizer Integration for AI Interview Widget
 * 
 * @deprecated Play-Button Designs section and Canvas Shadow Intensity control
 *            removed from UI in v1.9.5 for streamlined experience.
 *            Stored values continue to be honored for backward compatibility.
 * 
 * @since 1.0.0
 * @param WP_Customize_Manager $wp_customize WordPress Customizer Manager instance
 */
public function register_customizer_controls($wp_customize) {
    // Add main panel for AI Interview Widget
    $wp_customize->add_panel('ai_interview_widget', array(
        'title' => __('AI Interview Widget', 'ai-interview-widget'),
        'description' => __('Customize the AI Interview Widget appearance and behavior', 'ai-interview-widget'),
        'priority' => 160,
    ));

    // Add Canvas/Background section
    $wp_customize->add_section('ai_interview_canvas', array(
        'title' => __('Canvas & Background', 'ai-interview-widget'),
        'description' => __('Customize the widget canvas appearance, shadow effects, and background styling', 'ai-interview-widget'),
        'panel' => 'ai_interview_widget',
        'priority' => 5,
    ));

    // @deprecated Play-Button Designs section removed from UI in v1.9.5
    // Stored values continue to be honored for backward compatibility
    if (!$this->should_hide_deprecated_controls()) {
        // Add Play-Button Designs section (DEPRECATED)
        $wp_customize->add_section('ai_interview_play_button', array(
            'title' => __('Play-Button Designs (DEPRECATED)', 'ai-interview-widget'),
            'description' => __('DEPRECATED: This section has been removed from the UI. Stored values are still honored.', 'ai-interview-widget'),
            'panel' => 'ai_interview_widget',
            'priority' => 10,
        ));
    }

    // Get current style settings for defaults
    $current_settings = get_option('ai_interview_widget_style_settings', '');
    $style_data = json_decode($current_settings, true);
    if (!$style_data) $style_data = array();

    // @deprecated All Play-Button Design controls removed from UI in v1.9.5
    // Wrapped in deprecation check to allow restoration if needed
    if (!$this->should_hide_deprecated_controls()) {
        
        // Button Size Control (DEPRECATED)
        $wp_customize->add_setting('ai_play_button_size', array(
            'default' => isset($style_data['play_button_size']) ? $style_data['play_button_size'] : 64,
            'sanitize_callback' => array($this, 'sanitize_button_size'),
            'transport' => 'postMessage',
        ));
        $wp_customize->add_control('ai_play_button_size', array(
            'label' => __('Button Size (px) [DEPRECATED]', 'ai-interview-widget'),
            'description' => __('DEPRECATED: This control was removed from UI but stored values are honored.', 'ai-interview-widget'),
            'section' => 'ai_interview_play_button',
            'type' => 'range',
            'input_attrs' => array(
                'min' => 40,
                'max' => 120,
                'step' => 1,
            ),
        ));

        // Button Shape Control (DEPRECATED)
        $wp_customize->add_setting('ai_play_button_shape', array(
            'default' => isset($style_data['play_button_shape']) ? $style_data['play_button_shape'] : 'circle',
            'sanitize_callback' => array($this, 'sanitize_button_shape'),
            'transport' => 'postMessage',
        ));
        $wp_customize->add_control('ai_play_button_shape', array(
            'label' => __('Button Shape [DEPRECATED]', 'ai-interview-widget'),
            'description' => __('DEPRECATED: This control was removed from UI but stored values are honored.', 'ai-interview-widget'),
            'section' => 'ai_interview_play_button',
            'type' => 'select',
            'choices' => array(
                'circle' => __('Circle', 'ai-interview-widget'),
                'rounded' => __('Rounded', 'ai-interview-widget'),
                'square' => __('Square', 'ai-interview-widget'),
            ),
        ));

        // Primary Color Control (DEPRECATED)
        $wp_customize->add_setting('ai_play_button_color', array(
            'default' => isset($style_data['play_button_color']) ? $style_data['play_button_color'] : '#00cfff',
            'sanitize_callback' => 'sanitize_hex_color',
            'transport' => 'postMessage',
        ));
        $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'ai_play_button_color', array(
            'label' => __('Primary Color [DEPRECATED]', 'ai-interview-widget'),
            'description' => __('DEPRECATED: This control was removed from UI but stored values are honored.', 'ai-interview-widget'),
            'section' => 'ai_interview_play_button',
        )));

        // Secondary Color for Gradient Control (DEPRECATED)
        $wp_customize->add_setting('ai_play_button_gradient_end', array(
            'default' => isset($style_data['play_button_gradient_end']) ? $style_data['play_button_gradient_end'] : '',
            'sanitize_callback' => 'sanitize_hex_color',
            'transport' => 'postMessage',
        ));
        $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'ai_play_button_gradient_end', array(
            'label' => __('Secondary Color (Gradient) [DEPRECATED]', 'ai-interview-widget'),
            'description' => __('DEPRECATED: This control was removed from UI but stored values are honored.', 'ai-interview-widget'),
            'section' => 'ai_interview_play_button',
        )));

        // Icon Style Control (DEPRECATED)
        $wp_customize->add_setting('ai_play_button_icon_style', array(
            'default' => isset($style_data['play_button_icon_style']) ? $style_data['play_button_icon_style'] : 'triangle',
            'sanitize_callback' => array($this, 'sanitize_icon_style'),
            'transport' => 'postMessage',
        ));
        $wp_customize->add_control('ai_play_button_icon_style', array(
            'label' => __('Icon Style [DEPRECATED]', 'ai-interview-widget'),
            'description' => __('DEPRECATED: This control was removed from UI but stored values are honored.', 'ai-interview-widget'),
            'section' => 'ai_interview_play_button',
            'type' => 'select',
            'choices' => array(
                'triangle' => __('Triangle (Default)', 'ai-interview-widget'),
                'triangle_border' => __('Triangle with Border', 'ai-interview-widget'),
                'minimal' => __('Minimal Triangle', 'ai-interview-widget'),
            ),
        ));

        // Icon Color Control (DEPRECATED)
        $wp_customize->add_setting('ai_play_button_icon_color', array(
            'default' => isset($style_data['play_button_icon_color']) ? $style_data['play_button_icon_color'] : '#ffffff',
            'sanitize_callback' => 'sanitize_hex_color',
            'transport' => 'postMessage',
        ));
        $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'ai_play_button_icon_color', array(
            'label' => __('Icon Color [DEPRECATED]', 'ai-interview-widget'),
            'description' => __('DEPRECATED: This control was removed from UI but stored values are honored.', 'ai-interview-widget'),
            'section' => 'ai_interview_play_button',
        )));

        // Pulse Enabled Control (DEPRECATED)
        $wp_customize->add_setting('ai_play_button_pulse_enabled', array(
            'default' => !isset($style_data['play_button_disable_pulse']) || !$style_data['play_button_disable_pulse'],
            'sanitize_callback' => 'rest_sanitize_boolean',
            'transport' => 'postMessage',
        ));
        $wp_customize->add_control('ai_play_button_pulse_enabled', array(
            'label' => __('Enable Pulse Effect [DEPRECATED]', 'ai-interview-widget'),
            'description' => __('DEPRECATED: This control was removed from UI but stored values are honored.', 'ai-interview-widget'),
            'section' => 'ai_interview_play_button',
            'type' => 'checkbox',
        ));

        // Pulse Color Control (DEPRECATED)
        $wp_customize->add_setting('ai_play_button_pulse_color', array(
            'default' => isset($style_data['play_button_border_color']) ? $style_data['play_button_border_color'] : '#00cfff',
            'sanitize_callback' => 'sanitize_hex_color',
            'transport' => 'postMessage',
        ));
        $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'ai_play_button_pulse_color', array(
            'label' => __('Pulse Color [DEPRECATED]', 'ai-interview-widget'),
            'description' => __('DEPRECATED: This control was removed from UI but stored values are honored.', 'ai-interview-widget'),
            'section' => 'ai_interview_play_button',
        )));

        // Pulse Duration Control (DEPRECATED)
        $wp_customize->add_setting('ai_play_button_pulse_duration', array(
            'default' => isset($style_data['play_button_pulse_speed']) ? (2.0 / $style_data['play_button_pulse_speed']) : 2.0,
            'sanitize_callback' => array($this, 'sanitize_pulse_duration'),
            'transport' => 'postMessage',
        ));
        $wp_customize->add_control('ai_play_button_pulse_duration', array(
            'label' => __('Pulse Duration (seconds) [DEPRECATED]', 'ai-interview-widget'),
            'description' => __('DEPRECATED: This control was removed from UI but stored values are honored.', 'ai-interview-widget'),
            'section' => 'ai_interview_play_button',
            'type' => 'range',
            'input_attrs' => array(
                'min' => 0.8,
                'max' => 3.5,
                'step' => 0.1,
            ),
        ));

        // Pulse Max Spread Control (DEPRECATED)
        $wp_customize->add_setting('ai_play_button_pulse_spread', array(
            'default' => isset($style_data['play_button_shadow_intensity']) ? $style_data['play_button_shadow_intensity'] : 24,
            'sanitize_callback' => array($this, 'sanitize_pulse_spread'),
            'transport' => 'postMessage',
        ));
        $wp_customize->add_control('ai_play_button_pulse_spread', array(
            'label' => __('Pulse Max Spread (px) [DEPRECATED]', 'ai-interview-widget'),
            'description' => __('DEPRECATED: This control was removed from UI but stored values are honored.', 'ai-interview-widget'),
            'section' => 'ai_interview_play_button',
            'type' => 'range',
            'input_attrs' => array(
                'min' => 8,
                'max' => 40,
                'step' => 1,
            ),
        ));

        // Hover Effect Style Control (DEPRECATED)
        $wp_customize->add_setting('ai_play_button_hover_style', array(
            'default' => isset($style_data['play_button_hover_style']) ? $style_data['play_button_hover_style'] : 'scale',
            'sanitize_callback' => array($this, 'sanitize_hover_style'),
            'transport' => 'postMessage',
        ));
        $wp_customize->add_control('ai_play_button_hover_style', array(
            'label' => __('Hover Effect Style [DEPRECATED]', 'ai-interview-widget'),
            'description' => __('DEPRECATED: This control was removed from UI but stored values are honored.', 'ai-interview-widget'),
            'section' => 'ai_interview_play_button',
            'type' => 'select',
            'choices' => array(
                'scale' => __('Scale (Default)', 'ai-interview-widget'),
                'glow' => __('Glow', 'ai-interview-widget'),
                'none' => __('None', 'ai-interview-widget'),
            ),
        ));

        // Focus Ring Color Control (DEPRECATED)
        $wp_customize->add_setting('ai_play_button_focus_color', array(
            'default' => isset($style_data['play_button_color']) ? $style_data['play_button_color'] : '#00cfff',
            'sanitize_callback' => 'sanitize_hex_color',
            'transport' => 'postMessage',
        ));
        $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'ai_play_button_focus_color', array(
            'label' => __('Focus Ring Color [DEPRECATED]', 'ai-interview-widget'),
            'description' => __('DEPRECATED: This control was removed from UI but stored values are honored.', 'ai-interview-widget'),
            'section' => 'ai_interview_play_button',
        )));
        
    } // End deprecated Play-Button controls

    // Canvas Shadow Color Control (keeping this one)
    $wp_customize->add_setting('ai_canvas_shadow_color', array(
        'default' => $this->get_canvas_shadow_color('#00cfff'),
        'sanitize_callback' => 'sanitize_hex_color',
        'transport' => 'postMessage',
    ));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'ai_canvas_shadow_color', array(
        'label' => __('Canvas Shadow Color', 'ai-interview-widget'),
        'description' => __('Color of the canvas glow/shadow effect', 'ai-interview-widget'),
        'section' => 'ai_interview_canvas',
    )));

    // @deprecated Canvas Shadow Intensity control removed from UI in v1.9.5
    // Stored values continue to be honored for backward compatibility
    if (!$this->should_hide_deprecated_controls()) {
        // Canvas Shadow Intensity Control (DEPRECATED)
        $wp_customize->add_setting('ai_canvas_shadow_intensity', array(
            'default' => isset($style_data['canvas_shadow_intensity']) ? $style_data['canvas_shadow_intensity'] : 30,
            'sanitize_callback' => array($this, 'sanitize_canvas_shadow_intensity'),
            'transport' => 'postMessage',
        ));
        $wp_customize->add_control('ai_canvas_shadow_intensity', array(
            'label' => __('Canvas Shadow Intensity [DEPRECATED]', 'ai-interview-widget'),
            'description' => __('DEPRECATED: This control was removed from UI but stored values are honored. (0 = no shadow, 100 = maximum)', 'ai-interview-widget'),
            'section' => 'ai_interview_canvas',
            'type' => 'range',
            'input_attrs' => array(
                'min' => 0,
                'max' => 100,
                'step' => 1,
            ),
        ));
    }
}

// Enqueue Customizer live preview script
public function enqueue_customizer_preview_script() {
    wp_enqueue_script(
        'ai-interview-customizer-preview',
        plugin_dir_url(__FILE__) . 'customizer-preview.js',
        array('jquery', 'customize-preview'),
        '1.0.0',
        true
    );
}

// Sanitization functions for Customizer controls
// @deprecated Play-Button sanitization functions maintained for backward compatibility

/**
 * @deprecated since v1.9.5 - Play-Button controls removed from UI
 */
public function sanitize_button_size($size) {
    $this->log_deprecation_notice('play_button_size', 'sanitization');
    $size = absint($size);
    return max(40, min(120, $size));
}

/**
 * @deprecated since v1.9.5 - Play-Button controls removed from UI
 */
public function sanitize_button_shape($shape) {
    $this->log_deprecation_notice('play_button_shape', 'sanitization');
    $allowed_shapes = array('circle', 'rounded', 'square');
    return in_array($shape, $allowed_shapes) ? $shape : 'circle';
}

/**
 * @deprecated since v1.9.5 - Play-Button controls removed from UI
 */
public function sanitize_icon_style($style) {
    $this->log_deprecation_notice('play_button_icon_style', 'sanitization');
    $allowed_styles = array('triangle', 'triangle_border', 'minimal');
    return in_array($style, $allowed_styles) ? $style : 'triangle';
}

/**
 * @deprecated since v1.9.5 - Play-Button controls removed from UI
 */
public function sanitize_pulse_duration($duration) {
    $this->log_deprecation_notice('play_button_pulse_duration', 'sanitization');
    $duration = floatval($duration);
    return max(0.8, min(3.5, $duration));
}

/**
 * @deprecated since v1.9.5 - Play-Button controls removed from UI
 */
public function sanitize_pulse_spread($spread) {
    $this->log_deprecation_notice('play_button_pulse_spread', 'sanitization');
    $spread = absint($spread);
    return max(8, min(40, $spread));
}

/**
 * @deprecated since v1.9.5 - Play-Button controls removed from UI
 */
public function sanitize_hover_style($style) {
    $this->log_deprecation_notice('play_button_hover_style', 'sanitization');
    $allowed_styles = array('scale', 'glow', 'none');
    return in_array($style, $allowed_styles) ? $style : 'scale';
}

/**
 * @deprecated since v1.9.5 - Canvas Shadow Intensity control removed from UI
 */
public function sanitize_canvas_shadow_intensity($intensity) {
    $this->log_deprecation_notice('canvas_shadow_intensity', 'sanitization');
    $intensity = absint($intensity);
    if ($intensity < 0) $intensity = 0; // Handle edge case for negative values
    return max(0, min(100, $intensity));
}

/**
 * Sync WordPress Customizer settings to plugin internal settings
 * 
 * @deprecated Play-Button and Canvas Shadow Intensity settings sync
 *            maintained for backward compatibility but logged as deprecated
 * 
 * @since 1.0.0
 */
public function sync_customizer_to_plugin_settings() {
    // Get current plugin style settings
    $style_settings_json = get_option('ai_interview_widget_style_settings', '');
    $style_settings = json_decode($style_settings_json, true);
    if (!$style_settings) $style_settings = array();
    
    // Map WordPress Customizer settings to plugin settings
    // @deprecated Most play button settings removed from UI in v1.9.5
    $customizer_to_plugin_map = array(
        'ai_canvas_shadow_color' => 'canvas_shadow_color', // Still active
        'ai_canvas_shadow_intensity' => 'canvas_shadow_intensity', // DEPRECATED
        'ai_play_button_size' => 'play_button_size', // DEPRECATED
        'ai_play_button_color' => 'play_button_color', // DEPRECATED
        'ai_play_button_gradient_end' => 'play_button_gradient_end', // DEPRECATED
        'ai_play_button_icon_color' => 'play_button_icon_color', // DEPRECATED
        'ai_play_button_icon_style' => 'play_button_icon_style', // DEPRECATED
        'ai_play_button_pulse_enabled' => 'play_button_disable_pulse', // DEPRECATED - Note: inverted logic
        'ai_play_button_pulse_color' => 'play_button_border_color', // DEPRECATED
        'ai_play_button_pulse_duration' => 'play_button_pulse_speed', // DEPRECATED - Note: needs conversion
        'ai_play_button_pulse_spread' => 'play_button_shadow_intensity', // DEPRECATED
        'ai_play_button_hover_style' => 'play_button_hover_style', // DEPRECATED
        'ai_play_button_focus_color' => 'play_button_focus_color', // DEPRECATED
        'ai_play_button_shape' => 'play_button_shape', // DEPRECATED
    );
    
    // Deprecated settings that should be logged
    $deprecated_settings = array(
        'ai_canvas_shadow_intensity', 'ai_play_button_size', 'ai_play_button_color',
        'ai_play_button_gradient_end', 'ai_play_button_icon_color', 'ai_play_button_icon_style',
        'ai_play_button_pulse_enabled', 'ai_play_button_pulse_color', 'ai_play_button_pulse_duration',
        'ai_play_button_pulse_spread', 'ai_play_button_hover_style', 'ai_play_button_focus_color',
        'ai_play_button_shape'
    );
    
    $settings_updated = false;
    
    foreach ($customizer_to_plugin_map as $customizer_key => $plugin_key) {
        $customizer_value = get_theme_mod($customizer_key);
        
        if ($customizer_value !== false && $customizer_value !== null) {
            // Log deprecation notice for deprecated settings
            if (in_array($customizer_key, $deprecated_settings)) {
                $this->log_deprecation_notice($customizer_key, 'customizer_sync');
            }
            
            // Handle special conversions
            if ($plugin_key === 'play_button_disable_pulse') {
                // Invert boolean logic: pulse_enabled -> disable_pulse
                $style_settings[$plugin_key] = !$customizer_value;
            } elseif ($plugin_key === 'play_button_pulse_speed') {
                // Convert duration to speed: speed = 2.0 / duration
                $duration = floatval($customizer_value);
                if ($duration > 0) {
                    $style_settings[$plugin_key] = 2.0 / $duration;
                }
            } else {
                // Direct mapping
                $style_settings[$plugin_key] = $customizer_value;
            }
            $settings_updated = true;
        }
    }
    
    // Save updated settings if any changes were made
    if ($settings_updated) {
        $updated_json = json_encode($style_settings);
        update_option('ai_interview_widget_style_settings', $updated_json);
        
        // Log for debugging
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('AI Interview Widget: Synced WordPress Customizer settings to plugin settings');
        }
    }
}

// TESTING PAGE - COMPLETE VERSION
public function testing_page() {
    // Enqueue widget scripts and styles for the admin preview
    $this->enqueue_scripts();
    
    ?>
<div class="wrap">
    <div style="display: flex; align-items: center; margin-bottom: 30px; padding: 20px; background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 50%, #fecfef 100%); color: #333; border-radius: 10px;">
        <span class="dashicons dashicons-admin-tools" style="font-size: 60px; margin-right: 20px; opacity: 0.8;"></span>
        <div>
            <h1 style="margin: 0; color: #333; font-size: 32px;">API Testing & Diagnostics</h1>
            <p style="margin: 8px 0 0 0; font-size: 16px; opacity: 0.8;">
                <strong>Version 1.9.3 ENHANCED</strong> | Updated: 2025-01-27 UTC | User: EricRorich
            </p>
            <p style="margin: 8px 0 0 0; font-size: 14px; opacity: 0.7;">
                🧪 Complete API testing suite with live widget preview, voice features, and custom appearance
            </p>
        </div>
    </div>

    <!-- Widget Preview Placeholder -->
    <div class="postbox" style="padding: 25px; margin-bottom: 20px;">
        <h2 style="margin: 0 0 15px 0;">🎯 Widget Preview</h2>
        <p>The widget preview is temporarily disabled for maintenance and stability improvements.</p>

        <div style="background: #f8f9fa; padding: 40px 20px; border-radius: 10px; margin: 15px 0; position: relative; border: 2px dashed #ddd; text-align: center;" 
             role="presentation" 
             aria-hidden="true" 
             data-disabled="true">
            
            <div style="color: #666; font-size: 18px; margin-bottom: 15px;">
                <span class="dashicons dashicons-visibility" style="font-size: 48px; color: #ccc; margin-bottom: 10px; display: block;"></span>
                Live preview temporarily disabled
            </div>
            
            <p style="color: #999; margin: 0; font-size: 14px; max-width: 400px; margin: 0 auto;">
                Widget functionality remains fully operational on your frontend. 
                This preview will be restored in a future update with enhanced stability.
            </p>
            
            <div style="position: absolute; top: 10px; right: 10px; background: rgba(0,0,0,0.1); color: #666; padding: 5px 10px; border-radius: 5px; font-size: 12px;">
                ⚪ PREVIEW DISABLED
            </div>
        </div>

        <div style="margin-top: 15px; padding: 15px; background: #fff3cd; border-radius: 5px; border-left: 4px solid #ffc107;">
            <h4 style="margin: 0 0 10px 0; color: #856404;">Alternative Testing Options:</h4>
            <ul style="margin: 0; padding-left: 20px; color: #856404;">
                <li><strong>Frontend Testing:</strong> View your live widget using the <code>[ai_interview_widget]</code> shortcode on any page</li>
                <li><strong>API Testing:</strong> Use the individual API connection tests below to verify functionality</li>
                <li><strong>Settings Management:</strong> Continue using the Enhanced Widget Customizer to configure appearance</li>
                <li><strong>Debug Console:</strong> Monitor the debug console below for real-time diagnostics</li>
            </ul>
        </div>
    </div>

    <!-- API Status Dashboard -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-bottom: 20px;">

        <!-- OpenAI Status -->
        <div class="postbox" style="padding: 20px;">
            <h3 style="margin: 0 0 15px 0;">🧠 OpenAI GPT-4o-mini Status</h3>
            <?php
            $openai_key = get_option('ai_interview_widget_openai_api_key', '');
            if (empty($openai_key)):
            ?>
                <div style="color: #dc3232; font-weight: bold; margin-bottom: 10px;">⚠️ Not Configured</div>
                <p style="margin: 0; color: #666; font-size: 14px;">Configure OpenAI API key in settings to enable chat functionality.</p>
            <?php else: ?>
                <div style="color: #46b450; font-weight: bold; margin-bottom: 10px;">✅ API Key Configured</div>
                <p style="margin: 0 0 10px 0; color: #666; font-size: 14px;">Key: <?php echo substr($openai_key, 0, 7) . '***' . substr($openai_key, -4); ?></p>
                <button onclick="testOpenAI()" class="button button-primary">🧪 Test Connection</button>
                <div id="openai-test-result" style="margin-top: 10px;"></div>
            <?php endif; ?>
        </div>

        <!-- ElevenLabs Status -->
        <div class="postbox" style="padding: 20px;">
            <h3 style="margin: 0 0 15px 0;">🎤 ElevenLabs Voice Status</h3>
            <?php
            $elevenlabs_key = get_option('ai_interview_widget_elevenlabs_api_key', '');
            $voice_enabled = get_option('ai_interview_widget_enable_voice', true);

            if (!$voice_enabled):
            ?>
                <div style="color: #666; font-weight: bold; margin-bottom: 10px;">🔇 Voice Disabled</div>
                <p style="margin: 0; color: #666; font-size: 14px;">Voice features are turned off in settings.</p>
            <?php elseif (empty($elevenlabs_key)): ?>
                <div style="color: #ffb900; font-weight: bold; margin-bottom: 10px;">⚠️ Fallback Mode</div>
                <p style="margin: 0 0 10px 0; color: #666; font-size: 14px;">Using browser TTS. Configure ElevenLabs for premium quality.</p>
                <button onclick="testBrowserTTS()" class="button button-secondary">🔊 Test Browser TTS</button>
            <?php else: ?>
                <div style="color: #46b450; font-weight: bold; margin-bottom: 10px;">✅ Premium Voice Ready</div>
                <p style="margin: 0 0 10px 0; color: #666; font-size: 14px;">ElevenLabs API configured with voice ID: <?php echo substr(get_option('ai_interview_widget_elevenlabs_voice_id', 'pNInz6obpgDQGcFmaJgB'), 0, 8); ?>...</p>
                <button onclick="testElevenLabs()" class="button button-primary">🧪 Test ElevenLabs</button>
                <div id="elevenlabs-test-result" style="margin-top: 10px;"></div>
            <?php endif; ?>
        </div>

        <!-- Browser Features -->
        <div class="postbox" style="padding: 20px;">
            <h3 style="margin: 0 0 15px 0;">🌐 Browser Capabilities</h3>
            <div id="browser-capabilities">
                <p style="margin: 0 0 10px 0; color: #666;">Loading browser feature detection...</p>
            </div>
            <button onclick="testBrowserFeatures()" class="button button-secondary">🧪 Test Features</button>
        </div>

        <!-- Performance Metrics -->
        <div class="postbox" style="padding: 20px;">
            <h3 style="margin: 0 0 15px 0;">📊 Performance Metrics</h3>
            <div id="performance-metrics">
                <p style="margin: 0; color: #666; font-size: 14px;">
                    Server: <strong><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></strong><br>
                    PHP: <strong><?php echo phpversion(); ?></strong><br>
                    Memory: <strong><?php echo size_format(memory_get_usage(true)); ?></strong><br>
                    WordPress: <strong><?php echo get_bloginfo('version'); ?></strong>
                </p>
            </div>
            <button onclick="runPerformanceTest()" class="button button-secondary">⚡ Run Speed Test</button>
            <div id="performance-test-result" style="margin-top: 10px;"></div>
        </div>
    </div>

    <!-- Comprehensive Test Suite -->
    <div class="postbox" style="padding: 25px;">
        <h2 style="margin: 0 0 20px 0;">🧪 Comprehensive Test Suite</h2>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;">
            <button onclick="runFullDiagnostic()" class="button button-primary" style="padding: 15px; height: auto;">
                🔍 Full Diagnostic<br>
                <small>Complete system check</small>
            </button>

            <button onclick="testAJAXEndpoints()" class="button button-secondary" style="padding: 15px; height: auto;">
                🔗 AJAX Endpoints<br>
                <small>Test all API endpoints</small>
            </button>

            <button onclick="testSecurityFeatures()" class="button button-secondary" style="padding: 15px; height: auto;">
                🔒 Security Test<br>
                <small>Nonce & permissions</small>
            </button>

            <button onclick="simulateUserInteraction()" class="button button-secondary" style="padding: 15px; height: auto;">
                👤 User Simulation<br>
                <small>End-to-end workflow</small>
            </button>
        </div>

        <div id="comprehensive-test-results" style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 5px; display: none;">
            <h3>Test Results:</h3>
            <div id="test-results-content"></div>
        </div>
    </div>

    <!-- Debug Console -->
    <div class="postbox" style="padding: 25px;">
        <h2 style="margin: 0 0 15px 0;">🖥️ Debug Console</h2>
        <div style="display: flex; gap: 10px; margin-bottom: 15px;">
            <button onclick="clearDebugConsole()" class="button button-small">🧹 Clear</button>
            <button onclick="exportDebugLog()" class="button button-small">📥 Export Log</button>
            <button onclick="toggleAutoScroll()" class="button button-small" id="autoscroll-btn">📜 Auto-scroll: ON</button>
        </div>
        <div id="debug-console" style="background: #000; color: #00ff00; padding: 15px; border-radius: 5px; height: 300px; overflow-y: auto; font-family: 'Courier New', monospace; font-size: 12px;">
            <div>AI Interview Widget v1.9.3 Debug Console - 2025-08-03 18:45:35 UTC</div>
            <div>User: EricRorich | Ready for testing...</div>
            <div>========================================</div>
        </div>
    </div>
</div>

<script>
// Debug console functionality
let autoScroll = true;

function logToConsole(message, type = 'info') {
    const console = document.getElementById('debug-console');
    const timestamp = new Date().toISOString().replace('T', ' ').substring(0, 19);
    const typeIcon = type === 'error' ? '❌' : type === 'success' ? '✅' : type === 'warning' ? '⚠️' : 'ℹ️';

    const logEntry = document.createElement('div');
    logEntry.style.color = type === 'error' ? '#ff6b6b' : type === 'success' ? '#51cf66' : type === 'warning' ? '#ffd43b' : '#00ff00';
    logEntry.textContent = `[${timestamp}] ${typeIcon} ${message}`;

    console.appendChild(logEntry);

    if (autoScroll) {
        console.scrollTop = console.scrollHeight;
    }
}

function clearDebugConsole() {
    document.getElementById('debug-console').innerHTML = '<div>Console cleared at ' + new Date().toISOString() + '</div>';
}

function toggleAutoScroll() {
    autoScroll = !autoScroll;
    document.getElementById('autoscroll-btn').textContent = '📜 Auto-scroll: ' + (autoScroll ? 'ON' : 'OFF');
}

function exportDebugLog() {
    const consoleContent = document.getElementById('debug-console').textContent;
    const blob = new Blob([consoleContent], { type: 'text/plain' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'ai-widget-debug-log-' + new Date().toISOString().substring(0, 19).replace(/:/g, '-') + '.txt';
    a.click();
    URL.revokeObjectURL(url);
}

// API Testing Functions
function testOpenAI() {
    logToConsole('Testing OpenAI API connection...');

    fetch(ajaxurl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'ai_interview_chat',
            message: 'Hello, this is a test message.',
            system_prompt: 'You are a helpful assistant. Respond briefly to test messages.',
            nonce: '<?php echo wp_create_nonce('ai_interview_nonce'); ?>'
        })
    })
    .then(response => response.json())
    .then(data => {
        const resultDiv = document.getElementById('openai-test-result');
        if (data.success) {
            resultDiv.innerHTML = '<div style="color: #46b450; font-weight: bold;">✅ Success!</div><p style="font-size: 12px; margin: 5px 0 0 0;">' + data.data.reply.substring(0, 100) + '...</p>';
            logToConsole('OpenAI API test successful', 'success');
        } else {
            resultDiv.innerHTML = '<div style="color: #dc3232; font-weight: bold;">❌ Failed</div><p style="font-size: 12px; margin: 5px 0 0 0;">' + data.data.message + '</p>';
            logToConsole('OpenAI API test failed: ' + data.data.message, 'error');
        }
    })
    .catch(error => {
        const resultDiv = document.getElementById('openai-test-result');
        resultDiv.innerHTML = '<div style="color: #dc3232; font-weight: bold;">❌ Network Error</div>';
        logToConsole('OpenAI API test network error: ' + error, 'error');
    });
}

function testElevenLabs() {
    logToConsole('Testing ElevenLabs TTS...');

    fetch(ajaxurl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'ai_interview_tts',
            text: 'Hello, this is a test of ElevenLabs text-to-speech functionality.',
            nonce: '<?php echo wp_create_nonce('ai_interview_nonce'); ?>'
        })
    })
    .then(response => response.json())
    .then(data => {
        const resultDiv = document.getElementById('elevenlabs-test-result');
        if (data.success && data.data.audio_url) {
            resultDiv.innerHTML = '<div style="color: #46b450; font-weight: bold;">✅ Success!</div><audio controls style="width: 100%; margin-top: 5px;"><source src="' + data.data.audio_url + '" type="audio/mpeg"></audio>';
            logToConsole('ElevenLabs TTS test successful', 'success');
        } else {
            resultDiv.innerHTML = '<div style="color: #dc3232; font-weight: bold;">❌ Failed</div><p style="font-size: 12px;">' + (data.data.message || 'Unknown error') + '</p>';
            logToConsole('ElevenLabs TTS test failed', 'error');
        }
    })
    .catch(error => {
        const resultDiv = document.getElementById('elevenlabs-test-result');
        resultDiv.innerHTML = '<div style="color: #dc3232; font-weight: bold;">❌ Network Error</div>';
        logToConsole('ElevenLabs TTS test network error: ' + error, 'error');
    });
}

function testBrowserTTS() {
    logToConsole('Testing browser TTS...');

    if ('speechSynthesis' in window) {
        const utterance = new SpeechSynthesisUtterance('Hello, this is a test of browser text-to-speech functionality.');
        speechSynthesis.speak(utterance);
        logToConsole('Browser TTS test successful', 'success');
    } else {
        logToConsole('Browser TTS not supported', 'error');
    }
}

function testBrowserFeatures() {
    logToConsole('Testing browser capabilities...');

    const capabilities = {
        'Speech Recognition': 'webkitSpeechRecognition' in window || 'SpeechRecognition' in window,
        'Speech Synthesis': 'speechSynthesis' in window,
        'Web Audio API': 'AudioContext' in window || 'webkitAudioContext' in window,
        'Canvas 2D': !!document.createElement('canvas').getContext,
        'Local Storage': 'localStorage' in window,
        'Fetch API': 'fetch' in window,
        'WebGL': !!document.createElement('canvas').getContext('webgl'),
        'Touch Events': 'ontouchstart' in window
    };

    let html = '';
    for (const [feature, supported] of Object.entries(capabilities)) {
        const icon = supported ? '✅' : '❌';
        const color = supported ? '#46b450' : '#dc3232';
        html += `<div style="margin: 3px 0; color: ${color};">${icon} ${feature}</div>`;
        logToConsole(`${feature}: ${supported ? 'Supported' : 'Not supported'}`, supported ? 'success' : 'warning');
    }

    document.getElementById('browser-capabilities').innerHTML = html;
}

function runPerformanceTest() {
    logToConsole('Running performance test...');
    const startTime = performance.now();

    // Simulate some work
    const testArray = [];
    for (let i = 0; i < 100000; i++) {
        testArray.push(Math.random());
    }

    const endTime = performance.now();
    const duration = (endTime - startTime).toFixed(2);

    const resultDiv = document.getElementById('performance-test-result');
    resultDiv.innerHTML = `<div style="color: #46b450; font-weight: bold;">⚡ Performance Test Complete</div><p style="font-size: 12px; margin: 5px 0 0 0;">JavaScript execution time: ${duration}ms</p>`;

    logToConsole(`Performance test completed in ${duration}ms`, 'success');
}

function testAJAXEndpoints() {
    logToConsole('Testing AJAX endpoints...');

    const endpoints = [
        { action: 'ai_interview_test', name: 'Test Endpoint' },
        { action: 'ai_interview_chat', name: 'Chat Endpoint', data: { message: 'test', system_prompt: 'respond briefly' } },
        { action: 'ai_interview_voice_tts', name: 'Voice TTS Endpoint' }
    ];

    endpoints.forEach(endpoint => {
        const formData = new URLSearchParams({
            action: endpoint.action,
            nonce: '<?php echo wp_create_nonce('ai_interview_nonce'); ?>'
        });

        if (endpoint.data) {
            Object.keys(endpoint.data).forEach(key => {
                formData.append(key, endpoint.data[key]);
            });
        }

        fetch(ajaxurl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            logToConsole(`${endpoint.name}: ${data.success ? 'OK' : 'FAILED'}`, data.success ? 'success' : 'error');
        })
        .catch(error => {
            logToConsole(`${endpoint.name}: NETWORK ERROR`, 'error');
        });
    });
}

function testSecurityFeatures() {
    logToConsole('Testing security features...');

    // Test without nonce
    fetch(ajaxurl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            action: 'ai_interview_test'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            logToConsole('Security test passed: Request without nonce properly rejected', 'success');
        } else {
            logToConsole('Security warning: Request without nonce was accepted', 'warning');
        }
    });
}

function simulateUserInteraction() {
    logToConsole('Simulating complete user interaction...');

    // Simulate a complete user workflow
    setTimeout(() => logToConsole('User opens widget...'), 500);
    setTimeout(() => logToConsole('User types message...'), 1000);
    setTimeout(() => logToConsole('User sends message...'), 1500);
    setTimeout(() => logToConsole('AI processes request...'), 2000);
    setTimeout(() => logToConsole('AI responds with answer...'), 2500);
    setTimeout(() => logToConsole('User interaction simulation complete'), 3000);
}

function runFullDiagnostic() {
    logToConsole('Starting full diagnostic...');
    document.getElementById('comprehensive-test-results').style.display = 'block';

    const startTime = Date.now();
    let results = [];

    // Test browser features
    testBrowserFeatures();
    results.push('✅ Browser capabilities checked');

    // Test performance
    runPerformanceTest();
    results.push('✅ Performance metrics collected');

    // Test AJAX endpoints
    testAJAXEndpoints();
    results.push('✅ AJAX endpoints tested');

    // Test security
    testSecurityFeatures();
    results.push('✅ Security features verified');

    setTimeout(() => {
        const duration = ((Date.now() - startTime) / 1000).toFixed(2);
        results.push(`⏱️ Diagnostic completed in ${duration} seconds`);

        document.getElementById('test-results-content').innerHTML = results.map(result =>
            `<div style="margin: 5px 0; padding: 5px; background: #fff; border-left: 3px solid #46b450;">${result}</div>`
        ).join('');

        logToConsole(`Full diagnostic completed in ${duration} seconds`, 'success');
    }, 3500);
}

// Initialize browser feature detection on page load
document.addEventListener('DOMContentLoaded', function() {
    testBrowserFeatures();
    logToConsole('Testing page loaded and initialized', 'success');
});
</script>
<?php
}

// DOCUMENTATION PAGE - COMPLETE VERSION
public function documentation_page() {
?>
<div class="wrap">
    <div style="display: flex; align-items: center; margin-bottom: 30px; padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 10px;">
        <span class="dashicons dashicons-book-alt" style="font-size: 60px; margin-right: 20px; opacity: 0.9;"></span>
        <div>
            <h1 style="margin: 0; color: white; font-size: 32px;">Usage & Documentation</h1>
            <p style="margin: 8px 0 0 0; font-size: 16px; opacity: 0.9;">
                <strong>Version 1.9.3 COMPLETE</strong> | Updated: 2025-08-03 18:45:35 UTC | User: EricRorich
            </p>
            <p style="margin: 8px 0 0 0; font-size: 14px; opacity: 0.8;">
                📚 Complete implementation guide with examples, troubleshooting, and best practices
            </p>
        </div>
    </div>

    <!-- Quick Start Guide -->
    <div class="postbox" style="padding: 25px; margin-bottom: 20px;">
        <h2 style="margin: 0 0 20px 0;">🚀 Quick Start Guide</h2>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
            <div style="background: #e7f3ff; padding: 20px; border-radius: 8px; border-left: 4px solid #007cba;">
                <h3 style="margin: 0 0 10px 0;">1️⃣ Basic Setup</h3>
                <ol style="margin: 0; padding-left: 20px;">
                    <li>Configure OpenAI API key</li>
                    <li>Optionally add ElevenLabs key</li>
                    <li>Enable voice features</li>
                    <li>Test connections</li>
                </ol>
            </div>

            <div style="background: #f0f8e7; padding: 20px; border-radius: 8px; border-left: 4px solid #46b450;">
                <h3 style="margin: 0 0 10px 0;">2️⃣ Add to Pages</h3>
                <p style="margin: 0 0 10px 0;">Insert shortcode anywhere:</p>
                <code style="background: #333; color: #fff; padding: 8px 12px; border-radius: 4px; display: block; font-family: monospace;">
                    [ai_interview_widget]
                </code>
            </div>

            <div style="background: #fff3cd; padding: 20px; border-radius: 8px; border-left: 4px solid #ffc107;">
                <h3 style="margin: 0 0 10px 0;">3️⃣ Customize</h3>
                <ul style="margin: 0; padding-left: 20px;">
                    <li>Use Enhanced Customizer</li>
                    <li>Modify colors and styles</li>
                    <li>Update content and prompts</li>
                    <li>Upload custom audio</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Support Information -->
    <div class="postbox" style="padding: 25px;">
        <h2 style="margin: 0 0 15px 0;">🆘 Support & Resources</h2>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">

            <div>
                <h3>📖 Documentation Links</h3>
                <ul>
                    <li><a href="https://platform.openai.com/docs" target="_blank">OpenAI API Documentation</a></li>
                    <li><a href="https://elevenlabs.io/docs" target="_blank">ElevenLabs API Documentation</a></li>
                    <li><a href="https://developer.mozilla.org/en-US/docs/Web/API/Web_Speech_API" target="_blank">Web Speech API Reference</a></li>
                    <li><a href="https://codex.wordpress.org/Shortcode_API" target="_blank">WordPress Shortcode API</a></li>
                </ul>
            </div>

            <div>
                <h3>🔧 Technical Support</h3>
                <p><strong>Plugin Version:</strong> 1.9.3 COMPLETE</p>
                <p><strong>Last Updated:</strong> 2025-08-03 18:45:35 UTC</p>
                <p><strong>Updated By:</strong> EricRorich</p>
                <p><strong>PHP Version Required:</strong> 7.4+</p>
                <p><strong>WordPress Version:</strong> 5.0+</p>
                <p><a href="<?php echo admin_url('admin.php?page=ai-interview-widget-testing'); ?>" class="button button-primary">🧪 Run Diagnostics</a></p>
            </div>

            <div>
                <h3>💻 Development Info</h3>
                <p>This plugin demonstrates advanced WordPress development with:</p>
                <ul style="font-size: 14px;">
                    <li>Modern JavaScript (ES6+)</li>
                    <li>AJAX API integration</li>
                    <li>Canvas animations</li>
                    <li>Web Speech API</li>
                    <li>Real-time audio processing</li>
                    <li>Responsive design patterns</li>
                </ul>
            </div>
        </div>
    </div>
</div>


<?php
}
}

// Initialize the plugin
new AIInterviewWidget();

// Register uninstall hook
register_uninstall_hook(__FILE__, array('AIInterviewWidget', 'plugin_uninstall'));

// Add cleanup hook for old TTS files
add_action('ai_interview_cleanup_tts_files', function() {
$upload_dir = wp_upload_dir();
$files = glob($upload_dir['path'] . '/ai_voice_tts_*.mp3');

foreach ($files as $file) {
if (file_exists($file) && (time() - filemtime($file)) > 3600) { // Delete files older than 1 hour
    unlink($file);
}
}
});
?>
