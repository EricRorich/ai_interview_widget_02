<?php
/**
 * Elementor Assets Manager
 * 
 * Handles Elementor-specific asset enqueuing.
 * 
 * @package EricRorich\AIInterviewWidget
 * @since 2.0.0
 */

namespace EricRorich\AIInterviewWidget\Integrations\Elementor;

use EricRorich\AIInterviewWidget\Core\Assets;

/**
 * Elementor Assets class
 * 
 * Manages Elementor-specific asset loading with manifest support.
 * 
 * @since 2.0.0
 */
class ElementorAssets {

    /**
     * Core assets manager
     * 
     * @var Assets
     */
    private $assets;

    /**
     * Constructor
     * 
     * @param Assets $assets Core assets manager
     */
    public function __construct(Assets $assets) {
        $this->assets = $assets;
    }

    /**
     * Enqueue Elementor-specific assets
     * 
     * @return void
     */
    public function enqueue_elementor_assets() {
        // Get manifest-based asset URLs
        $elementor_css = $this->assets->get_manifest_asset_url('elementor-widgets.css');
        $elementor_js = $this->assets->get_manifest_asset_url('elementor-widgets.js');

        // Enqueue CSS
        wp_enqueue_style(
            'aiw-elementor-widgets',
            $elementor_css,
            [],
            $this->assets->get_asset_version('elementor-widgets.css')
        );

        // Enqueue JavaScript
        wp_enqueue_script(
            'aiw-elementor-widgets',
            $elementor_js,
            ['jquery', 'elementor-frontend'],
            $this->assets->get_asset_version('elementor-widgets.js'),
            true
        );

        // Localize script with Elementor-specific data
        wp_localize_script('aiw-elementor-widgets', 'aiwElementor', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aiw_nonce'),
            'isElementorEditor' => defined('ELEMENTOR_VERSION') && \Elementor\Plugin::$instance->editor->is_edit_mode(),
            'i18n' => [
                'loading' => __('Loading...', 'ai-interview-widget'),
                'error' => __('An error occurred. Please try again.', 'ai-interview-widget'),
                'noResponse' => __('No response received.', 'ai-interview-widget'),
            ]
        ]);
    }
}