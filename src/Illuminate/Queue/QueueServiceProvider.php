<?php namespace Illuminate\Queue;

use Illuminate\Support\ServiceProvider;
use Illuminate\Queue\Console\WorkCommand;
use Illuminate\Queue\Console\ListenCommand;
use Illuminate\Queue\Connectors\SqsConnector;
use Illuminate\Queue\Console\SubscribeCommand;
use Illuminate\Queue\Console\UnsubscribeCommand;
use Illuminate\Queue\Console\UpdateCommand;
use Illuminate\Queue\Connectors\SyncConnector;
use Illuminate\Queue\Connectors\IronConnector;
use Illuminate\Queue\Connectors\RedisConnector;
use Illuminate\Queue\Connectors\BeanstalkdConnector;
use Illuminate\Queue\Failed\DatabaseFailedJobProvider;

class QueueServiceProvider extends ServiceProvider {

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
		$this->registerManager();

		$this->registerWorker();

		$this->registerListener();

		$this->registerSubscriber();

		$this->registerUnsubscriber();

		$this->registerUpdater();

		$this->registerFailedJobServices();
	}

	/**
	 * Register the queue manager.
	 *
	 * @return void
	 */
	protected function registerManager()
	{
		$this->app->bindShared('queue', function($app)
		{
			// Once we have an instance of the queue manager, we will register the various
			// resolvers for the queue connectors. These connectors are responsible for
			// creating the classes that accept queue configs and instantiate queues.
			$manager = new QueueManager($app);

			$this->registerConnectors($manager);

			return $manager;
		});
	}

	/**
	 * Register the queue worker.
	 *
	 * @return void
	 */
	protected function registerWorker()
	{
		$this->registerWorkCommand();

		$this->app->bindShared('queue.worker', function($app)
		{
			return new Worker($app['queue'], $app['queue.failer'], $app['events']);
		});
	}

	/**
	 * Register the queue worker console command.
	 *
	 * @return void
	 */
	protected function registerWorkCommand()
	{
		$this->app->bindShared('command.queue.work', function($app)
		{
			return new WorkCommand($app['queue.worker']);
		});

		$this->commands('command.queue.work');
	}

	/**
	 * Register the queue listener.
	 *
	 * @return void
	 */
	protected function registerListener()
	{
		$this->registerListenCommand();

		$this->app->bindShared('queue.listener', function($app)
		{
			return new Listener($app['path.base']);
		});
	}

	/**
	 * Register the queue listener console command.
	 *
	 * @return void
	 */
	protected function registerListenCommand()
	{
		$this->app->bindShared('command.queue.listen', function($app)
		{
			return new ListenCommand($app['queue.listener']);
		});

		$this->commands('command.queue.listen');
	}

	/**
	 * Register the push queue subscribe command.
	 *
	 * @return void
	 */
	protected function registerSubscriber()
	{
		$this->app->bindShared('command.queue.subscribe', function($app)
		{
			return new SubscribeCommand;
		});

		$this->commands('command.queue.subscribe');
	}

	/**
	 * Register the push queue unsubscribe command.
	 *
	 * @return void
	 */
	protected function registerUnsubscriber()
	{
		$this->app->bindShared('command.queue.unsubscribe', function($app)
		{
			return new UnsubscribeCommand;
		});

		$this->commands('command.queue.unsubscribe');
	}

	/**
	 * Register the push queue update command.
	 *
	 * @return void
	 */
	protected function registerUpdater()
	{
		$this->app->bindShared('command.queue.update', function($app)
		{
			return new UpdateCommand;
		});

		$this->commands('command.queue.update');
	}

	/**
	 * Register the connectors on the queue manager.
	 *
	 * @param  \Illuminate\Queue\QueueManager  $manager
	 * @return void
	 */
	public function registerConnectors($manager)
	{
		foreach (array('Sync', 'Beanstalkd', 'Redis', 'Sqs', 'Iron') as $connector)
		{
			$this->{"register{$connector}Connector"}($manager);
		}
	}

	/**
	 * Register the Sync queue connector.
	 *
	 * @param  \Illuminate\Queue\QueueManager  $manager
	 * @return void
	 */
	protected function registerSyncConnector($manager)
	{
		$manager->addConnector('sync', function()
		{
			return new SyncConnector;
		});
	}

	/**
	 * Register the Beanstalkd queue connector.
	 *
	 * @param  \Illuminate\Queue\QueueManager  $manager
	 * @return void
	 */
	protected function registerBeanstalkdConnector($manager)
	{
		$manager->addConnector('beanstalkd', function()
		{
			return new BeanstalkdConnector;
		});
	}

	/**
	 * Register the Redis queue connector.
	 *
	 * @param  \Illuminate\Queue\QueueManager  $manager
	 * @return void
	 */
	protected function registerRedisConnector($manager)
	{
		$app = $this->app;	

		$manager->addConnector('redis', function() use ($app)
		{
			return new RedisConnector($app['redis']);
		});
	}

	/**
	 * Register the Amazon SQS queue connector.
	 *
	 * @param  \Illuminate\Queue\QueueManager  $manager
	 * @return void
	 */
	protected function registerSqsConnector($manager)
	{
		$app = $this->app;
	
		$manager->addConnector('sqs', function() use ($app)
		{
			return new SqsConnector($app['request']);
		});

		$this->registerRequestBinder('sqs');
	}

	/**
	 * Register the IronMQ queue connector.
	 *
	 * @param  \Illuminate\Queue\QueueManager  $manager
	 * @return void
	 */
	protected function registerIronConnector($manager)
	{
		$app = $this->app;

		$manager->addConnector('iron', function() use ($app)
		{
			return new IronConnector($app['encrypter'], $app['request']);
		});

		$this->registerRequestBinder('iron');
	}

	/**
	 * Register the request rebinding event for the push queue.
	 *
	 * @param string $driver
	 * @return void
	 */
	protected function registerRequestBinder($driver)
	{
		$this->app->rebinding('request', function($app, $request) use ($driver)
		{
			if ($app['queue']->connected($driver))
			{
				$app['queue']->connection($driver)->setRequest($request);
			}
		});
	}

	/**
	 * Register the failed job services.
	 *
	 * @return void
	 */
	protected function registerFailedJobServices()
	{
		$this->app->bindShared('queue.failer', function($app)
		{
			$config = $app['config']['queue.failed'];

			return new DatabaseFailedJobProvider($app['db'], $config['database'], $config['table']);
		});
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array(
			'queue', 'queue.worker', 'queue.listener', 'queue.failer',
			'command.queue.work', 'command.queue.listen', 'command.queue.subscribe',
			'command.queue.unsubscribe', 'command.queue.update'
		);
	}

}
