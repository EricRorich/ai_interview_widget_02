<?php
/**
 * Generic Widget Template
 * 
 * Fallback template for AI interview widgets.
 * 
 * @package AIInterviewWidget
 * @since 2.0.0
 * 
 * Available variables:
 * @var array $settings Widget settings
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Extract settings
$widget_title = $settings['widget_title'] ?? __('AI Interview Assistant', 'ai-interview-widget');
$widget_description = $settings['widget_description'] ?? __('Chat with my AI assistant to learn more about my experience and skills.', 'ai-interview-widget');
$enable_voice = $settings['enable_voice'] ?? true;
$primary_color = $settings['primary_color'] ?? '#007cba';
$animation = $settings['animation'] ?? 'fadeIn';
$wrapper_tag = $settings['wrapper_tag'] ?? 'div';
$show_avatar = $settings['show_avatar'] ?? true;

// Generate unique ID
$widget_id = 'aiw-widget-' . wp_generate_uuid4();

// CSS custom properties  
$css_vars = [
    '--aiw-primary-color: ' . esc_attr($primary_color),
];

$wrapper_classes = [
    'aiw-widget',
    'aiw-interview-widget',
    'aiw-container'
];

if ($animation && $animation !== 'none') {
    $wrapper_classes[] = 'aiw-animate';
    $wrapper_classes[] = 'aiw-animate-' . esc_attr($animation);
}

$wrapper_tag = in_array($wrapper_tag, ['div', 'section', 'article']) ? $wrapper_tag : 'div';
?>

<<?php echo esc_html($wrapper_tag); ?> 
    id="<?php echo esc_attr($widget_id); ?>"
    class="<?php echo esc_attr(implode(' ', $wrapper_classes)); ?>"
    style="<?php echo esc_attr(implode('; ', $css_vars)); ?>"
    data-animation="<?php echo esc_attr($animation); ?>"
>
    
    <div class="aiw-header">
        <h3 class="aiw-title"><?php echo esc_html($widget_title); ?></h3>
        <p class="aiw-description"><?php echo esc_html($widget_description); ?></p>
        
        <?php if ($enable_voice): ?>
        <button type="button" class="aiw-voice-toggle" aria-pressed="false">
            <span class="aiw-voice-icon">🎤</span>
            <span class="aiw-voice-text"><?php esc_html_e('Enable Voice', 'ai-interview-widget'); ?></span>
        </button>
        <?php endif; ?>
    </div>

    <div class="aiw-chat-container">
        <div class="aiw-messages" role="log" aria-live="polite">
            <div class="aiw-message aiw-message-assistant aiw-message-visible">
                <?php if ($show_avatar): ?>
                <div class="aiw-message-avatar">
                    <div class="aiw-avatar aiw-avatar-assistant">
                        <span class="aiw-avatar-icon">🤖</span>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="aiw-message-content">
                    <?php esc_html_e('Hi! I\'m Eric\'s AI assistant. Feel free to ask me about his experience, skills, or projects.', 'ai-interview-widget'); ?>
                </div>
                
                <div class="aiw-message-time">
                    <?php echo esc_html(current_time('H:i')); ?>
                </div>
            </div>
        </div>

        <form class="aiw-chat-form aiw-input-container">
            <div class="aiw-input-wrapper">
                <label for="<?php echo esc_attr($widget_id); ?>-input" class="screen-reader-text">
                    <?php esc_html_e('Type your message', 'ai-interview-widget'); ?>
                </label>
                
                <input 
                    type="text" 
                    id="<?php echo esc_attr($widget_id); ?>-input"
                    class="aiw-message-input" 
                    placeholder="<?php esc_attr_e('Type your message...', 'ai-interview-widget'); ?>"
                    maxlength="500"
                    autocomplete="off"
                />
            </div>

            <button type="submit" class="aiw-send-button" disabled>
                <span class="aiw-send-icon">📤</span>
                <span class="aiw-send-text"><?php esc_html_e('Send', 'ai-interview-widget'); ?></span>
            </button>
        </form>
    </div>

    <div class="aiw-footer">
        <div class="aiw-powered-by">
            <small><?php esc_html_e('Powered by AI Interview Widget', 'ai-interview-widget'); ?></small>
        </div>
    </div>

</<?php echo esc_html($wrapper_tag); ?>>