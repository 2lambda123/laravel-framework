<?php namespace Illuminate\Filesystem;

use Illuminate\Support\ServiceProvider;
use Illuminate\Filesystem\Adapters\ConnectionFactory as Factory;

class FilesystemServiceProvider extends ServiceProvider {

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->registerNativeFilesystem();

		$this->registerFlysystem();
	}

	/**
	 * Register the native filesystem implementation.
	 *
	 * @return void
	 */
	protected function registerNativeFilesystem()
	{
		$this->app->bindShared('files', function() { return new Filesystem; });
	}

	/**
	 * Register the driver based filesystem.
	 *
	 * @return void
	 */
	protected function registerFlysystem()
	{
		$this->registerFactory();

		$this->registerManager();

		$this->app->bindShared('filesystem.disk', function()
		{
			return $this->app['filesystem']->disk($this->getDefaultDriver());
		});

		$this->app->bindShared('filesystem.cloud', function()
		{
			return $this->app['filesystem']->disk($this->getCloudDriver());
		});
	}

	/**
	 * Register the filesystem factory.
	 *
	 * @return void
	 */
	protected function registerFactory()
	{
		$this->app->bindShared('filesystem.factory', function()
		{
			return new Factory();
		});
	}

	/**
	 * Register the filesystem manager.
	 *
	 * @return void
	 */
	protected function registerManager()
	{
		$this->app->bindShared('filesystem', function()
		{
			return new FilesystemManager($this->app, $app['filesystem.factory']);
		});
	}

	/**
	 * Get the default file driver.
	 *
	 * @return string
	 */
	protected function getDefaultDriver()
	{
		return $this->app['config']['filesystems.default'];
	}

	/**
	 * Get the default cloud based file driver.
	 *
	 * @return string
	 */
	protected function getCloudDriver()
	{
		return $this->app['config']['filesystems.cloud'];
	}

}
