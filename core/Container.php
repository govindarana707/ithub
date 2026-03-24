<?php

namespace Core;

use ReflectionClass;
use ReflectionException;

/**
 * Dependency Injection Container
 * 
 * Manages service dependencies and implements IoC pattern
 */
class Container
{
    private array $bindings = [];
    private array $instances = [];
    private array $singletons = [];

    /**
     * Bind a concrete implementation to an interface
     */
    public function bind(string $abstract, $concrete = null, bool $singleton = false): void
    {
        if ($concrete === null) {
            $concrete = $abstract;
        }

        $this->bindings[$abstract] = $concrete;
        
        if ($singleton) {
            $this->singletons[$abstract] = true;
        }
    }

    /**
     * Register a singleton
     */
    public function singleton(string $abstract, $concrete = null): void
    {
        $this->bind($abstract, $concrete, true);
    }

    /**
     * Resolve a dependency
     */
    public function make(string $abstract)
    {
        // Check if we have an existing instance
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        // Get the concrete implementation
        $concrete = $this->getConcrete($abstract);

        // Check if it's a singleton
        if (isset($this->singletons[$abstract])) {
            $instance = $this->build($concrete);
            $this->instances[$abstract] = $instance;
            return $instance;
        }

        return $this->build($concrete);
    }

    /**
     * Get concrete implementation
     */
    private function getConcrete(string $abstract)
    {
        return $this->bindings[$abstract] ?? $abstract;
    }

    /**
     * Build an instance of the concrete
     */
    private function build($concrete)
    {
        // If it's a closure, resolve it
        if ($concrete instanceof \Closure) {
            return $concrete($this);
        }

        try {
            $reflector = new ReflectionClass($concrete);
        } catch (ReflectionException $e) {
            throw new \RuntimeException("Cannot resolve {$concrete}: {$e->getMessage()}");
        }

        // Check if class is instantiable
        if (!$reflector->isInstantiable()) {
            throw new \RuntimeException("Class {$concrete} is not instantiable");
        }

        // Get constructor
        $constructor = $reflector->getConstructor();

        // If no constructor, instantiate directly
        if ($constructor === null) {
            return new $concrete();
        }

        // Get constructor parameters
        $parameters = $constructor->getParameters();
        $dependencies = $this->getDependencies($parameters);

        // Create new instance with dependencies
        return $reflector->newInstanceArgs($dependencies);
    }

    /**
     * Get dependencies for constructor parameters
     */
    private function getDependencies(array $parameters): array
    {
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $dependency = $parameter->getType();

            if ($dependency === null) {
                // If no type hint, try to get default value
                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                } else {
                    throw new \RuntimeException("Cannot resolve dependency for {$parameter->getName()}");
                }
            } elseif ($dependency->isBuiltin()) {
                // Built-in type, try to get default value
                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                } else {
                    throw new \RuntimeException("Cannot resolve built-in dependency for {$parameter->getName()}");
                }
            } else {
                // Resolve the dependency from the container
                $dependencies[] = $this->make($dependency->getName());
            }
        }

        return $dependencies;
    }

    /**
     * Check if a binding exists
     */
    public function has(string $abstract): bool
    {
        return isset($this->bindings[$abstract]);
    }

    /**
     * Register an instance
     */
    public function instance(string $abstract, $instance): void
    {
        $this->instances[$abstract] = $instance;
        $this->singletons[$abstract] = true;
    }

    /**
     * Call a method with dependency injection
     */
    public function call($callable, array $parameters = [])
    {
        if ($callable instanceof \Closure) {
            return $this->callClosure($callable, $parameters);
        } elseif (is_string($callable) && strpos($callable, '::') !== false) {
            return $this->callMethod($callable, $parameters);
        } elseif (is_array($callable)) {
            return $this->callMethod($callable, $parameters);
        }

        throw new \RuntimeException('Invalid callable provided');
    }

    /**
     * Call a closure with dependency injection
     */
    private function callClosure(\Closure $closure, array $parameters)
    {
        $reflection = new \ReflectionFunction($closure);
        $args = $this->getMethodDependencies($reflection, $parameters);
        
        return $closure(...$args);
    }

    /**
     * Call a method with dependency injection
     */
    private function callMethod($callable, array $parameters)
    {
        if (is_string($callable)) {
            $callable = explode('::', $callable);
        }

        [$instance, $method] = $callable;

        if (is_string($instance)) {
            $instance = $this->make($instance);
        }

        $reflection = new \ReflectionMethod($instance, $method);
        $args = $this->getMethodDependencies($reflection, $parameters);
        
        return $instance->$method(...$args);
    }

    /**
     * Get method dependencies
     */
    private function getMethodDependencies(\ReflectionFunctionAbstract $reflection, array $parameters): array
    {
        $args = [];
        
        foreach ($reflection->getParameters() as $param) {
            $name = $param->getName();
            
            if (isset($parameters[$name])) {
                $args[] = $parameters[$name];
            } elseif ($param->getType() && !$param->getType()->isBuiltin()) {
                $args[] = $this->make($param->getType()->getName());
            } elseif ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
            } else {
                throw new \RuntimeException("Cannot resolve dependency for {$name}");
            }
        }
        
        return $args;
    }

    /**
     * Clear all bindings and instances
     */
    public function flush(): void
    {
        $this->bindings = [];
        $this->instances = [];
        $this->singletons = [];
    }

    /**
     * Get all bindings
     */
    public function getBindings(): array
    {
        return $this->bindings;
    }

    /**
     * Get all instances
     */
    public function getInstances(): array
    {
        return $this->instances;
    }
}
