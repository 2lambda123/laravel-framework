<?php

use Illuminate\Cache\Console\CacheTableCommand;
use Illuminate\Container\Container;
use Mockery as m;

class CacheTableCommandTest extends PHPUnit_Framework_TestCase {

    public function tearDown()
    {
        m::close();
    }

    public function testCreateMakesMigration()
    {
        $command = new CacheTableCommandTestStub(
            $files = m::mock('Illuminate\Filesystem\Filesystem'),
            $composer = m::mock('Illuminate\Foundation\Composer')
        );
        $creator = m::mock('Illuminate\Database\Migrations\MigrationCreator')->shouldIgnoreMissing();

        $app = new Container();
        $app['path.database'] = __DIR__;
        $app['migration.creator'] = $creator;
        $command->setLaravel($app);
        $path = __DIR__.'/migrations';
        $creator->shouldReceive('create')->once()->with('create_cache_table', $path)->andReturn($path);
        $files->shouldReceive('get')->once()->andReturn('foo');
        $files->shouldReceive('put')->once()->with($path, 'foo');
        $composer->shouldReceive('dumpAutoloads')->once();

        $this->runCommand($command);
    }

    protected function runCommand($command, $input = array())
    {
        return $command->run(new Symfony\Component\Console\Input\ArrayInput($input), new Symfony\Component\Console\Output\NullOutput);
    }
}

class CacheTableCommandTestStub extends CacheTableCommand
{

    public function call($command, array $arguments = array())
    {
        //
    }
}