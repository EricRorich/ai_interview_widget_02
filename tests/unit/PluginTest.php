<?php
/**
 * Unit tests for the Plugin core class
 * 
 * @package EricRorich\AIInterviewWidget\Tests
 */

namespace EricRorich\AIInterviewWidget\Tests\Unit;

use PHPUnit\Framework\TestCase;
use EricRorich\AIInterviewWidget\Core\Plugin;

/**
 * Test Plugin core functionality
 */
class PluginTest extends TestCase {

    /**
     * Test plugin singleton pattern
     */
    public function test_plugin_singleton() {
        $instance1 = Plugin::get_instance();
        $instance2 = Plugin::get_instance();
        
        $this->assertInstanceOf(Plugin::class, $instance1);
        $this->assertSame($instance1, $instance2, 'Plugin should be a singleton');
    }

    /**
     * Test plugin version
     */
    public function test_plugin_version() {
        $plugin = Plugin::get_instance();
        $version = $plugin->get_version();
        
        $this->assertIsString($version);
        $this->assertNotEmpty($version);
        $this->assertEquals('2.0.0', $version);
    }

    /**
     * Test plugin initialization state
     */
    public function test_plugin_initialization_state() {
        $plugin = Plugin::get_instance();
        
        // Should not be initialized initially
        $this->assertFalse($plugin->is_initialized(), 'Plugin should not be initialized initially');
        
        // After init, should be initialized
        $plugin->init();
        $this->assertTrue($plugin->is_initialized(), 'Plugin should be initialized after init()');
    }

    /**
     * Test service registration
     */
    public function test_service_registration() {
        $plugin = Plugin::get_instance();
        $plugin->init();
        
        // Assets service should be registered
        $assets_service = $plugin->get_service('assets');
        $this->assertNotNull($assets_service, 'Assets service should be registered');
        $this->assertInstanceOf('EricRorich\AIInterviewWidget\Core\Assets', $assets_service);
    }

    /**
     * Test non-existent service returns null
     */
    public function test_non_existent_service() {
        $plugin = Plugin::get_instance();
        $plugin->init();
        
        $non_existent = $plugin->get_service('non_existent_service');
        $this->assertNull($non_existent, 'Non-existent service should return null');
    }
}