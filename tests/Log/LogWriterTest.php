<?php

namespace Illuminate\Tests\Log;

use Mockery as m;
use Illuminate\Log\Writer;
use PHPUnit\Framework\TestCase;

class LogWriterTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testFileHandlerCanBeAdded()
    {
        $writer = new Writer($monolog = m::mock(\Monolog\Logger::class));
        $monolog->shouldReceive('pushHandler')->once()->with(m::type(\Monolog\Handler\StreamHandler::class));
        $writer->useFiles(__DIR__);
    }

    public function testRotatingFileHandlerCanBeAdded()
    {
        $writer = new Writer($monolog = m::mock(\Monolog\Logger::class));
        $monolog->shouldReceive('pushHandler')->once()->with(m::type(\Monolog\Handler\RotatingFileHandler::class));
        $writer->useDailyFiles(__DIR__, 5);
    }

    public function testErrorLogHandlerCanBeAdded()
    {
        $writer = new Writer($monolog = m::mock(\Monolog\Logger::class));
        $monolog->shouldReceive('pushHandler')->once()->with(m::type(\Monolog\Handler\ErrorLogHandler::class));
        $writer->useErrorLog();
    }

    public function testMethodsPassErrorAdditionsToMonolog()
    {
        $writer = new Writer($monolog = m::mock(\Monolog\Logger::class));
        $monolog->shouldReceive('error')->once()->with('foo', []);

        $writer->error('foo');
    }

    public function testWriterFiresEventsDispatcher()
    {
        $writer = new Writer($monolog = m::mock(\Monolog\Logger::class), $events = new \Illuminate\Events\Dispatcher);
        $monolog->shouldReceive('error')->once()->with('foo', []);

        $events->listen(\Illuminate\Log\Events\MessageLogged::class, function ($event) {
            $_SERVER['__log.level'] = $event->level;
            $_SERVER['__log.message'] = $event->message;
            $_SERVER['__log.context'] = $event->context;
        });

        $writer->error('foo');
        $this->assertTrue(isset($_SERVER['__log.level']));
        $this->assertEquals('error', $_SERVER['__log.level']);
        unset($_SERVER['__log.level']);
        $this->assertTrue(isset($_SERVER['__log.message']));
        $this->assertEquals('foo', $_SERVER['__log.message']);
        unset($_SERVER['__log.message']);
        $this->assertTrue(isset($_SERVER['__log.context']));
        $this->assertEquals([], $_SERVER['__log.context']);
        unset($_SERVER['__log.context']);
    }

    /**
     * @expectedException RuntimeException
     */
    public function testListenShortcutFailsWithNoDispatcher()
    {
        $writer = new Writer($monolog = m::mock(\Monolog\Logger::class));
        $writer->listen(function () {
        });
    }

    public function testListenShortcut()
    {
        $writer = new Writer($monolog = m::mock(\Monolog\Logger::class), $events = m::mock(\Illuminate\Contracts\Events\Dispatcher::class));

        $callback = function () {
            return 'success';
        };
        $events->shouldReceive('listen')->with(\Illuminate\Log\Events\MessageLogged::class, $callback)->once();

        $writer->listen($callback);
    }
}
