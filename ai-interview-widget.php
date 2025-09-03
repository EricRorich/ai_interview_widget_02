<?php
/**
 * Plugin Name: AI Interview Widget
 * Description: Interactive AI widget for Eric Rorich's portfolio with voice capabilities and WordPress/Elementor integration. Refactored for modern WordPress development standards.
 * Version: 0.2.0
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
define('AIW_VERSION', '0.2.0');
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
    
    // Fire action after bootstrap for extensions
    do_action('ai_interview_widget_bootstrapped', $plugin);
});

/**
 * Utility function for backward compatibility
 * 
 * Returns the main plugin instance for external access.
 * 
 * @return Plugin
 */
function ai_interview_widget() {
    return Plugin::get_instance();
}

/**
 * Add plugin action links
 */
add_filter('plugin_action_links_' . plugin_basename(__FILE__), function($links) {
    $settings_link = '<a href="' . admin_url('admin.php?page=ai-interview-widget') . '">' . __('Settings', 'ai-interview-widget') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
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