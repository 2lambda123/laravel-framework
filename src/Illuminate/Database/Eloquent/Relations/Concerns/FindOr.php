<?php

namespace Illuminate\Database\Eloquent\Relations\Concerns;

use Closure;

trait FindOr
{
    /**
     * Find a related model by its primary key or call a callback.
     *
     * @param  int|array  $ids
     * @param  \Closure|array  $columns
     * @param  \Closure|null  $callback
     * @return \Illuminate\Database\Eloquent\Model|static|mixed
     */
    public function findOr($ids, $columns = ['*'], Closure $callback = null)
    {
        if ($columns instanceof Closure) {
            $callback = $columns;
            $columns = ['*'];
        }

        if (! is_null($model = $this->find($ids, $columns))) {
            return $model;
        }

        return $callback();
    }
}
