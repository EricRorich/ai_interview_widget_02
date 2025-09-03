<?php
/**
 * Plugin Name: AI Interview Widget
 * Description: Interactive AI widget for Eric Rorich's portfolio with voice capabilities and WordPress/Elementor integration. Refactored for modern WordPress development standards.
 * Version: 2.0.0
 * Author: Eric Rorich
 * License: GPL v2 or later
 * Text Domain: ai-interview-widget
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.3
 * Requires PHP: 7.4
 * 
 * @package EricRorich\AIInterviewWidget
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('AIW_VERSION', '2.0.0');
define('AIW_PLUGIN_FILE', __FILE__);
define('AIW_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AIW_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AIW_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Autoloader - Include Composer autoloader if available, otherwise use fallback
if (file_exists(AIW_PLUGIN_DIR . 'vendor/autoload.php')) {
    require_once AIW_PLUGIN_DIR . 'vendor/autoload.php';
} else {
    // Fallback autoloader for environments without Composer
    spl_autoload_register(function ($class) {
        // Only autoload our namespace
        $namespace = 'EricRorich\\AIInterviewWidget\\';
        
        if (strpos($class, $namespace) !== 0) {
            return;
        }
        
        // Convert class name to file path
        $relative_class = substr($class, strlen($namespace));
        $file_path = AIW_PLUGIN_DIR . 'src/' . str_replace('\\', '/', $relative_class) . '.php';
        
        if (file_exists($file_path)) {
            require_once $file_path;
        }
    });
}

// Import required classes
use EricRorich\AIInterviewWidget\Core\Plugin;
use EricRorich\AIInterviewWidget\Setup\Activator;
use EricRorich\AIInterviewWidget\Setup\Deactivator;
use EricRorich\AIInterviewWidget\Setup\Uninstaller;

/**
 * Plugin activation hook
 */
register_activation_hook(__FILE__, function() {
    Activator::activate();
});

/**
 * Plugin deactivation hook
 */
register_deactivation_hook(__FILE__, function() {
    Deactivator::deactivate();
});

/**
 * Plugin uninstall hook
 */
register_uninstall_hook(__FILE__, function() {
    Uninstaller::uninstall();
});

/**
 * Initialize the plugin
 */
add_action('plugins_loaded', function() {
    $plugin = Plugin::get_instance();
    $plugin->init();
});

/**
 * Add nonce to footer for AJAX requests (backward compatibility)
 */
add_action('wp_footer', function() {
    if (!wp_script_is('aiw-widget-script', 'enqueued')) {
        return;
    }
    ?>
    <script type="text/javascript">
        window.aiwNonce = '<?php echo wp_create_nonce('aiw_nonce'); ?>';
    </script>
    <?php
});

/**
 * Shortcode handler (backward compatibility)
 */
add_shortcode('ai_interview_widget', function($atts = [], $content = '', $tag = '') {
    // Ensure assets are loaded
    wp_enqueue_style('aiw-widget-style');
    wp_enqueue_script('aiw-widget-script');
    
    // Parse attributes
    $atts = shortcode_atts([
        'title' => __('AI Interview Assistant', 'ai-interview-widget'),
        'description' => __('Chat with my AI assistant to learn more about my experience and skills.', 'ai-interview-widget'),
        'enable_voice' => 'yes',
        'primary_color' => '#007cba',
    ], $atts, $tag);
    
    // Convert attributes to settings format for template
    $settings = [
        'widget_title' => $atts['title'],
        'widget_description' => $atts['description'],
        'enable_voice' => $atts['enable_voice'],
        'primary_color' => $atts['primary_color'],
    ];
    
    // Load template
    ob_start();
    $template_path = AIW_PLUGIN_DIR . 'templates/widget-base.php';
    if (file_exists($template_path)) {
        include $template_path;
    } else {
        echo '<div class="aiw-error">AI Interview Widget template not found.</div>';
    }
    return ob_get_clean();
});

/**
 * AJAX Handlers for backward compatibility
 * 
 * These handlers preserve the existing AJAX endpoints while delegating
 * to the new class-based system for processing.
 */

