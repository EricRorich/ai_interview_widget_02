<?php
/**
 * Admin Service Provider
 * 
 * Handles admin functionality and asset enqueuing.
 * 
 * @package EricRorich\AIInterviewWidget
 * @since 2.0.0
 */

namespace EricRorich\AIInterviewWidget\Admin;

use EricRorich\AIInterviewWidget\Core\Container;
use EricRorich\AIInterviewWidget\Core\Contracts\ServiceProviderInterface;

/**
 * Admin Service Provider
 * 
 * Manages admin assets, settings, and functionality.
 * 
 * @since 2.0.0
 */
class AdminServiceProvider implements ServiceProviderInterface {

    /**
     * Register services with the container
     * 
     * @param Container $container The service container
     * @return void
     */
    public function register(Container $container) {
        // Register admin services
        $container->singleton('admin.service', function($container) {
            return new AdminService();
        });

        $container->singleton('admin.assets', function($container) {
            return new AdminAssets($container->make('assets'));
        });

        $container->singleton('admin.settings', function($container) {
            return new SettingsManager();
        });
    }

    /**
     * Boot services after all providers are registered
     * 
     * @param Container $container The service container
     * @return void
     */
    public function boot(Container $container) {
        if (is_admin()) {
            // Initialize admin service (for backward compatibility)
            $admin_service = $container->make('admin.service');
            if (method_exists($admin_service, 'init')) {
                $admin_service->init();
            }

            // Initialize admin assets
            $admin_assets = $container->make('admin.assets');
            add_action('admin_enqueue_scripts', [$admin_assets, 'enqueue_assets']);

            // Initialize settings
            $settings = $container->make('admin.settings');
            add_action('admin_init', [$settings, 'init']);
            add_action('admin_menu', [$settings, 'add_admin_menu']);
        }
    }
}

/**
 * Admin Assets class
 * 
 * Manages admin asset enqueuing with manifest support.
 * 
 * @since 2.0.0
 */
class AdminAssets {

    /**
     * Core assets manager
     * 
     * @var \EricRorich\AIInterviewWidget\Core\Assets
     */
    private $assets;

    /**
     * Constructor
     * 
     * @param \EricRorich\AIInterviewWidget\Core\Assets $assets Core assets manager
     */
    public function __construct($assets) {
        $this->assets = $assets;
    }

    /**
     * Enqueue admin assets
     * 
     * @param string $hook Current admin page hook
     * @return void
     */
    public function enqueue_assets($hook) {
        if (!$this->should_load_admin_assets($hook)) {
            return;
        }

        // Get manifest-based asset URLs
        $admin_css = $this->assets->get_manifest_asset_url('admin.css');
        $admin_js = $this->assets->get_manifest_asset_url('admin.js');

        // Enqueue CSS
        wp_enqueue_style(
            'aiw-admin',
            $admin_css,
            [],
            $this->assets->get_asset_version('admin.css')
        );

        // Enqueue JavaScript
        wp_enqueue_script(
            'aiw-admin',
            $admin_js,
            ['jquery', 'wp-color-picker'],
            $this->assets->get_asset_version('admin.js'),
            true
        );

        // Localize script with admin data
        wp_localize_script('aiw-admin', 'aiwAdmin', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aiw_admin_nonce'),
            'i18n' => [
                'confirmReset' => __('Are you sure you want to reset this setting?', 'ai-interview-widget'),
                'confirmDelete' => __('Are you sure you want to delete this preset?', 'ai-interview-widget'),
                'saving' => __('Saving...', 'ai-interview-widget'),
                'saved' => __('Settings saved successfully!', 'ai-interview-widget'),
                'error' => __('An error occurred while saving.', 'ai-interview-widget'),
            ]
        ]);

