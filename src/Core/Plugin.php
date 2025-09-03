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
use EricRorich\AIInterviewWidget\Setup\UpgradeServiceProvider;
use EricRorich\AIInterviewWidget\Admin\AdminServiceProvider;

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
     * Dependency injection container
     * 
     * @var Container
     */
    private $container;

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
        // Initialize container
        $container = new Container();
        
        // Register core services
        $container->singleton('assets', function() {
            return new Assets();
        });
        
        // Register service providers
        $providers = apply_filters('ai_interview_widget_service_providers', [
            new I18nServiceProvider(),
            new UpgradeServiceProvider(),
            new \EricRorich\AIInterviewWidget\Frontend\FrontendServiceProvider(),
        ]);
        
        // Add admin provider only in admin
        if (is_admin()) {
            $providers[] = new AdminServiceProvider();
        }
        
        // Add Elementor provider when available
        if (did_action('elementor/loaded') || class_exists('\Elementor\Plugin')) {
            $providers[] = new \EricRorich\AIInterviewWidget\Integrations\Elementor\ElementorServiceProvider();
        }
        
        // Register all providers
        foreach ($providers as $provider) {
            $provider->register($container);
        }
        
        // Boot all providers
        foreach ($providers as $provider) {
            $provider->boot($container);
        }
        
        $this->container = $container;
        
        // Legacy services for backward compatibility
        $this->services['assets'] = $container->make('assets');
        
        if (is_admin() && $container->has('admin.service')) {
            $this->services['admin'] = $container->make('admin.service');
        }
        
        if ($container->has('elementor.widget_manager')) {
            $this->services['elementor'] = $container->make('elementor.widget_manager');
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
     * Get the dependency injection container
     * 
     * @return Container
     */
    public function get_container() {
        return $this->container;
    }

    /**
     * Check if plugin is initialized
     * 
     * @return bool
     */
    public function is_initialized() {
        return $this->initialized;
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
}