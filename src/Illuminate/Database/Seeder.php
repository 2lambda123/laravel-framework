<?php

namespace Illuminate\Database;

use Illuminate\Console\Command;
use Illuminate\Container\Container;
use Illuminate\Support\Arr;
use InvalidArgumentException;

abstract class Seeder
{
    /**
     * The container instance.
     *
     * @var \Illuminate\Container\Container
     */
    protected $container;

    /**
     * The console command instance.
     *
     * @var \Illuminate\Console\Command
     */
    protected $command;

    /**
     * Seed the given connection from the given path.
     *
     * @param  array|string  $class
     * @param  bool  $silent
     * @return $this
     */
    public function call($class, $silent = false)
    {
        $classes = Arr::wrap($class);

        foreach ($classes as $class) {
            $seeder = $this->resolve($class);

            $name = get_class($seeder);

            if ($this->shouldBeWritten($silent)) {
                $this->command->getOutput()->writeln("<comment>Seeding:</comment> {$name}");
            }

            $startTime = microtime(true);

            try {
                $seeder->__invoke();
            } catch (SeederNotAuthorizedException $e) {
                if ($this->shouldBeWritten($silent)) {
                    $this->command->getOutput()->writeln("<info>Skipped:</info>  {$name}");
                }
                continue;
            }

            $runTime = round(microtime(true) - $startTime, 2);

            if ($this->shouldBeWritten($silent)) {
                $this->command->getOutput()->writeln("<info>Seeded:</info>  {$name} ({$runTime} seconds)");
            }
        }

        return $this;
    }

    /**
     * Silently seed the given connection from the given path.
     *
     * @param  array|string  $class
     * @return void
     */
    public function callSilent($class)
    {
        $this->call($class, true);
    }

    /**
     * Resolve an instance of the given seeder class.
     *
     * @param  string  $class
     * @return \Illuminate\Database\Seeder
     */
    protected function resolve($class)
    {
        if (isset($this->container)) {
            $instance = $this->container->make($class);

            $instance->setContainer($this->container);
        } else {
            $instance = new $class;
        }

        if (isset($this->command)) {
            $instance->setCommand($this->command);
        }

        return $instance;
    }

    /**
     * Set the IoC container instance.
     *
     * @param  \Illuminate\Container\Container  $container
     * @return $this
     */
    public function setContainer(Container $container)
    {
        $this->container = $container;

        return $this;
    }

    /**
     * Set the console command instance.
     *
     * @param  \Illuminate\Console\Command  $command
     * @return $this
     */
    public function setCommand(Command $command)
    {
        $this->command = $command;

        return $this;
    }

    /**
     * Run the database seeds.
     *
     * @return mixed
     *
     * @throws \InvalidArgumentException
     * @throws SeederNotAuthorizedException
     */
    public function __invoke()
    {
        if (! method_exists($this, 'run')) {
            throw new InvalidArgumentException('Method [run] missing from '.get_class($this));
        }

        if (! $this->authorize()) {
            throw new SeederNotAuthorizedException(get_class($this).' was not authorized to run.');
        }

        return isset($this->container)
            ? $this->container->call([$this, 'run'])
            : $this->run();
    }

    /**
     * Authorizes call.
     *
     * @return bool
     */
    protected function authorize(): bool
    {
        return true;
    }

    /**
     * Checks if anything should be written in the console output.
     *
     * @param  bool  $silent
     * @return $this
     */
    protected function shouldBeWritten(bool $silent): bool
    {
        return $silent === false && isset($this->command);
    }
}
