<?php
/**
 * Migration 2.1.0 - Enhanced Features
 * 
 * Adds support for enhanced features and new settings.
 * 
 * @package EricRorich\AIInterviewWidget
 * @since 2.0.0
 */

namespace EricRorich\AIInterviewWidget\Setup\Migrations;

use EricRorich\AIInterviewWidget\Setup\Contracts\MigrationInterface;

/**
 * Migration 210 class (version 2.1.0)
 * 
 * Example migration for future enhancements.
 * 
 * @since 2.0.0
 */
class Migration_210 implements MigrationInterface {

    /**
     * Get the target version for this migration
     * 
     * @return string Version string
     */
    public function targetVersion(): string {
        return '2.1.0';
    }

    /**
     * Run the migration
     * 
     * @return bool True on success, false on failure
     */
    public function run(): bool {
        try {
            // Add new settings for enhanced features
            $enhanced_settings = get_option('ai_interview_widget_settings', []);
            
            // Add new options if they don't exist
            $new_options = [
                'enable_analytics' => false,
                'analytics_provider' => 'none',
                'conversation_export' => false,
                'multi_language_support' => true,
                'auto_language_detection' => true,
                'default_language' => 'en',
            ];

            foreach ($new_options as $key => $value) {
                if (!array_key_exists($key, $enhanced_settings)) {
                    $enhanced_settings[$key] = $value;
                }
            }

            update_option('ai_interview_widget_settings', $enhanced_settings);

            // Create analytics table if analytics enabled
            $this->create_analytics_table();

            // Update conversation table with new columns
            $this->update_conversation_table();

            return true;

        } catch (\Exception $e) {
            error_log('AI Interview Widget Migration 2.1.0 failed: ' . $e->getMessage());
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
            // Remove added settings
            $settings = get_option('ai_interview_widget_settings', []);
            $keys_to_remove = [
                'enable_analytics',
                'analytics_provider', 
                'conversation_export',
                'multi_language_support',
                'auto_language_detection',
                'default_language'
            ];

            foreach ($keys_to_remove as $key) {
                unset($settings[$key]);
            }

            update_option('ai_interview_widget_settings', $settings);

            // Drop analytics table
            global $wpdb;
            $table_name = $wpdb->prefix . 'aiw_analytics';
            $wpdb->query("DROP TABLE IF EXISTS {$table_name}");

            return true;

        } catch (\Exception $e) {
            error_log('AI Interview Widget Migration 2.1.0 rollback failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get migration description
     * 
     * @return string Migration description
     */
    public function getDescription(): string {
        return 'Enhanced features - analytics support, multi-language, conversation export';
    }

    /**
     * Create analytics table
     * 
     * @return void
     */
    private function create_analytics_table() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'aiw_analytics';
        
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table_name} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            event_type varchar(50) NOT NULL,
            event_data longtext DEFAULT NULL,
            user_id bigint(20) unsigned DEFAULT NULL,
            session_id varchar(255) DEFAULT NULL,
            page_url text DEFAULT NULL,
            user_agent text DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY event_type (event_type),
            KEY user_id (user_id),
            KEY session_id (session_id),
            KEY created_at (created_at)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    /**
     * Update conversation table with new columns
     * 
     * @return void
     */
    private function update_conversation_table() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'aiw_conversations';
        
        // Check if columns already exist
        $columns = $wpdb->get_results("DESCRIBE {$table_name}");
        $existing_columns = array_column($columns, 'Field');

        // Add language column if it doesn't exist
        if (!in_array('language', $existing_columns)) {
            $wpdb->query("ALTER TABLE {$table_name} ADD COLUMN language varchar(10) DEFAULT 'en' AFTER ai_response");
        }

        // Add sentiment column if it doesn't exist  
        if (!in_array('sentiment', $existing_columns)) {
            $wpdb->query("ALTER TABLE {$table_name} ADD COLUMN sentiment varchar(20) DEFAULT NULL AFTER language");
        }

        // Add rating column if it doesn't exist
        if (!in_array('rating', $existing_columns)) {
            $wpdb->query("ALTER TABLE {$table_name} ADD COLUMN rating tinyint(1) DEFAULT NULL AFTER sentiment");
        }
    }
}