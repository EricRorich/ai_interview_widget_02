<?php
/**
 * Main Plugin Bootstrap Class
 * 
 * Handles plugin initialization, service registration, and dependency management.
 * 
 * @package EricRorich\AIInterviewWidget
 * @since 2.0.0
 */

namespace EricRorich\AIInterviewWidget\Core;

use EricRorich\AIInterviewWidget\Admin\AdminService;
use EricRorich\AIInterviewWidget\Core\Assets;
use EricRorich\AIInterviewWidget\Setup\Requirements;
use EricRorich\AIInterviewWidget\Widgets\ElementorService;

/**
 * Main Plugin class - Service Container and Bootstrap
 * 
 * Coordinates plugin initialization and manages service dependencies.
 * 
 * @since 2.0.0
 */
class Plugin {

    /**
     * Plugin version
     * 
     * @var string
     */
    const VERSION = '2.0.0';

    /**
     * Plugin instance
     * 
     * @var Plugin
     */
    private static $instance = null;

    /**
     * Service instances
     * 
     * @var array
     */
    private $services = [];

    /**
     * Plugin initialized flag
     * 
     * @var bool
     */
    private $initialized = false;

    /**
     * Get plugin instance (singleton)
     * 
     * @return Plugin
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Private constructor to enforce singleton
     */
    private function __construct() {
        // Prevent direct instantiation
    }

    /**
     * Initialize the plugin
     * 
     * @return void
     */
    public function init() {
        if ($this->initialized) {
            return;
        }

        // Check requirements first
        $requirements = new Requirements();
        $requirements_check = $requirements->check();
        
        if (is_wp_error($requirements_check)) {
            $this->show_admin_notice($requirements_check->get_error_message(), 'error');
            return;
        }

        // Register services
        $this->register_services();

        // Initialize services
        $this->initialize_services();

        $this->initialized = true;
    }

    /**
     * Register all plugin services
     * 
     * @return void
     */
    private function register_services() {
        // Core services
        $this->services['assets'] = new Assets();
        
        // Admin services (only in admin)
        if (is_admin()) {
            $this->services['admin'] = new AdminService();
        }

        // Elementor service (when Elementor is active)
        if (did_action('elementor/loaded') || class_exists('\Elementor\Plugin')) {
            $this->services['elementor'] = new ElementorService();
        }
    }

    /**
     * Initialize all registered services
     * 
     * @return void
     */
    private function initialize_services() {
        foreach ($this->services as $service) {
            if (method_exists($service, 'init')) {
                $service->init();
            }
        }
    }

    /**
     * Get a registered service
     * 
     * @param string $service_name Service name
     * @return object|null Service instance or null if not found
     */
    public function get_service($service_name) {
        return isset($this->services[$service_name]) ? $this->services[$service_name] : null;
    }

    /**
     * Show admin notice
     * 
     * @param string $message Notice message
     * @param string $type Notice type (error, warning, info, success)
     * @return void
     */
    private function show_admin_notice($message, $type = 'info') {
        add_action('admin_notices', function() use ($message, $type) {
            printf(
                '<div class="notice notice-%s"><p><strong>AI Interview Widget:</strong> %s</p></div>',
                esc_attr($type),
                esc_html($message)
            );
        });
    }

    /**
     * Get plugin version
     * 
     * @return string
     */
    public function get_version() {
        return self::VERSION;
    }

    /**
     * Check if plugin is initialized
     * 
     * @return bool
     */
    public function is_initialized() {
        return $this->initialized;
    }
}