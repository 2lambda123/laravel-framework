<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Env;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

class ServeCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'serve';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Serve the application on the PHP development server';

    /**
     * The current port offset.
     *
     * @var int
     */
    protected $portOffset = 0;

    /**
     * The environment variables that are passed thru to the built-in PHP webserver.
     *
     * @var array
     */
    protected $passthruEnvVars;

    /**
     * Create a new serve command instance.
     *
     * @param  array  $passthruEnvVars
     * @return void
     */
    public function __construct(array $passthruEnvVars = ['APP_ENV', 'LARAVEL_SAIL', 'PHP_CLI_SERVER_WORKERS'])
    {
        parent::__construct();

        $this->passthruEnvVars = $passthruEnvVars;
    }

    /**
     * Execute the console command.
     *
     * @return int
     *
     * @throws \Exception
     */
    public function handle()
    {
        chdir(public_path());

        $this->line("<info>Starting Laravel development server:</info> http://{$this->host()}:{$this->port()}");

        $environmentFile = $this->option('env')
                            ? base_path('.env').'.'.$this->option('env')
                            : base_path('.env');

        $hasEnvironment = file_exists($environmentFile);

        $environmentLastModified = $hasEnvironment
                            ? filemtime($environmentFile)
                            : now()->addDays(30)->getTimestamp();

        $process = $this->startProcess($hasEnvironment);

        while ($process->isRunning()) {
            if ($hasEnvironment) {
                clearstatcache(false, $environmentFile);
            }

            if (! $this->option('no-reload') &&
                $hasEnvironment &&
                filemtime($environmentFile) > $environmentLastModified) {
                $environmentLastModified = filemtime($environmentFile);

                $this->comment('Environment modified. Restarting server...');

                $process->stop(5);

                $process = $this->startProcess($hasEnvironment);
            }

            usleep(500 * 1000);
        }

        $status = $process->getExitCode();

        if ($status && $this->canTryAnotherPort()) {
            $this->portOffset += 1;

            return $this->handle();
        }

        return $status;
    }

    /**
     * Start a new server process.
     *
     * @param  bool  $hasEnvironment
     * @return \Symfony\Component\Process\Process
     */
    protected function startProcess($hasEnvironment)
    {
        $process = new Process($this->serverCommand(), null, collect($_ENV)->mapWithKeys(function ($value, $key) use ($hasEnvironment) {
            if ($this->option('no-reload') || ! $hasEnvironment) {
                return [$key => $value];
            }

            return in_array($key, $this->passthruEnvVars)
                    ? [$key => $value]
                    : [$key => false];
        })->all());

        $process->start(function ($type, $buffer) {
            $this->output->write($buffer);
        });

        return $process;
    }

    /**
     * Get the full server command.
     *
     * @return array
     */
    protected function serverCommand()
    {
        return [
            (new PhpExecutableFinder)->find(false),
            '-S',
            $this->host().':'.$this->port(),
            base_path('server.php'),
        ];
    }

    /**
     * Get the host for the command.
     *
     * @return string
     */
    protected function host()
    {
        return $this->input->getOption('host');
    }

    /**
     * Get the port for the command.
     *
     * @return string
     */
    protected function port()
    {
        $port = $this->input->getOption('port') ?: 8000;

        return $port + $this->portOffset;
    }

    /**
     * Check if the command has reached its max amount of port tries.
     *
     * @return bool
     */
    protected function canTryAnotherPort()
    {
        return is_null($this->input->getOption('port')) &&
               ($this->input->getOption('tries') > $this->portOffset);
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['host', null, InputOption::VALUE_OPTIONAL, 'The host address to serve the application on', '127.0.0.1'],
            ['port', null, InputOption::VALUE_OPTIONAL, 'The port to serve the application on', Env::get('SERVER_PORT')],
            ['tries', null, InputOption::VALUE_OPTIONAL, 'The max number of ports to attempt to serve from', 10],
            ['no-reload', null, InputOption::VALUE_NONE, 'Do not reload the development server on .env file changes'],
        ];
    }
}
