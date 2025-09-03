<?php
/**
 * Service Provider Interface
 * 
 * Contract for service providers in the plugin architecture.
 * 
 * @package EricRorich\AIInterviewWidget
 * @since 2.0.0
 */

namespace EricRorich\AIInterviewWidget\Core\Contracts;

use EricRorich\AIInterviewWidget\Core\Container;

/**
 * Service Provider interface
 * 
 * Defines the contract for service registration and bootstrapping.
 * 
 * @since 2.0.0
 */
interface ServiceProviderInterface {

    /**
     * Register services with the container
     * 
     * @param Container $container The service container
     * @return void
     */
    public function register(Container $container);

    /**
     * Boot services after all providers are registered
     * 
     * @param Container $container The service container
     * @return void
     */
    public function boot(Container $container);
}