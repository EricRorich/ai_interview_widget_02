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
     * Asset manifest data
     * 
     * @var array|null
     */
    private $manifest = null;

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
     * Get manifest-based asset URL
     * 
     * @param string $asset_name Asset name from manifest
     * @return string Asset URL
     */
    public function get_manifest_asset_url(string $asset_name): string {
        $manifest = $this->get_manifest();
        
        if (isset($manifest[$asset_name])) {
            return $this->plugin_url . 'assets/build/' . $manifest[$asset_name];
        }
        
        // Fallback to src files if manifest not available
        return $this->get_fallback_asset_url($asset_name);
    }

    /**
     * Get asset version from manifest or fallback
     * 
     * @param string $asset_name Asset name
     * @return string Version string
     */
    public function get_asset_version(string $asset_name): string {
        $manifest = $this->get_manifest();
        
        if (isset($manifest[$asset_name])) {
            // Extract hash from filename if present
            $filename = $manifest[$asset_name];
            if (preg_match('/\.([a-f0-9]{8,})\./', $filename, $matches)) {
                return $matches[1];
            }
        }
        
        // Fallback to file modification time or plugin version
        $fallback_path = $this->get_fallback_asset_path($asset_name);
        if ($fallback_path && file_exists($fallback_path)) {
            return defined('WP_DEBUG') && WP_DEBUG ? filemtime($fallback_path) : $this->version;
        }
        
        return $this->version;
    }

    /**
     * Get asset manifest
     * 
     * @return array Manifest data
     */
    private function get_manifest(): array {
        if ($this->manifest === null) {
            $manifest_path = $this->plugin_dir . 'assets/build/manifest.json';
            
            if (file_exists($manifest_path)) {
                $manifest_content = file_get_contents($manifest_path);
                $this->manifest = json_decode($manifest_content, true) ?: [];
            } else {
                $this->manifest = [];
            }
        }
        
        return $this->manifest;
    }

    /**
     * Get fallback asset URL when manifest not available
     * 
     * @param string $asset_name Asset name
     * @return string Fallback URL
     */
    private function get_fallback_asset_url(string $asset_name): string {
        $fallback_map = [
            'frontend.css' => 'src/css/frontend.css',
            'frontend.js' => 'src/js/frontend.js',
            'admin.css' => 'src/css/admin.css', 
            'admin.js' => 'src/js/admin.js',
            'elementor-widgets.css' => 'src/css/elementor-widgets.css',
            'elementor-widgets.js' => 'src/js/elementor-widgets.js',
        ];
        
        $fallback_path = $fallback_map[$asset_name] ?? "src/{$asset_name}";
        return $this->plugin_url . 'assets/' . $fallback_path;
    }

    /**
     * Get fallback asset file path
     * 
     * @param string $asset_name Asset name
     * @return string|null Fallback file path or null
     */
    private function get_fallback_asset_path(string $asset_name): ?string {
        $fallback_map = [
            'frontend.css' => 'src/css/frontend.css',
            'frontend.js' => 'src/js/frontend.js', 
            'admin.css' => 'src/css/admin.css',
            'admin.js' => 'src/js/admin.js',
            'elementor-widgets.css' => 'src/css/elementor-widgets.css',
            'elementor-widgets.js' => 'src/js/elementor-widgets.js',
        ];
        
        $fallback_path = $fallback_map[$asset_name] ?? "src/{$asset_name}";
        $full_path = $this->plugin_dir . 'assets/' . $fallback_path;
        
        return file_exists($full_path) ? $full_path : null;
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