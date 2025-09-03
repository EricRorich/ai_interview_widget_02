<?php
/**
 * PHPUnit bootstrap file for AI Interview Widget tests
 * 
 * Loads WordPress test environment and plugin for testing.
 * 
 * @package EricRorich\AIInterviewWidget\Tests
 */

// Determine WordPress test library path
$wp_tests_dir = getenv('WP_TESTS_DIR');

if (!$wp_tests_dir) {
    // Common test library locations
    $possible_paths = [
        '/tmp/wordpress-tests-lib',
        '/tmp/wordpress-develop/tests/phpunit',
        dirname(__FILE__) . '/../../../../wordpress-tests-lib',
        dirname(__FILE__) . '/../../../wordpress-develop/tests/phpunit',
    ];
    
    foreach ($possible_paths as $path) {
        if (file_exists($path . '/includes/functions.php')) {
            $wp_tests_dir = $path;
            break;
        }
    }
}

if (!$wp_tests_dir || !file_exists($wp_tests_dir . '/includes/functions.php')) {
    echo "WordPress test library not found. Please set WP_TESTS_DIR environment variable or install WordPress test suite.\n";
    echo "You can install it using:\n";
    echo "bash bin/install-wp-tests.sh wordpress_test root '' localhost latest\n";
    exit(1);
}

// Give access to tests_add_filter() function
require_once $wp_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin being tested
 */
function _manually_load_plugin() {
    $plugin_dir = dirname(dirname(__FILE__));
    
    // Define constants that would normally be set by WordPress
    if (!defined('WP_PLUGIN_DIR')) {
        define('WP_PLUGIN_DIR', $plugin_dir);
    }
    
    // Load the plugin
    require $plugin_dir . '/ai-interview-widget.php';
}

tests_add_filter('muplugins_loaded', '_manually_load_plugin');

// Start up the WP testing environment
require $wp_tests_dir . '/includes/bootstrap.php';

// Load Yoast PHPUnit Polyfills if available
if (class_exists('Yoast\PHPUnitPolyfills\Autoload')) {
    // Already loaded via Composer
} elseif (file_exists(dirname(__DIR__) . '/vendor/yoast/phpunit-polyfills/phpunitpolyfills-autoload.php')) {
    require_once dirname(__DIR__) . '/vendor/yoast/phpunit-polyfills/phpunitpolyfills-autoload.php';
}

// Set up test environment
define('AIW_TESTING', true);

echo "WordPress test environment loaded.\n";
echo "Plugin directory: " . dirname(dirname(__FILE__)) . "\n";
echo "Test suite ready.\n\n";