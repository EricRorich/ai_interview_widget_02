<?php
/**
 * AI Interview Elementor Widget
 * 
 * Elementor widget for the AI Interview functionality.
 * 
 * @package EricRorich\AIInterviewWidget
 * @since 2.0.0
 */

namespace EricRorich\AIInterviewWidget\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;

/**
 * AI Interview Widget for Elementor
 * 
 * @since 2.0.0
 */
class InterviewWidget extends Widget_Base {

    /**
     * Get widget name
     * 
     * @return string Widget name
     */
    public function get_name() {
        return 'ai-interview-widget';
    }

    /**
     * Get widget title
     * 
     * @return string Widget title
     */
    public function get_title() {
        return esc_html__('AI Interview Widget', 'ai-interview-widget');
    }

    /**
     * Get widget icon
     * 
     * @return string Widget icon
     */
    public function get_icon() {
        return 'eicon-play-o';
    }

    /**
     * Get widget categories
     * 
     * @return array Widget categories
     */
    public function get_categories() {
        return ['ai-interview-widgets'];
    }

    /**
     * Get widget keywords
     * 
     * @return array Widget keywords
     */
    public function get_keywords() {
        return ['ai', 'interview', 'chat', 'voice', 'assistant'];
    }

    /**
     * Register widget controls
     * 
     * @return void
     */
    protected function _register_controls() {
        // Content Section
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
            'widget_description',
            [
                'label' => esc_html__('Description', 'ai-interview-widget'),
                'type' => Controls_Manager::TEXTAREA,
                'default' => esc_html__('Chat with my AI assistant to learn more about my experience and skills.', 'ai-interview-widget'),
                'placeholder' => esc_html__('Enter widget description', 'ai-interview-widget'),
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
                'scheme' => [
                    'type' => 'color',
                    'value' => '#007cba',
                ],
                'default' => '#007cba',
                'selectors' => [
                    '{{WRAPPER}} .ai-interview-widget' => '--aiw-color-primary: {{VALUE}}',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name' => 'background',
                'label' => esc_html__('Background', 'ai-interview-widget'),
                'types' => ['classic', 'gradient'],
                'selector' => '{{WRAPPER}} .ai-interview-widget',
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'border',
                'label' => esc_html__('Border', 'ai-interview-widget'),
                'selector' => '{{WRAPPER}} .ai-interview-widget',
            ]
        );

        $this->add_control(
            'border_radius',
            [
                'label' => esc_html__('Border Radius', 'ai-interview-widget'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .ai-interview-widget' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'padding',
            [
                'label' => esc_html__('Padding', 'ai-interview-widget'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .ai-interview-widget' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Typography Section
        $this->start_controls_section(
            'typography_section',
            [
                'label' => esc_html__('Typography', 'ai-interview-widget'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'title_typography',
                'label' => esc_html__('Title Typography', 'ai-interview-widget'),
                'selector' => '{{WRAPPER}} .ai-interview-widget .aiw-title',
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'content_typography',
                'label' => esc_html__('Content Typography', 'ai-interview-widget'),
                'selector' => '{{WRAPPER}} .ai-interview-widget .aiw-content',
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Render widget output
     * 
     * @return void
     */
    protected function render() {
        $settings = $this->get_settings_for_display();
        
        // Load template
        $template_path = AIW_PLUGIN_DIR . 'templates/widget-base.php';
        
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            // Fallback if template not found
            echo do_shortcode('[ai_interview_widget]');
        }
    }

    /**
     * Render widget output in the editor
     * 
     * @return void
     */
    protected function _content_template() {
        ?>
        <div class="ai-interview-widget">
            <# if ( settings.widget_title ) { #>
                <h3 class="aiw-title">{{{ settings.widget_title }}}</h3>
            <# } #>
            
            <# if ( settings.widget_description ) { #>
                <p class="aiw-content">{{{ settings.widget_description }}}</p>
            <# } #>
            
            <div class="aiw-placeholder">
                <p><?php echo esc_html__('AI Interview Widget will appear here on the frontend.', 'ai-interview-widget'); ?></p>
            </div>
        </div>
        <?php
    }
}