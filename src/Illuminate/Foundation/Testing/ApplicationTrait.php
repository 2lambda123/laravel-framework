<?php namespace Illuminate\Foundation\Testing;

use Illuminate\Http\Request;
use Illuminate\Contracts\Auth\Authenticatable as UserContract;
use Illuminate\Support\Facades\Facade;

trait ApplicationTrait {

	/**
	 * The Illuminate application instance.
	 *
	 * @var \Illuminate\Foundation\Application
	 */
	protected $app;

	/**
	 * The last response returned by the application.
	 *
	 * @var \Illuminate\Http\Response
	 */
	protected $response;

	/**
	 * Refresh the application instance.
	 *
	 * @return void
	 */
	protected function refreshApplication()
	{
		putenv('APP_ENV=testing');
		
		$this->app = $this->createApplication();
	}

	/**
	 * Call the given URI and return the Response.
	 *
	 * @param  string  $method
	 * @param  string  $uri
	 * @param  array   $parameters
	 * @param  array   $cookies
	 * @param  array   $files
	 * @param  array   $server
	 * @param  string  $content
	 * @return \Illuminate\Http\Response
	 */
	public function call($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
	{
		$request = Request::create($uri, $method, $parameters, $cookies, $files, $server, $content);

		$this->response = $this->app->make('Illuminate\Contracts\Http\Kernel')->handle($request);

		// Clear the resolved request instance, otherwise subsequent invocations of call()
		// in the same test will reuse the first request instance
		Facade::clearResolvedInstances();

		return $this->response;
	}

	/**
	 * Call the given HTTPS URI and return the Response.
	 *
	 * @param  string  $method
	 * @param  string  $uri
	 * @param  array   $parameters
	 * @param  array   $cookies
	 * @param  array   $files
	 * @param  array   $server
	 * @param  string  $content
	 * @return \Illuminate\Http\Response
	 */
	public function callSecure($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
	{
		$uri = 'https://localhost/'.ltrim($uri, '/');

		return $this->response = $this->call($method, $uri, $parameters, $cookies, $files, $server, $content);
	}

	/**
	 * Call a controller action and return the Response.
	 *
	 * @param  string  $method
	 * @param  string  $action
	 * @param  array   $wildcards
	 * @param  array   $parameters
	 * @param  array   $cookies
	 * @param  array   $files
	 * @param  array   $server
	 * @param  string  $content
	 * @return \Illuminate\Http\Response
	 */
	public function action($method, $action, $wildcards = [], $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
	{
		$uri = $this->app['url']->action($action, $wildcards, true);

		return $this->response = $this->call($method, $uri, $parameters, $cookies, $files, $server, $content);
	}

	/**
	 * Call a named route and return the Response.
	 *
	 * @param  string  $method
	 * @param  string  $name
	 * @param  array   $routeParameters
	 * @param  array   $parameters
	 * @param  array   $cookies
	 * @param  array   $files
	 * @param  array   $server
	 * @param  string  $content
	 * @return \Illuminate\Http\Response
	 */
	public function route($method, $name, $routeParameters = [], $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
	{
		$uri = $this->app['url']->route($name, $routeParameters);

		return $this->response = $this->call($method, $uri, $parameters, $cookies, $files, $server, $content);
	}

	/**
	 * Set the session to the given array.
	 *
	 * @param  array  $data
	 * @return void
	 */
	public function session(array $data)
	{
		$this->startSession();

		foreach ($data as $key => $value)
		{
			$this->app['session']->put($key, $value);
		}
	}

	/**
	 * Flush all of the current session data.
	 *
	 * @return void
	 */
	public function flushSession()
	{
		$this->startSession();

		$this->app['session']->flush();
	}

	/**
	 * Start the session for the application.
	 *
	 * @return void
	 */
	protected function startSession()
	{
		if ( ! $this->app['session']->isStarted())
		{
			$this->app['session']->start();
		}
	}

	/**
	 * Set the currently logged in user for the application.
	 *
	 * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
	 * @param  string  $driver
	 * @return void
	 */
	public function be(UserContract $user, $driver = null)
	{
		$this->app['auth']->driver($driver)->setUser($user);
	}

	/**
	 * Seed a given database connection.
	 *
	 * @param  string  $class
	 * @return void
	 */
	public function seed($class = 'DatabaseSeeder')
	{
		$this->app['Illuminate\Contracts\Console\Kernel']->call('db:seed', array('--class' => $class));
	}

}
