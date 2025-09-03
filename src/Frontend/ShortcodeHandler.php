<?php
/**
 * Shortcode Handler
 * 
 * Manages WordPress shortcode registration and rendering.
 * 
 * @package EricRorich\AIInterviewWidget
 * @since 2.0.0
 */

namespace EricRorich\AIInterviewWidget\Frontend;

/**
 * Shortcode Handler class
 * 
 * Handles registration and rendering of WordPress shortcodes.
 * 
 * @since 2.0.0
 */
class ShortcodeHandler {

    /**
     * Register shortcodes
     * 
     * @return void
     */
    public function register_shortcodes() {
        add_shortcode('ai_interview_widget', [$this, 'render_widget_shortcode']);
    }

    /**
     * Render the AI interview widget shortcode
     * 
     * @param array $atts Shortcode attributes
     * @param string $content Shortcode content
     * @param string $tag Shortcode tag
     * @return string Rendered HTML
     */
    public function render_widget_shortcode($atts = [], $content = '', $tag = '') {
        // Parse attributes with defaults
        $atts = shortcode_atts([
            'title' => __('AI Interview Assistant', 'ai-interview-widget'),
            'description' => __('Chat with my AI assistant to learn more about my experience and skills.', 'ai-interview-widget'),
            'enable_voice' => 'yes',
            'primary_color' => '#007cba',
            'animation' => 'fadeIn',
            'wrapper_tag' => 'div',
            'show_avatar' => 'yes',
        ], $atts, $tag);

        // Ensure frontend assets are loaded
        wp_enqueue_style('aiw-frontend');
        wp_enqueue_script('aiw-frontend');

        // Convert attributes to settings format
        $settings = [
            'widget_title' => esc_html($atts['title']),
            'widget_description' => esc_html($atts['description']),
            'enable_voice' => $atts['enable_voice'] === 'yes',
            'primary_color' => sanitize_hex_color($atts['primary_color']),
            'animation' => sanitize_text_field($atts['animation']),
            'wrapper_tag' => in_array($atts['wrapper_tag'], ['div', 'section', 'article']) ? $atts['wrapper_tag'] : 'div',
            'show_avatar' => $atts['show_avatar'] === 'yes',
        ];

        // Render widget template
        return $this->render_template('widget-interview', $settings);
    }

    /**
     * Render a template with provided data
     * 
     * @param string $template Template name
     * @param array $data Template data
     * @return string Rendered HTML
     */
    protected function render_template(string $template, array $data = []): string {
        $template_path = $this->locate_template($template);
        
        if (!$template_path) {
            return '<div class="aiw-error">' . 
                   esc_html__('Template not found:', 'ai-interview-widget') . ' ' . 
                   esc_html($template) . 
                   '</div>';
        }

        // Extract data to variables for template
        extract($data, EXTR_SKIP);

        ob_start();
        include $template_path;
        return ob_get_clean();
    }

    /**
     * Locate template file
     * 
     * @param string $template Template name
     * @return string|false Template path or false if not found
     */
    protected function locate_template(string $template) {
        $template_file = $template . '.php';
        
        // Check theme first
        $theme_template = locate_template([
            'ai-interview-widget/' . $template_file,
            'templates/ai-interview-widget/' . $template_file,
        ]);

        if ($theme_template) {
            return $theme_template;
        }

        // Check plugin templates
        $plugin_template = AIW_PLUGIN_DIR . 'templates/' . $template_file;
        if (file_exists($plugin_template)) {
            return $plugin_template;
        }

        return false;
    }
}