<?php namespace Illuminate\Routing; use Illuminate\Support\ServiceProvider; class RoutingServiceProvider extends ServiceProvider { public function register() { $this->registerRouter(); $this->registerUrlGenerator(); $this->registerRedirector(); } protected function registerRouter() { $this->app['router'] = $this->app->share(function($app) { $router = new Router($app['events'], $app); if ($app['env'] == 'testing') { $router->disableFilters(); } return $router; }); } protected function registerUrlGenerator() { $this->app['url'] = $this->app->share(function($app) { $routes = $app['router']->getRoutes(); return new UrlGenerator($routes, $app->rebinding('request', function($app, $request) { $app['url']->setRequest($request); })); }); } protected function registerRedirector() { $this->app['redirect'] = $this->app->share(function($app) { $redirector = new Redirector($app['url']); if (isset($app['session.store'])) { $redirector->setSession($app['session.store']); } return $redirector; }); } }
