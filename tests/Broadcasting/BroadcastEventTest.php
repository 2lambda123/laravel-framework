<?php

use Mockery as m;
use PHPUnit\Framework\TestCase;

class BroadcastEventTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testBasicEventBroadcastParameterFormatting()
    {
        $broadcaster = m::mock('Illuminate\Contracts\Broadcasting\Broadcaster');

        $broadcaster->shouldReceive('broadcast')->once()->with(
            ['test-channel'], 'TestBroadcastEvent', ['firstName' => 'Taylor', 'lastName' => 'Otwell', 'collection' => ['foo' => 'bar']]
        );

        $event = new TestBroadcastEvent;
        $serializedEvent = serialize($event);
        $jobData = ['event' => $serializedEvent];

        $job = m::mock('Illuminate\Contracts\Queue\Job');
        $job->shouldReceive('delete')->once();

        (new Illuminate\Broadcasting\BroadcastEvent($broadcaster))->fire($job, $jobData);
    }

    public function testManualParameterSpecification()
    {
        $broadcaster = m::mock('Illuminate\Contracts\Broadcasting\Broadcaster');

        $broadcaster->shouldReceive('broadcast')->once()->with(
            ['test-channel'], 'TestBroadcastEventWithManualData', ['name' => 'Taylor', 'socket' => null]
        );

        $event = new TestBroadcastEventWithManualData;
        $serializedEvent = serialize($event);
        $jobData = ['event' => $serializedEvent];

        $job = m::mock('Illuminate\Contracts\Queue\Job');
        $job->shouldReceive('delete')->once();

        (new Illuminate\Broadcasting\BroadcastEvent($broadcaster))->fire($job, $jobData);
    }
}

class TestBroadcastEvent
{
    public $firstName = 'Taylor';
    public $lastName = 'Otwell';
    public $collection;

    public function __construct()
    {
        $this->collection = collect(['foo' => 'bar']);
    }

    public function broadcastOn()
    {
        return ['test-channel'];
    }
}

class TestBroadcastEventWithManualData extends TestBroadcastEvent
{
    public function broadcastWith()
    {
        return ['name' => 'Taylor'];
    }
}
