<?php
/**
 * AI Interview Widget Base Template
 * 
 * Template for rendering the AI Interview Widget in Elementor and other contexts.
 * 
 * @package EricRorich\AIInterviewWidget
 * @since 2.0.0
 * 
 * @var array $settings Widget settings
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get settings with defaults
$title = isset($settings['widget_title']) ? $settings['widget_title'] : __('AI Interview Assistant', 'ai-interview-widget');
$description = isset($settings['widget_description']) ? $settings['widget_description'] : __('Chat with my AI assistant to learn more about my experience and skills.', 'ai-interview-widget');
$enable_voice = isset($settings['enable_voice']) && $settings['enable_voice'] === 'yes';
$primary_color = isset($settings['primary_color']) ? $settings['primary_color'] : '#007cba';

// Generate unique ID for this widget instance
$widget_id = 'aiw-widget-' . wp_generate_uuid4();
?>

<div class="ai-interview-widget" id="<?php echo esc_attr($widget_id); ?>" style="--aiw-color-primary: <?php echo esc_attr($primary_color); ?>;">
    
    <?php if (!empty($title)): ?>
        <div class="aiw-header">
            <h3 class="aiw-title"><?php echo esc_html($title); ?></h3>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($description)): ?>
        <div class="aiw-description">
            <p class="aiw-content"><?php echo esc_html($description); ?></p>
        </div>
    <?php endif; ?>
    
    <!-- Main widget container -->
    <div class="aiw-container">
        
        <!-- Play button section -->
        <div class="aiw-play-section">
            <button type="button" class="aiw-play-button" id="<?php echo esc_attr($widget_id); ?>-play-btn" aria-label="<?php esc_attr_e('Start AI Interview', 'ai-interview-widget'); ?>">
                <span class="aiw-play-icon">▶</span>
                <span class="aiw-play-text"><?php esc_html_e('Start Interview', 'ai-interview-widget'); ?></span>
            </button>
        </div>
        
        <!-- Audio visualization (hidden initially) -->
        <div class="aiw-visualization" id="<?php echo esc_attr($widget_id); ?>-visualization" style="display: none;">
            <canvas class="aiw-viz-canvas" id="<?php echo esc_attr($widget_id); ?>-canvas" aria-hidden="true"></canvas>
        </div>
        
        <!-- Chat interface (hidden initially) -->
        <div class="aiw-chat-container" id="<?php echo esc_attr($widget_id); ?>-chat" style="display: none;">
            <div class="aiw-chat-messages" id="<?php echo esc_attr($widget_id); ?>-messages" role="log" aria-live="polite" aria-label="<?php esc_attr_e('Chat messages', 'ai-interview-widget'); ?>">
                <!-- Messages will be populated here -->
            </div>
            
            <div class="aiw-chat-input">
                <div class="aiw-input-group">
                    <input 
                        type="text" 
                        class="aiw-message-input" 
                        id="<?php echo esc_attr($widget_id); ?>-input"
                        placeholder="<?php esc_attr_e('Type your message...', 'ai-interview-widget'); ?>"
                        aria-label="<?php esc_attr_e('Chat message input', 'ai-interview-widget'); ?>"
                    >
                    <button 
                        type="button" 
                        class="aiw-send-button" 
                        id="<?php echo esc_attr($widget_id); ?>-send"
                        aria-label="<?php esc_attr_e('Send message', 'ai-interview-widget'); ?>"
                    >
                        <span class="aiw-send-icon">➤</span>
                    </button>
                </div>
                
                <?php if ($enable_voice): ?>
                <div class="aiw-voice-controls">
                    <button 
                        type="button" 
                        class="aiw-voice-button" 
                        id="<?php echo esc_attr($widget_id); ?>-voice"
                        aria-label="<?php esc_attr_e('Voice input', 'ai-interview-widget'); ?>"
                    >
                        <span class="aiw-voice-icon">🎤</span>
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Status/loading indicator -->
        <div class="aiw-status" id="<?php echo esc_attr($widget_id); ?>-status" style="display: none;">
            <div class="aiw-loading-spinner"></div>
            <span class="aiw-status-text"><?php esc_html_e('Loading...', 'ai-interview-widget'); ?></span>
        </div>
        
    </div>
    
    <!-- Error display -->
    <div class="aiw-error" id="<?php echo esc_attr($widget_id); ?>-error" style="display: none;" role="alert">
        <span class="aiw-error-text"></span>
        <button type="button" class="aiw-error-close" aria-label="<?php esc_attr_e('Close error', 'ai-interview-widget'); ?>">×</button>
    </div>
    
</div>

<script type="text/javascript">
// Initialize widget when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    if (typeof window.AIWWidget !== 'undefined') {
        window.AIWWidget.init('<?php echo esc_js($widget_id); ?>', {
            enableVoice: <?php echo $enable_voice ? 'true' : 'false'; ?>,
            primaryColor: '<?php echo esc_js($primary_color); ?>'
        });
    }
});
</script>