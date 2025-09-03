<?php
/**
 * Asset Manifest Test
 * 
 * Tests for asset manifest functionality.
 * 
 * @package EricRorich\AIInterviewWidget\Tests
 * @since 2.0.0
 */

namespace EricRorich\AIInterviewWidget\Tests\Integration;

use PHPUnit\Framework\TestCase;
use EricRorich\AIInterviewWidget\Core\Assets;

/**
 * Asset Manifest Test class
 * 
 * @since 2.0.0
 */
class AssetManifestEnqueueTest extends TestCase {

    private $assets;

    protected function setUp(): void {
        // Mock the constants
        if (!defined('AIW_VERSION')) {
            define('AIW_VERSION', '2.0.0');
        }
        if (!defined('AIW_PLUGIN_URL')) {
            define('AIW_PLUGIN_URL', 'https://example.com/wp-content/plugins/ai-interview-widget/');
        }
        if (!defined('AIW_PLUGIN_DIR')) {
            define('AIW_PLUGIN_DIR', '/path/to/plugin/');
        }

        $this->assets = new Assets();
    }

    public function test_manifest_asset_url_with_manifest() {
        // Create a mock manifest
        $manifest_data = [
            'frontend.js' => 'frontend.12345678.js',
            'frontend.css' => 'frontend.12345678.css'
        ];
        
        // Create temporary manifest file
        $manifest_path = sys_get_temp_dir() . '/test_manifest.json';
        file_put_contents($manifest_path, json_encode($manifest_data));
        
        // Use reflection to set the manifest
        $reflection = new \ReflectionClass($this->assets);
        $manifest_property = $reflection->getProperty('manifest');
        $manifest_property->setAccessible(true);
        $manifest_property->setValue($this->assets, $manifest_data);
        
        $url = $this->assets->get_manifest_asset_url('frontend.js');
        $this->assertStringContainsString('frontend.12345678.js', $url);
        $this->assertStringContainsString('assets/build/', $url);
        
        // Clean up
        if (file_exists($manifest_path)) {
            unlink($manifest_path);
        }
    }

    public function test_asset_version_from_manifest() {
        // Create a mock manifest with hashed filenames
        $manifest_data = [
            'frontend.js' => 'frontend.12345678.js',
            'admin.css' => 'admin.abcd1234.css'
        ];
        
        // Use reflection to set the manifest
        $reflection = new \ReflectionClass($this->assets);
        $manifest_property = $reflection->getProperty('manifest');
        $manifest_property->setAccessible(true);
        $manifest_property->setValue($this->assets, $manifest_data);
        
        $version = $this->assets->get_asset_version('frontend.js');
        $this->assertEquals('12345678', $version);
        
        $version = $this->assets->get_asset_version('admin.css');
        $this->assertEquals('abcd1234', $version);
    }

    public function test_fallback_asset_url_without_manifest() {
        // Use reflection to set empty manifest
        $reflection = new \ReflectionClass($this->assets);
        $manifest_property = $reflection->getProperty('manifest');
        $manifest_property->setAccessible(true);
        $manifest_property->setValue($this->assets, []);
        
        $url = $this->assets->get_manifest_asset_url('frontend.js');
        $this->assertStringContainsString('src/js/frontend.js', $url);
        $this->assertStringNotContainsString('build/', $url);
    }

    public function test_asset_version_fallback() {
        // Use reflection to set empty manifest
        $reflection = new \ReflectionClass($this->assets);
        $manifest_property = $reflection->getProperty('manifest');
        $manifest_property->setAccessible(true);
        $manifest_property->setValue($this->assets, []);
        
        $version = $this->assets->get_asset_version('frontend.js');
        $this->assertEquals(AIW_VERSION, $version);
    }
}