<?php

namespace Illuminate\Foundation\Support\Providers;

use Illuminate\Foundation\Events\DiscoverEvents;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

/**
 * @mixin ServiceProvider
 *
 * @property string[] $listen - The event handler mappings for the application.
 * @property string[] $subscribe - The subscribers to register.
 * @property string[] $observers - The observers to register.
 */
trait WithEvents
{
    /**
     * Register the application's event listeners.
     *
     * @return void
     */
    public function registerWithEvents()
    {
        $this->booting(function () {
            $events = $this->getEvents();

            foreach ($events as $event => $listeners) {
                foreach (array_unique($listeners) as $listener) {
                    Event::listen($event, $listener);
                }
            }

            foreach ($this->subscribes() as $subscriber) {
                Event::subscribe($subscriber);
            }

            foreach ($this->observers() as $model => $observers) {
                $model::observe($observers);
            }
        });
    }

    /**
     * Get the events and handlers.
     *
     * @return array
     */
    public function listens()
    {
        return $this->listen ?? [];
    }

    /**
     * Get the subscribes.
     *
     * @return array
     */
    public function subscribes()
    {
        return $this->subscribe ?? [];
    }

    /**
     * Get the observers.
     *
     * @return array
     */
    public function observers()
    {
        return $this->observers ?? [];
    }

    /**
     * Get the discovered events and listeners for the application.
     *
     * @return array
     */
    public function getEvents()
    {
        if ($this->app->eventsAreCached()) {
            $cache = require $this->app->getCachedEventsPath();

            return $cache[get_class($this)] ?? [];
        } else {
            return array_merge_recursive(
                $this->discoveredEvents(),
                $this->listens()
            );
        }
    }

    /**
     * Get the discovered events for the application.
     *
     * @return array
     */
    protected function discoveredEvents()
    {
        return $this->shouldDiscoverEvents()
                    ? $this->discoverEvents()
                    : [];
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     *
     * @return bool
     */
    public function shouldDiscoverEvents()
    {
        return false;
    }

    /**
     * Discover the events and listeners for the application.
     *
     * @return array
     */
    public function discoverEvents()
    {
        return collect($this->discoverEventsWithin())
                    ->reject(function ($directory) {
                        return ! is_dir($directory);
                    })
                    ->reduce(function ($discovered, $directory) {
                        return array_merge_recursive(
                            $discovered,
                            DiscoverEvents::within($directory, $this->eventDiscoveryBasePath())
                        );
                    }, []);
    }

    /**
     * Get the listener directories that should be used to discover events.
     *
     * @return array
     */
    protected function discoverEventsWithin()
    {
        return [
            $this->app->path('Listeners'),
        ];
    }

    /**
     * Get the base path to be used during event discovery.
     *
     * @return string
     */
    protected function eventDiscoveryBasePath()
    {
        return base_path();
    }
}
