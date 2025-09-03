<?php
/**
 * Dependency Injection Container
 * 
 * Lightweight DI container for managing service dependencies.
 * 
 * @package EricRorich\AIInterviewWidget
 * @since 2.0.0
 */

namespace EricRorich\AIInterviewWidget\Core;

use ReflectionClass;
use ReflectionException;

/**
 * Simple DI Container implementation
 * 
 * Provides service binding, singleton pattern, and basic dependency resolution.
 * 
 * @since 2.0.0
 */
class Container {

    /**
     * Service bindings
     * 
     * @var array
     */
    private $bindings = [];

    /**
     * Singleton instances
     * 
     * @var array
     */
    private $instances = [];

    /**
     * Shared instances (singletons)
     * 
     * @var array
     */
    private $shared = [];

    /**
     * Bind a service to the container
     * 
     * @param string $abstract Service identifier
     * @param mixed $concrete Service implementation (closure, class name, or instance)
     * @param bool $shared Whether this should be a singleton
     * @return void
     */
    public function bind(string $abstract, $concrete = null, bool $shared = false) {
        if (is_null($concrete)) {
            $concrete = $abstract;
        }

        $this->bindings[$abstract] = compact('concrete', 'shared');

        if ($shared) {
            $this->shared[$abstract] = true;
        }
    }

    /**
     * Register a singleton binding
     * 
     * @param string $abstract Service identifier
     * @param mixed $concrete Service implementation
     * @return void
     */
    public function singleton(string $abstract, $concrete = null) {
        $this->bind($abstract, $concrete, true);
    }

    /**
     * Check if a service is bound
     * 
     * @param string $abstract Service identifier
     * @return bool
     */
    public function has(string $abstract): bool {
        return isset($this->bindings[$abstract]) || isset($this->instances[$abstract]);
    }

    /**
     * Resolve a service from the container
     * 
     * @param string $abstract Service identifier
     * @param array $parameters Constructor parameters
     * @return mixed
     * @throws \Exception
     */
    public function make(string $abstract, array $parameters = []) {
        // Return existing singleton instance
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        $concrete = $this->getConcrete($abstract);

        // Build the service
        if ($this->isBuildable($concrete, $abstract)) {
            $object = $this->build($concrete, $parameters);
        } else {
            $object = $this->make($concrete, $parameters);
        }

        // Store singleton instances
        if ($this->isShared($abstract)) {
            $this->instances[$abstract] = $object;
        }

        return $object;
    }

    /**
     * Get the concrete implementation for an abstract service
     * 
     * @param string $abstract Service identifier
     * @return mixed
     */
    protected function getConcrete(string $abstract) {
        if (isset($this->bindings[$abstract])) {
            return $this->bindings[$abstract]['concrete'];
        }

        return $abstract;
    }

    /**
     * Determine if the concrete is buildable
     * 
     * @param mixed $concrete
     * @param string $abstract
     * @return bool
     */
    protected function isBuildable($concrete, string $abstract): bool {
        return $concrete === $abstract || is_callable($concrete);
    }

    /**
     * Check if a service should be shared (singleton)
     * 
     * @param string $abstract Service identifier
     * @return bool
     */
    protected function isShared(string $abstract): bool {
        return isset($this->shared[$abstract]) || 
               (isset($this->bindings[$abstract]['shared']) && $this->bindings[$abstract]['shared']);
    }

    /**
     * Build a service instance
     * 
     * @param mixed $concrete Service implementation
     * @param array $parameters Constructor parameters
     * @return mixed
     * @throws \Exception
     */
    protected function build($concrete, array $parameters = []) {
        // If it's a closure, call it
        if (is_callable($concrete)) {
            return call_user_func_array($concrete, [$this]);
        }

        // Try to resolve class via reflection
        try {
            $reflector = new ReflectionClass($concrete);
        } catch (ReflectionException $e) {
            throw new \Exception("Target class [{$concrete}] does not exist.", 0, $e);
        }

        if (!$reflector->isInstantiable()) {
            throw new \Exception("Target [{$concrete}] is not instantiable.");
        }

        $constructor = $reflector->getConstructor();

        // No constructor, just instantiate
        if (is_null($constructor)) {
            return new $concrete;
        }

        $dependencies = $constructor->getParameters();

        // Resolve constructor dependencies
        $instances = $this->resolveDependencies($dependencies, $parameters);

        return $reflector->newInstanceArgs($instances);
    }

    /**
     * Resolve constructor dependencies
     * 
     * @param array $dependencies Constructor parameters
     * @param array $parameters Provided parameters
     * @return array
     * @throws \Exception
     */
    protected function resolveDependencies(array $dependencies, array $parameters = []): array {
        $results = [];

        foreach ($dependencies as $dependency) {
            $paramName = $dependency->getName();

            // Use provided parameter if available
            if (array_key_exists($paramName, $parameters)) {
                $results[] = $parameters[$paramName];
                continue;
            }

            // Try to resolve by type hint
            $type = $dependency->getType();
            
            if ($type && !$type->isBuiltin()) {
                $results[] = $this->make($type->getName());
                continue;
            }

            // Use default value if available
            if ($dependency->isDefaultValueAvailable()) {
                $results[] = $dependency->getDefaultValue();
                continue;
            }

            throw new \Exception("Unable to resolve dependency [{$paramName}]");
        }

        return $results;
    }

    /**
     * Get all registered bindings
     * 
     * @return array
     */
    public function getBindings(): array {
        return $this->bindings;
    }

    /**
     * Flush all bindings and instances
     * 
     * @return void
     */
    public function flush() {
        $this->bindings = [];
        $this->instances = [];
        $this->shared = [];
    }
}