<?php

namespace Illuminate\Support;

class ReadableHigherOrderCollectionProxy
{
    /**
     * The collection being operated on.
     *
     * @var \Illuminate\Support\Collection
     */
    protected $collection;

    /**
     * The method being proxied.
     *
     * @var string
     */
    protected $method;

    /**
     * Create a new proxy instance.
     *
     * @param  \Illuminate\Support\Collection  $collection
     * @param  string  $method
     * @return void
     */
    public function __construct(Collection $collection, $method)
    {
        $this->method = $method;
        $this->collection = $collection;
    }

    public static function perform(Collection $collection, $method)
    {
        $action = 'filter';

        foreach ($collection->getProxies() as $proxy) {
            if (starts_with($method, $proxy)) {
                $action = $proxy;
            }
        }

        $method = str_replace(Str::cartesian($collection->getProxies(), $collection->getFillers()), '', $method);

        $partial = new ReadableHigherOrderCollectionProxy($collection, $action);

        return $partial->{$method}();
    }

    /**
     * Proxy accessing an attribute onto the collection items.
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->collection->{$this->method}(function ($value) use ($key) {
            return is_array($value) ? $value[$key] : $value->{$key};
        });
    }

    /**
     * Proxy a method call onto the collection items.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->collection->{$this->method}(function ($value) use ($method, $parameters) {
            if (!method_exists($value, $method)) {
                $method = Str::snake($method);
            }

            return $value->{$method}(...$parameters);
        });
    }
}
