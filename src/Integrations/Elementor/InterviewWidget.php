<?php
/**
 * Interview Widget for Elementor
 * 
 * Main AI interview widget for Elementor page builder.
 * 
 * @package EricRorich\AIInterviewWidget
 * @since 2.0.0
 */

namespace EricRorich\AIInterviewWidget\Integrations\Elementor;

use Elementor\Controls_Manager;

/**
 * Interview Widget class
 * 
 * Elementor widget for the AI interview functionality.
 * 
 * @since 2.0.0
 */
class InterviewWidget extends BaseWidget {

    /**
     * Get widget name
     * 
     * @return string Widget name
     */
    public function get_name() {
        return 'ai_interview_widget';
    }

    /**
     * Get widget title
     * 
     * @return string Widget title
     */
    public function get_title() {
        return esc_html__('AI Interview', 'ai-interview-widget');
    }

    /**
     * Get widget icon
     * 
     * @return string Widget icon
     */
    public function get_icon() {
        return 'eicon-comments';
    }

    /**
     * Get widget keywords
     * 
     * @return array Widget keywords
     */
    public function get_keywords() {
        return ['ai', 'interview', 'chat', 'assistant', 'voice'];
    }

    /**
     * Register widget controls
     * 
     * @return void
     */
    protected function register_controls() {
        // Register common controls from base class
        $this->register_common_controls();

        // Add specific controls for interview widget
        $this->start_controls_section(
            'interview_settings',
            [
                'label' => esc_html__('Interview Settings', 'ai-interview-widget'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'max_messages',
            [
                'label' => esc_html__('Max Messages', 'ai-interview-widget'),
                'type' => Controls_Manager::NUMBER,
                'default' => 50,
                'min' => 10,
                'max' => 200,
                'description' => esc_html__('Maximum number of messages in conversation history', 'ai-interview-widget'),
            ]
        );

        $this->add_control(
            'enable_typing_indicator',
            [
                'label' => esc_html__('Typing Indicator', 'ai-interview-widget'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'ai-interview-widget'),
                'label_off' => esc_html__('No', 'ai-interview-widget'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'auto_scroll',
            [
                'label' => esc_html__('Auto Scroll', 'ai-interview-widget'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'ai-interview-widget'),
                'label_off' => esc_html__('No', 'ai-interview-widget'),
                'return_value' => 'yes',
                'default' => 'yes',
                'description' => esc_html__('Automatically scroll to new messages', 'ai-interview-widget'),
            ]
        );

        $this->add_control(
            'conversation_starter',
            [
                'label' => esc_html__('Conversation Starter', 'ai-interview-widget'),
                'type' => Controls_Manager::TEXTAREA,
                'default' => esc_html__('Hi! I\'m Eric\'s AI assistant. Feel free to ask me about his experience, skills, or projects.', 'ai-interview-widget'),
                'placeholder' => esc_html__('Enter initial message', 'ai-interview-widget'),
            ]
        );

        $this->end_controls_section();

        // Chat appearance controls
        $this->start_controls_section(
            'chat_style',
            [
                'label' => esc_html__('Chat Appearance', 'ai-interview-widget'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'chat_height',
            [
                'label' => esc_html__('Chat Height', 'ai-interview-widget'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'vh'],
                'range' => [
                    'px' => [
                        'min' => 300,
                        'max' => 800,
                        'step' => 10,
                    ],
                    'vh' => [
                        'min' => 30,
                        'max' => 80,
                        'step' => 5,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 400,
                ],
                'selectors' => [
                    '{{WRAPPER}} .aiw-chat-container' => 'height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'border_radius',
            [
                'label' => esc_html__('Border Radius', 'ai-interview-widget'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .aiw-widget' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Render widget output on frontend
     * 
     * @return void
     */
    protected function render() {
        $settings = $this->get_settings_for_display();
        
        // Ensure frontend assets are loaded
        wp_enqueue_style('aiw-elementor-widgets');
        wp_enqueue_script('aiw-elementor-widgets');

        // Add widget-specific CSS class
        $this->add_render_attribute('wrapper', 'class', 'aiw-interview-widget-elementor');

        echo '<div ' . $this->get_render_attribute_string('wrapper') . '>';
        
        // Render using template
        $this->render_template('widget-interview', $settings);
        
        echo '</div>';
    }

    /**
     * Render widget output in Elementor editor
     * 
     * @return void
     */
    protected function content_template() {
        ?>
        <#
        var wrapperClass = 'aiw-interview-widget-elementor';
        #>
        <div class="{{ wrapperClass }}">
            <div class="aiw-widget">
                <div class="aiw-header">
                    <# if (settings.widget_title) { #>
                        <h3 class="aiw-title">{{{ settings.widget_title }}}</h3>
                    <# } #>
                    <# if (settings.widget_subtitle) { #>
                        <p class="aiw-subtitle">{{{ settings.widget_subtitle }}}</p>
                    <# } #>
                </div>
                <div class="aiw-chat-container">
                    <div class="aiw-messages">
                        <# if (settings.conversation_starter) { #>
                        <div class="aiw-message aiw-message-assistant">
                            <div class="aiw-message-content">
                                {{{ settings.conversation_starter }}}
                            </div>
                        </div>
                        <# } #>
                    </div>
                    <div class="aiw-input-container">
                        <input type="text" class="aiw-message-input" placeholder="<?php echo esc_attr__('Type your message...', 'ai-interview-widget'); ?>" disabled>
                        <button class="aiw-send-button" disabled>
                            <?php echo esc_html__('Send', 'ai-interview-widget'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}