<?php
/**
 * Elementor Integration Test
 * 
 * Tests for Elementor widget registration.
 * 
 * @package EricRorich\AIInterviewWidget\Tests
 * @since 2.0.0
 */

namespace EricRorich\AIInterviewWidget\Tests\Integration\Elementor;

use PHPUnit\Framework\TestCase;
use EricRorich\AIInterviewWidget\Integrations\Elementor\WidgetManager;
use EricRorich\AIInterviewWidget\Integrations\Elementor\InterviewWidget;
use EricRorich\AIInterviewWidget\Integrations\Elementor\InterviewListWidget;

/**
 * Elementor Integration Test class
 * 
 * @since 2.0.0
 */
class ElementorRegistrationTest extends TestCase {

    private $widget_manager;

    protected function setUp(): void {
        $this->widget_manager = new WidgetManager();
    }

    public function test_widget_manager_exists() {
        $this->assertInstanceOf(WidgetManager::class, $this->widget_manager);
    }

    public function test_interview_widget_properties() {
        $widget = new InterviewWidget();
        
        $this->assertEquals('ai_interview_widget', $widget->get_name());
        $this->assertStringContainsString('AI Interview', $widget->get_title());
        $this->assertContains('ai-interview', $widget->get_categories());
        $this->assertContains('aiw-elementor-widgets', $widget->get_script_depends());
    }

    public function test_interview_list_widget_properties() {
        $widget = new InterviewListWidget();
        
        $this->assertEquals('ai_interview_list_widget', $widget->get_name());
        $this->assertStringContainsString('Topics', $widget->get_title());
        $this->assertContains('ai-interview', $widget->get_categories());
        $this->assertContains('aiw-elementor-widgets', $widget->get_script_depends());
    }

    public function test_widget_keywords() {
        $interview_widget = new InterviewWidget();
        $keywords = $interview_widget->get_keywords();
        
        $this->assertIsArray($keywords);
        $this->assertContains('ai', $keywords);
        $this->assertContains('interview', $keywords);
        $this->assertContains('chat', $keywords);
    }

    public function test_widget_icons() {
        $interview_widget = new InterviewWidget();
        $list_widget = new InterviewListWidget();
        
        $this->assertIsString($interview_widget->get_icon());
        $this->assertIsString($list_widget->get_icon());
        $this->assertStringContainsString('eicon-', $interview_widget->get_icon());
        $this->assertStringContainsString('eicon-', $list_widget->get_icon());
    }
}