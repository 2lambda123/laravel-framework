<?php

namespace Illuminate\Foundation\Testing\Concerns;

use DateTimeInterface;
use Illuminate\Foundation\Testing\Wormhole;
use Illuminate\Support\Carbon;
use Illuminate\Support\CarbonImmutable;

trait InteractsWithTime
{
    /**
     * Begin travelling to another time.
     *
     * @param  int  $value
     * @return \Illuminate\Foundation\Testing\Wormhole
     */
    public function travel($value)
    {
        return new Wormhole($value);
    }

    /**
     * Travel to another time.
     *
     * @param  \DateTimeInterface  $date
     * @param  callable|null  $callback
     * @return mixed
     */
    public function travelTo(DateTimeInterface $date, $callback = null)
    {
        Carbon::setTestNow($date);
        CarbonImmutable::setTestNow($date);

        if ($callback) {
            return tap($callback(), function () {
                Carbon::setTestNow();
                CarbonImmutable::setTestNow();
            });
        }
    }

    /**
     * Travel back to the current time.
     *
     * @return \DateTimeInterface
     */
    public function travelBack()
    {
        Carbon::setTestNow();
        CarbonImmutable::setTestNow();

        return Carbon::now();
    }
}
