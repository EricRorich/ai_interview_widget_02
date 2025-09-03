<?php
/**
 * Integration tests for WordPress and Elementor functionality
 * 
 * @package EricRorich\AIInterviewWidget\Tests
 */

namespace EricRorich\AIInterviewWidget\Tests\Integration;

use WP_UnitTestCase;
use EricRorich\AIInterviewWidget\Core\Plugin;

/**
 * Test WordPress integration
 */
class WordPressIntegrationTest extends WP_UnitTestCase {

    /**
     * Test plugin constants are defined
     */
    public function test_plugin_constants_defined() {
        $this->assertTrue(defined('AIW_VERSION'), 'AIW_VERSION should be defined');
        $this->assertTrue(defined('AIW_PLUGIN_FILE'), 'AIW_PLUGIN_FILE should be defined');
        $this->assertTrue(defined('AIW_PLUGIN_DIR'), 'AIW_PLUGIN_DIR should be defined');
        $this->assertTrue(defined('AIW_PLUGIN_URL'), 'AIW_PLUGIN_URL should be defined');
        
        $this->assertEquals('2.0.0', AIW_VERSION);
    }

    /**
     * Test shortcode registration
     */
    public function test_shortcode_registration() {
        global $shortcode_tags;
        
        $this->assertArrayHasKey('ai_interview_widget', $shortcode_tags, 'ai_interview_widget shortcode should be registered');
        $this->assertTrue(is_callable($shortcode_tags['ai_interview_widget']), 'Shortcode handler should be callable');
    }

    /**
     * Test shortcode output
     */
    public function test_shortcode_output() {
        $output = do_shortcode('[ai_interview_widget]');
        
        $this->assertNotEmpty($output, 'Shortcode should produce output');
        $this->assertStringContainsString('ai-interview-widget', $output, 'Output should contain main widget class');
        $this->assertStringContainsString('aiw-container', $output, 'Output should contain widget container');
    }

    /**
     * Test shortcode with attributes
     */
    public function test_shortcode_with_attributes() {
        $output = do_shortcode('[ai_interview_widget title="Custom Title" enable_voice="no"]');
        
        $this->assertStringContainsString('Custom Title', $output, 'Custom title should appear in output');
    }

    /**
     * Test AJAX actions are registered
     */
    public function test_ajax_actions_registered() {
        $this->assertTrue(has_action('wp_ajax_ai_interview_chat'), 'Chat AJAX action should be registered');
        $this->assertTrue(has_action('wp_ajax_nopriv_ai_interview_chat'), 'Public chat AJAX action should be registered');
        $this->assertTrue(has_action('wp_ajax_ai_interview_test'), 'Test AJAX action should be registered');
        $this->assertTrue(has_action('wp_ajax_nopriv_ai_interview_test'), 'Public test AJAX action should be registered');
    }

    /**
     * Test plugin initialization
     */
    public function test_plugin_initialization() {
        $plugin = Plugin::get_instance();
        
        $this->assertInstanceOf(Plugin::class, $plugin, 'Plugin instance should be available');
        $this->assertTrue($plugin->is_initialized(), 'Plugin should be initialized');
    }

    /**
     * Test admin functionality (when in admin context)
     */
    public function test_admin_functionality() {
        // Set admin context
        set_current_screen('dashboard');
        
        // Check if admin hooks are set up
        $this->assertTrue(has_action('admin_menu'), 'Admin menu action should be registered');
        $this->assertTrue(has_action('admin_init'), 'Admin init action should be registered');
    }

    /**
     * Test filters and hooks
     */
    public function test_filters_and_hooks() {
        $this->assertTrue(has_filter('upload_mimes'), 'Upload mimes filter should be registered');
        $this->assertTrue(has_filter('query_vars'), 'Query vars filter should be registered');
        $this->assertTrue(has_action('wp_footer'), 'Footer action should be registered');
        $this->assertTrue(has_action('template_redirect'), 'Template redirect action should be registered');
    }

    /**
     * Test backward compatibility class
     */
    public function test_backward_compatibility() {
        $this->assertTrue(class_exists('AIInterviewWidget'), 'Backward compatibility class should exist');
        
        $old_instance = new \AIInterviewWidget();
        $this->assertInstanceOf('AIInterviewWidget', $old_instance, 'Old class should be instantiable');
    }
}