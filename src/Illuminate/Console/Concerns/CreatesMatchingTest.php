<?php

namespace Illuminate\Console\Concerns;

use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputOption;

trait CreatesMatchingTest
{
    /**
     * Add the standard command options for generating matching tests.
     *
     * @return void
     */
    protected function addTestOptions()
    {
        $this->getDefinition()->addOption(new InputOption(
            'test',
            null,
            InputOption::VALUE_NONE,
            'Generate an accompanying test for the '.$this->type
        ));

        $this->getDefinition()->addOption(new InputOption(
            'pest',
            null,
            InputOption::VALUE_NONE,
            'Generate an accompanying Pest test for the '.$this->type
        ));
    }

    /**
     * Create the matching test case if requested.
     *
     * @param  string  $path
     * @return void
     */
    protected function handleTestCreation($path)
    {
        if (! $this->option('test') && ! $this->option('pest')) {
            return;
        }

        $this->call('make:test', [
            'name' => Str::of($path)->after($this->laravel['path'])->beforeLast('.php')->append('Test'),
            '--pest' => $this->option('pest'),
        ]);
    }
}
