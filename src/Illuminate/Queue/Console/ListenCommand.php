<?php

namespace Illuminate\Queue\Console;

use Illuminate\Queue\Listener;
use Illuminate\Console\Command;
use Illuminate\Queue\ListenerOptions;
use Symfony\Component\Process\ProcessUtils;

class ListenCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'queue:listen
                            {connection? : The name of connection}
                            {--delay=0 : Amount of time to delay failed jobs}
                            {--force : Force the worker to run even in maintenance mode}
                            {--memory=128 : The memory limit in megabytes}
                            {--queue= : The queue to listen on}
                            {--sleep=3 : Number of seconds to sleep when no job is available}
                            {--timeout=60 : The number of seconds a child process can run}
                            {--tries=0 : Number of times to attempt a job before logging it failed}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Listen to a given queue';

    /**
     * The queue listener instance.
     *
     * @var \Illuminate\Queue\Listener
     */
    protected $listener;

    /**
     * Create a new queue listen command.
     *
     * @param  \Illuminate\Queue\Listener  $listener
     * @return void
     */
    public function __construct(Listener $listener)
    {
        parent::__construct();

        $this->setOutputHandler($this->listener = $listener);
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        // We need to get the right queue for the connection which is set in the queue
        // configuration file for the application. We will pull it based on the set
        // connection being run for the queue operation currently being executed.
        $queue = $this->getQueue(
            $connection = $this->input->getArgument('connection')
        );

        $this->listener->listen(
            $connection, $queue, $this->gatherOptions()
        );
    }

    /**
     * Get the name of the queue connection to listen on.
     *
     * @param  string  $connection
     * @return string
     */
    protected function getQueue($connection)
    {
        $connection = $connection ?: $this->laravel['config']['queue.default'];

        return $this->input->getOption('queue') ?: $this->laravel['config']->get(
            "queue.connections.{$connection}.queue", 'default'
        );
    }

    /**
     * Get the listener options for the command.
     *
     * @return \Illuminate\Queue\ListenerOptions
     */
    protected function gatherOptions()
    {
        return new ListenerOptions(
            $this->option('delay'), $this->option('memory'),
            $this->option('timeout'), $this->option('sleep'), $this->option('tries'),
            $this->option('force'), $this->resolveExtraParameters()
        );
    }

    /**
     * Set the options on the queue listener.
     *
     * @param  \Illuminate\Queue\Listener  $listener
     * @return void
     */
    protected function setOutputHandler(Listener $listener)
    {
        $listener->setOutputHandler(function ($type, $line) {
            $this->output->write($line);
        });
    }

    /**
     * @return string
     */
    protected function resolveExtraParameters()
    {
        return ' '.implode(' ', array_filter([
            $this->resolveEnvironmentParameter(),
            $this->resolveVerbosityParameter()
        ]));
    }

    /**
     * Resolve a Symfony verbosity level back to its CLI parameter
     *
     * @return string
     */
    protected function resolveVerbosityParameter()
    {
        $map = array_flip($this->verbosityMap);

        if (isset($map[$this->output->getVerbosity()])) {
            return '-'.$map[$this->output->getVerbosity()];
        }
    }

    /**
     * @return string
     */
    protected function resolveEnvironmentParameter()
    {
        if ($this->option('env')) {
            return '--env='.ProcessUtils::escapeArgument($this->option('env'));
        }
    }
}
