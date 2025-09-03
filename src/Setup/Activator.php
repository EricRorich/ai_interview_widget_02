<?php
/**
 * Plugin Activator
 * 
 * Handles plugin activation tasks.
 * 
 * @package EricRorich\AIInterviewWidget
 * @since 2.0.0
 */

namespace EricRorich\AIInterviewWidget\Setup;

/**
 * Plugin activation handler
 * 
 * Performs necessary setup tasks when the plugin is activated.
 * 
 * @since 2.0.0
 */
class Activator {

    /**
     * Plugin activation handler
     * 
     * @return void
     */
    public static function activate() {
        // Check requirements before activation
        $requirements = new Requirements();
        $check_result = $requirements->check();
        
        if (is_wp_error($check_result)) {
            wp_die(
                esc_html($check_result->get_error_message()),
                'Plugin Activation Error',
                ['back_link' => true]
            );
        }

        // Create default options if they don't exist
        self::create_default_options();

        // Add rewrite rules for audio files (if needed)
        self::add_rewrite_rules();

        // Flush rewrite rules
        flush_rewrite_rules();

        // Set activation flag
        update_option('aiw_plugin_activated', true);
        update_option('aiw_plugin_version', AIW_VERSION);
    }

    /**
     * Create default plugin options
     * 
     * @return void
     */
    private static function create_default_options() {
        $default_options = [
            'aiw_settings' => [
                'api_provider' => 'openai',
                'api_key' => '',
                'model' => 'gpt-4o-mini',
                'primary_color' => '#007cba',
                'background_color' => '#ffffff',
                'text_color' => '#000000',
                'enable_voice' => true,
                'enable_debug' => false,
            ],
            'aiw_customizer_settings' => [],
            'aiw_design_presets' => []
        ];

        foreach ($default_options as $option_name => $default_value) {
            if (false === get_option($option_name)) {
                add_option($option_name, $default_value);
            }
        }
    }

    /**
     * Add custom rewrite rules if needed
     * 
     * @return void
     */
    private static function add_rewrite_rules() {
        // Add custom rewrite rules for audio file handling if needed
        // This preserves functionality from the original plugin
        add_rewrite_rule(
            '^aiw-audio/([^/]+)/?$',
            'index.php?aiw_audio_file=$matches[1]',
            'top'
        );
    }

    /**
     * Create necessary database tables (if any)
     * 
     * @return void
     */
    private static function create_tables() {
        global $wpdb;

        // Currently no custom tables needed
        // This method is here for future extensibility
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Example table creation (commented out):
        /*
        $table_name = $wpdb->prefix . 'aiw_chat_logs';
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) DEFAULT NULL,
            conversation_id varchar(100) NOT NULL,
            message text NOT NULL,
            response text NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY conversation_id (conversation_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        */
    }
}