<?php

use Illuminate\Pipeline\Pipeline;
use Mockery as m;
use Illuminate\Bus\Dispatcher;
use Illuminate\Container\Container;

class BusDispatcherTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testBasicDispatchingOfCommandsToHandlers()
	{
		$container = new Container;
		$pipeline = new Pipeline($container);
		$handler = m::mock('StdClass');
		$handler->shouldReceive('handle')->once()->andReturn('foo');
		$container->instance('Handler', $handler);
		$dispatcher = new Dispatcher($container, $pipeline);
		$dispatcher->mapUsing(function() { return 'Handler@handle'; });

		$result = $dispatcher->dispatch(new BusDispatcherTestBasicCommand);
		$this->assertEquals('foo', $result);
	}


	public function testCommandsThatShouldBeQueuedAreQueued()
	{
		$container = new Container;
		$pipeline = new Pipeline($container);
		$dispatcher = new Dispatcher($container, $pipeline, function() {
			$mock = m::mock('Illuminate\Contracts\Queue\Queue');
			$mock->shouldReceive('push')->once();
			return $mock;
		});

		$dispatcher->dispatch(m::mock('Illuminate\Contracts\Queue\ShouldBeQueued'));
	}


	public function testCommandsThatShouldBeQueuedAreQueuedUsingCustomHandler()
	{
		$container = new Container;
		$pipeline = new Pipeline($container);
		$dispatcher = new Dispatcher($container, $pipeline, function() {
			$mock = m::mock('Illuminate\Contracts\Queue\Queue');
			$mock->shouldReceive('push')->once();
			return $mock;
		});

		$dispatcher->dispatch(new BusDispatcherTestCustomQueueCommand);
	}


	public function testHandlersThatShouldBeQueuedAreQueued()
	{
		$container = new Container;
		$pipeline = new Pipeline($container);
		$dispatcher = new Dispatcher($container, $pipeline, function() {
			$mock = m::mock('Illuminate\Contracts\Queue\Queue');
			$mock->shouldReceive('push')->once();
			return $mock;
		});
		$dispatcher->mapUsing(function() { return 'BusDispatcherTestQueuedHandler@handle'; });

		$dispatcher->dispatch(new BusDispatcherTestBasicCommand);
	}


	public function testDispatchNowShouldNeverQueue()
	{
		$container = new Container;
		$pipeline = new Pipeline($container);
		$handler = m::mock('StdClass');
		$handler->shouldReceive('handle')->once()->andReturn('foo');
		$container->instance('Handler', $handler);
		$dispatcher = new Dispatcher($container, $pipeline);
		$dispatcher->mapUsing(function() { return 'Handler@handle'; });

		$result = $dispatcher->dispatch(m::mock('Illuminate\Contracts\Queue\ShouldBeQueued'));
		$this->assertEquals('foo', $result);
	}


	public function testDispatchShouldCallAfterResolvingIfCommandNotQueued()
	{
		$container = new Container;
		$pipeline = new Pipeline($container);
		$handler = m::mock('StdClass')->shouldIgnoreMissing();
		$handler->shouldReceive('after')->once();
		$container->instance('Handler', $handler);
		$dispatcher = new Dispatcher($container, $pipeline);
		$dispatcher->mapUsing(function() { return 'Handler@handle'; });

		$dispatcher->dispatch(new BusDispatcherTestBasicCommand, function($handler) { $handler->after(); });
	}


	public function testDispatchingFromArray()
	{
		$container = new Container();
		$pipeline = new Pipeline($container);
		$instance = new Dispatcher($container, $pipeline);
		$result = $instance->dispatchFromArray('BusDispatcherTestSelfHandlingCommand', ['firstName' => 'taylor', 'lastName' => 'otwell']);
		$this->assertEquals('taylor otwell', $result);
	}


	public function testMarshallArguments()
	{
		$container = new Container();
		$pipeline = new Pipeline($container);
		$instance = new Dispatcher($container, $pipeline);
		$result = $instance->dispatchFromArray('BusDispatcherTestArgumentMapping', ['flag' => false, 'emptyString' => '']);
		$this->assertTrue($result);
	}


	public function testDispatchingSentThroughPipes()
	{
		$container = new Container();
		$pipeline = new Pipeline($container);

		$pipeDispatcher = m::mock('BusDispatcherPipelineDispatcher');
		$pipeDispatcher->shouldReceive('dispatch')->once();
		$container->instance('BusDispatcherPipelineDispatcher', $pipeDispatcher);

		$handler = m::mock('StdClass')->shouldIgnoreMissing();
		$container->instance('Handler', $handler);

		$dispatcher = new Dispatcher($container, $pipeline);
		$dispatcher->mapUsing(function() { return 'Handler@handle'; });
		$dispatcher->setPipes(['BusDispatcherPipelineDispatcher']);
		$dispatcher->dispatch(new BusDispatcherTestBasicCommand);
	}

}

class BusDispatcherTestBasicCommand {

}

class BusDispatcherTestArgumentMapping implements Illuminate\Contracts\Bus\SelfHandling {
	public $flag, $emptyString;
	public function __construct($flag, $emptyString)
	{
		$this->flag = $flag;
		$this->emptyString = $emptyString;
	}
	public function handle()
	{
		return true;
	}
}

class BusDispatcherTestSelfHandlingCommand implements Illuminate\Contracts\Bus\SelfHandling {
	public $firstName, $lastName;
	public function __construct($firstName, $lastName)
	{
		$this->firstName = $firstName;
		$this->lastName = $lastName;
	}
	public function handle()
	{
		return $this->firstName.' '.$this->lastName;
	}
}

class BusDispatcherTestBasicHandler {
	public function handle(BusDispatcherTestBasicCommand $command)
	{

	}
}

class BusDispatcherTestQueuedHandler implements Illuminate\Contracts\Queue\ShouldBeQueued {

}


class BusDispatcherTestCustomQueueCommand implements Illuminate\Contracts\Queue\ShouldBeQueued {
	public function queue($queue, $command)
	{
		$queue->push($command);
	}
}


class BusDispatcherPipelineDispatcher {
	public function dispatch($command) {}
}

