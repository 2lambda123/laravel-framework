<?php namespace Illuminate\Cookie;

use Illuminate\Support\ServiceProvider;

class CookieServiceProvider extends ServiceProvider {

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{

		$this->app['cookie.defaults'] = $this->cookieDefaults();

		// The Illuminate cookie creator is just a convenient way to make cookies
		// that share a given set of options. Typically cookies created by the
		// application will have the same settings so this just DRY's it up.
		$this->app['cookie'] = $this->app->share(function($app)
		{
			$options = $app['cookie.defaults'];

			return new CookieJar($app['request'], $app['encrypter'], $options);
		});

        //now add an after filter to send the queued cookies
        $app = $this->app;
        $this->app->after(function($request, $response) use ($app)
        {
            $queuedCookies = $app['cookie']->getQueuedCookies();
            foreach ($queuedCookies as $cookie)
            {
                $response->headers->setCookie($cookie);
            }
        });

    }

	/**
	 * Get the default cookie options.
	 *
	 * @return array
	 */
	protected function cookieDefaults()
	{
		return array(
			'path' => '/', 'domain' => null, 'secure' => false, 'httpOnly' => true,
		);
	}

}