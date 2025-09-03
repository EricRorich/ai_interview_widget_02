<?php
/**
 * Upgrade Service Provider
 * 
 * Handles plugin version management and migration execution.
 * 
 * @package EricRorich\AIInterviewWidget
 * @since 2.0.0
 */

namespace EricRorich\AIInterviewWidget\Setup;

use EricRorich\AIInterviewWidget\Core\Container;
use EricRorich\AIInterviewWidget\Core\Contracts\ServiceProviderInterface;
use EricRorich\AIInterviewWidget\Setup\Upgrade\VersionManager;
use EricRorich\AIInterviewWidget\Setup\Migrations\Migration_200;
use EricRorich\AIInterviewWidget\Setup\Migrations\Migration_210;
use EricRorich\AIInterviewWidget\Setup\Migrations\Migration_020;

/**
 * Upgrade Service Provider
 * 
 * Manages plugin upgrades and migrations.
 * 
 * @since 2.0.0
 */
class UpgradeServiceProvider implements ServiceProviderInterface {

    /**
     * Register services with the container
     * 
     * @param Container $container The service container
     * @return void
     */
    public function register(Container $container) {
        $container->singleton('version_manager', function($container) {
            return new VersionManager(AIW_VERSION);
        });

        $container->bind('migrations', function($container) {
            return [
                new Migration_200(),
                new Migration_210(),
                new Migration_020(),
            ];
        });
    }

    /**
     * Boot services after all providers are registered
     * 
     * @param Container $container The service container
     * @return void
     */
    public function boot(Container $container) {
        // Run migrations on admin_init to ensure WordPress is fully loaded
        add_action('admin_init', function() use ($container) {
            $this->run_migrations($container);
        });
    }

    /**
     * Run pending migrations
     * 
     * @param Container $container The service container
     * @return void
     */
    private function run_migrations(Container $container) {
        $version_manager = $container->make('version_manager');
        $migrations = $container->make('migrations');

        if ($version_manager->needsUpgrade()) {
            $success = $version_manager->runPendingMigrations($migrations);
            
            if (!$success) {
                // Show admin notice about failed migration
                add_action('admin_notices', function() {
                    echo '<div class="notice notice-error">';
                    echo '<p><strong>' . esc_html__('AI Interview Widget', 'ai-interview-widget') . '</strong>: ';
                    echo esc_html__('Plugin upgrade failed. Please check error logs for details.', 'ai-interview-widget');
                    echo '</p></div>';
                });
            }
        }
    }
}