        // Enqueue WordPress color picker
        wp_enqueue_style('wp-color-picker');
    }

    /**
     * Determine if admin assets should be loaded on current page
     * 
     * @param string $hook Current admin page hook
     * @return bool
     */
    private function should_load_admin_assets($hook) {
        // Always load on our admin pages
        $our_pages = [
            'toplevel_page_ai-interview-widget',
            'ai-interview-widget_page_ai-interview-settings',
            'ai-interview-widget_page_ai-interview-customizer'
        ];

        if (in_array($hook, $our_pages)) {
            return true;
        }

        // Load on customizer
        if (is_customize_preview()) {
            return true;
        }

        // Load on post edit pages (for Elementor)
        if (in_array($hook, ['post.php', 'post-new.php'])) {
            return true;
        }

        return false;
    }
}

/**
 * Settings Manager class
 * 
 * Handles admin settings and options management.
 * 
 * @since 2.0.0
 */
class SettingsManager {

    /**
     * Initialize settings
     * 
     * @return void
     */
    public function init() {
        // Register settings
        register_setting('ai_interview_widget_settings', 'ai_interview_widget_settings', [
            'sanitize_callback' => [$this, 'sanitize_settings']
        ]);

        register_setting('ai_interview_widget_advanced', 'ai_interview_widget_advanced', [
            'sanitize_callback' => [$this, 'sanitize_advanced_settings']
        ]);
    }

    /**
     * Add admin menu
     * 
     * @return void
     */
    public function add_admin_menu() {
        add_menu_page(
            __('AI Interview Widget', 'ai-interview-widget'),
            __('AI Interview', 'ai-interview-widget'),
            'manage_options',
            'ai-interview-widget',
            [$this, 'render_main_page'],
            'dashicons-format-chat',
            30
        );

        add_submenu_page(
            'ai-interview-widget',
            __('Settings', 'ai-interview-widget'),
            __('Settings', 'ai-interview-widget'),
            'manage_options',
            'ai-interview-settings',
            [$this, 'render_settings_page']
        );
    }

    /**
     * Render main admin page
     * 
     * @return void
     */
    public function render_main_page() {
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('AI Interview Widget', 'ai-interview-widget') . '</h1>';
        echo '<p>' . esc_html__('Configure your AI interview widget settings below.', 'ai-interview-widget') . '</p>';
        echo '</div>';
    }

    /**
     * Render settings page
     * 
     * @return void
     */
    public function render_settings_page() {
        echo '<div class="wrap aiw-admin-container">';
        echo '<h1>' . esc_html__('AI Interview Widget Settings', 'ai-interview-widget') . '</h1>';
        echo '<form method="post" action="options.php" class="aiw-admin-form">';
        
        settings_fields('ai_interview_widget_settings');
        do_settings_sections('ai_interview_widget_settings');
        
        echo '<div class="aiw-button-group">';
        submit_button(__('Save Settings', 'ai-interview-widget'), 'primary', 'submit', false, ['class' => 'aiw-button']);
        echo '</div>';
        
        echo '</form>';
        echo '</div>';
    }

    /**
     * Sanitize main settings
     * 
     * @param array $input Raw input values
     * @return array Sanitized values
     */
    public function sanitize_settings($input) {
        $sanitized = [];

        if (isset($input['api_provider'])) {
            $sanitized['api_provider'] = sanitize_text_field($input['api_provider']);
        }

        if (isset($input['enable_voice'])) {
            $sanitized['enable_voice'] = (bool) $input['enable_voice'];
        }

        if (isset($input['primary_color'])) {
            $sanitized['primary_color'] = sanitize_hex_color($input['primary_color']);
        }

        return $sanitized;
    }

    /**
     * Sanitize advanced settings
     * 
     * @param array $input Raw input values
     * @return array Sanitized values
     */
    public function sanitize_advanced_settings($input) {
        $sanitized = [];

        if (isset($input['debug_mode'])) {
            $sanitized['debug_mode'] = (bool) $input['debug_mode'];
        }

        if (isset($input['log_conversations'])) {
            $sanitized['log_conversations'] = (bool) $input['log_conversations'];
        }

        if (isset($input['rate_limit_requests'])) {
            $sanitized['rate_limit_requests'] = absint($input['rate_limit_requests']);
        }

        return $sanitized;
    }
}