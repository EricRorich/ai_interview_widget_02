<?php
/**
 * Interview Widget Template
 * 
 * Template for the AI interview widget.
 * 
 * @package AIInterviewWidget
 * @since 2.0.0
 * 
 * Available variables:
 * @var string $widget_title
 * @var string $widget_subtitle  
 * @var bool $enable_voice
 * @var string $primary_color
 * @var string $animation_type
 * @var string $wrapper_tag
 * @var bool $show_avatar
 * @var string $conversation_starter
 * @var int $max_messages
 * @var bool $enable_typing_indicator
 * @var bool $auto_scroll
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Set defaults
$widget_title = $widget_title ?? __('AI Interview Assistant', 'ai-interview-widget');
$widget_subtitle = $widget_subtitle ?? __('Chat with my AI assistant to learn more about my experience and skills.', 'ai-interview-widget');
$enable_voice = $enable_voice ?? true;
$primary_color = $primary_color ?? '#007cba';
$animation_type = $animation_type ?? 'fadeIn';
$wrapper_tag = $wrapper_tag ?? 'div';
$show_avatar = $show_avatar ?? true;
$conversation_starter = $conversation_starter ?? __('Hi! I\'m Eric\'s AI assistant. Feel free to ask me about his experience, skills, or projects.', 'ai-interview-widget');
$max_messages = $max_messages ?? 50;
$enable_typing_indicator = $enable_typing_indicator ?? true;
$auto_scroll = $auto_scroll ?? true;

// Generate unique ID
$widget_id = 'aiw-widget-' . wp_generate_uuid4();

// CSS custom properties
$css_vars = [
    '--aiw-primary-color: ' . esc_attr($primary_color),
];

$wrapper_classes = [
    'aiw-widget',
    'aiw-interview-widget'
];

if ($animation_type && $animation_type !== 'none') {
    $wrapper_classes[] = 'aiw-animate';
    $wrapper_classes[] = 'aiw-animate-' . esc_attr($animation_type);
}

$wrapper_attributes = [
    'id' => $widget_id,
    'class' => implode(' ', $wrapper_classes),
    'style' => implode('; ', $css_vars),
    'data-animation' => esc_attr($animation_type),
    'data-auto-scroll' => $auto_scroll ? 'true' : 'false',
    'data-max-messages' => esc_attr($max_messages),
];

$wrapper_tag = in_array($wrapper_tag, ['div', 'section', 'article']) ? $wrapper_tag : 'div';
?>

<<?php echo esc_html($wrapper_tag); ?> <?php echo $this->build_attributes($wrapper_attributes); ?>>
    
    <?php if ($widget_title || $widget_subtitle): ?>
    <div class="aiw-header">
        <?php if ($widget_title): ?>
            <h3 class="aiw-title"><?php echo esc_html($widget_title); ?></h3>
        <?php endif; ?>
        
        <?php if ($widget_subtitle): ?>
            <p class="aiw-subtitle"><?php echo esc_html($widget_subtitle); ?></p>
        <?php endif; ?>
        
        <?php if ($enable_voice): ?>
            <button type="button" class="aiw-voice-toggle" aria-pressed="false">
                <span class="aiw-voice-icon">🎤</span>
                <span class="aiw-voice-text"><?php esc_html_e('Enable Voice', 'ai-interview-widget'); ?></span>
            </button>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="aiw-chat-container">
        <div class="aiw-messages" role="log" aria-live="polite" aria-label="<?php esc_attr_e('Chat messages', 'ai-interview-widget'); ?>">
            
            <?php if ($conversation_starter): ?>
            <div class="aiw-message aiw-message-assistant aiw-message-visible">
                <?php if ($show_avatar): ?>
                    <div class="aiw-message-avatar">
                        <div class="aiw-avatar aiw-avatar-assistant">
                            <span class="aiw-avatar-icon">🤖</span>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="aiw-message-content">
                    <?php echo esc_html($conversation_starter); ?>
                </div>
                
                <div class="aiw-message-time">
                    <?php echo esc_html(current_time('H:i')); ?>
                </div>
            </div>
            <?php endif; ?>
            
        </div>

        <form class="aiw-chat-form aiw-input-container" role="form" aria-label="<?php esc_attr_e('Send message', 'ai-interview-widget'); ?>">
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
                    aria-describedby="<?php echo esc_attr($widget_id); ?>-help"
                />
                
                <div id="<?php echo esc_attr($widget_id); ?>-help" class="screen-reader-text">
                    <?php esc_html_e('Press Enter to send your message', 'ai-interview-widget'); ?>
                </div>
            </div>

            <button 
                type="submit" 
                class="aiw-send-button" 
                disabled
                aria-label="<?php esc_attr_e('Send message', 'ai-interview-widget'); ?>"
            >
                <span class="aiw-send-icon">📤</span>
                <span class="aiw-send-text"><?php esc_html_e('Send', 'ai-interview-widget'); ?></span>
            </button>
        </form>
    </div>

    <div class="aiw-footer">
        <div class="aiw-powered-by">
            <small>
                <?php esc_html_e('Powered by AI Interview Widget', 'ai-interview-widget'); ?>
            </small>
        </div>
    </div>

</<?php echo esc_html($wrapper_tag); ?>>

<?php
// Helper function to build HTML attributes
if (!function_exists('build_attributes')) {
    function build_attributes($attributes) {
        $html = '';
        foreach ($attributes as $key => $value) {
            if ($value !== null && $value !== '') {
                $html .= sprintf(' %s="%s"', esc_html($key), esc_attr($value));
            }
        }
        return $html;
    }
}

// Make build_attributes available as method for template
if (!method_exists($this, 'build_attributes')) {
    $this->build_attributes = function($attributes) {
        return build_attributes($attributes);
    };
}
?>