// Chat handler
add_action('wp_ajax_ai_interview_chat', 'aiw_handle_chat_ajax');
add_action('wp_ajax_nopriv_ai_interview_chat', 'aiw_handle_chat_ajax');

function aiw_handle_chat_ajax() {
    check_ajax_referer('aiw_nonce', 'nonce');
    
    $message = sanitize_text_field($_POST['message'] ?? '');
    $conversation_id = sanitize_text_field($_POST['conversation_id'] ?? '');
    
    if (empty($message)) {
        wp_send_json_error(['message' => __('Message is required.', 'ai-interview-widget')]);
    }
    
    // Placeholder response - in the full implementation, this would
    // delegate to a service class that handles the AI API calls
    $response = [
        'response' => __('Hello! This is a placeholder response. The full AI integration will be available in the complete implementation.', 'ai-interview-widget'),
        'conversation_id' => $conversation_id ?: wp_generate_uuid4(),
    ];
    
    wp_send_json_success($response);
}

// TTS handler
add_action('wp_ajax_ai_interview_tts', 'aiw_handle_tts_ajax');
add_action('wp_ajax_nopriv_ai_interview_tts', 'aiw_handle_tts_ajax');

function aiw_handle_tts_ajax() {
    check_ajax_referer('aiw_nonce', 'nonce');
    
    $text = sanitize_text_field($_POST['text'] ?? '');
    
    if (empty($text)) {
        wp_send_json_error(['message' => __('Text is required for TTS.', 'ai-interview-widget')]);
    }
    
    // Placeholder - real implementation would generate audio
    wp_send_json_success([
        'audio_url' => '',
        'message' => __('TTS functionality will be implemented in the complete version.', 'ai-interview-widget')
    ]);
}

// Test handler
add_action('wp_ajax_ai_interview_test', 'aiw_handle_test_ajax');
add_action('wp_ajax_nopriv_ai_interview_test', 'aiw_handle_test_ajax');

function aiw_handle_test_ajax() {
    check_ajax_referer('aiw_nonce', 'nonce');
    
    wp_send_json_success([
        'message' => __('AJAX connection successful!', 'ai-interview-widget'),
        'timestamp' => current_time('mysql'),
    ]);
}

/**
 * Backward compatibility class instantiation
 * 
 * For sites that might be directly accessing the old AIInterviewWidget class,
 * we provide a compatibility layer.
 */
if (!class_exists('AIInterviewWidget')) {
    class AIInterviewWidget {
        public function __construct() {
            // Deprecated notice
            if (defined('WP_DEBUG') && WP_DEBUG) {
                trigger_error(
                    'AIInterviewWidget class is deprecated. Use the new namespaced classes instead.',
                    E_USER_DEPRECATED
                );
            }
        }
        
        public function render_widget($atts = []) {
            return do_shortcode('[ai_interview_widget]');
        }
    }
    
    // Instantiate for backward compatibility
    new AIInterviewWidget();
}

/**
 * Load text domain for translations
 */
add_action('init', function() {
    load_plugin_textdomain(
        'ai-interview-widget',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages'
    );
});

/**
 * Add query vars for audio handling (backward compatibility)
 */
add_filter('query_vars', function($vars) {
    $vars[] = 'aiw_audio_file';
    return $vars;
});

/**
 * Handle custom audio file requests (backward compatibility)
 */
add_action('template_redirect', function() {
    $audio_file = get_query_var('aiw_audio_file');
    if ($audio_file) {
        // Handle audio file serving - placeholder for now
        status_header(404);
        exit;
    }
});

/**
 * Add custom mime types for audio uploads
 */
add_filter('upload_mimes', function($mimes) {
    $mimes['mp3'] = 'audio/mpeg';
    $mimes['wav'] = 'audio/wav';
    $mimes['ogg'] = 'audio/ogg';
    return $mimes;
});

/**
 * Add plugin action links
 */
add_filter('plugin_action_links_' . plugin_basename(__FILE__), function($links) {
    $settings_link = '<a href="' . admin_url('admin.php?page=ai-interview-widget') . '">' . __('Settings', 'ai-interview-widget') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
});

/**
 * Plugin loaded - everything initialized
 */
do_action('aiw_plugin_loaded', Plugin::get_instance());