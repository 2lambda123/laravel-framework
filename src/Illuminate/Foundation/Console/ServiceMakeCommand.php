<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\GeneratorCommand;

class ServiceMakeCommand extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:service';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Service Class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Service';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if (parent::handle() === false && ! $this->option('force')) {
            return;
        }
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return $this->resolveStubPath('/stubs/service.stub');
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return is_dir(app_path('Services')) ? $rootNamespace.'\\Services' : $rootNamespace;
    }
}
