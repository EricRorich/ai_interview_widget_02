<?php
/**
 * Elementor Integration Service
 * 
 * Handles registration and management of Elementor widgets.
 * 
 * @package EricRorich\AIInterviewWidget
 * @since 2.0.0
 */

namespace EricRorich\AIInterviewWidget\Widgets;

use EricRorich\AIInterviewWidget\Widgets\InterviewWidget;

/**
 * Elementor service class
 * 
 * Manages Elementor widget registration and integration.
 * 
 * @since 2.0.0
 */
class ElementorService {

    /**
     * Initialize Elementor integration
     * 
     * @return void
     */
    public function init() {
        // Register widget when Elementor is ready
        add_action('elementor/widgets/widgets_registered', [$this, 'register_widgets']);
        
        // Add widget categories
        add_action('elementor/elements/categories_registered', [$this, 'add_widget_categories']);
    }

    /**
     * Register custom widgets
     * 
     * @param \Elementor\Widgets_Manager $widgets_manager Elementor widgets manager
     * @return void
     */
    public function register_widgets($widgets_manager) {
        if (!class_exists('\Elementor\Widget_Base')) {
            return;
        }

        // Register our AI Interview Widget
        $widgets_manager->register_widget_type(new InterviewWidget());
    }

    /**
     * Add custom widget categories
     * 
     * @param \Elementor\Elements_Manager $elements_manager Elementor elements manager
     * @return void
     */
    public function add_widget_categories($elements_manager) {
        $elements_manager->add_category(
            'ai-interview-widgets',
            [
                'title' => esc_html__('AI Interview Widgets', 'ai-interview-widget'),
                'icon' => 'fa fa-plug',
            ]
        );
    }
}