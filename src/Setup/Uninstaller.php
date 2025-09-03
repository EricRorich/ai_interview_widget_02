<?php
/**
 * Plugin Uninstaller
 * 
 * Handles complete plugin removal and cleanup.
 * 
 * @package EricRorich\AIInterviewWidget
 * @since 2.0.0
 */

namespace EricRorich\AIInterviewWidget\Setup;

/**
 * Plugin uninstall handler
 * 
 * Removes all plugin data when uninstalled.
 * 
 * @since 2.0.0
 */
class Uninstaller {

    /**
     * Plugin uninstall handler
     * 
     * @return void
     */
    public static function uninstall() {
        // Check if uninstall is authorized
        if (!defined('WP_UNINSTALL_PLUGIN')) {
            return;
        }

        // Remove plugin options
        self::remove_options();

        // Remove user meta
        self::remove_user_meta();

        // Drop custom tables (if any)
        self::drop_tables();

        // Clean up uploads
        self::cleanup_uploads();

        // Clear any remaining transients
        self::cleanup_transients();
    }

    /**
     * Remove all plugin options
     * 
     * @return void
     */
    private static function remove_options() {
        $options_to_remove = [
            'aiw_settings',
            'aiw_customizer_settings',
            'aiw_design_presets',
            'aiw_plugin_version',
            'aiw_plugin_activated',
            'aiw_plugin_deactivated',
            'aiw_model_cache',
            'aiw_provider_cache',
        ];

        foreach ($options_to_remove as $option) {
            delete_option($option);
            delete_site_option($option); // For multisite
        }
    }

    /**
     * Remove user meta data
     * 
     * @return void
     */
    private static function remove_user_meta() {
        global $wpdb;

        // Remove plugin-specific user meta
        $wpdb->query(
            "DELETE FROM {$wpdb->usermeta} 
             WHERE meta_key LIKE 'aiw_%'"
        );
    }

    /**
     * Drop custom database tables
     * 
     * @return void
     */
    private static function drop_tables() {
        global $wpdb;

        // Currently no custom tables, but prepared for future use
        $tables_to_drop = [
            // $wpdb->prefix . 'aiw_chat_logs',
        ];

        foreach ($tables_to_drop as $table) {
            $wpdb->query("DROP TABLE IF EXISTS {$table}");
        }
    }

    /**
     * Clean up uploaded files
     * 
     * @return void
     */
    private static function cleanup_uploads() {
        $upload_dir = wp_upload_dir();
        $aiw_dirs = [
            $upload_dir['basedir'] . '/aiw-audio',
            $upload_dir['basedir'] . '/aiw-temp',
        ];

        foreach ($aiw_dirs as $dir) {
            if (is_dir($dir)) {
                self::remove_directory_recursive($dir);
            }
        }
    }

    /**
     * Clean up transients
     * 
     * @return void
     */
    private static function cleanup_transients() {
        global $wpdb;

        $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_aiw_%' 
             OR option_name LIKE '_transient_timeout_aiw_%'"
        );
    }

    /**
     * Recursively remove directory and its contents
     * 
     * @param string $dir Directory path
     * @return bool
     */
    private static function remove_directory_recursive($dir) {
        if (!is_dir($dir)) {
            return false;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? self::remove_directory_recursive($path) : wp_delete_file($path);
        }

        return rmdir($dir);
    }
}