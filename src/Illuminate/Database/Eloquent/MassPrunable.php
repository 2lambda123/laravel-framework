<?php

namespace Illuminate\Database\Eloquent;

use Illuminate\Database\Events\ModelsPruned;
use LogicException;

trait MassPrunable
{
    /**
     * Get the prunable model query.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function prunable()
    {
        throw new LogicException('Please implement the prunable method on your model.');
    }

    /**
     * Prune all prunable models in the database.
     *
     * @return int
     */
    public function pruneAll()
    {
        $query = tap($this->prunable(), function ($query) {
            $query->when(! $query->getQuery()->limit, function ($query) {
                $query->limit(1000);
            });
        });

        $total = 0;

        do {
            $total += $count = in_array(SoftDeletes::class, class_uses_recursive(get_class($this)))
                ? $query->forceDelete()
                : $query->delete();

            if ($count > 0) {
                event(new ModelsPruned(static::class, $total));
            }
        } while ($count > 0);

        return $total;
    }
}
