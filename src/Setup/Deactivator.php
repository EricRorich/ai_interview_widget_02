<?php
/**
 * Plugin Deactivator
 * 
 * Handles plugin deactivation tasks.
 * 
 * @package EricRorich\AIInterviewWidget
 * @since 2.0.0
 */

namespace EricRorich\AIInterviewWidget\Setup;

/**
 * Plugin deactivation handler
 * 
 * Performs cleanup tasks when the plugin is deactivated.
 * 
 * @since 2.0.0
 */
class Deactivator {

    /**
     * Plugin deactivation handler
     * 
     * @return void
     */
    public static function deactivate() {
        // Clear scheduled events
        self::clear_scheduled_events();

        // Flush rewrite rules
        flush_rewrite_rules();

        // Clean up transients
        self::cleanup_transients();

        // Set deactivation flag
        update_option('aiw_plugin_deactivated', true);
        delete_option('aiw_plugin_activated');
    }

    /**
     * Clear any scheduled cron events
     * 
     * @return void
     */
    private static function clear_scheduled_events() {
        // Clear any plugin-specific cron jobs
        $scheduled_events = [
            'aiw_cleanup_temp_files',
            'aiw_model_cache_refresh',
        ];

        foreach ($scheduled_events as $event) {
            wp_clear_scheduled_hook($event);
        }
    }

    /**
     * Clean up plugin transients
     * 
     * @return void
     */
    private static function cleanup_transients() {
        global $wpdb;

        // Delete plugin-specific transients
        $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_aiw_%' 
             OR option_name LIKE '_transient_timeout_aiw_%'"
        );
    }

    /**
     * Clean up temporary files (optional)
     * 
     * @return void
     */
    private static function cleanup_temp_files() {
        $upload_dir = wp_upload_dir();
        $aiw_temp_dir = $upload_dir['basedir'] . '/aiw-temp';

        if (is_dir($aiw_temp_dir)) {
            $files = glob($aiw_temp_dir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    wp_delete_file($file);
                }
            }
        }
    }
}