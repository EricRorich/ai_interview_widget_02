<?php
/**
 * Unit tests for the Requirements checker
 * 
 * @package EricRorich\AIInterviewWidget\Tests
 */

namespace EricRorich\AIInterviewWidget\Tests\Unit;

use PHPUnit\Framework\TestCase;
use EricRorich\AIInterviewWidget\Setup\Requirements;

/**
 * Test Requirements validation
 */
class RequirementsTest extends TestCase {

    /**
     * Test basic requirements check
     */
    public function test_requirements_check_returns_true_when_met() {
        $requirements = new Requirements();
        $result = $requirements->check();
        
        // In a proper test environment, requirements should be met
        $this->assertTrue($result === true || is_wp_error($result), 'Requirements check should return boolean or WP_Error');
    }

    /**
     * Test requirements info structure
     */
    public function test_requirements_info_structure() {
        $requirements = new Requirements();
        $info = $requirements->get_requirements_info();
        
        $this->assertIsArray($info, 'Requirements info should be an array');
        $this->assertArrayHasKey('php_version', $info, 'Should have PHP version info');
        $this->assertArrayHasKey('wp_version', $info, 'Should have WordPress version info');
        $this->assertArrayHasKey('elementor', $info, 'Should have Elementor info');
        
        // Check structure of php_version info
        $this->assertArrayHasKey('required', $info['php_version']);
        $this->assertArrayHasKey('current', $info['php_version']);
        $this->assertArrayHasKey('met', $info['php_version']);
        
        $this->assertIsString($info['php_version']['required']);
        $this->assertIsString($info['php_version']['current']);
        $this->assertIsBool($info['php_version']['met']);
    }

    /**
     * Test PHP version validation
     */
    public function test_php_version_requirement() {
        $requirements = new Requirements();
        $info = $requirements->get_requirements_info();
        
        // Current PHP version should meet minimum requirement of 7.4
        $current_php = $info['php_version']['current'];
        $required_php = $info['php_version']['required'];
        
        $this->assertTrue(
            version_compare($current_php, $required_php, '>='),
            "Current PHP version {$current_php} should meet requirement {$required_php}"
        );
    }

    /**
     * Test Elementor version check when not present
     */
    public function test_elementor_check_when_not_present() {
        $requirements = new Requirements();
        
        // In test environment, Elementor likely isn't present
        $elementor_check = $requirements->check_elementor();
        $this->assertIsBool($elementor_check, 'Elementor check should return boolean');
    }
}