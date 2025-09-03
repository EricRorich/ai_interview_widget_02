<?php
/**
 * Widget Manager for Elementor Integration
 * 
 * Manages registration of Elementor widgets and categories.
 * 
 * @package EricRorich\AIInterviewWidget
 * @since 2.0.0
 */

namespace EricRorich\AIInterviewWidget\Integrations\Elementor;

/**
 * Widget Manager class
 * 
 * Handles Elementor widget registration and category management.
 * 
 * @since 2.0.0
 */
class WidgetManager {

    /**
     * Register widgets with Elementor
     * 
     * @param \Elementor\Widgets_Manager $widgets_manager Elementor widgets manager
     * @return void
     */
    public function register_widgets($widgets_manager) {
        if (!class_exists('\Elementor\Widget_Base')) {
            return;
        }

        // Register Interview Widget
        $widgets_manager->register_widget_type(new InterviewWidget());
        
        // Register Interview List Widget
        $widgets_manager->register_widget_type(new InterviewListWidget());
    }

    /**
     * Add custom widget categories
     * 
     * @param \Elementor\Elements_Manager $elements_manager Elementor elements manager
     * @return void
     */
    public function add_widget_categories($elements_manager) {
        $elements_manager->add_category(
            'ai-interview',
            [
                'title' => esc_html__('AI Interview', 'ai-interview-widget'),
                'icon' => 'fa fa-plug',
            ]
        );
    }
}