<?php namespace Illuminate\Pipeline;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Pipeline\Hub as HubContract;

class PipelineServiceProvider extends ServiceProvider {

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
		$this->app->singleton(HubContract::class, Hub::class);
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return [
			HubContract::class,
		];
	}

}
