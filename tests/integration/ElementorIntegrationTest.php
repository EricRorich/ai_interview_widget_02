<?php
/**
 * Integration tests for Elementor functionality
 * 
 * @package EricRorich\AIInterviewWidget\Tests
 */

namespace EricRorich\AIInterviewWidget\Tests\Integration;

use WP_UnitTestCase;
use EricRorich\AIInterviewWidget\Widgets\ElementorService;
use EricRorich\AIInterviewWidget\Widgets\InterviewWidget;

/**
 * Test Elementor integration
 * 
 * Note: These tests will be skipped if Elementor is not available
 */
class ElementorIntegrationTest extends WP_UnitTestCase {

    /**
     * Set up test
     */
    public function setUp(): void {
        parent::setUp();
        
        // Skip tests if Elementor is not available
        if (!class_exists('\Elementor\Plugin')) {
            $this->markTestSkipped('Elementor is not available in test environment');
        }
    }

    /**
     * Test Elementor service initialization
     */
    public function test_elementor_service_initialization() {
        $service = new ElementorService();
        $this->assertInstanceOf(ElementorService::class, $service);
        
        // Test that init method exists and is callable
        $this->assertTrue(method_exists($service, 'init'), 'ElementorService should have init method');
    }

    /**
     * Test widget class structure
     */
    public function test_widget_class_structure() {
        // Only test if Elementor Widget_Base is available
        if (!class_exists('\Elementor\Widget_Base')) {
            $this->markTestSkipped('Elementor Widget_Base not available');
        }
        
        $widget = new InterviewWidget();
        $this->assertInstanceOf(InterviewWidget::class, $widget);
        $this->assertInstanceOf('\Elementor\Widget_Base', $widget);
        
        // Test required widget methods
        $this->assertEquals('ai-interview-widget', $widget->get_name());
        $this->assertIsString($widget->get_title());
        $this->assertIsString($widget->get_icon());
        $this->assertIsArray($widget->get_categories());
        $this->assertIsArray($widget->get_keywords());
    }

    /**
     * Test widget categories
     */
    public function test_widget_categories() {
        if (!class_exists('\Elementor\Widget_Base')) {
            $this->markTestSkipped('Elementor Widget_Base not available');
        }
        
        $widget = new InterviewWidget();
        $categories = $widget->get_categories();
        
        $this->assertContains('ai-interview-widgets', $categories, 'Widget should be in ai-interview-widgets category');
    }

    /**
     * Test widget keywords
     */
    public function test_widget_keywords() {
        if (!class_exists('\Elementor\Widget_Base')) {
            $this->markTestSkipped('Elementor Widget_Base not available');
        }
        
        $widget = new InterviewWidget();
        $keywords = $widget->get_keywords();
        
        $this->assertContains('ai', $keywords, 'Widget should have "ai" keyword');
        $this->assertContains('interview', $keywords, 'Widget should have "interview" keyword');
        $this->assertContains('chat', $keywords, 'Widget should have "chat" keyword');
    }

    /**
     * Test that Elementor hooks are registered when appropriate
     */
    public function test_elementor_hooks_registration() {
        $service = new ElementorService();
        $service->init();
        
        // Check if the appropriate actions are registered
        $this->assertTrue(
            has_action('elementor/widgets/widgets_registered', [$service, 'register_widgets']) ||
            has_action('elementor/widgets/register', [$service, 'register_widgets']),
            'Widget registration action should be hooked'
        );
        
        $this->assertTrue(
            has_action('elementor/elements/categories_registered', [$service, 'add_widget_categories']) ||
            has_action('elementor/init', [$service, 'add_widget_categories']),
            'Category registration action should be hooked'
        );
    }

    /**
     * Test widget template existence
     */
    public function test_widget_template_exists() {
        $template_path = AIW_PLUGIN_DIR . 'templates/widget-base.php';
        $this->assertFileExists($template_path, 'Widget template file should exist');
        
        // Test template contains expected elements
        $template_content = file_get_contents($template_path);
        $this->assertStringContainsString('ai-interview-widget', $template_content);
        $this->assertStringContainsString('aiw-container', $template_content);
        $this->assertStringContainsString('aiw-play-button', $template_content);
    }
}