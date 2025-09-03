<?php
/**
 * Elementor Service Provider
 * 
 * Handles Elementor widget registration and integration.
 * 
 * @package EricRorich\AIInterviewWidget
 * @since 2.0.0
 */

namespace EricRorich\AIInterviewWidget\Integrations\Elementor;

use EricRorich\AIInterviewWidget\Core\Container;
use EricRorich\AIInterviewWidget\Core\Contracts\ServiceProviderInterface;

/**
 * Elementor Service Provider
 * 
 * Manages Elementor widget registration and assets.
 * 
 * @since 2.0.0
 */
class ElementorServiceProvider implements ServiceProviderInterface {

    /**
     * Register services with the container
     * 
     * @param Container $container The service container
     * @return void
     */
    public function register(Container $container) {
        // Only register if Elementor is available
        if (!$this->is_elementor_available()) {
            return;
        }

        $container->singleton('elementor.widget_manager', function($container) {
            return new WidgetManager();
        });

        $container->singleton('elementor.assets', function($container) {
            return new ElementorAssets($container->make('assets'));
        });
    }

    /**
     * Boot services after all providers are registered
     * 
     * @param Container $container The service container
     * @return void
     */
    public function boot(Container $container) {
        if (!$this->is_elementor_available()) {
            // Show admin notice if Elementor is not active
            if (is_admin()) {
                add_action('admin_notices', [$this, 'elementor_missing_notice']);
            }
            return;
        }

        // Register widgets
        add_action('elementor/widgets/widgets_registered', function($widgets_manager) use ($container) {
            $widget_manager = $container->make('elementor.widget_manager');
            $widget_manager->register_widgets($widgets_manager);
        });

        // Add widget categories
        add_action('elementor/elements/categories_registered', function($elements_manager) use ($container) {
            $widget_manager = $container->make('elementor.widget_manager');
            $widget_manager->add_widget_categories($elements_manager);
        });

        // Enqueue Elementor-specific assets
        add_action('elementor/frontend/after_enqueue_styles', function() use ($container) {
            $assets = $container->make('elementor.assets');
            $assets->enqueue_elementor_assets();
        });
    }

    /**
     * Check if Elementor is available
     * 
     * @return bool
     */
    private function is_elementor_available(): bool {
        return did_action('elementor/loaded') || class_exists('\Elementor\Plugin');
    }

    /**
     * Show admin notice when Elementor is missing
     * 
     * @return void
     */
    public function elementor_missing_notice() {
        if (current_user_can('activate_plugins')) {
            echo '<div class="notice notice-warning is-dismissible">';
            echo '<p><strong>' . esc_html__('AI Interview Widget', 'ai-interview-widget') . '</strong>: ';
            echo esc_html__('Elementor is not installed or activated. Some features may not be available.', 'ai-interview-widget');
            echo '</p></div>';
        }
    }
}