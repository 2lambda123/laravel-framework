<?php

namespace Illuminate\Routing;

use Illuminate\Support\Arr;

class RouteParameterBinder
{
    /**
     * The route instance.
     *
     * @var \Illuminate\Routing\Route
     */
    protected $route;

    /**
     * Create a new Route parameter binder instance.
     *
     * @param  \Illuminate\Routing\Route  $route
     * @return void
     */
    public function __construct($route)
    {
        $this->route = $route;
    }

    /**
     * Get the parameters for the route.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function parameters($request)
    {
        // If the route has a regular expression for the host part of the URI, we will
        // compile that and get the parameter matches for this domain. We will then
        // merge them into this parameters array so that this array is completed.
        $parameters = $this->bindPathParameters($request);

        // If the route has a regular expression for the host part of the URI, we will
        // compile that and get the parameter matches for this domain. We will then
        // merge them into this parameters array so that this array is completed.
        if (! is_null($this->route->compiled->getHostRegex())) {
            $parameters = $this->bindHostParameters(
                $request, $parameters
            );
        }

        return $this->replaceDefaults($parameters);
    }

    /**
     * Get the parameter matches for the path portion of the URI.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    protected function bindPathParameters($request)
    {
        preg_match($this->route->compiled->getRegex(), '/'.$request->decodedPath(), $matches);

        return $this->matchToKeys(array_slice($matches, 1));
    }

    /**
     * Extract the parameter list from the host part of the request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  array  $parameters
     * @return array
     */
    protected function bindHostParameters($request, $parameters)
    {
        preg_match($this->route->compiled->getHostRegex(), $request->getHost(), $matches);

        return array_merge($this->matchToKeys(array_slice($matches, 1)), $parameters);
    }

    /**
     * Combine a set of parameter matches with the route's keys.
     *
     * @param  array  $matches
     * @return array
     */
    protected function matchToKeys(array $matches)
    {
        if (empty($parameterNames = $this->route->parameterNames())) {
            return [];
        }

        $parameters = [];

        foreach ($parameterNames as $parameter) {
            $parameter = new RouteParameter($parameter);

            if (array_key_exists($parameter->name(), $matches)) {
                $parameters[$parameter->parameter()] = $matches[$parameter->name()];
            }
        }

        return $parameters;
    }

    /**
     * Replace null parameters with their defaults.
     *
     * @param  array  $parameters
     * @return array
     */
    protected function replaceDefaults(array $parameters)
    {
        foreach ($parameters as $key => $value) {
            $parameters[$key] = isset($value) ? $value : Arr::get($this->route->defaults, $key);
        }

        foreach ($this->route->defaults as $key => $value) {
            if (! isset($parameters[$key])) {
                $parameters[$key] = $value;
            }
        }

        return $parameters;
    }
}
