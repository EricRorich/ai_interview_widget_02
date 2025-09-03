<?php
/**
 * Migration 2.0.0 - Initial Setup
 * 
 * Sets up initial plugin options and data structure.
 * 
 * @package EricRorich\AIInterviewWidget
 * @since 2.0.0
 */

namespace EricRorich\AIInterviewWidget\Setup\Migrations;

use EricRorich\AIInterviewWidget\Setup\Contracts\MigrationInterface;

/**
 * Migration 200 class (version 2.0.0)
 * 
 * Initial migration for the advanced architecture refactor.
 * 
 * @since 2.0.0
 */
class Migration_200 implements MigrationInterface {

    /**
     * Get the target version for this migration
     * 
     * @return string Version string
     */
    public function targetVersion(): string {
        return '2.0.0';
    }

    /**
     * Run the migration
     * 
     * @return bool True on success, false on failure
     */
    public function run(): bool {
        try {
            // Set up default plugin options
            $default_options = [
                'ai_interview_widget_settings' => [
                    'api_provider' => 'openai',
                    'enable_voice' => true,
                    'enable_typing_indicator' => true,
                    'max_conversation_length' => 50,
                    'primary_color' => '#007cba',
                    'secondary_color' => '#f1f1f1',
                    'animation_style' => 'fadeIn',
                    'cache_enabled' => true,
                    'cache_duration' => 3600,
                ],
                'ai_interview_widget_advanced' => [
                    'debug_mode' => false,
                    'log_conversations' => false,
                    'rate_limiting' => true,
                    'rate_limit_requests' => 10,
                    'rate_limit_window' => 60,
                ],
            ];

            foreach ($default_options as $option_name => $option_value) {
                if (!get_option($option_name)) {
                    add_option($option_name, $option_value);
                }
            }

            // Create conversation log table if logging enabled
            $this->create_conversation_table();

            // Clear any existing cache
            $this->clear_legacy_cache();

            return true;

        } catch (\Exception $e) {
            error_log('AI Interview Widget Migration 2.0.0 failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Rollback the migration
     * 
     * @return bool True on success, false on failure
     */
    public function rollback(): bool {
        try {
            // Remove options added by this migration
            delete_option('ai_interview_widget_settings');
            delete_option('ai_interview_widget_advanced');

            // Drop conversation table
            global $wpdb;
            $table_name = $wpdb->prefix . 'aiw_conversations';
            $wpdb->query("DROP TABLE IF EXISTS {$table_name}");

            return true;

        } catch (\Exception $e) {
            error_log('AI Interview Widget Migration 2.0.0 rollback failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get migration description
     * 
     * @return string Migration description
     */
    public function getDescription(): string {
        return 'Initial setup for advanced architecture - default options and conversation logging';
    }

    /**
     * Create conversation logging table
     * 
     * @return void
     */
    private function create_conversation_table() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'aiw_conversations';
        
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table_name} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            conversation_id varchar(255) NOT NULL,
            user_message text NOT NULL,
            ai_response text NOT NULL,
            user_ip varchar(45) DEFAULT NULL,
            user_agent text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY conversation_id (conversation_id),
            KEY created_at (created_at)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    /**
     * Clear legacy cache entries
     * 
     * @return void
     */
    private function clear_legacy_cache() {
        // Clear any legacy transients
        global $wpdb;
        
        $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_aiw_%' 
             OR option_name LIKE '_transient_timeout_aiw_%'"
        );

        // Clear object cache if available
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }
    }
}