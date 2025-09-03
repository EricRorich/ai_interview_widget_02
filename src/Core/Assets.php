<?php
/**
 * Assets Management Class
 * 
 * Handles enqueuing of CSS and JavaScript files for both admin and public areas.
 * 
 * @package EricRorich\AIInterviewWidget
 * @since 2.0.0
 */

namespace EricRorich\AIInterviewWidget\Core;

/**
 * Assets management class
 * 
 * Coordinates asset loading with proper versioning and dependencies.
 * 
 * @since 2.0.0
 */
class Assets {

    /**
     * Plugin version for cache busting
     * 
     * @var string
     */
    private $version;

    /**
     * Plugin URL for asset paths
     * 
     * @var string
     */
    private $plugin_url;

    /**
     * Plugin directory for file checks
     * 
     * @var string
     */
    private $plugin_dir;

    /**
     * Constructor
     */
    public function __construct() {
        $this->version = AIW_VERSION;
        $this->plugin_url = AIW_PLUGIN_URL;
        $this->plugin_dir = AIW_PLUGIN_DIR;
    }

    /**
     * Initialize asset hooks
     * 
     * @return void
     */
    public function init() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_public_assets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    /**
     * Enqueue public (frontend) assets
     * 
     * @return void
     */
    public function enqueue_public_assets() {
        // Main widget CSS
        wp_enqueue_style(
            'aiw-widget-style',
            $this->get_asset_url('public/css/ai-interview-widget.css'),
            [],
            $this->get_asset_version('public/css/ai-interview-widget.css')
        );

        // Main widget JS
        wp_enqueue_script(
            'aiw-widget-script',
            $this->get_asset_url('public/js/ai-interview-widget.js'),
            ['jquery'],
            $this->get_asset_version('public/js/ai-interview-widget.js'),
            true
        );

        // Geolocation helper JS
        wp_enqueue_script(
            'aiw-geo-script',
            $this->get_asset_url('public/js/aiw-geo.js'),
            ['aiw-widget-script'],
            $this->get_asset_version('public/js/aiw-geo.js'),
            true
        );

        // Localize script for AJAX
        wp_localize_script('aiw-widget-script', 'aiwAjax', [
            'url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aiw_nonce'),
            'strings' => [
                'error' => __('An error occurred. Please try again.', 'ai-interview-widget'),
                'loading' => __('Loading...', 'ai-interview-widget'),
            ]
        ]);
    }

    /**
     * Enqueue admin assets
     * 
     * @param string $hook Current admin page hook
     * @return void
     */
    public function enqueue_admin_assets($hook) {
        // Only load on our admin pages and customizer
        if (!$this->should_load_admin_assets($hook)) {
            return;
        }

        // Admin styles
        wp_enqueue_style(
            'aiw-admin-style',
            $this->get_asset_url('admin/css/admin-styles.css'),
            ['wp-color-picker'],
            $this->get_asset_version('admin/css/admin-styles.css')
        );

        // Admin enhancements JS
        wp_enqueue_script(
            'aiw-admin-enhancements',
            $this->get_asset_url('admin/js/admin-enhancements.js'),
            ['jquery', 'wp-color-picker'],
            $this->get_asset_version('admin/js/admin-enhancements.js'),
            true
        );

        // Live preview JS (for customizer)
        if (is_customize_preview() || $hook === 'appearance_page_ai-interview-customizer') {
            wp_enqueue_script(
                'aiw-live-preview',
                $this->get_asset_url('admin/js/aiw-live-preview.js'),
                ['jquery', 'aiw-admin-enhancements'],
                $this->get_asset_version('admin/js/aiw-live-preview.js'),
                true
            );

            wp_enqueue_script(
                'aiw-debug-window',
                $this->get_asset_url('admin/js/aiw-debug-window.js'),
                ['aiw-live-preview'],
                $this->get_asset_version('admin/js/aiw-debug-window.js'),
                true
            );

            wp_enqueue_script(
                'aiw-customizer-fix',
                $this->get_asset_url('admin/js/customizer-partial-fix.js'),
                ['aiw-live-preview'],
                $this->get_asset_version('admin/js/customizer-partial-fix.js'),
                true
            );
        }

        // Localize admin script
        wp_localize_script('aiw-admin-enhancements', 'aiwAdminAjax', [
            'url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aiw_admin_nonce'),
            'strings' => [
                'confirm_reset' => __('Are you sure you want to reset this setting?', 'ai-interview-widget'),
                'confirm_delete' => __('Are you sure you want to delete this preset?', 'ai-interview-widget'),
            ]
        ]);
    }

    /**
     * Get asset URL with proper HTTPS handling
     * 
     * @param string $asset_path Relative path to asset
     * @return string Full asset URL
     */
    private function get_asset_url($asset_path) {
        $url = $this->plugin_url . 'assets/' . $asset_path;
        
        // Ensure HTTPS on secure sites
        if (is_ssl() && strpos($url, 'http://') === 0) {
            $url = set_url_scheme($url, 'https');
        }
        
        return $url;
    }

    /**
     * Get asset version for cache busting
     * 
     * @param string $asset_path Relative path to asset
     * @return string Version string
     */
    private function get_asset_version($asset_path) {
        $file_path = $this->plugin_dir . 'assets/' . $asset_path;
        
        // Use file modification time in development, plugin version in production
        if (defined('WP_DEBUG') && WP_DEBUG && file_exists($file_path)) {
            return filemtime($file_path);
        }
        
        return $this->version;
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
            'ai-interview-widget_page_ai-interview-customizer',
            'appearance_page_ai-interview-customizer'
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