<?php

namespace Illuminate\Support\Hooks;

use Closure;
use Illuminate\Support\Collection;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;

class HookCollection extends Collection
{
    protected static array $cache = [];

    protected static array $registrars = [];

    public static function for($class)
    {
        if (is_object($class)) {
            $class = get_class($class);
        }

        return static::$cache[$class] ??= new static(static::loadHooks($class));
    }

    public static function clearCache()
    {
        static::$cache = [];
    }

    public static function register($className, Closure $callback)
    {
        static::$registrars[$className][] = $callback;
    }

    public static function registerTraitPrefix($className, $prefix)
    {
        static::register($className, function($hooks, $class) use ($prefix) {
            foreach (class_uses_recursive($class) as $trait) {
                $method = $prefix.class_basename($trait);

                if (method_exists($class, $method)) {
                    $hooks->push(new Hook($prefix, Closure::fromCallable([$class, $method])));
                }
            }
        });
    }

    protected static function loadHooks($class)
    {
        $classNames = array_merge([$class => $class], class_parents($class), class_implements($class));

        return collect((new ReflectionClass($class))->getMethods())
            ->map(fn($method) => static::hookForMethod($method, $classNames))
            ->filter();
    }

    protected static function hookForMethod(ReflectionMethod $method, array $classNames): ?Hook
    {
        if (static::methodReturnsHook($method)) {
            return $method->invoke(null);
        }

        return null;
    }

    protected static function methodReturnsHook(ReflectionMethod $method): bool
    {
        return $method->isStatic()
            && $method->getReturnType() instanceof ReflectionNamedType
            && $method->getReturnType()->getName() === Hook::class;
    }

    public function run($name, ...$arguments)
    {
        $this->where('name', $name)
            ->sortBy('weight')
            ->each(fn($hook) => $hook->run($arguments));
    }
}
