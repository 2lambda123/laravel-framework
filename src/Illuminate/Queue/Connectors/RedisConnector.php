<?php namespace Illuminate\Queue\Connectors;

use Illuminate\Redis\RedisInterface;
use Illuminate\Queue\RedisQueue;

class RedisConnector implements ConnectorInterface {

	/**
	* The Redis database instance.
	*
	 * @var \Illuminate\Redis\RedisInterface
	 */
	protected $redis;

	/**
	 * The connection name.
	 *
	 * @var string
	 */
	protected $connection;

	/**
	 * Create a new Redis queue connector instance.
	 *
	 * @param  \Illuminate\Redis\RedisInterface  $redis
	 * @param  string|null  $connection
	 * @return void
	 */
	public function __construct(RedisInterface $redis, $connection = null)
	{
		$this->redis = $redis;
		$this->connection = $connection;
	}

	/**
	 * Establish a queue connection.
	 *
	 * @param  array  $config
	 * @return \Illuminate\Queue\QueueInterface
	 */
	public function connect(array $config)
	{
		return new RedisQueue($this->redis, $config['queue'], $this->connection);
	}

}
