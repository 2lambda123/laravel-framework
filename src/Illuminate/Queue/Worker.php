<?php namespace Illuminate\Queue;

use Illuminate\Queue\Jobs\Job;
use Illuminate\Events\Dispatcher;
use Illuminate\Queue\Failed\FailedJobProviderInterface;

class Worker {

	/**
	 * THe queue manager instance.
	 *
	 * @var \Illuminate\Queue\QueueManager
	 */
	protected $manager;

	/**
	 * The failed job provider implementation.
	 *
	 * @var \Illuminate\Queue\Failed\FailedJobProviderInterface
	 */
	protected $failer;

	/**
	 * The event dispatcher instance.
	 *
	 * @var \Illuminate\Events\Dispatcher
	 */
	protected $events;

	/**
	 * Create a new queue worker.
	 *
	 * @param  \Illuminate\Queue\QueueManager  $manager
	 * @param  \Illuminate\Queue\Failed\FailedJobProviderInterface  $failer
	 * @param  \Illuminate\Events\Dispatcher  $events
	 * @return void
	 */
	public function __construct(QueueManager $manager,
                                FailedJobProviderInterface $failer = null,
                                Dispatcher $events = null)
	{
		$this->failer = $failer;
		$this->events = $events;
		$this->manager = $manager;
	}

	/**
	 * Listen to the given queue in a loop.
	 *
	 * @param  string  $connectionName
	 * @param  string  $queue
	 * @param  int     $delay
	 * @param  int     $memory
	 * @param  int     $sleep
	 * @param  int     $maxTries
	 * @return array
	 */
	public function daemon($connectionName, $queue = null, $delay = 0, $memory = 128, $sleep = 3, $maxTries = 0)
	{
		while (true)
		{
			if ($this->daemonShouldRun())
			{
				$this->pop($connectionName, $queue, $delay, $sleep, $maxTries);
			}
			else
			{
				$this->sleep($sleep);
			}

			if ($this->memoryExceeded($memory))
			{
				$this->stop();
			}
		}
	}

	/**
	 * Deteremine if the daemon should process on this iteration.
	 *
	 * @return bool
	 */
	protected function daemonShouldRun()
	{
		return $this->events->until('illuminate.queue.looping') !== false;
	}

	/**
	 * Listen to the given queue.
	 *
	 * @param  string  $connectionName
	 * @param  string  $queue
	 * @param  int     $delay
	 * @param  int     $sleep
	 * @param  int     $maxTries
	 * @return array
	 */
	public function pop($connectionName, $queue = null, $delay = 0, $sleep = 3, $maxTries = 0)
	{
		$connection = $this->manager->connection($connectionName);

		$job = $this->getNextJob($connection, $queue);

		// If we're able to pull a job off of the stack, we will process it and
		// then immediately return back out. If there is no job on the queue
		// we will "sleep" the worker for the specified number of seconds.
		if ( ! is_null($job))
		{
			return $this->process(
				$this->manager->getName($connectionName), $job, $maxTries, $delay
			);
		}
		else
		{
			$this->sleep($sleep);

			return ['job' => null, 'failed' => false];
		}
	}

	/**
	 * Get the next job from the queue connection.
	 *
	 * @param  \Illuminate\Queue\Queue  $connection
	 * @param  string  $queue
	 * @return \Illuminate\Queue\Jobs\Job|null
	 */
	protected function getNextJob($connection, $queue)
	{
		if (is_null($queue)) return $connection->pop();

		foreach (explode(',', $queue) as $queue)
		{
			if ( ! is_null($job = $connection->pop($queue))) return $job;
		}
	}

	/**
	 * Process a given job from the queue.
	 *
	 * @param  string  $connection
	 * @param  \Illuminate\Queue\Jobs\Job  $job
	 * @param  int  $maxTries
	 * @param  int  $delay
	 * @return void
	 *
	 * @throws \Exception
	 */
	public function process($connection, Job $job, $maxTries = 0, $delay = 0)
	{
		if ($maxTries > 0 && $job->attempts() > $maxTries)
		{
			return $this->logFailedJob($connection, $job);
		}

		try
		{
			// First we will fire off the job. Once it is done we will see if it will
			// be auto-deleted after processing and if so we will go ahead and run
			// the delete method on the job. Otherwise we will just keep moving.
			$job->fire();

			if ($job->autoDelete()) $job->delete();

			return ['job' => $job, 'failed' => false];
		}

		catch (\Exception $e)
		{
			// If we catch an exception, we will attempt to release the job back onto
			// the queue so it is not lost. This will let is be retried at a later
			// time by another listener (or the same one). We will do that here.
			if ( ! $job->isDeleted()) $job->release($delay);

			throw $e;
		}
	}

	/**
	 * Log a failed job into storage.
	 *
	 * @param  string  $connection
	 * @param  \Illuminate\Queue\Jobs\Job  $job
	 * @return array
	 */
	protected function logFailedJob($connection, Job $job)
	{
		if ($this->failer)
		{
			$this->failer->log($connection, $job->getQueue(), $job->getRawBody());

			$job->delete();

			$this->raiseFailedJobEvent($connection, $job);
		}

		return ['job' => $job, 'failed' => true];
	}

	/**
	 * Raise the failed queue job event.
	 *
	 * @param  string  $connection
	 * @param  \Illuminate\Queue\Jobs\Job  $job
	 * @return void
	 */
	protected function raiseFailedJobEvent($connection, Job $job)
	{
		if ($this->events)
		{
			$data = json_decode($job->getRawBody(), true);

			$this->events->fire('illuminate.queue.failed', array($connection, $job, $data));
		}
	}

	/**
	 * Determine if the memory limit has been exceeded.
	 *
	 * @param  int   $memoryLimit
	 * @return bool
	 */
	public function memoryExceeded($memoryLimit)
	{
		return (memory_get_usage() / 1024 / 1024) >= $memoryLimit;
	}

	/**
	 * Stop listening and bail out of the script.
	 *
	 * @return void
	 */
	public function stop()
	{
		die;
	}

	/**
	 * Sleep the script for a given number of seconds.
	 *
	 * @param  int   $seconds
	 * @return void
	 */
	public function sleep($seconds)
	{
		sleep($seconds);
	}

	/**
	 * Get the queue manager instance.
	 *
	 * @return \Illuminate\Queue\QueueManager
	 */
	public function getManager()
	{
		return $this->manager;
	}

	/**
	 * Set the queue manager instance.
	 *
	 * @param  \Illuminate\Queue\QueueManager  $manager
	 * @return void
	 */
	public function setManager(QueueManager $manager)
	{
		$this->manager = $manager;
	}

}
