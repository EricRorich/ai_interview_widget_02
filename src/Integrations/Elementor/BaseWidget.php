<?php
/**
 * Base Elementor Widget
 * 
 * Abstract base class for all AI Interview Elementor widgets.
 * 
 * @package EricRorich\AIInterviewWidget
 * @since 2.0.0
 */

namespace EricRorich\AIInterviewWidget\Integrations\Elementor;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

/**
 * Base Widget class
 * 
 * Provides common functionality for all AI Interview widgets.
 * 
 * @since 2.0.0
 */
abstract class BaseWidget extends Widget_Base {

    /**
     * Get widget categories
     * 
     * @return array Widget categories
     */
    public function get_categories() {
        return ['ai-interview'];
    }

    /**
     * Get widget script dependencies
     * 
     * @return array Script dependencies
     */
    public function get_script_depends() {
        return ['aiw-elementor-widgets'];
    }

    /**
     * Get widget style dependencies
     * 
     * @return array Style dependencies
     */
    public function get_style_depends() {
        return ['aiw-elementor-widgets'];
    }

    /**
     * Register common controls
     * 
     * @return void
     */
    protected function register_common_controls() {
        // Title Section
        $this->start_controls_section(
            'content_section',
            [
                'label' => esc_html__('Content', 'ai-interview-widget'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'widget_title',
            [
                'label' => esc_html__('Title', 'ai-interview-widget'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('AI Interview Assistant', 'ai-interview-widget'),
                'placeholder' => esc_html__('Enter widget title', 'ai-interview-widget'),
            ]
        );

        $this->add_control(
            'widget_subtitle',
            [
                'label' => esc_html__('Subtitle', 'ai-interview-widget'),
                'type' => Controls_Manager::TEXTAREA,
                'default' => esc_html__('Chat with my AI assistant to learn more about my experience and skills.', 'ai-interview-widget'),
                'placeholder' => esc_html__('Enter subtitle', 'ai-interview-widget'),
            ]
        );

        $this->end_controls_section();

        // Settings Section
        $this->start_controls_section(
            'settings_section',
            [
                'label' => esc_html__('Settings', 'ai-interview-widget'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'enable_voice',
            [
                'label' => esc_html__('Enable Voice', 'ai-interview-widget'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'ai-interview-widget'),
                'label_off' => esc_html__('No', 'ai-interview-widget'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'show_avatar',
            [
                'label' => esc_html__('Show Avatar', 'ai-interview-widget'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'ai-interview-widget'),
                'label_off' => esc_html__('No', 'ai-interview-widget'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'animation_type',
            [
                'label' => esc_html__('Animation', 'ai-interview-widget'),
                'type' => Controls_Manager::SELECT,
                'default' => 'fadeIn',
                'options' => [
                    'none' => esc_html__('None', 'ai-interview-widget'),
                    'fadeIn' => esc_html__('Fade In', 'ai-interview-widget'),
                    'slideIn' => esc_html__('Slide In', 'ai-interview-widget'),
                    'bounceIn' => esc_html__('Bounce In', 'ai-interview-widget'),
                ],
            ]
        );

        $this->add_control(
            'wrapper_tag',
            [
                'label' => esc_html__('HTML Tag', 'ai-interview-widget'),
                'type' => Controls_Manager::SELECT,
                'default' => 'div',
                'options' => [
                    'div' => 'DIV',
                    'section' => 'SECTION',
                    'article' => 'ARTICLE',
                ],
            ]
        );

        $this->end_controls_section();

        // Style Section
        $this->start_controls_section(
            'style_section',
            [
                'label' => esc_html__('Style', 'ai-interview-widget'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'primary_color',
            [
                'label' => esc_html__('Primary Color', 'ai-interview-widget'),
                'type' => Controls_Manager::COLOR,
                'default' => '#007cba',
                'selectors' => [
                    '{{WRAPPER}} .aiw-primary-color' => 'color: {{VALUE}}',
                    '{{WRAPPER}} .aiw-primary-bg' => 'background-color: {{VALUE}}',
                    '{{WRAPPER}} .aiw-primary-border' => 'border-color: {{VALUE}}',
                ],
            ]
        );

        $this->add_control(
            'secondary_color',
            [
                'label' => esc_html__('Secondary Color', 'ai-interview-widget'),
                'type' => Controls_Manager::COLOR,
                'default' => '#f1f1f1',
                'selectors' => [
                    '{{WRAPPER}} .aiw-secondary-color' => 'color: {{VALUE}}',
                    '{{WRAPPER}} .aiw-secondary-bg' => 'background-color: {{VALUE}}',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Render template with provided data
     * 
     * @param string $template Template name (relative to templates/elementor/)
     * @param array $settings Widget settings
     * @return void
     */
    protected function render_template(string $template, array $settings = []) {
        $template_path = $this->locate_template($template);
        
        if (!$template_path) {
            echo '<div class="aiw-error">' . 
                 esc_html__('Template not found:', 'ai-interview-widget') . ' ' . 
                 esc_html($template) . 
                 '</div>';
            return;
        }

        // Make settings available to template
        extract($settings, EXTR_SKIP);

        include $template_path;
    }

    /**
     * Locate widget template
     * 
     * @param string $template Template name
     * @return string|false Template path or false if not found
     */
    protected function locate_template(string $template) {
        $template_file = $template . '.php';
        
        // Check theme first
        $theme_template = locate_template([
            'ai-interview-widget/elementor/' . $template_file,
            'templates/ai-interview-widget/elementor/' . $template_file,
        ]);

        if ($theme_template) {
            return $theme_template;
        }

        // Check plugin templates
        $plugin_template = AIW_PLUGIN_DIR . 'templates/elementor/' . $template_file;
        if (file_exists($plugin_template)) {
            return $plugin_template;
        }

        return false;
    }

    /**
     * Get translated string with proper escaping
     * 
     * @param string $string String to translate
     * @param bool $escape Whether to escape the output
     * @return string
     */
    protected function get_string(string $string, bool $escape = true): string {
        $translated = __($string, 'ai-interview-widget');
        return $escape ? esc_html($translated) : $translated;
    }
}