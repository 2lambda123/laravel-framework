<?php

namespace Illuminate\Foundation\Console;

use Generator;
use Illuminate\Console\Command;
use Illuminate\Foundation\Support\Providers\WithEvents;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'event:cache')]
class EventCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'event:cache';

    /**
     * The name of the console command.
     *
     * This name is used to identify the command during lazy loading.
     *
     * @var string|null
     *
     * @deprecated
     */
    protected static $defaultName = 'event:cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Discover and cache the application's events and listeners";

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->call('event:clear');

        file_put_contents(
            $this->laravel->getCachedEventsPath(),
            '<?php return '.var_export($this->getEvents(), true).';'
        );

        $this->info('Events cached successfully.');
    }

    /**
     * Get all of the events and listeners configured for the application.
     *
     * @return array
     */
    protected function getEvents()
    {
        $events = [];

        foreach ($this->getProvidersUsingWithEvents() as $provider) {
            $providerEvents = array_merge_recursive($provider->shouldDiscoverEvents() ? $provider->discoverEvents() : [], $provider->listens());

            $events[get_class($provider)] = $providerEvents;
        }

        return $events;
    }

    protected function getProvidersUsingWithEvents(): Generator
    {
        $providers = $this->laravel->getProviders(ServiceProvider::class);

        foreach ($providers as $provider) {
            if (in_array(WithEvents::class, class_uses_recursive($provider))) {
                yield $provider;
            }
        }
    }
}
