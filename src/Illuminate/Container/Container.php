<?php

namespace Illuminate\Container;

use Closure;
use ReflectionException;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Container\Container as ContainerContract;

class Container extends ContainerAbstract implements ContainerContract
{
    use Traits\TagsTrait;
    use Traits\AliasTrait;
    use Traits\EventsTrait;
    use Traits\ExtendersTrait;
    use Traits\ContextualBindingsTrait;

    /**
     * Register a binding with the container.
     *
     * @param  string|array  $abstract
     * @param  \Closure|string|null  $concrete
     * @param  bool  $shared
     * @return void
     */
	public function bind($abstract, $concrete = null)
	{
        $abstract = self::normalize($abstract);
        $concrete = ($concrete) ? self::normalize($concrete) : $abstract;
        $parameters = ($concrete instanceof Closure) ? [$this] : [];

        if (is_array($abstract)) {
            $this->bindService(key($abstract), $concrete, $parameters);
            $this->alias(key($abstract), current($abstract));
        } else {
            $this->bindService($abstract, $concrete, $parameters);
        }
	}

    /**
     * Register a binding if it hasn't already been registered.
     *
     * @param  string  $abstract
     * @param  \Closure|string|null  $concrete
     * @param  bool  $shared
     * @return void
     */
    public function bindIf($abstract, $concrete = null)
    {
        if (!$this->bound($abstract)) {
            $this->bind($abstract, $concrete);
        }
    }

    /**
     * Register an existing instance as shared in the container.
     *
     * @param  string  $abstract
     * @param  mixed   $instance
     * @return void
     */
    public function instance($abstract, $instance)
    {
        $abstract = self::normalize($abstract);

        if (is_array($abstract)) {
            $this->bindPlain(key($abstract), $instance);
            $this->alias(key($abstract), current($abstract));
        } else {
            $this->bindPlain($abstract, $instance);
        }
    }

    /**
     * Register a shared binding in the container.
     *
     * @param  string|array  $abstract
     * @param  \Closure|string|null  $concrete
     * @return void
     */
    public function singleton($abstract, $concrete = null)
    {
        $abstract = self::normalize($abstract);
        $concrete = ($concrete) ? self::normalize($concrete) : $abstract;
        $parameters = ($concrete instanceof Closure) ? [$this] : [];

        if (is_array($abstract)) {
            $this->bindSingleton(key($abstract), $concrete, $parameters);
            $this->alias(key($abstract), current($abstract));
        } else {
            $this->bindSingleton($abstract, $concrete, $parameters);
        }
    }

    /**
     * Resolve the given type from the container.
     *
     * @param  mixed  $abstract
     * @param  array  $parameters
     * @return mixed
     */
    public function make($abstract, array $parameters = [])
    {
        $abstract = self::normalize($abstract);

        if (is_string($abstract) && strpos($abstract, '@')) {
            $parts = explode('@', $abstract, 2);

            return $this->resolve([$this->resolve($parts[0]), $parts[1]], $parameters);
        }

        return $this->resolve($abstract, $parameters);
    }

    /**
     * Resolve the given type from outside the container.
     *
     * @param  mixed  $abstract
     * @param  array  $parameters
     * @return mixed
     */
    public function call($abstract, array $parameters = [])
    {
        $resolver = new ContainerResolver();
        $abstract = self::normalize($abstract);

        if (is_string($abstract) && strpos($abstract, '@')) {
            $parts = explode('@', $abstract, 2);

            return $resolver->resolve([$resolver->resolve($parts[0]), $parts[1]], $parameters);
        }

        return $resolver->resolve($abstract, $parameters);
    }

    /**
     * Intercept the resolve call to add some features
     *
     * @param  mixed $abstract
     * @param  array  $parameters
     * @return mixed
     */
    public function resolve($abstract, array $parameters = [])
    {
        if ($this->isBinded($abstract)) {
            return $this->resolveBinded($abstract, $parameters);
        }

        return $this->resolveNonBinded($abstract, $parameters);
    }

    private function resolveBinded($abstract, array $parameters = [])
    {
        if (ContainerAbstract::isComputed($this->bindings[$abstract])) {
            return $this->bindings[$abstract][ContainerAbstract::VALUE];
        }

        $binding = $this->bindings[$abstract];
        $concrete = $binding[ContainerAbstract::VALUE];
        $resolved = parent::resolve($abstract, $parameters);

        $this->extendResolved($abstract, $resolved);
        $this->afterResolvingCallback($abstract, $concrete);

        return $resolved;
    }

    private function resolveNonBinded($concrete, array $parameters = [])
    {
        if ($concrete instanceof Closure) {
            array_unshift($parameters, $this);
        }

        $resolved = parent::resolve($concrete, $parameters);

        return $resolved;
    }

/*
    public function resolve($abstract, array $parameters = [])
    {
        $binding = ($this->isBinded($abstract)) ? $this->bindings[$abstract] : null;
        $concrete = ($binding) ? $binding[self::VALUE] : $abstract;

        if ($binding && $binding[self::IS_RESOLVED] && $binding[self::BINDING_TYPE] !== self::TYPE_SERVICE) {
            return $binding[self::VALUE];
        }
        if ($concrete instanceof Closure) {
            $parameters = [$this, $parameters];
        }

        $resolved = parent::resolve($abstract, $parameters);

        $resolved = $this->extendResolved($abstract, $resolved);

        $this->fireAfterResolving($abstract, $resolved);

        return $resolved;
    }
*/
    /**
     * Intercept the resolveParameter call to deal with contextual binding
     *
     * @param  \ReflectionParameter $parameter
     * @param  array                $parameters
     * @return mixed
     */
/*
    protected function resolveParameter(\ReflectionParameter $parameter, array $parameters = [])
    {
        $contextualBinding = $this->resolveContextualBinding($parameter);

        if ($contextualBinding) {
            try {
                return $this->make($contextualBinding, $parameters);
            } catch (\Exception $e){
                return $contextualBinding;
            }
        }

        return parent::resolveParameter($parameter, $parameters);
    }
*/

    /**
     * Determine if the given abstract type has been bound.
     *
     * @param  string  $abstract
     * @return bool
     */
    public function bound($abstract)
    {
        return $this->isBinded(self::normalize($abstract));
    }

    /**
     * Determine if the given abstract type has been resolved.
     *
     * @param  string $abstract
     * @return bool
     */
    public function resolved($abstract)
    {
        $abstract = self::normalize($abstract);

        return isset($this->bindings[$abstract]) && $this->bindings[$abstract][ContainerAbstract::IS_RESOLVED];
    }

    /**
     * Normalize the given class name by removing leading slashes.
     *
     * @param  mixed  $service
     * @return mixed
     */
    private static function normalize($service)
    {
        return is_string($service) ? ltrim($service, '\\') : $service;
    }
}
