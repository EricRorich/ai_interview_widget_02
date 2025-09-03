<?php
/**
 * Plugin Requirements Checker
 * 
 * Validates that the environment meets plugin requirements.
 * 
 * @package EricRorich\AIInterviewWidget
 * @since 2.0.0
 */

namespace EricRorich\AIInterviewWidget\Setup;

/**
 * Requirements validation class
 * 
 * Checks PHP version, WordPress version, and required plugins.
 * 
 * @since 2.0.0
 */
class Requirements {

    /**
     * Minimum PHP version
     * 
     * @var string
     */
    const MIN_PHP_VERSION = '7.4';

    /**
     * Minimum WordPress version
     * 
     * @var string
     */
    const MIN_WP_VERSION = '5.0';

    /**
     * Minimum Elementor version (optional)
     * 
     * @var string
     */
    const MIN_ELEMENTOR_VERSION = '3.0';

    /**
     * Check all requirements
     * 
     * @return true|WP_Error True if all requirements met, WP_Error otherwise
     */
    public function check() {
        $errors = [];

        // Check PHP version
        if (!$this->check_php_version()) {
            $errors[] = sprintf(
                'PHP version %s or higher is required. You are running version %s.',
                self::MIN_PHP_VERSION,
                PHP_VERSION
            );
        }

        // Check WordPress version
        if (!$this->check_wp_version()) {
            $errors[] = sprintf(
                'WordPress version %s or higher is required. You are running version %s.',
                self::MIN_WP_VERSION,
                get_bloginfo('version')
            );
        }

        // Check if we have required functions
        if (!$this->check_required_functions()) {
            $errors[] = 'Required PHP functions are not available.';
        }

        // Return errors if any
        if (!empty($errors)) {
            return new \WP_Error('requirements_not_met', implode(' ', $errors));
        }

        return true;
    }

    /**
     * Check PHP version requirement
     * 
     * @return bool
     */
    private function check_php_version() {
        return version_compare(PHP_VERSION, self::MIN_PHP_VERSION, '>=');
    }

    /**
     * Check WordPress version requirement
     * 
     * @return bool
     */
    private function check_wp_version() {
        global $wp_version;
        return version_compare($wp_version, self::MIN_WP_VERSION, '>=');
    }

    /**
     * Check required PHP functions
     * 
     * @return bool
     */
    private function check_required_functions() {
        $required_functions = [
            'curl_init',
            'json_encode',
            'json_decode',
        ];

        foreach ($required_functions as $function) {
            if (!function_exists($function)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if Elementor is active and meets version requirement
     * 
     * @return bool
     */
    public function check_elementor() {
        if (!class_exists('\Elementor\Plugin')) {
            return false;
        }

        if (defined('ELEMENTOR_VERSION')) {
            return version_compare(ELEMENTOR_VERSION, self::MIN_ELEMENTOR_VERSION, '>=');
        }

        return true; // Assume OK if version not defined
    }

    /**
     * Get requirements info for display
     * 
     * @return array
     */
    public function get_requirements_info() {
        return [
            'php_version' => [
                'required' => self::MIN_PHP_VERSION,
                'current' => PHP_VERSION,
                'met' => $this->check_php_version()
            ],
            'wp_version' => [
                'required' => self::MIN_WP_VERSION,
                'current' => get_bloginfo('version'),
                'met' => $this->check_wp_version()
            ],
            'elementor' => [
                'required' => self::MIN_ELEMENTOR_VERSION,
                'current' => defined('ELEMENTOR_VERSION') ? ELEMENTOR_VERSION : 'Not installed',
                'met' => $this->check_elementor()
            ]
        ];
    }
}