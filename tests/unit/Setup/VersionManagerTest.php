<?php
/**
 * Version Manager Test
 * 
 * Tests for the upgrade version management.
 * 
 * @package EricRorich\AIInterviewWidget\Tests
 * @since 2.0.0
 */

namespace EricRorich\AIInterviewWidget\Tests\Unit\Setup;

use PHPUnit\Framework\TestCase;
use EricRorich\AIInterviewWidget\Setup\Upgrade\VersionManager;
use EricRorich\AIInterviewWidget\Setup\Migrations\Migration_200;

/**
 * Version Manager Test class
 * 
 * @since 2.0.0
 */
class VersionManagerTest extends TestCase {

    private $version_manager;
    private $test_option_name = 'test_ai_interview_widget_version';
    private $test_migrations_option = 'test_ai_interview_widget_migrations';

    protected function setUp(): void {
        $this->version_manager = new VersionManager('2.0.0');
        
        // Override option names for testing
        $reflection = new \ReflectionClass($this->version_manager);
        $version_option = $reflection->getConstant('VERSION_OPTION');
        $migrations_option = $reflection->getConstant('MIGRATIONS_OPTION');
        
        // Clean up any existing test data
        delete_option($this->test_option_name);
        delete_option($this->test_migrations_option);
    }

    protected function tearDown(): void {
        // Clean up test options
        delete_option($this->test_option_name);
        delete_option($this->test_migrations_option);
    }

    public function test_get_stored_version_default() {
        $version = $this->version_manager->getStoredVersion();
        $this->assertEquals('0.0.0', $version);
    }

    public function test_update_stored_version() {
        $result = $this->version_manager->updateStoredVersion('1.5.0');
        $this->assertTrue($result);
        
        $version = $this->version_manager->getStoredVersion();
        $this->assertEquals('1.5.0', $version);
    }

    public function test_needs_upgrade() {
        // No version stored, should need upgrade
        $this->assertTrue($this->version_manager->needsUpgrade());
        
        // Same version, should not need upgrade
        $this->version_manager->updateStoredVersion('2.0.0');
        $this->assertFalse($this->version_manager->needsUpgrade());
        
        // Lower version, should need upgrade
        $this->version_manager->updateStoredVersion('1.0.0');
        $this->assertTrue($this->version_manager->needsUpgrade());
    }

    public function test_get_current_version() {
        $version = $this->version_manager->getCurrentVersion();
        $this->assertEquals('2.0.0', $version);
    }

    public function test_migration_history() {
        $history = $this->version_manager->getMigrationHistory();
        $this->assertIsArray($history);
        $this->assertEmpty($history);
        
        $test_history = ['1.0.0', '1.5.0'];
        $result = $this->version_manager->updateMigrationHistory($test_history);
        $this->assertTrue($result);
        
        $retrieved_history = $this->version_manager->getMigrationHistory();
        $this->assertEquals($test_history, $retrieved_history);
    }

    public function test_clear_migration_history() {
        $this->version_manager->updateMigrationHistory(['1.0.0']);
        $this->assertNotEmpty($this->version_manager->getMigrationHistory());
        
        $result = $this->version_manager->clearMigrationHistory();
        $this->assertTrue($result);
        
        $history = $this->version_manager->getMigrationHistory();
        $this->assertEmpty($history);
    }

    public function test_reset_version() {
        $this->version_manager->updateStoredVersion('1.0.0');
        $this->assertEquals('1.0.0', $this->version_manager->getStoredVersion());
        
        $result = $this->version_manager->resetVersion();
        $this->assertTrue($result);
        
        $version = $this->version_manager->getStoredVersion();
        $this->assertEquals('0.0.0', $version);
    }
}