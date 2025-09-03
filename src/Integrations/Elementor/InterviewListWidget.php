<?php
/**
 * Interview List Widget for Elementor
 * 
 * Displays a list of interview topics or conversations.
 * 
 * @package EricRorich\AIInterviewWidget
 * @since 2.0.0
 */

namespace EricRorich\AIInterviewWidget\Integrations\Elementor;

use Elementor\Controls_Manager;
use Elementor\Repeater;

/**
 * Interview List Widget class
 * 
 * Second example widget showing interview topics or conversation starters.
 * 
 * @since 2.0.0
 */
class InterviewListWidget extends BaseWidget {

    /**
     * Get widget name
     * 
     * @return string Widget name
     */
    public function get_name() {
        return 'ai_interview_list_widget';
    }

    /**
     * Get widget title
     * 
     * @return string Widget title
     */
    public function get_title() {
        return esc_html__('AI Interview Topics', 'ai-interview-widget');
    }

    /**
     * Get widget icon
     * 
     * @return string Widget icon
     */
    public function get_icon() {
        return 'eicon-bullet-list';
    }

    /**
     * Get widget keywords
     * 
     * @return array Widget keywords
     */
    public function get_keywords() {
        return ['ai', 'interview', 'topics', 'list', 'questions'];
    }

    /**
     * Register widget controls
     * 
     * @return void
     */
    protected function register_controls() {
        // Register common controls from base class
        $this->register_common_controls();

        // Topics list section
        $this->start_controls_section(
            'topics_section',
            [
                'label' => esc_html__('Interview Topics', 'ai-interview-widget'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $repeater = new Repeater();

        $repeater->add_control(
            'topic_title',
            [
                'label' => esc_html__('Topic Title', 'ai-interview-widget'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Topic Title', 'ai-interview-widget'),
                'placeholder' => esc_html__('Enter topic title', 'ai-interview-widget'),
            ]
        );

        $repeater->add_control(
            'topic_description',
            [
                'label' => esc_html__('Description', 'ai-interview-widget'),
                'type' => Controls_Manager::TEXTAREA,
                'default' => esc_html__('Topic description', 'ai-interview-widget'),
                'placeholder' => esc_html__('Enter topic description', 'ai-interview-widget'),
            ]
        );

        $repeater->add_control(
            'topic_questions',
            [
                'label' => esc_html__('Sample Questions', 'ai-interview-widget'),
                'type' => Controls_Manager::TEXTAREA,
                'default' => '',
                'placeholder' => esc_html__('Enter sample questions (one per line)', 'ai-interview-widget'),
            ]
        );

        $repeater->add_control(
            'topic_icon',
            [
                'label' => esc_html__('Icon', 'ai-interview-widget'),
                'type' => Controls_Manager::ICONS,
                'default' => [
                    'value' => 'fas fa-question-circle',
                    'library' => 'fa-solid',
                ],
            ]
        );

        $this->add_control(
            'topics_list',
            [
                'label' => esc_html__('Topics', 'ai-interview-widget'),
                'type' => Controls_Manager::REPEATER,
                'fields' => $repeater->get_controls(),
                'default' => [
                    [
                        'topic_title' => esc_html__('Technical Skills', 'ai-interview-widget'),
                        'topic_description' => esc_html__('Ask about programming languages, frameworks, and technical expertise.', 'ai-interview-widget'),
                        'topic_questions' => esc_html__("What programming languages do you know?\nWhat's your experience with React?\nTell me about your technical projects.", 'ai-interview-widget'),
                    ],
                    [
                        'topic_title' => esc_html__('Experience', 'ai-interview-widget'),
                        'topic_description' => esc_html__('Learn about work history and professional experience.', 'ai-interview-widget'),
                        'topic_questions' => esc_html__("What's your work experience?\nTell me about your projects.\nWhat achievements are you proud of?", 'ai-interview-widget'),
                    ],
                    [
                        'topic_title' => esc_html__('Education & Learning', 'ai-interview-widget'),
                        'topic_description' => esc_html__('Discover educational background and continuous learning.', 'ai-interview-widget'),
                        'topic_questions' => esc_html__("What's your educational background?\nHow do you stay updated with technology?\nWhat courses have you taken?", 'ai-interview-widget'),
                    ],
                ],
                'title_field' => '{{{ topic_title }}}',
            ]
        );

        $this->end_controls_section();

        // Display settings
        $this->start_controls_section(
            'display_settings',
            [
                'label' => esc_html__('Display Settings', 'ai-interview-widget'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'layout_style',
            [
                'label' => esc_html__('Layout Style', 'ai-interview-widget'),
                'type' => Controls_Manager::SELECT,
                'default' => 'cards',
                'options' => [
                    'cards' => esc_html__('Cards', 'ai-interview-widget'),
                    'list' => esc_html__('List', 'ai-interview-widget'),
                    'accordion' => esc_html__('Accordion', 'ai-interview-widget'),
                ],
            ]
        );

        $this->add_control(
            'columns',
            [
                'label' => esc_html__('Columns', 'ai-interview-widget'),
                'type' => Controls_Manager::SELECT,
                'default' => '3',
                'options' => [
                    '1' => '1',
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                ],
                'condition' => [
                    'layout_style' => 'cards',
                ],
            ]
        );

        $this->add_control(
            'show_questions',
            [
                'label' => esc_html__('Show Sample Questions', 'ai-interview-widget'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'ai-interview-widget'),
                'label_off' => esc_html__('No', 'ai-interview-widget'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->end_controls_section();

        // Style section for topics
        $this->start_controls_section(
            'topics_style',
            [
                'label' => esc_html__('Topics Style', 'ai-interview-widget'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'card_background',
            [
                'label' => esc_html__('Card Background', 'ai-interview-widget'),
                'type' => Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .aiw-topic-card' => 'background-color: {{VALUE}}',
                ],
                'condition' => [
                    'layout_style' => 'cards',
                ],
            ]
        );

        $this->add_control(
            'card_padding',
            [
                'label' => esc_html__('Card Padding', 'ai-interview-widget'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'default' => [
                    'top' => '20',
                    'right' => '20',
                    'bottom' => '20',
                    'left' => '20',
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .aiw-topic-card' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
        $this->add_render_attribute('wrapper', 'class', 'aiw-interview-list-widget-elementor');
        $this->add_render_attribute('wrapper', 'class', 'aiw-layout-' . $settings['layout_style']);
        
        if ($settings['layout_style'] === 'cards') {
            $this->add_render_attribute('wrapper', 'class', 'aiw-columns-' . $settings['columns']);
        }

        echo '<div ' . $this->get_render_attribute_string('wrapper') . '>';
        
        // Render using template
        $this->render_template('widget-interview-list', $settings);
        
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
        var wrapperClass = 'aiw-interview-list-widget-elementor aiw-layout-' + settings.layout_style;
        if (settings.layout_style === 'cards') {
            wrapperClass += ' aiw-columns-' + settings.columns;
        }
        #>
        <div class="{{ wrapperClass }}">
            <div class="aiw-widget">
                <# if (settings.widget_title) { #>
                    <h3 class="aiw-title">{{{ settings.widget_title }}}</h3>
                <# } #>
                <# if (settings.widget_subtitle) { #>
                    <p class="aiw-subtitle">{{{ settings.widget_subtitle }}}</p>
                <# } #>
                
                <div class="aiw-topics-container">
                    <# _.each(settings.topics_list, function(topic, index) { #>
                        <div class="aiw-topic-item aiw-topic-card">
                            <# if (topic.topic_icon.value) { #>
                                <div class="aiw-topic-icon">
                                    <i class="{{ topic.topic_icon.value }}"></i>
                                </div>
                            <# } #>
                            <h4 class="aiw-topic-title">{{{ topic.topic_title }}}</h4>
                            <# if (topic.topic_description) { #>
                                <p class="aiw-topic-description">{{{ topic.topic_description }}}</p>
                            <# } #>
                            <# if (settings.show_questions === 'yes' && topic.topic_questions) { #>
                                <div class="aiw-topic-questions">
                                    <strong><?php echo esc_html__('Sample Questions:', 'ai-interview-widget'); ?></strong>
                                    <div class="aiw-questions-list">
                                        {{{ topic.topic_questions.replace(/\n/g, '<br>') }}}
                                    </div>
                                </div>
                            <# } #>
                        </div>
                    <# }); #>
                </div>
            </div>
        </div>
        <?php
    }
}