<?php
/**
 * Uninstall script for AI Interview Widget
 * 
 * This file is executed when the plugin is uninstalled (deleted) from WordPress.
 * It handles complete cleanup of all plugin data.
 * 
 * @package EricRorich\AIInterviewWidget
 * @since 2.0.0
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Load the uninstaller class
require_once plugin_dir_path(__FILE__) . 'src/Setup/Uninstaller.php';

// Run the uninstall process
\EricRorich\AIInterviewWidget\Setup\Uninstaller::uninstall();