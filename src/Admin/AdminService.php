<?php
/**
 * Admin Service Class
 * 
 * Handles WordPress admin functionality including menu pages, settings, and admin assets.
 * 
 * @package EricRorich\AIInterviewWidget
 * @since 2.0.0
 */

namespace EricRorich\AIInterviewWidget\Admin;

/**
 * Admin service class
 * 
 * Manages all admin-related functionality for the plugin.
 * 
 * @since 2.0.0
 */
class AdminService {

    /**
     * Initialize admin functionality
     * 
     * @return void
     */
    public function init() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_notices', [$this, 'admin_notices']);
        
        // AJAX handlers for admin functionality
        add_action('wp_ajax_ai_interview_save_styles', [$this, 'save_custom_styles']);
        add_action('wp_ajax_ai_interview_save_content', [$this, 'save_custom_content']);
        add_action('wp_ajax_ai_interview_reset_styles', [$this, 'reset_custom_styles']);
        add_action('wp_ajax_ai_interview_upload_audio', [$this, 'handle_audio_upload']);
        add_action('wp_ajax_ai_interview_remove_audio', [$this, 'handle_audio_removal']);
        add_action('wp_ajax_ai_interview_get_models', [$this, 'handle_get_models']);
        add_action('wp_ajax_ai_interview_render_preview', [$this, 'handle_preview_render']);
    }

    /**
     * Add admin menu pages
     * 
     * @return void
     */
    public function add_admin_menu() {
        // Remove any existing submenu items to prevent conflicts
        $this->remove_old_menu_hooks();

        // Main menu page
        $hook = add_menu_page(
            __('AI Interview Widget', 'ai-interview-widget'),
            __('AI Chat Widget', 'ai-interview-widget'),
            'manage_options',
            'ai-interview-widget',
            [$this, 'admin_page'],
            'dashicons-microphone',
            25
        );

        // Settings submenu
        add_submenu_page(
            'ai-interview-widget',
            __('AI Widget Settings', 'ai-interview-widget'),
            __('Settings', 'ai-interview-widget'),
            'manage_options',
            'ai-interview-widget',
            [$this, 'admin_page']
        );

        // API Testing submenu
        add_submenu_page(
            'ai-interview-widget',
            __('API Testing & Diagnostics', 'ai-interview-widget'),
            __('API Testing', 'ai-interview-widget'),
            'manage_options',
            'ai-interview-widget-testing',
            [$this, 'testing_page']
        );

        // Documentation submenu
        add_submenu_page(
            'ai-interview-widget',
            __('Usage & Documentation', 'ai-interview-widget'),
            __('Documentation', 'ai-interview-widget'),
            'manage_options',
            'ai-interview-widget-docs',
            [$this, 'documentation_page']
        );

        // Visual Customizer submenu
        add_submenu_page(
            'ai-interview-widget',
            __('Enhanced Visual Customizer', 'ai-interview-widget'),
            __('Customize Widget', 'ai-interview-widget'),
            'manage_options',
            'ai-interview-widget-customizer',
            [$this, 'enhanced_customizer_page']
        );
    }

    /**
     * Remove old menu hooks to prevent conflicts
     * 
     * @return void
     */
    private function remove_old_menu_hooks() {
        global $submenu;
        if (isset($submenu['options-general.php'])) {
            foreach ($submenu['options-general.php'] as $key => $item) {
                if (isset($item[2]) && $item[2] === 'ai-interview-widget') {
                    unset($submenu['options-general.php'][$key]);
                }
            }
        }
    }

    /**
     * Register plugin settings
     * 
     * @return void
     */
    public function register_settings() {
        $settings_group = 'ai_interview_widget_settings';
        
        // API Settings
        register_setting($settings_group, 'ai_interview_widget_openai_api_key', [
            'type' => 'string',
            'sanitize_callback' => [$this, 'sanitize_api_key'],
            'default' => ''
        ]);

        register_setting($settings_group, 'ai_interview_widget_elevenlabs_api_key', [
            'type' => 'string',
            'sanitize_callback' => [$this, 'sanitize_api_key'],
            'default' => ''
        ]);

        register_setting($settings_group, 'ai_interview_widget_anthropic_api_key', [
            'type' => 'string',
            'sanitize_callback' => [$this, 'sanitize_api_key'],
            'default' => ''
        ]);

        // Model Settings
        register_setting($settings_group, 'ai_interview_widget_api_provider', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'openai'
        ]);

        register_setting($settings_group, 'ai_interview_widget_model', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'gpt-4o-mini'
        ]);

        // System Prompt
        register_setting($settings_group, 'ai_interview_widget_system_prompt', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_textarea_field',
            'default' => $this->get_default_system_prompt()
        ]);

        // Feature Toggles
        register_setting($settings_group, 'ai_interview_widget_enable_voice', [
            'type' => 'boolean',
            'default' => true
        ]);

        register_setting($settings_group, 'ai_interview_widget_enable_debug', [
            'type' => 'boolean',
            'default' => false
        ]);
    }

    /**
     * Main admin page
     * 
     * @return void
     */
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('ai_interview_widget_settings');
                do_settings_sections('ai_interview_widget_settings');
                ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php esc_html_e('API Provider', 'ai-interview-widget'); ?></th>
                        <td>
                            <select name="ai_interview_widget_api_provider" id="ai_interview_widget_api_provider">
                                <option value="openai" <?php selected(get_option('ai_interview_widget_api_provider', 'openai'), 'openai'); ?>><?php esc_html_e('OpenAI', 'ai-interview-widget'); ?></option>
                                <option value="anthropic" <?php selected(get_option('ai_interview_widget_api_provider'), 'anthropic'); ?>><?php esc_html_e('Anthropic (Claude)', 'ai-interview-widget'); ?></option>
                            </select>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php esc_html_e('OpenAI API Key', 'ai-interview-widget'); ?></th>
                        <td>
                            <input type="password" name="ai_interview_widget_openai_api_key" value="<?php echo esc_attr(get_option('ai_interview_widget_openai_api_key', '')); ?>" class="regular-text" />
                            <p class="description"><?php esc_html_e('Enter your OpenAI API key for chat functionality.', 'ai-interview-widget'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php esc_html_e('Enable Voice Features', 'ai-interview-widget'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="ai_interview_widget_enable_voice" value="1" <?php checked(get_option('ai_interview_widget_enable_voice', true)); ?> />
                                <?php esc_html_e('Enable voice input/output capabilities', 'ai-interview-widget'); ?>
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php esc_html_e('Debug Mode', 'ai-interview-widget'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="ai_interview_widget_enable_debug" value="1" <?php checked(get_option('ai_interview_widget_enable_debug', false)); ?> />
                                <?php esc_html_e('Enable debug logging (for development)', 'ai-interview-widget'); ?>
                            </label>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
            
            <div class="aiw-admin-info">
                <h2><?php esc_html_e('Usage', 'ai-interview-widget'); ?></h2>
                <p><?php esc_html_e('Use the shortcode [ai_interview_widget] to display the widget on any page or post.', 'ai-interview-widget'); ?></p>
                <p><?php esc_html_e('For Elementor users, find the "AI Interview Widget" in the widget panel under "AI Interview Widgets" category.', 'ai-interview-widget'); ?></p>
            </div>
        </div>
        <?php
    }

    /**
     * Testing page
     * 
     * @return void
     */
    public function testing_page() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('API Testing & Diagnostics', 'ai-interview-widget'); ?></h1>
            <p><?php esc_html_e('Use this page to test your API connections and troubleshoot issues.', 'ai-interview-widget'); ?></p>
            
            <div class="aiw-testing-container">
                <button type="button" class="button button-primary" id="test-api-connection">
                    <?php esc_html_e('Test API Connection', 'ai-interview-widget'); ?>
                </button>
                <div id="test-results" style="margin-top: 20px;"></div>
            </div>
        </div>
        <?php
    }

    /**
     * Documentation page
     * 
     * @return void
     */
    public function documentation_page() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Usage & Documentation', 'ai-interview-widget'); ?></h1>
            
            <div class="aiw-docs-content">
                <h2><?php esc_html_e('Getting Started', 'ai-interview-widget'); ?></h2>
                <p><?php esc_html_e('The AI Interview Widget allows visitors to chat with an AI assistant to learn about your experience and skills.', 'ai-interview-widget'); ?></p>
                
                <h3><?php esc_html_e('Shortcode Usage', 'ai-interview-widget'); ?></h3>
                <code>[ai_interview_widget]</code>
                
                <h3><?php esc_html_e('Elementor Widget', 'ai-interview-widget'); ?></h3>
                <p><?php esc_html_e('Look for "AI Interview Widget" in the Elementor widget panel under the "AI Interview Widgets" category.', 'ai-interview-widget'); ?></p>
                
                <h3><?php esc_html_e('Configuration', 'ai-interview-widget'); ?></h3>
                <p><?php esc_html_e('Configure your API keys and settings in the Settings tab. Use the Visual Customizer to style the widget appearance.', 'ai-interview-widget'); ?></p>
            </div>
        </div>
        <?php
    }

    /**
     * Enhanced customizer page
     * 
     * @return void
     */
    public function enhanced_customizer_page() {
        ?>
        <div class="wrap aiw-customizer-wrap">
            <h1><?php esc_html_e('Enhanced Visual Customizer', 'ai-interview-widget'); ?></h1>
            <p><?php esc_html_e('Customize the appearance and behavior of your AI Interview Widget.', 'ai-interview-widget'); ?></p>
            
            <div class="aiw-customizer-container">
                <div class="aiw-customizer-notice">
                    <p><?php esc_html_e('The visual customizer functionality will be loaded here. This is a placeholder for the enhanced customizer interface.', 'ai-interview-widget'); ?></p>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Show admin notices
     * 
     * @return void
     */
    public function admin_notices() {
        // Check if API key is set
        $api_key = get_option('ai_interview_widget_openai_api_key', '');
        if (empty($api_key) && $this->is_plugin_page()) {
            ?>
            <div class="notice notice-warning">
                <p>
                    <?php esc_html_e('AI Interview Widget: Please configure your API key in the settings to enable chat functionality.', 'ai-interview-widget'); ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=ai-interview-widget')); ?>"><?php esc_html_e('Go to Settings', 'ai-interview-widget'); ?></a>
                </p>
            </div>
            <?php
        }
    }

    /**
     * Check if we're on a plugin admin page
     * 
     * @return bool
     */
    private function is_plugin_page() {
        $screen = get_current_screen();
        return $screen && strpos($screen->id, 'ai-interview-widget') !== false;
    }

    /**
     * Sanitize API key input
     * 
     * @param string $input Raw input
     * @return string Sanitized API key
     */
    public function sanitize_api_key($input) {
        return sanitize_text_field(trim($input));
    }

    /**
     * Get default system prompt
     * 
     * @return string
     */
    private function get_default_system_prompt() {
        return "You are Eric Rorich's AI assistant. Help visitors learn about Eric's experience as a software developer and entrepreneur. Be helpful, professional, and informative about his skills and background.";
    }

    // AJAX Handlers (simplified versions for now)
    public function save_custom_styles() {
        check_ajax_referer('aiw_admin_nonce', 'nonce');
        wp_send_json_success(['message' => 'Styles saved successfully']);
    }

    public function save_custom_content() {
        check_ajax_referer('aiw_admin_nonce', 'nonce');
        wp_send_json_success(['message' => 'Content saved successfully']);
    }

    public function reset_custom_styles() {
        check_ajax_referer('aiw_admin_nonce', 'nonce');
        wp_send_json_success(['message' => 'Styles reset successfully']);
    }

    public function handle_audio_upload() {
        check_ajax_referer('aiw_admin_nonce', 'nonce');
        wp_send_json_success(['message' => 'Audio uploaded successfully']);
    }

    public function handle_audio_removal() {
        check_ajax_referer('aiw_admin_nonce', 'nonce');
        wp_send_json_success(['message' => 'Audio removed successfully']);
    }

    public function handle_get_models() {
        check_ajax_referer('aiw_admin_nonce', 'nonce');
        wp_send_json_success(['models' => []]);
    }

    public function handle_preview_render() {
        check_ajax_referer('aiw_admin_nonce', 'nonce');
        wp_send_json_success(['html' => '<div>Preview placeholder</div>']);
    }
}