<?php

namespace Illuminate\Tests\Integration\Foundation\Fixtures\EventDiscovery\Listeners;

use Illuminate\Tests\Integration\Foundation\Fixtures\EventDiscovery\Events\EventOne;
use Illuminate\Tests\Integration\Foundation\Fixtures\EventDiscovery\Events\EventTwo;

class UnionListener
{
    public function handle(EventOne|EventTwo $event)
    {
        //
    }
}
