<?php

namespace Illuminate\Foundation\Testing;

use Exception;

trait WithoutEvents
{
    /**
     * Prevent all event handles from being executed.
     *
     * @throws \Exception
     */
    public function setUpWithoutEvents()
    {
        if (method_exists($this, 'withoutEvents')) {
            $this->withoutEvents();
        } else {
            throw new Exception('Unable to disable events. ApplicationTrait not used.');
        }
    }

    /**
     * Prevent all event handles from being executed.
     *
     * @deprecated
     *
     * @throws \Exception
     */
    public function disableEventsForAllTests()
    {
        $this->setUpWithoutEvents();
    }
}
