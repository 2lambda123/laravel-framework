<?php

namespace Illuminate\Tests\Support;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Testing\Fakes\EventFake;
use Mockery as m;
use PHPUnit\Framework\Constraint\ExceptionMessage;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;

class SupportTestingEventFakeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->fake = new EventFake(m::mock(Dispatcher::class));
    }

    public function testAssertDispatched()
    {
        try {
            $this->fake->assertDispatched(EventStub::class);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The expected [Illuminate\Tests\Support\EventStub] event was not dispatched.'));
        }

        $this->fake->dispatch(EventStub::class);

        $this->fake->assertDispatched(EventStub::class);
    }

    public function testAssertDispatchedWithClosure()
    {
        $this->fake->dispatch(new EventStub);

        $this->fake->assertDispatched(function (EventStub $event) {
            return true;
        });
    }

    public function testAssertDispatchedWithCallbackInt()
    {
        $this->fake->dispatch(EventStub::class);
        $this->fake->dispatch(EventStub::class);

        try {
            $this->fake->assertDispatched(EventStub::class, 1);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The expected [Illuminate\Tests\Support\EventStub] event was dispatched 2 times instead of 1 times.'));
        }

        $this->fake->assertDispatched(EventStub::class, 2);
    }

    public function testAssertDispatchedTimes()
    {
        $this->fake->dispatch(EventStub::class);
        $this->fake->dispatch(EventStub::class);

        try {
            $this->fake->assertDispatchedTimes(EventStub::class, 1);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The expected [Illuminate\Tests\Support\EventStub] event was dispatched 2 times instead of 1 times.'));
        }

        $this->fake->assertDispatchedTimes(EventStub::class, 2);
    }

    public function testAssertNotDispatched()
    {
        $this->fake->assertNotDispatched(EventStub::class);

        $this->fake->dispatch(EventStub::class);

        try {
            $this->fake->assertNotDispatched(EventStub::class);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The unexpected [Illuminate\Tests\Support\EventStub] event was dispatched.'));
        }
    }

    public function testAssertNotDispatchedWithClosure()
    {
        $this->fake->dispatch(new EventStub);

        try {
            $this->fake->assertNotDispatched(function (EventStub $event) {
                return true;
            });
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The unexpected [Illuminate\Tests\Support\EventStub] event was dispatched.'));
        }
    }

    public function testAssertDispatchedWithIgnore()
    {
        $dispatcher = m::mock(Dispatcher::class);
        $dispatcher->shouldReceive('dispatch')->once();

        $fake = new EventFake($dispatcher, [
            'Foo',
            function ($event, $payload) {
                return $event === 'Bar' && $payload['id'] === 1;
            },
        ]);

        $fake->dispatch('Foo');
        $fake->dispatch('Bar', ['id' => 1]);
        $fake->dispatch('Baz');

        $fake->assertDispatched('Foo');
        $fake->assertDispatched('Bar');
        $fake->assertNotDispatched('Baz');
    }

    public function testFlushFakes()
    {
        $this->fake->dispatch(new EventStub);
        $this->fake->dispatch(new Event2Stub);

        $this->fake->assertDispatched(function (EventStub $event) {
            return true;
        });

        $this->fake->assertDispatched(function (Event2Stub $event) {
            return true;
        });

        $this->fake->flush(EventStub::class);

        $this->fake->assertNotDispatched(function (EventStub $event) {
            return true;
        });

        $this->fake->assertDispatched(function (Event2Stub $event) {
            return true;
        });
    }
}

class EventStub
{
    //
}

class Event2Stub
{
    //
}
