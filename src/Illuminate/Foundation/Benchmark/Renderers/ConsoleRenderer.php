<?php

namespace Illuminate\Foundation\Benchmark\Renderers;

use Illuminate\Console\View\Components\Factory;
use Illuminate\Contracts\Foundation\BenchmarkRenderer;
use Illuminate\Support\Str;
use Symfony\Component\Console\Output\ConsoleOutput;
use function Termwind\terminal;

class ConsoleRenderer implements BenchmarkRenderer
{
    use Concerns\InspectsClosures, Concerns\Terminatable;

    /**
     * The output implementation, if any.
     *
     * @var \Symfony\Component\Console\Output\OutputInterface|null
     */
    protected $output;

    /**
     * {@inheritdoc}
     */
    public function render($results, $repeats)
    {
        $components = new Factory($this->output ?: new ConsoleOutput());

        $components->info(sprintf('Benchmarking [%s] script(s) using [%s] repetitions.', $results->count(), $repeats));

        $averages = $results->map(fn ($result) => $result->average)->toArray();

        $fasterIndex = min(array_keys($averages, min($averages)));

        $results->each(function ($result, $index) use ($results, $components, $fasterIndex) {
            $average = number_format($result->average * 1000, 3).'ms';

            if (! is_string($key = $result->key)) {
                $code = $this->getCode($result->callback);

                $limit = terminal()->width() - strlen($average) - 16;
                $key = Str::limit($code, $limit, '…');
            }

            $key = sprintf('[%s] <fg=gray>%s</>', $index + 1, $key);
            $color = $index == $fasterIndex && $results->count() > 1 ? 'green' : 'default';

            $components->twoColumnDetail($key, sprintf('<fg=%s;options=bold>%s</>', $color, $average));
        });

        $components->newLine();

        $this->terminate();
    }

    /**
     * Sets the output implementation.
     *
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return void
     */
    public function setOutput($output)
    {
        $this->output = $output;
    }
}
