<?php

use Mockery as m;
use Symfony\Component\Process\ProcessUtils;
use Symfony\Component\Process\PhpExecutableFinder;

class FoundationComposerTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testDumpAutoloadRunsTheCorrectCommand()
    {
        $composer = $this->getMock('Illuminate\Foundation\Composer', ['getProcess'], [$files = m::mock('Illuminate\Filesystem\Filesystem'), __DIR__]);
        $files->shouldReceive('exists')->once()->with(__DIR__.'/composer.phar')->andReturn(true);
        $process = m::mock('stdClass');
        $composer->expects($this->once())->method('getProcess')->will($this->returnValue($process));
        $binary = ProcessUtils::escapeArgument((new PhpExecutableFinder)->find(false));
        $process->shouldReceive('setCommandLine')->once()->with($binary.(defined('HHVM_VERSION') ? ' --php' : '').' composer.phar dump-autoload');
        $process->shouldReceive('run')->once();

        $composer->dumpAutoloads();
    }

    public function testDumpAutoloadRunsTheCorrectCommandWhenComposerIsntPresent()
    {
        $composer = $this->getMock('Illuminate\Foundation\Composer', ['getProcess'], [$files = m::mock('Illuminate\Filesystem\Filesystem'), __DIR__]);
        $files->shouldReceive('exists')->once()->with(__DIR__.'/composer.phar')->andReturn(false);
        $process = m::mock('stdClass');
        $composer->expects($this->once())->method('getProcess')->will($this->returnValue($process));
        $process->shouldReceive('setCommandLine')->once()->with('composer dump-autoload');
        $process->shouldReceive('run')->once();

        $composer->dumpAutoloads();
    }
}
