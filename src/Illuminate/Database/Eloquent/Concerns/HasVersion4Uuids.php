<?php

namespace Illuminate\Database\Eloquent\Concerns;

use Illuminate\Support\Generator;

trait HasVersion4Uuids
{
    use HasUuids;

    /**
     * Generate a new UUID (version 4) for the model.
     *
     * @return string
     */
    public function newUniqueId()
    {
        return (string) Generator::orderedUuid();
    }
}
