<?php

namespace Illuminate\Contracts\Routing;

use Illuminate\Routing\Route;

interface ControllerDispatcher
{
    /**
     * Dispatch a request to a given controller and method.
     *
     * @param  Route  $route
     * @param  mixed  $controller
     * @param  string  $method
     * @return mixed
     */
    public function dispatch(Route $route, $controller, $method);

    /**
     * Get the middleware for the controller instance.
     *
     * @param  \Illuminate\Routing\Controller  $controller
     * @param  string  $method
     * @return array
     */
    public function getMiddleware($controller, $method);
}
