<?php namespace Illuminate\Bus;

use Illuminate\Bus\Dispatcher;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Bus\QueueingDispatcher;
use Illuminate\Contracts\Bus\Dispatcher as DispatcherContract;

class BusServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = true;

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app->singleton(Dispatcher::class, function($app)
		{
			return new Dispatcher($app, function() use ($app)
			{
				return $app[Queue::class];
			});
		});

		$this->app->alias(Dispatcher::class, DispatcherContract::class);

		$this->app->alias(Dispatcher::class, QueueingDispatcher::class);
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return [
			Dispatcher::class,
			DispatcherContract::class,
			QueueingDispatcher::class,
		];
	}

}
