<?php

namespace Illuminate\Support;

use Carbon\Carbon as BaseCarbon;
use Carbon\CarbonImmutable as BaseCarbonImmutable;
use Illuminate\Support\Traits\Conditionable;

class CarbonImmutable extends BaseCarbonImmutable
{
    use Conditionable;

    /**
     * {@inheritdoc}
     */
    public static function setTestNow($testNow = null)
    {
        BaseCarbon::setTestNow($testNow);
        BaseCarbonImmutable::setTestNow($testNow);
    }
}
