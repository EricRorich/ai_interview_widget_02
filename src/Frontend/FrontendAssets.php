<?php
/**
 * Frontend Assets Manager
 * 
 * Handles enqueuing of frontend CSS and JavaScript.
 * 
 * @package EricRorich\AIInterviewWidget
 * @since 2.0.0
 */

namespace EricRorich\AIInterviewWidget\Frontend;

use EricRorich\AIInterviewWidget\Core\Assets;

/**
 * Frontend Assets class
 * 
 * Manages frontend asset enqueuing with manifest support.
 * 
 * @since 2.0.0
 */
class FrontendAssets {

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
     * Enqueue frontend assets
     * 
     * @return void
     */
    public function enqueue_assets() {
        // Get manifest-based asset URLs
        $frontend_css = $this->assets->get_manifest_asset_url('frontend.css');
        $frontend_js = $this->assets->get_manifest_asset_url('frontend.js');

        // Enqueue CSS
        wp_enqueue_style(
            'aiw-frontend',
            $frontend_css,
            [],
            $this->assets->get_asset_version('frontend.css')
        );

        // Enqueue JavaScript
        wp_enqueue_script(
            'aiw-frontend',
            $frontend_js,
            ['jquery'],
            $this->assets->get_asset_version('frontend.js'),
            true
        );

        // Localize script with AJAX data
        wp_localize_script('aiw-frontend', 'aiwFrontend', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aiw_nonce'),
            'i18n' => [
                'loading' => __('Loading...', 'ai-interview-widget'),
                'error' => __('An error occurred. Please try again.', 'ai-interview-widget'),
                'chatPlaceholder' => __('Type your message...', 'ai-interview-widget'),
            ]
        ]);
    }
}