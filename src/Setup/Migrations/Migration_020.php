<?php
/**
 * Migration 0.2.0 - Plugin Architecture Finalization
 * 
 * Finalizes the plugin architecture and ensures proper versioning.
 * 
 * @package EricRorich\AIInterviewWidget
 * @since 0.2.0
 */

namespace EricRorich\AIInterviewWidget\Setup\Migrations;

use EricRorich\AIInterviewWidget\Setup\Contracts\MigrationInterface;

/**
 * Migration 020 class (version 0.2.0)
 * 
 * Migration for finalizing plugin architecture with proper service providers,
 * asset management, and i18n support.
 * 
 * @since 0.2.0
 */
class Migration_020 implements MigrationInterface {

    /**
     * Get the target version for this migration
     * 
     * @return string Version string
     */
    public function targetVersion(): string {
        return '0.2.0';
    }

    /**
     * Run the migration
     * 
     * @return bool True on success, false on failure
     */
    public function run(): bool {
        try {
            // Update plugin version option
            update_option('ai_interview_widget_version', '0.2.0');
            
            // Set up finalized default options
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
                    'elementor_enabled' => true,
                    'i18n_domain' => 'ai-interview-widget',
                ],
                'ai_interview_widget_advanced' => [
                    'debug_mode' => false,
                    'log_conversations' => false,
                    'rate_limiting' => true,
                    'rate_limit_requests' => 10,
                    'rate_limit_window' => 60,
                    'asset_manifest_enabled' => true,
                    'service_provider_extensibility' => true,
                ],
                'ai_interview_widget_build' => [
                    'assets_version' => time(), // Cache busting for non-built assets
                    'manifest_last_checked' => 0,
                    'build_environment' => 'production',
                ],
            ];

            foreach ($default_options as $option_name => $option_value) {
                // Merge with existing options to preserve user settings
                $existing_option = get_option($option_name, []);
                if (is_array($existing_option)) {
                    $merged_option = array_merge($option_value, $existing_option);
                    update_option($option_name, $merged_option);
                } else {
                    // First time install
                    add_option($option_name, $option_value);
                }
            }

            // Clean up any legacy options from older versions
            $this->cleanup_legacy_options();

            // Ensure conversation table is up to date
            $this->ensure_conversation_table();

            // Clear manifest cache to force reload
            $this->clear_manifest_cache();

            // Set migration completion flag
            $migration_history = get_option('ai_interview_widget_migrations', []);
            if (!in_array('0.2.0', $migration_history)) {
                $migration_history[] = '0.2.0';
                update_option('ai_interview_widget_migrations', $migration_history);
            }

            return true;

        } catch (\Exception $e) {
            error_log('AI Interview Widget Migration 0.2.0 failed: ' . $e->getMessage());
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
            // Remove version option
            delete_option('ai_interview_widget_version');
            
            // Remove migration-specific options
            delete_option('ai_interview_widget_build');

            // Remove from migration history
            $migration_history = get_option('ai_interview_widget_migrations', []);
            $migration_history = array_diff($migration_history, ['0.2.0']);
            update_option('ai_interview_widget_migrations', $migration_history);

            return true;

        } catch (\Exception $e) {
            error_log('AI Interview Widget Migration 0.2.0 rollback failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get migration description
     * 
     * @return string Migration description
     */
    public function getDescription(): string {
        return __('Finalize plugin architecture with service providers, asset management, and i18n support', 'ai-interview-widget');
    }

    /**
     * Clean up legacy options from older versions
     * 
     * @return void
     */
    private function cleanup_legacy_options() {
        // Remove any deprecated options from pre-0.2.0 versions
        $legacy_options = [
            'aiw_legacy_mode',
            'aiw_old_cache_system',
            'ai_interview_widget_beta_features',
        ];

        foreach ($legacy_options as $option) {
            delete_option($option);
        }
    }

    /**
     * Ensure conversation logging table is current
     * 
     * @return void
     */
    private function ensure_conversation_table() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'aiw_conversations';
        
        // Check if table exists
        $table_exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = %s AND table_name = %s",
                DB_NAME,
                $table_name
            )
        );

        if (!$table_exists) {
            $this->create_conversation_table();
        }
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
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY conversation_id (conversation_id),
            KEY created_at (created_at)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    /**
     * Clear manifest and asset cache
     * 
     * @return void
     */
    private function clear_manifest_cache() {
        // Clear any manifest-related transients
        global $wpdb;
        
        $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_aiw_manifest_%' 
             OR option_name LIKE '_transient_timeout_aiw_manifest_%'"
        );

        // Clear general plugin cache
        if (function_exists('wp_cache_delete')) {
            wp_cache_delete('ai_interview_widget_manifest', 'aiw');
            wp_cache_delete('ai_interview_widget_assets', 'aiw');
        }
    }
}