<?php
/**
 * Frontend Service Provider
 * 
 * Handles public-facing functionality and asset enqueuing.
 * 
 * @package EricRorich\AIInterviewWidget
 * @since 2.0.0
 */

namespace EricRorich\AIInterviewWidget\Frontend;

use EricRorich\AIInterviewWidget\Core\Container;
use EricRorich\AIInterviewWidget\Core\Contracts\ServiceProviderInterface;

/**
 * Frontend Service Provider
 * 
 * Manages frontend assets, shortcodes, and public functionality.
 * 
 * @since 2.0.0
 */
class FrontendServiceProvider implements ServiceProviderInterface {

    /**
     * Register services with the container
     * 
     * @param Container $container The service container
     * @return void
     */
    public function register(Container $container) {
        // Register frontend services
        $container->singleton('frontend.assets', function($container) {
            return new FrontendAssets($container->make('assets'));
        });

        $container->singleton('frontend.shortcodes', function($container) {
            return new ShortcodeHandler();
        });
    }

    /**
     * Boot services after all providers are registered
     * 
     * @param Container $container The service container
     * @return void
     */
    public function boot(Container $container) {
        if (!is_admin()) {
            // Initialize frontend assets
            $assets = $container->make('frontend.assets');
            add_action('wp_enqueue_scripts', [$assets, 'enqueue_assets']);

            // Initialize shortcodes
            $shortcodes = $container->make('frontend.shortcodes');
            add_action('init', [$shortcodes, 'register_shortcodes']);
        }
    }
}